<?php

namespace Modules\Student\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Response;

class StudentController extends Controller
{
    public function fetchInstitutions(Request $request, $institution = null)
    {
        if (! is_null($institution)) {
            $institution = Institution::where('guid', $institution)->with('activePrograms')
                ->whereHas('allocations', function ($query) {
                    $query->where('status', 'active');
                })->first();

            return Response::json(['status' => true, 'institution' => $institution]);
        }

        $institutions = Institution::active()->whereHas('allocations', function ($query) {
            $query->where('status', 'active');
        })->get();

        return Response::json(['status' => true, 'institutions' => $institutions]);
    }

    public function faqList(Request $request): \Inertia\Response
    {
        $faqs = Faq::where('active_status', true)->orderBy('order', 'asc')->get();

        return Inertia::render('Student::Faq', ['status' => true, 'results' => $faqs,]);
    }
}
