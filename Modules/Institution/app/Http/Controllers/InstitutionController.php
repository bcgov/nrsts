<?php

namespace Modules\Institution\Http\Controllers;

use App\Events\StaffRoleChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionStaffEditRequest;
use App\Models\Claim;
use App\Models\InstitutionStaff;
use App\Models\ProgramYear;
use App\Models\Role;
use App\Models\User;
use App\Models\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::find(Auth::user()->id);
        $institution = $user->institution;

        $cacheProgramYear = Cache::remember('global_program_years_' . $user->institution->guid, now()->addHours(1), function () use ($user){
            $programYears = ProgramYear::orderBy('id')->get();
            $programYear = ProgramYear::where('status', 'active')->first();

            $programs = $user->institution->activePrograms
                ->sortBy('program_name') // Sort by program_name in ascending order
                ->pluck('program_name', 'guid')
                ->toArray();

            return [
                'list' => $programYears,
                'default' => $programYear->guid,
                'programs' => $programs,
            ];
        });

        $programYear = ProgramYear::where('guid', $cacheProgramYear['default'])->first();

        // Load all active offerings for the program year (each carries its own budget/seats).
        $institution->load(['offerings' => function ($query) use ($programYear) {
            $query->where('program_year_guid', $programYear->guid)->where('active_status', true);
            $query->with('program');
            $query->orderBy('offering_name');
        }]);

        $offeringGuids = $institution->offerings->pluck('guid');

        // Application/claim counts by lifecycle status across the institution's active offerings.
        $statusCounts = Claim::where('institution_guid', $institution->guid)
            ->whereIn('program_offering_guid', $offeringGuids)
            ->selectRaw('claim_status, count(*) as total')
            ->groupBy('claim_status')
            ->pluck('total', 'claim_status');

        $stats = [
            'submitted' => (int) ($statusCounts['Submitted'] ?? 0),
            'hold' => (int) ($statusCounts['Hold'] ?? 0),
            'trainingStarted' => (int) ($statusCounts['Training Started'] ?? 0),
            'trainingEnded' => (int) ($statusCounts['Training Ended'] ?? 0),
            'completed' => (int) ($statusCounts['Completed'] ?? 0),
        ];

        // Statuses that consume a seat in an offering.
        $seatConsumingStatuses = ['Submitted', 'EI Confirmed', 'Training Started', 'Training Ended', 'Completed'];

        // Support payment amount per week per seat (Utils variable, e.g. 400).
        $supportPaymentPerWeek = (float) (Util::where('field_type', 'Support Payment Per Week')
            ->where('active_flag', true)
            ->value('field_name') ?? 0);

        $totalSeats = 0;
        $seatsUsed = 0;
        $totalFunding = 0.0;

        // Build a per-offering summary for the dashboard: seats, usage and funding.
        $offeringSummaries = $institution->offerings->map(function ($offering) use (
            $seatConsumingStatuses, $supportPaymentPerWeek, &$totalSeats, &$seatsUsed, &$totalFunding
        ) {
            $seats = (int) $offering->total_seats;

            $used = Claim::where('program_offering_guid', $offering->guid)
                ->whereIn('claim_status', $seatConsumingStatuses)
                ->count();

            $weeks = (int) ($offering->program->number_weeks ?? 0);
            $funding = $supportPaymentPerWeek * $seats * $weeks;

            $totalSeats += $seats;
            $seatsUsed += $used;
            $totalFunding += $funding;

            return [
                'guid' => $offering->guid,
                'offering_name' => $offering->offering_name,
                'total_seats' => $seats,
                'seats_used' => $used,
                'seats_available' => max($seats - $used, 0),
                'number_weeks' => $weeks,
                'funding' => $funding,
            ];
        })->values();

        $seatSummary = [
            'total' => $totalSeats,
            'used' => $seatsUsed,
            'available' => max($totalSeats - $seatsUsed, 0),
        ];

        return Inertia::render('Institution::Dashboard', [
            'results' => $institution,
            'programYear' => $programYear,
            'offeringSummaries' => $offeringSummaries,
            'stats' => $stats,
            'seatSummary' => $seatSummary,
            'totalFunding' => $totalFunding,
            'supportPaymentPerWeek' => $supportPaymentPerWeek,
        ]);
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $institution = $user->institution;

        return Inertia::render('Institution::Institution', ['institution' => $institution]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response::render
     */
    public function staffList(Request $request): \Inertia\Response
    {
        $user = User::find(Auth::user()->id);
        $institution = $user->institution->staff()->with('user.roles')->get();

        return Inertia::render('Institution::Staff', ['status' => true, 'results' => $institution]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function staffUpdate(InstitutionStaffEditRequest $request): \Inertia\Response
    {
        InstitutionStaff::where('id', $request->id)->update($request->validated());
        $user = User::find(Auth::user()->id);
        $institution = $user->institution->staff;

        return Inertia::render('Institution::Staff', ['status' => true, 'results' => $institution]);
    }

    /**
     * Update the specified resource role in storage.
     */
    public function staffUpdateRole(Request $request): \Inertia\Response
    {
        $newRole = Role::where('name', Role::Institution_GUEST)->first();
        if ($request->input('role') === 'User') {
            $newRole = Role::where('name', Role::Institution_USER)->first();
        }

        $rolesToCheck = [Role::Ministry_ADMIN, Role::SUPER_ADMIN, Role::Institution_ADMIN, Role::Institution_USER];
        if (Auth::user()->roles()->pluck('name')->intersect($rolesToCheck)->isNotEmpty() && Auth::user()->disabled === false) {
            $staff = InstitutionStaff::where('id', $request->input('id'))->first();

            if (! is_null($staff)) {
                //reset roles
                $roles = Role::whereIn('name', [Role::Institution_ADMIN, Role::Institution_USER, Role::Institution_GUEST])->get();
                foreach ($roles as $role) {
                    $staff->user->roles()->detach($role);
                }

                $staff->user->roles()->attach($newRole);
                event(new StaffRoleChanged($staff->user, $newRole));
            }
        }

        $user = User::find(Auth::user()->id);
        $institution = $user->institution->staff;

        return Inertia::render('Institution::Staff', ['status' => true, 'results' => $institution]);
    }
}
