<?php

namespace Modules\Ministry\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramInformationEditRequest;
use App\Http\Requests\ProgramStoreRequest;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Response;

class ProgramController extends Controller
{
    /**
     * Display a listing of the programs.
     */
    public function index()
    {
        $programs = Program::query()
            ->withCount(['offerings' => function ($query) {
                $query->where('active_status', true);
            }])
            ->withSum(['offerings' => function ($query) {
                $query->where('active_status', true);
            }], 'total_seats');

        if (request()->filter_name !== null) {
            $programs = $programs->where('program_name', 'ILIKE', '%'.request()->filter_name.'%');
        }

        $programs = $programs->orderBy('program_name')
            ->paginate(25)
            ->onEachSide(1)
            ->appends(request()->query());

        // Ministry-configured weekly support payment amount ($) used to calculate
        // the weekly support cost column (amount x number of seats).
        $supportPaymentPerWeek = (float) (Util::where('field_type', 'Support Payment Per Week')
            ->where('active_flag', true)
            ->value('field_name') ?? 0);

        return Inertia::render('Ministry::Programs', [
            'status' => true,
            'results' => $programs,
            'supportPaymentPerWeek' => $supportPaymentPerWeek,
        ]);
    }

    /**
     * Show the specified program with its offerings.
     */
    public function show(Program $program, $page = 'details')
    {
        $program = Program::where('id', $program->id)
            ->with(['offerings' => function ($query) {
                $query->with('py')->orderBy('offering_name');
            }])
            ->first();

        $institutions = Institution::where('active_status', true)->orderBy('name')->get(['id', 'guid', 'name']);

        $programYears = \App\Models\ProgramYear::orderByDesc('status')->orderByDesc('start_date')
            ->get(['id', 'guid', 'start_date', 'end_date', 'status']);

        return Inertia::render('Ministry::Program', [
            'page' => $page,
            'results' => $program,
            'institutions' => $institutions,
            'programYears' => $programYears,
        ]);
    }

    public function fetchPrograms(Request $request, ?Program $program = null)
    {
        $body = Program::where(['institution_guid' => $request->input('institution_guid'), 'active_status' => true])->get();
        if (! is_null($program)) {
            $body = $program;
        }

        return Response::json(['status' => true, 'body' => $body]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProgramStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        $program = Program::create($request->validated());

        $program = Program::find($program->id);

        return Redirect::route('ministry.institutions.show', [$program->institution->id, 'programs']);

    }

    /**
     * Update the specified program's information.
     */
    public function update(ProgramInformationEditRequest $request): \Illuminate\Http\RedirectResponse
    {
        $program = Program::where('id', $request->id)->first();

        $program->update(
            collect($request->validated())->except('id')->toArray()
        );

        // Deactivating a program cascades to all of its offerings.
        if (! $program->active_status) {
            ProgramOffering::where('program_guid', $program->guid)
                ->where('active_status', true)
                ->update(['active_status' => false]);
        }

        return Redirect::route('ministry.programs.show', [$request->id]);
    }
}
