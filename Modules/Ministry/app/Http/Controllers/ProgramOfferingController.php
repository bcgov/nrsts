<?php

namespace Modules\Ministry\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramOfferingEditRequest;
use App\Http\Requests\ProgramOfferingStoreRequest;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProgramOfferingController extends Controller
{
    /**
     * Display a listing of every offering across all institutions.
     */
    public function index(Request $request)
    {
        $offerings = ProgramOffering::query()
            ->with(['institution', 'program', 'py'])
            ->when($request->filter_name, function ($query) use ($request) {
                $query->where('offering_name', 'ILIKE', '%'.$request->filter_name.'%');
            })
            ->when($request->filter_status, function ($query) use ($request) {
                $query->where('offering_status', $request->filter_status);
            })
            ->orderBy('offering_name')
            ->paginate(25)
            ->onEachSide(1)
            ->appends($request->query());

        return Inertia::render('Ministry::Offerings', [
            'status' => true,
            'results' => $offerings,
            'filters' => [
                'filter_name' => $request->filter_name,
                'filter_status' => $request->filter_status,
            ],
        ]);
    }

    /**
     * Store a newly created program offering in storage.
     */
    public function store(ProgramOfferingStoreRequest $request): RedirectResponse
    {
        ProgramOffering::create($request->validated());

        $program = Program::where('guid', $request->program_guid)->first();

        return Redirect::route('ministry.programs.show', [$program->id, 'offerings']);
    }

    /**
     * Update the specified program offering in storage.
     */
    public function update(ProgramOfferingEditRequest $request): RedirectResponse
    {
        ProgramOffering::where('id', $request->id)->update(
            collect($request->validated())->except('id')->toArray()
        );

        $program = Program::where('guid', $request->program_guid)->first();

        return Redirect::route('ministry.programs.show', [$program->id, 'offerings']);
    }
}
