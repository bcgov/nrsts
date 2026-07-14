<?php

namespace Modules\Institution\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ProgramYear;
use App\Models\User;
use App\Services\PdexService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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

        return Inertia::render('Institution::Students', ['status' => true, 'results' => $students]);
    }

    /**
     * Show a single applicant (identified by user_guid) and their claims.
     */
    public function show($student, $page = 'claims')
    {
        $user = User::find(Auth::user()->id);
        $institution = $user->institution;

        $claims = Claim::where('user_guid', $student)
            ->where('institution_guid', $institution->guid)
            ->with('program', 'allocation', 'institution')
            ->orderByDesc('created_at')
            ->get();

        $applicant = $this->buildApplicant($student, $claims);

        $countries = app(PdexService::class)->countries();
        $program_years = Cache::remember('program_years', 380, function () {
            return ProgramYear::where('status', 'active')->orderBy('guid')->get();
        });

        return Inertia::render('Institution::Student', ['page' => $page, 'results' => $applicant,
            'countries' => $countries, 'programYears' => $program_years]);
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
            'email' => $latest?->email,
            'sin' => $latest?->sin,
            'dob' => $latest?->dob,
            'city' => $latest?->city,
            'zip_code' => $latest?->zip_code,
            'claims' => $claims->values(),
        ];
    }

    private function paginateStudents()
    {
        $user = User::find(Auth::user()->id);
        $institution = $user->institution;

        $claims = Claim::where('institution_guid', $institution->guid)
            ->orderByDesc('created_at')
            ->get();

        $applicants = $claims->groupBy('user_guid')->map(function ($group, $userGuid) {
            return $this->buildApplicant($userGuid, $group->values());
        })->values();

        if (request()->filter_last_name !== null) {
            $term = request()->filter_last_name;
            $applicants = $applicants->filter(fn ($a) => stripos((string) $a['last_name'], $term) !== false);
        }
        if (request()->filter_email !== null) {
            $term = request()->filter_email;
            $applicants = $applicants->filter(fn ($a) => stripos((string) $a['email'], $term) !== false);
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
