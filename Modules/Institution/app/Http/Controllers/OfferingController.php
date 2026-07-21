<?php

namespace Modules\Institution\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\ProgramYear;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Modules\Institution\Http\Requests\InstitutionOfferingEditRequest;
use Modules\Institution\Http\Requests\InstitutionOfferingStoreRequest;

class OfferingController extends Controller
{
    /**
     * Display the institution's program offerings.
     */
    public function index(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $institution = $user->institution;

        $activeProgramYear = ProgramYear::where('status', 'active')->first();

        $offerings = ProgramOffering::where('institution_guid', $institution->guid)
            ->when($request->filter_name, function ($query) use ($request) {
                $query->where('offering_name', 'ILIKE', '%'.$request->filter_name.'%');
            })
            ->with(['py', 'program'])
            ->orderByDesc('created_at')
            ->get();

        // Institutions may only offer active, global programs.
        $programs = Program::isActive()->orderBy('program_name')->get(['id', 'guid', 'program_name']);

        return Inertia::render('Institution::Offerings', [
            'results' => $offerings,
            'institution' => $institution,
            'programs' => $programs,
            'programYear' => $activeProgramYear,
            'filters' => ['filter_name' => $request->filter_name],
        ]);
    }

    /**
     * Store a newly created offering (as a draft or submitted request).
     */
    public function store(InstitutionOfferingStoreRequest $request): RedirectResponse
    {
        ProgramOffering::create($request->validated());

        return Redirect::route('institution.offerings.index');
    }

    /**
     * Update an offering. Only draft offerings owned by the institution can be
     * changed; authorization is enforced in the request class.
     */
    public function update(InstitutionOfferingEditRequest $request): RedirectResponse
    {
        ProgramOffering::where('id', $request->id)->update(
            collect($request->validated())->except('id')->toArray()
        );

        return Redirect::route('institution.offerings.index');
    }
}
