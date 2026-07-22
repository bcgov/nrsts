<?php

namespace Modules\Student\Http\Controllers;

use App\Events\ApplicationSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationEditRequest;
use App\Http\Requests\ApplicationStoreRequest;
use App\Http\Requests\ApplicationTransitionRequest;
use App\Models\Claim;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\Util;
use App\Services\PdexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Response;

class ApplicationController extends Controller
{
    /**
     * Update the specified resource in storage.
     */
    public function update(ApplicationEditRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = collect($request->validated())->except(['allocation_limit_reached'])->toArray();
        $validated = $this->applyEiReferenceCode($validated);
        $validated = $this->applyProvinceDecline($validated);

        $claim = Claim::find($request->id);
        $claim->fill($validated);
        $claim->save();

        $application = Claim::find($request->id);
        event(new ApplicationSubmitted($application, $application->claim_status));

        return Redirect::route('student.home');
    }

    /**
     * Store the specified resource in storage.
     */
    public function store(ApplicationStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        $validated = collect($request->validated())->except(['allocation_limit_reached'])->toArray();
        $validated = $this->applyEiReferenceCode($validated);
        $validated = $this->applyProvinceDecline($validated);

        $application = Claim::create($validated);

        event(new ApplicationSubmitted($application, $application->claim_status));

        return Redirect::route('student.home');
    }

    /**
     * When an application is submitted, resolve the EI Reference Code for the
     * selected region from Utils (EI Reference Codes > field_name = region) and
     * store its description on the claim.
     */
    private function applyEiReferenceCode(array $validated): array
    {
        if (($validated['claim_status'] ?? null) !== 'Submitted') {
            return $validated;
        }

        $region = trim((string) ($validated['region'] ?? ''));

        if ($region === '') {
            return $validated;
        }

        $code = Util::where('field_type', 'EI Reference Codes')
            ->where('field_name', $region)
            ->where('active_flag', true)
            ->value('field_description');

        if (! empty($code)) {
            $validated['ei_reference_code'] = $code;
        }

        return $validated;
    }

    /**
     * NRSTS funding is only available to British Columbia residents. When an
     * application is submitted with a province outside BC, the claim is
     * automatically declined and the reason is recorded on the outcome status.
     */
    private function applyProvinceDecline(array $validated): array
    {
        if (($validated['claim_status'] ?? null) !== 'Submitted') {
            return $validated;
        }

        $province = strtolower(trim((string) ($validated['province'] ?? '')));

        if (! in_array($province, ['bc', 'b.c.', 'british columbia'], true)) {
            $validated['claim_status'] = 'Declined';
            $validated['outcome_status'] = 'Automatically declined: the applicant\'s province is outside British Columbia. NRSTS funding is only available to BC residents.';
        }

        return $validated;
    }

    /**
     * Advance the learner's application through the apprentice-training workflow.
     * Allowed transitions: EI Confirmed -> Training Started, Training Started -> Training Ended.
     */
    public function transition(ApplicationTransitionRequest $request): \Illuminate\Http\RedirectResponse
    {
        $claim = Claim::find($request->id);
        $claim->claim_status = $request->claim_status;

        if ($request->claim_status === 'Training Ended') {
            $claim->employment_status_exit = $request->employment_status_exit;
        }

        $claim->save();

        return Redirect::route('student.home');
    }

    public function applications(Request $request, $page = 'applications')
    {
        $user = Auth::user();

        // BCSC data is stored in the session at login (see UserController::pdexLogin).
        $providerUser = json_decode($request->session()->get('bcsc_provider_user_' . $user->id));
        $individualData = json_decode($request->session()->get('bcsc_pdex_individual_' . $user->id), true);

        $claimPrefill = $this->buildClaimPrefill($individualData);

        // Diagnostics (no PII): confirm the session carried PDEX applicant data
        // and how many claim fields were prefilled, to trace blank fields on prod.
        \Log::info('Student applications prefill', [
            'user_id' => $user->id,
            'session_individual_present' => $request->session()->has('bcsc_pdex_individual_' . $user->id),
            'individual_data_is_array' => is_array($individualData),
            'individual_wrapper_keys' => is_array($individualData['individual'] ?? null) ? array_keys($individualData['individual']) : [],
            'prefill_keys' => array_keys($claimPrefill),
            'prefill_count' => count($claimPrefill),
        ]);

        return Inertia::render('Student::Dashboard', [
            'status' => true,
            'results' => $user,
            'page' => $page,
            'providerUser' => $providerUser,
            'individual_data' => $individualData,
            'claim_prefill' => $claimPrefill,
            'studentUtils' => app(PdexService::class)->studentUtils(),
        ]);
    }

    /**
     * Map the PDEX individual token data to claim columns so a new claim can be
     * prefilled. Only non-empty values are returned so form defaults are kept.
     */
    private function buildClaimPrefill($individualData): array
    {
        $individual = is_array($individualData) && is_array($individualData['individual'] ?? null)
            ? $individualData['individual']
            : [];

        // PDEX individual token key => claim column.
        $map = [
            'social_insurance_number' => 'social_insurance_number',
            'first_name' => 'first_name',
            'middle_name' => 'middle_name',
            'last_name' => 'last_name',
            'email_address' => 'email_address',
            'phone_number' => 'phone_number',
            'date_of_birth' => 'date_of_birth',
            'gender' => 'gender',
            'marital_status' => 'marital_status',
            'address_line1' => 'address_line1',
            'city' => 'city',
            'province' => 'province',
            'postal_code' => 'postal_code',
            'country' => 'country',
            'employment_status' => 'employment_status_intake',
            'highest_level_of_education' => 'highest_level_of_education',
            'immigration_status' => 'immigration_status',
            'disability_status' => 'disability_status',
            'indigenous_status' => 'indigenous_status',
            'indigenous_group' => 'indigenous_group',
            'racial_identity' => 'racial_identity',
            'is_visible_minority' => 'is_visible_minority',
            'immigration_year' => 'immigration_year',
        ];

        $prefill = [];
        foreach ($map as $sourceKey => $claimColumn) {
            $value = $individual[$sourceKey] ?? null;
            if ($value !== null && $value !== '') {
                $prefill[$claimColumn] = $value;
            }
        }

        // The select dropdowns in the form bind to the option *label*, but the
        // PDEX token delivers the coded *value* (e.g. "man", "some_post_secondary").
        // Translate those coded values to their labels so the selects match.
        $studentUtils = app(PdexService::class)->studentUtils();
        $options = $studentUtils['options'] ?? [];

        // claim column => PDEX studentUtils field_id (select fields only).
        $selectFields = [
            'gender' => 'gender',
            'marital_status' => 'marital_status',
            'indigenous_group' => 'indigenous_group',
            'racial_identity' => 'racial_identity',
            'immigration_status' => 'immigration_status',
            'highest_level_of_education' => 'highest_level_of_education',
            'employment_status_intake' => 'employment_status',
            'province' => 'province',
        ];

        foreach ($selectFields as $claimColumn => $fieldId) {
            if (! isset($prefill[$claimColumn]) || empty($options[$fieldId])) {
                continue;
            }

            $current = (string) $prefill[$claimColumn];
            foreach ($options[$fieldId] as $opt) {
                // Match on the coded value or the label (case-insensitively) and
                // store the label, which is what the dropdown option uses.
                if (strcasecmp((string) ($opt['value'] ?? ''), $current) === 0
                    || strcasecmp((string) ($opt['label'] ?? ''), $current) === 0) {
                    $prefill[$claimColumn] = $opt['label'];
                    break;
                }
            }
        }

        // Match the country dropdown option casing (e.g. "CANADA" => "Canada").
        if (! empty($prefill['country'])) {
            $prefill['country'] = ucwords(strtolower((string) $prefill['country']));
        }

        // Diagnostics (no PII): compare the applicant keys received against the
        // keys actually prefilled, and whether the select option lists were
        // available for value->label translation (empty lists blank selects).
        \Log::info('PDEX buildClaimPrefill', [
            'individual_non_empty_keys' => array_keys(array_filter($individual, fn ($v) => $v !== null && $v !== '')),
            'prefill_keys' => array_keys($prefill),
            'select_option_lists_present' => array_keys(array_filter($options, fn ($o) => ! empty($o))),
            'student_utils_options_empty' => empty($options),
        ]);

        return $prefill;
    }

    public function fetchApplications(Request $request)
    {
        $body = $this->paginateClaims();

        return Response::json(['status' => true, 'body' => $body]);
    }

    public function fetchInstitutions(Request $request, $institution = null)
    {
        if (! is_null($institution)) {
            $institution = Institution::where('guid', $institution)->first();

            if ($institution) {
                // Only offer programs that have an active offering at this institution,
                // so applicants cannot select a program without a valid offering.
                $programGuids = ProgramOffering::where('institution_guid', $institution->guid)
                    ->where('offering_status', 'approved')
                    ->pluck('program_guid')
                    ->unique()
                    ->all();

                $programs = Program::isActive()
                    ->whereIn('guid', $programGuids)
                    ->orderBy('program_name')
                    ->get();

                $institution->setRelation('activePrograms', $programs);
            }

            return Response::json(['status' => true, 'institution' => $institution]);
        }

        $institutions = Institution::active()->get();

        return Response::json(['status' => true, 'institutions' => $institutions]);
    }

    private function paginateClaims()
    {
        $claims = Claim::where('user_guid', Auth::user()->guid)->with('program', 'institution', 'offering.py');

        if (request()->sort !== null) {
            $claims = $claims->orderBy(request()->sort, request()->direction);
        } else {
            $claims = $claims->orderBy('created_at', 'desc');
        }

        return $claims->paginate(25)->onEachSide(1)->appends(request()->query());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('student::create');
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('student::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('student::edit');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
