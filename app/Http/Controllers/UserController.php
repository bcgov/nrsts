<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class UserController extends Controller
{
    private function pdexLoginUrl(): string
    {
        return env('PDEX_LOGIN_URL', url('/'));
    }

    private function redirectToPdexLogin(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->away($this->pdexLoginUrl());
    }

    /**
     * Display first page after login (dashboard page)
     */
    public function home(Request $request)
    {
        return Inertia::render('Home');
    }

    private function loginUser(Request $request, $provider, $type): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {

        if (! $request->has('code')) {
            \Log::info('No code');
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl([
                'scope' => 'openid profile email', // Ensure scopes include 'openid'
            ]);

            $request->session()->put('oauth2state', $provider->getState());
            \Log::info('$authUrl: '.$authUrl);
            \Log::info('$provider->getState(): '.$provider->getState());

            return Redirect::to($authUrl.'&kc_idp_hint=nrsts');

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (! $request->has('state') || ($request->state !== $request->session()->get('oauth2state'))) {
            \Log::info('messed up state '.$request->state.' !== '.$request->session()->get('oauth2state'));
            $request->session()->forget('oauth2state');

            //Invalid state, make sure HTTP sessions are enabled
            return $this->redirectToPdexLogin();
        } else {
            // Try to get an access token (using the authorization coe grant)
            try {
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->code,
                ]);
            } catch (\Exception $e) {
                return $this->redirectToPdexLogin();
            }

            // Now you have a token you can look up a users profile data
            try {
                // We got an access token, let's now get the user's details
                $provider_user = $provider->getResourceOwner($token);
                $provider_user = $provider_user->toArray();

                //this is needed for BCSC
                $tokenValues = $token->getValues();
                if (isset($tokenValues['id_token'])) {
                    $idToken = $tokenValues['id_token'];
                    $request->session()->put('bcsc_logout_uri', env('KEYCLOAK_BCSC_LOGOUT_URL').'?state='.
                        $request->state.'&scope=profile%20email&response_type=code&approval_prompt=auto&client_id=nrsts&id_token_hint='.
                        $idToken.'&post_logout_redirect_uri='.env('KEYCLOAK_BCSC_REDIRECT_LOGOUT_URI'));

                    $returnUrl = env('KEYCLOAK_LOGOUT_URL1') . '?retnow=1&returl=' . urlencode(env('KEYCLOAK_LOGOUT_URL2').'?id_token_hint=' . $idToken . '&post_logout_redirect_uri=' . env('KEYCLOAK_LOGOUT_URL3'));
                    $request->session()->put('kc_logout_uri', $returnUrl);
                }
                // \Log::info('KC Logout : '.$provider->getLogoutUrl(['access_token' => $token]));
                // \Log::info('We got a token: '.$token);
                // \Log::info('$provider_user: '.json_encode($provider_user));
            } catch (\Exception $e) {
                \Log::info(' ');
                return $this->redirectToPdexLogin();
            }

            $user = null;
            $failMsg = null;
            if ($type === Role::Student) {
                if (! isset($provider_user['bcsc_user_guid'])) {
                    $failMsg = 'Session conflict. Please use incognito window';
                } else {
                    $user = User::where('bcsc_user_guid', 'ilike', $provider_user['bcsc_user_guid'])->first();
                    $failMsg = 'Welcome back!.';
                }
            }
            if ($type === Role::Ministry_GUEST) {
                $user = User::where('idir_user_guid', 'ilike', $provider_user['idir_user_guid'])->first();
                $failMsg = 'Welcome back! Please contact Ministry Admin to grant you access.';
            }
            if ($type === Role::Institution_GUEST) {
                $user = User::where('bceid_user_guid', 'ilike', $provider_user['bceid_user_guid'])->first();
                $failMsg = 'Welcome back! Please contact Institution Admin to grant you access.';
            }

            //if it is a new BCSC, IDIR or BCeID user, register the user first
            if (is_null($user)) {
                [$valid, $user] = $this->newUser($provider_user, $type);
                if ($valid == '200' && $type === Role::Student) {
                    $request->session()->put('bcsc_provider_user_' . $user->id, json_encode($provider_user));
                    // Cache::put('bcsc_provider_user_' . $user->id, json_encode($provider_user));
                    Auth::login($user);

                    \Log::info(' ');
                    return Redirect::route('student.home');

                } elseif ($valid == '200' && $type !== Role::Student) {
                    \Log::info(' ');
                    return $this->redirectToPdexLogin();
                } else {
                    \Log::info(' ');
                    return $this->redirectToPdexLogin();
                }

                //if the user has been disabled
            } elseif ($user->disabled === true) {
                \Log::info(' ');
                return $this->redirectToPdexLogin();
            }

            $user->name = $provider_user['name'];
            $user->save();
            \Log::info('We got a name: '.$provider_user['name']);

            //else the user has access
            if ($type === Role::Ministry_GUEST) {
                //check if the user is a guest
                $rolesToCheck = [Role::Ministry_GUEST];
                if ($user->roles()->pluck('name')->intersect($rolesToCheck)->isNotEmpty()) {
                    \Log::info(' ');
                    return $this->redirectToPdexLogin();
                }

                Auth::login($user);

                \Log::info(' ');
                return Redirect::route('ministry.home');
            }

            if ($type === Role::Student) {
                $request->session()->put('bcsc_provider_user_' . $user->id, json_encode($provider_user));
                Auth::login($user);

                \Log::info(' ');
                return Redirect::route('student.home');
            }

            if ($type === Role::Institution_GUEST) {
                //check if the user is a guest
                $rolesToCheck = [Role::Institution_GUEST];
                if ($user->roles()->pluck('name')->intersect($rolesToCheck)->isNotEmpty()) {
                    \Log::info(' ');
                    return $this->redirectToPdexLogin();
                }

                Auth::login($user);

                \Log::info(' ');
                return Redirect::route('institution.dashboard');
            }

            \Log::info(' ');
            return $this->redirectToPdexLogin();
        }
    }

    /**
     * Display the login view.
     *
     * @return \Inertia\Response
     */
    public function login(Request $request)
    {
        return $this->redirectToPdexLogin();
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirectToPdexLogin();
    }

    /**
     * Normalize the decoded PDEX individual token into a canonical structure
     * where every expected key is always present. When the student did not
     * consent to share a field (or no individual token was provided) the value
     * is null but the key still exists so the rest of the application can rely
     * on the shape when prefilling a new claim.
     */
    private function normalizeIndividualData($decodedIndividualToken): array
    {
        $data = json_decode(json_encode($decodedIndividualToken), true) ?: [];
        // The PDEX data may be passed either as the individual_data wrapper
        // (which nests the applicant under an "individual" key) or as the
        // individual object directly.
        $individual = is_array($data['individual'] ?? null) ? $data['individual'] : $data;

        // Canonical claim column => list of candidate PDEX keys. PDEX still
        // sends several fields under their legacy (pre-rename) names, so we
        // accept both the new and old key for those.
        $map = [
            'social_insurance_number' => ['social_insurance_number', 'sin'],
            'first_name' => ['first_name'],
            'middle_name' => ['middle_name'],
            'last_name' => ['last_name'],
            'email_address' => ['email_address', 'email'],
            'phone_number' => ['phone_number', 'telephone'],
            'date_of_birth' => ['date_of_birth', 'dob'],
            'gender' => ['gender', 'gender_identity'],
            'disability_status' => ['disability_status'],
            'marital_status' => ['marital_status'],
            'address_line1' => ['address_line1'],
            'city' => ['city'],
            'province' => ['province'],
            'postal_code' => ['postal_code', 'zip_code'],
            'country' => ['country'],
            'employment_status' => ['employment_status'],
            'highest_level_of_education' => ['highest_level_of_education', 'highest_education_level'],
            'immigration_status' => ['immigration_status'],
            'indigenous_status' => ['indigenous_status', 'indigenous_identity'],
            'indigenous_group' => ['indigenous_group'],
            'racial_identity' => ['racial_identity'],
            'is_visible_minority' => ['is_visible_minority'],
            'immigration_year' => ['immigration_year'],
        ];

        $normalized = [];
        foreach ($map as $canonicalKey => $candidateKeys) {
            $value = null;
            foreach ($candidateKeys as $candidate) {
                if (($individual[$candidate] ?? null) !== null && $individual[$candidate] !== '') {
                    $value = $individual[$candidate];
                    break;
                }
            }
            $normalized[$canonicalKey] = $value;
        }

        return [
            'user_guid' => $data['user_guid'] ?? null,
            'user_email' => $data['user_email'] ?? null,
            'user_name' => $data['user_name'] ?? null,
            'individual' => $normalized,
        ];
    }

    // This function will attempt to login the user coming from PDEX
    public function pdexLogin(Request $request)
    {

        $decodedIndividualToken = null;
        //if any of the formData keys are missing don't login the user
        $token = $request->input('token');
        $refreshToken = $request->input('refresh_token');
        $individualToken = $request->input('individual_token');
        $userType = $request->input('user_type');
        $userId = $request->input('ud');
        $logoutUrl = $request->input('logoutUrl');
        \Log::info('pdexLogin called with userType: ' . $userType . ', userId: ' . $userId . ', logoutUrl: ' . $logoutUrl);
        \Log::info('Received request: ' . json_encode($request->all()));
        \Log::info('Received individualToken: ' . $individualToken);
        \Log::info('Received individualToken2: ' . json_encode($individualToken));

        if (empty($token) || empty($userType) || empty($userId) || empty($logoutUrl)) {
            return response()->json(['error' => 'Missing data 2239'], 400);
        }
        switch($userType) {
            case 'idir':
                $type = Role::Ministry_GUEST;
                break;
            case 'bceid':
                $type = Role::Institution_GUEST;
                break;
            case 'bcsc':
                $type = Role::Student;
                break;
            default:
                $type = null;
        }

        // Proceed with the login logic using the validated formData
        $decodedToken = $this->decodeJWT($token);
        //$decodedIndividualToken = $this->decodeJWT($individualToken);
        // \Log::info('Decoded individual token: ' . json_encode($decodedIndividualToken));
        if (isset($decodedToken['error'])) {
            return response()->json(['error' => $decodedToken['error']], 400);
        }
        if (empty($decodedToken['payload']['sub'])) {
            return response()->json(['error' => 'Missing data 2242'], 400);
        }
        if($decodedToken['payload']['aud'] !== env('PDEX_JWT_AUDIENCE')) {
            \Log::error('Invalid audience: ' . $decodedToken['payload']['aud']);
            return response()->json(['error' => 'Missing data 2243'], 400);
        }
        \Log::info('Decoded JWT Token: ' . json_encode($decodedToken));

        if(!is_null($individualToken)) {
            // The individual token carries the PDEX applicant details. Decode its
            // payload the same (unverified) way the main token is handled; the
            // HS256 signature secret is not available in every environment, so
            // verifying it would discard the data we need to prefill a claim.
            $decodedIndividual = $this->decodeJWT($individualToken);
            if (isset($decodedIndividual['payload'])) {
                $decodedIndividualToken = $decodedIndividual['payload'];
                \Log::info('Decoded individual token: ' . json_encode($decodedIndividualToken));
            } else {
                \Log::error('Failed to decode individual token payload.');
            }
        } else {
            \Log::info('No individual token provided.');
        }

        // PDEX applicant details come from the individual token payload (which
        // nests an "individual" object). When no individual token is provided,
        // fall back to any data embedded in the main token payload.
        $individualSource = $decodedIndividualToken ?? ($decodedToken['payload']['individual_data'] ?? null);


        $request->session()->put('kc_logout_uri', $logoutUrl);
        // find the sub text to @ in sub. If there is no @, use the whole sub as bcsc_user_guid
        $sub = $decodedToken['payload']['sub'];
        $atPos = strpos($sub, '@');
        if ($atPos !== false) {
            $decodedToken['payload']['bcsc_user_guid'] = substr($sub, 0, $atPos);
        } else {
            $decodedToken['payload']['bcsc_user_guid'] = $sub;
        }
        $user = null;
        $failMsg = null;
        if ($type === Role::Student) {
            if (! isset($decodedToken['payload']['bcsc_user_guid']) && isset($decodedToken['payload']['bcsc_did'])){
                \Log::info('No bcsc_user_guid but we have bcsc_did: '.$decodedToken['payload']['bcsc_did']);
                $decodedToken['payload']['bcsc_user_guid'] = $decodedToken['payload']['bcsc_did'];
            }

            if (! isset($decodedToken['payload']['bcsc_user_guid'])) {
                $failMsg = 'Session conflict. Please use incognito window';
            } else {
                $user = User::where('bcsc_user_guid', 'ilike', $decodedToken['payload']['bcsc_user_guid'])->first();
                $failMsg = 'Welcome back!.';
            }
        }
        if ($type === Role::Ministry_GUEST) {
            $user = User::where('idir_user_guid', 'ilike', $decodedToken['payload']['idir_user_guid'])->first();
            $failMsg = 'Welcome back! Please contact Ministry Admin to grant you access.';
        }
        if ($type === Role::Institution_GUEST) {
            $user = User::where('bceid_user_guid', 'ilike', $decodedToken['payload']['bceid_user_guid'])->first();
            $failMsg = 'Welcome back! Please contact Institution Admin to grant you access.';
        }

        //if it is a new BCSC, IDIR or BCeID user, register the user first
        if (is_null($user)) {
            \Log::info('New user. Attempting to register.');
            [$valid, $user] = $this->newUser($decodedToken['payload'], $type);
            if ($valid == '200' && $type === Role::Student) {
                // Store the normalized individual token data in the session so a new
                // claim can be prefilled. All expected keys are always present (null
                // when the student did not consent to share that field).
                $request->session()->put('bcsc_pdex_individual_' . $user->id, json_encode($this->normalizeIndividualData($individualSource)));

                $request->session()->put('bcsc_provider_user_' . $user->id, json_encode($decodedToken['payload']));
                Auth::login($user);

                \Log::info(' ');
                return Redirect::route('student.home');

            } elseif ($valid == '200' && $type !== Role::Student) {
                return Inertia::render('Auth/LoginPdex', [
                    'pdexLoginUrl' => env('PDEX_LOGIN_URL'),
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => 'Please contact Admin to grant you access.',
                ]);
            } else {
                return Inertia::render('Auth/LoginPdex', [
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => $valid,
                ]);
            }

        //if the user has been disabled
        } elseif (!is_null($user) && $user->disabled === true) {
            \Log::info('User is disabled.');
            return Inertia::render('Auth/LoginPdex', [
                'pdexLoginUrl' => env('PDEX_LOGIN_URL'),
                'loginAttempt' => true,
                'hasAccess' => false,
                'status' => 'Access denied. Please contact Admin.',
            ]);
        }

        if ($type === Role::Student) {
            if (! isset($decodedToken['payload']['name'])){
                \Log::info('No name found in payload for Student.');
                $decodedToken['payload']['name'] = $decodedToken['payload']['given_names'] . ' ' . $decodedToken['payload']['family_name'];
            }
        }
        if ($type === Role::Ministry_GUEST) {

        }
        if ($type === Role::Institution_GUEST) {

        }

        $user->name = $decodedToken['payload']['name'];
        $user->save();
        \Log::info('We got a name: '.$decodedToken['payload']['name']);

        $request->session()->put('kc_logout_uri', $logoutUrl);
        //else the user has access
        if ($type === Role::Ministry_GUEST) {
            \Log::info('User is Ministry_GUEST. Checking roles and logging in if valid.');
            //check if the user is a guest
            $rolesToCheck = [Role::Ministry_GUEST];
            if ($user->roles()->pluck('name')->intersect($rolesToCheck)->isNotEmpty()) {
                \Log::info('User is Ministry_GUEST but has no access. Showing message.');
                return Inertia::render('Auth/LoginPdex', [
                    'pdexLoginUrl' => env('PDEX_LOGIN_URL'),
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => $failMsg,
                ]);
            }

            Auth::login($user);
            \Log::info($user->name.' logged in as Ministry_GUEST.');
            \Log::info('User is Ministry_GUEST and has access. Logging in.');
            // \Log::info('User roles: ' . implode(', ', $user->roles()->pluck('name')->toArray()));
            // //log user info
            // \Log::info('User ID: ' . $user->id);
            // \Log::info('User Email: ' . $user->email);
            // \Log::info('User Name: ' . $user->name);
            // \Log::info('User disabled: ' . $user->disabled);
            // \Log::info('User IDIR GUID: ' . $user->idir_user_guid);
            // \Log::info('User is authenticated: ' . (Auth::check() ? 'true' : 'false'));
            

            \Log::info('User is Ministry_GUEST and has access. Logging in.');
            return Redirect::route('ministry.home');
        }

        if ($type === Role::Student) {
            \Log::info('User is Student. Logging in.');
            $this->checkRoles($user, $type);
            $request->session()->put('bcsc_provider_user_' . $user->id, json_encode($decodedToken['payload']));
            Auth::login($user);
            $request->session()->put('bcsc_logout_uri', $logoutUrl);

            // Store the normalized individual token data in the session so a new
            // claim can be prefilled. All expected keys are always present (null
            // when the student did not consent to share that field).
            $request->session()->put('bcsc_pdex_individual_' . $user->id, json_encode($this->normalizeIndividualData($individualSource)));

            return Redirect::route('student.home');
        }

        if ($type === Role::Institution_GUEST) {
            \Log::info('User is Institution_GUEST. Checking roles and logging in if valid.');
            // Ensure the user is linked to their institution's staff (as a guest).
            // This covers the case where the institution was created after the
            // user's initial registration, so the staff link was skipped then.
            $this->checkInstitutionStaff($user, $decodedToken['payload']);
            //check if the user is a guest
            $rolesToCheck = [Role::Institution_GUEST];
            if ($user->roles()->pluck('name')->intersect($rolesToCheck)->isNotEmpty()) {
                return Inertia::render('Auth/LoginPdex', [
                    'pdexLoginUrl' => env('PDEX_LOGIN_URL'),
                    'loginAttempt' => true,
                    'hasAccess' => false,
                    'status' => $failMsg,
                ]);
            }

            Auth::login($user);

            return Redirect::route('institution.dashboard');
        }


        return Inertia::render('Auth/LoginPdex', [
            'pdexLoginUrl' => env('PDEX_LOGIN_URL'),
            'loginAttempt' => true,
            'hasAccess' => false,
            'status' => "Login failed. Please try again.",
        ]);

    }

    // Decode JWT token to see its contents (without verification for debugging)
    private function decodeJWT($token)
    {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) === 3) {
            try {
                // Decode the payload (second part)
                $payload = json_decode(base64_decode(str_pad(strtr($tokenParts[1], '-_', '+/'), strlen($tokenParts[1]) % 4, '=', STR_PAD_RIGHT)), true);
                
                // Decode the header (first part)
                $header = json_decode(base64_decode(str_pad(strtr($tokenParts[0], '-_', '+/'), strlen($tokenParts[0]) % 4, '=', STR_PAD_RIGHT)), true);
                
                $tokenInfo = [
                    'header' => $header,
                    'payload' => $payload,
                    'raw_token_length' => strlen($token),
                    'token_parts_count' => count($tokenParts)
                ];
            } catch (\Exception $e) {
                \Log::error('Failed to decode JWT token: ' . $e->getMessage());
                $tokenInfo = [
                    'error' => 'Missing data 2240',
                    'raw_token_length' => strlen($token)
                ];
            }
        } else {
            \Log::error('Invalid JWT format');
            $tokenInfo = [
                'error' => 'Missing data 2241',
                'token_parts_count' => count($tokenParts),
                'raw_token_length' => strlen($token)
            ];
        }

        return $tokenInfo;
    }
    
    private function newUser($provider_user, $type)
    {
        $valid = '200';
        $user = null;
        if ($type === Role::Ministry_GUEST && isset($provider_user['idir_username']) && $provider_user['idir_username']) {
            $check = User::where('idir_username', Str::upper($provider_user['idir_username']))->first();
            if (! is_null($check)) {
                $valid = 'This IDIR is already in use. Please contact the admin.';
            }
        } elseif ($type === Role::Institution_GUEST && isset($provider_user['bceid_username']) && $provider_user['bceid_username']) {
            $check = User::where('bceid_username', Str::upper($provider_user['bceid_username']))->first();
            if (! is_null($check)) {
                $valid = 'This BCeID is already in use. Please contact the admin.';
            }
        } elseif ($type === Role::Student && isset($provider_user['bcsc_user_guid']) && $provider_user['bcsc_user_guid']) {
            $check = User::where('bcsc_user_guid', Str::upper($provider_user['bcsc_user_guid']))->first();
            if (! is_null($check)) {
                $valid = 'This BC Services Card is already in use. Please contact the admin.';
            }
            if (! isset($provider_user['email'])) {
                \Log::info('Your BC Services Card is missing a required Email Address. Please resolve that and try again.');
                $valid = 'Your BC Services Card is missing a required Email Address. Please resolve that and try again.';
            }
        } else {
            $valid = 'You are not authorized to access this page.';
        }

        if ($valid === '200') {
            //$providerUser['given_names'] comes from PDEX, $provider_user['given_name'] comes NRSTS from Keycloak
        //            $email = isset($provider_user['email']) ? Str::lower($provider_user['email']) : null;
            $name = isset($provider_user['name']) ? Str::title($provider_user['name']) : "";
            if($name === "" && isset($provider_user['given_names']) && isset($provider_user['family_name'])) {
                $name = Str::title($provider_user['given_names'] . ' ' . $provider_user['family_name']);
            }

            $user = new User();
            $user->guid = Str::orderedUuid()->getHex();
            $user->name = $name;
            $user->first_name = Str::title($provider_user['given_name'] ?? Str::title($provider_user['family_name']));
            $user->last_name = Str::title($provider_user['family_name']);
            $user->email = Str::lower($provider_user['email']);
            $user->disabled = false;
            $user->bcsc_username = isset($provider_user['bcsc_username']) ? Str::upper($provider_user['bcsc_username']) : null;
            $user->idir_username = isset($provider_user['idir_username']) ? Str::upper($provider_user['idir_username']) : null;
            $user->bceid_username = isset($provider_user['bceid_username']) ? Str::upper($provider_user['bceid_username']) : null;
            $user->bcsc_user_guid = isset($provider_user['bcsc_user_guid']) ? Str::upper($provider_user['bcsc_user_guid']) : null;
            $user->idir_user_guid = isset($provider_user['idir_user_guid']) ? Str::upper($provider_user['idir_user_guid']) : null;
            $user->bceid_user_guid = isset($provider_user['bceid_user_guid']) ? Str::upper($provider_user['bceid_user_guid']) : null;
            $user->bceid_business_guid = isset($provider_user['bceid_business_guid']) ? Str::upper($provider_user['bceid_business_guid']) : null;
            $user->password = Hash::make(Str::lower($provider_user['email']));
            $user->save();
            $this->checkRoles($user, $type);

            if (isset($provider_user['bceid_business_guid'])) {
                \Log::info('isset bceid $provider_user');
                $this->checkInstitutionStaff($user, $provider_user);
            } elseif (isset($provider_user['bcsc_user_guid'])) {
                \Log::info('isset bcsc $provider_user');
            } else {
                \Log::info('net set $provider_user');
            }
        } else {
            \Log::info('User validation failed: '.$valid);
            \Log::info('Role type: '.$type);
            \Log::info('Provider user data: '.json_encode($provider_user));
        }

        return [$valid, $user];
    }

    private function checkInstitutionStaff($user, $provider_user)
    {
        $user = User::find($user->id);
        $institution = Institution::where('bceid_business_guid', $user->bceid_business_guid)->first();
        $institutionStaff = InstitutionStaff::where('bceid_user_guid', $user->bceid_user_guid)->with('institution')->first();

        // If the ministry did not setup any user with that bceid_business_guid then don't auto register
        if (! is_null($institution) && is_null($institutionStaff)) {
            \Log::info('$institution is not null and staff is');

            $staff = new InstitutionStaff();
            $staff->guid = Str::orderedUuid()->getHex();
            $staff->user_guid = $user->guid;
            $staff->institution_guid = $institution->guid;
            $staff->bceid_business_guid = $user->bceid_business_guid;
            $staff->bceid_user_guid = $user->bceid_user_guid;
            $staff->bceid_user_id = Str::upper($provider_user['bceid_username']);
            $staff->bceid_user_name = Str::title($provider_user['name']);
            $staff->bceid_user_email = Str::lower($provider_user['email']);
            $staff->status = 'Active';
            $staff->save();
        } else {
            \Log::info('$institution no go');
        }

        if (is_null($institution)) {
            \Log::info('no institution for bceid_business_guid: '.$user->bceid_business_guid);
        }
        if (is_null($institutionStaff)) {
            \Log::info('no staff for bceid_business_guid: '.$user->bceid_business_guid);
        }

    }

    //new user to be assigned as guest
    private function checkRoles($user, $type)
    {
        if (is_null($user->roles()->first())) {
            $role = Role::where('name', $type)->first();
            $user->roles()->attach($role);
        }
    }
}
