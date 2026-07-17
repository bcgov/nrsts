<?php

namespace Modules\Student\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Response;

class StudentController extends Controller
{
    public function fetchInstitutions(Request $request, $institution = null)
    {
        // Applications may only target offerings in the active program year.
        $activeProgramYearGuid = ProgramYear::where('status', 'active')->value('guid');

        if (! is_null($institution)) {
            $institution = Institution::where('guid', $institution)
                ->whereHas('activeOfferings', function ($query) use ($activeProgramYearGuid) {
                    $query->where('program_year_guid', $activeProgramYearGuid);
                })->first();

            if ($institution) {
                // Only programs that have an active offering for this institution
                // in the active program year.
                $programs = Program::isActive()
                    ->whereHas('offerings', function ($query) use ($activeProgramYearGuid, $institution) {
                        $query->where('active_status', true)
                            ->where('program_year_guid', $activeProgramYearGuid)
                            ->where('institution_guid', $institution->guid);
                    })
                    ->orderBy('program_name')
                    ->get();

                $institution->setRelation('activePrograms', $programs);
            }

            return Response::json(['status' => true, 'institution' => $institution]);
        }

        $institutions = Institution::active()
            ->whereHas('activeOfferings', function ($query) use ($activeProgramYearGuid) {
                $query->where('program_year_guid', $activeProgramYearGuid);
            })->get();

        return Response::json(['status' => true, 'institutions' => $institutions]);
    }

    public function faqList(Request $request): \Inertia\Response
    {
        $faqs = Faq::where('active_status', true)->orderBy('order', 'asc')->get();

        return Inertia::render('Student::Faq', ['status' => true, 'results' => $faqs,]);
    }
}
