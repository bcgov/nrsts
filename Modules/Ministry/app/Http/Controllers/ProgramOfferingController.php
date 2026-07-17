<?php

namespace Modules\Ministry\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramOfferingEditRequest;
use App\Http\Requests\ProgramOfferingStoreRequest;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ProgramOfferingController extends Controller
{
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
