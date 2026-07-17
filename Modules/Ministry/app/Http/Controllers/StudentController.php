<?php

namespace Modules\Ministry\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ProgramYear;
use App\Services\PdexService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class StudentController extends Controller
{
    /**
     * Display a listing of the applicants (derived from claims).
     */
    public function index()
    {
        $students = $this->paginateStudents();

        return Inertia::render('Ministry::Students', ['status' => true, 'results' => $students]);
    }

    /**
     * Show a single applicant (identified by user_guid) and their claims.
     */
    public function show($student, $page = 'details')
    {
        $claims = Claim::where('user_guid', $student)
            ->with('program', 'offering', 'institution')
            ->orderByDesc('created_at')
            ->get();

        $applicant = $this->buildApplicant($student, $claims);

        $countries = app(PdexService::class)->countries();
        $program_years = Cache::remember('program_years_ministry', 380, function () {
            return ProgramYear::orderBy('guid')->get();
        });

        return Inertia::render('Ministry::Student', [
            'page' => $page,
            'results' => $applicant,
            'countries' => $countries,
            'programYears' => $program_years,
        ]);
    }

    /**
     * Build an applicant representation from a set of claims. The profile is
     * taken from the most recent claim, since profile data now lives on claims.
     */
    private function buildApplicant($userGuid, $claims)
    {
        $latest = $claims->first();

        return [
            'guid' => $userGuid,
            'user_guid' => $userGuid,
            'first_name' => $latest?->first_name,
            'middle_name' => $latest?->middle_name,
            'last_name' => $latest?->last_name,
            'email' => $latest?->email_address,
            'sin' => $latest?->social_insurance_number,
            'dob' => $latest?->date_of_birth,
            'city' => $latest?->city,
            'zip_code' => $latest?->postal_code,
            'claims' => $claims->values(),
        ];
    }

    private function paginateStudents()
    {
        $claims = Claim::orderByDesc('created_at')->get();

        $applicants = $claims->groupBy('user_guid')->map(function ($group, $userGuid) {
            return $this->buildApplicant($userGuid, $group->values());
        })->values();

        if (request()->filter_term !== null && request()->filter_type !== null) {
            $term = request()->filter_term;
            $field = match (request()->filter_type) {
                'fname' => 'first_name',
                'lname' => 'last_name',
                'sin' => 'sin',
                'email' => 'email',
                default => null,
            };
            if ($field !== null) {
                $applicants = $applicants->filter(fn ($a) => stripos((string) $a[$field], $term) !== false);
            }
        }

        if (request()->sort !== null) {
            $applicants = request()->direction === 'desc'
                ? $applicants->sortByDesc(request()->sort)
                : $applicants->sortBy(request()->sort);
        } else {
            $applicants = $applicants->sortBy('last_name');
        }

        $applicants = $applicants->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $items = $applicants->forPage($page, $perPage)->values();

        return (new LengthAwarePaginator($items, $applicants->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]))->onEachSide(1);
    }
}
