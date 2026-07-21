<?php

namespace Modules\Ministry\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionEditRequest;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\ProgramYear;
use App\Models\Util;
use App\Services\PdexService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Response;

class InstitutionController extends Controller
{
    public function __construct(private readonly PdexService $pdex)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $institutions = $this->paginateInst();

        return Inertia::render('Ministry::Institutions', ['status' => true, 'results' => $institutions]);
    }

    /**
     * Show the specified resource.
     */
    public function show(Institution $institution, $page = 'details')
    {
        $guid = $institution->guid;

        $institution = Institution::where('id', $institution->id)->with(
            ['staff.user.roles', 'offerings.program', 'offerings.py']
        )->withCount(['claims', 'offerings', 'staff'])->first();

        $countries = $this->pdex->countries();
        $program_years = Cache::remember('program_years_ministry', 380, function () {
            return ProgramYear::orderBy('guid')->get();
        });

        // The offering budget is derived: total seats x the Ministry's weekly support payment amount.
        $supportPaymentPerWeek = (float) (Util::where('field_type', 'Support Payment Per Week')
            ->where('active_flag', true)
            ->value('field_name') ?? 0);

        $pdexInstitution = null;
        $pdexSites = null;

        if (! empty($guid)) {
            $pdexInstitution = $this->pdex->get('/institutions/'.$guid);
            $pdexSites = $this->pdex->get('/institution-sites/'.$guid);
        }

        return Inertia::render('Ministry::Institution', ['page' => $page, 'results' => $institution,
            'countries' => $countries, 'programYears' => $program_years,
            'supportPaymentPerWeek' => $supportPaymentPerWeek,
            'pdexInstitution' => $pdexInstitution, 'pdexSites' => $pdexSites]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InstitutionEditRequest $request): RedirectResponse
    {
        $institution = Institution::where('id', $request->id)->first();

        $institution->update($request->safe()->except('last_touch_by_user_guid'));

        // Deactivating an institution cascades to all of its approved offerings.
        if (! $institution->active_status) {
            ProgramOffering::where('institution_guid', $institution->guid)
                ->where('offering_status', 'approved')
                ->update(['offering_status' => 'inactive']);
        }

        return Redirect::route('ministry.institutions.show', [$request->id]);
    }

    /**
     * Fetch institutions from the PDEX API and upsert them by guid.
     */
    public function fetchFromPdex(): RedirectResponse
    {
        \Log::info('PDEX "Fetch Institutions from PDEX" attempt', [
            'user_id' => optional(auth()->user())->id,
            'user_guid' => optional(auth()->user())->guid,
            'user_email' => optional(auth()->user())->email,
            'ip' => request()->ip(),
            'api_url' => (string) config('services.pdex.api_url'),
        ]);

        $baseUrl = rtrim((string) config('services.pdex.api_url'), '/');

        if ($baseUrl === '') {
            \Log::warning('PDEX institutions fetch aborted: PDEX API URL is not configured.');

            return Redirect::route('ministry.institutions.index')
                ->with('error', 'PDEX API URL is not configured.');
        }

        $token = $this->pdex->token();

        if (empty($token)) {
            \Log::warning('PDEX institutions fetch aborted: could not obtain an access token.');

            return Redirect::route('ministry.institutions.index')
                ->with('error', 'Could not obtain an access token from the PDEX token endpoint.');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get($baseUrl.'/institutions');
        } catch (\Throwable $e) {
            \Log::error('PDEX institutions fetch failed: '.$e->getMessage());

            return Redirect::route('ministry.institutions.index')
                ->with('error', 'Unable to reach the PDEX API.');
        }

        \Log::info('PDEX institutions fetch response received', [
            'request_url' => $baseUrl.'/institutions',
            'status' => $response->status(),
            'content_type' => $response->header('Content-Type'),
        ]);

        if ($response->failed()) {
            \Log::error('PDEX institutions fetch returned HTTP '.$response->status().': '.$response->body(), [
                'request_url' => $baseUrl.'/institutions',
                'response_headers' => $response->headers(),
            ]);

            return Redirect::route('ministry.institutions.index')
                ->with('error', 'Failed to fetch institutions from PDEX (HTTP '.$response->status().').');
        }

        $payload = $response->json();
        $records = $payload['data'] ?? $payload;

        if (! is_array($records)) {
            \Log::error('PDEX institutions fetch: unexpected response shape: '.$response->body());

            return Redirect::route('ministry.institutions.index')
                ->with('error', 'Unexpected response from the PDEX API.');
        }

        $count = 0;
        foreach ($records as $record) {
            if (empty($record['guid'])) {
                continue;
            }

            Institution::updateOrCreate(
                ['guid' => $record['guid']],
                [
                    'name' => $record['legal_operating_name'] ?? ($record['name'] ?? ''),
                    'bceid_business_guid' => $record['bceid_business_guid'] ?? null,
                    'active_status' => $record['active_status'] ?? true,
                ]
            );
            $count++;
        }

        \Log::info("PDEX institutions fetch: imported {$count} institution(s).");

        return Redirect::route('ministry.institutions.index')
            ->with('success', "Imported {$count} institution(s) from PDEX.");
    }

    private function paginateInst()
    {
        $institutions = Institution::with('activeOfferings');

        if (request()->filter_name !== null) {
            $institutions = $institutions->where('name', 'ILIKE', '%'.request()->filter_name.'%');
        }

        if (request()->sort !== null) {
            $institutions = $institutions->orderBy(request()->sort, request()->direction);
        } else {
            $institutions = $institutions->orderBy('name');
        }

        return $institutions->paginate(25)->onEachSide(1)->appends(request()->query());
    }

}
