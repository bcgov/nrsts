<?php

namespace Modules\Ministry\Http\Controllers;

use App\Events\ProgramYearUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\FaqEditRequest;
use App\Http\Requests\FaqStoreRequest;
use App\Http\Requests\ProgramYearEditRequest;
use App\Http\Requests\ProgramYearStoreRequest;
use App\Http\Requests\UtilEditRequest;
use App\Http\Requests\UtilStoreRequest;
use App\Models\Allocation;
use App\Models\Claim;
use App\Models\Faq;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use App\Models\ProgramYear;
use App\Models\Role;
use App\Models\User;
use App\Models\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Response;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response::render
     */
    public function staffList(Request $request): \Inertia\Response
    {
        $staff = User::with('roles')
            ->whereHas('roles', function ($q) {
                return $q->whereIn('name', [Role::Ministry_ADMIN, Role::Ministry_USER, Role::Ministry_GUEST]);
            })->orderBy('created_at', 'desc')->get();

        foreach ($staff as $user) {
            if ($user->roles->contains('name', Role::Ministry_ADMIN)) {
                $user->access_type = 'A';
            } elseif ($user->roles->contains('name', Role::Ministry_USER)) {
                $user->access_type = 'U';
            } else {
                $user->access_type = 'G';
            }
        }

        return Inertia::render('Ministry::Maintenance', ['status' => true, 'results' => $staff, 'page' => 'staff']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\RedirectResponse::render
     */
    public function updateStatus(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        if (Gate::denies('update', $user)) {
            Log::warning('403 Access denied in MaintenanceController::updateStatus', [
                'target_user_id' => $user->id,
                'user_id' => $request->user()?->id,
                'route' => $request->path(),
            ]);
            abort(403);
        }
        $user->disabled = $request->input('disabled');
        $user->save();

        return Redirect::route('ministry.maintenance.staff.list');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\RedirectResponse::render
     */
    public function updateRole(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        if (Gate::denies('update', $user)) {
            Log::warning('403 Access denied in MaintenanceController::updateRole', [
                'target_user_id' => $user->id,
                'user_id' => $request->user()?->id,
                'route' => $request->path(),
            ]);
            abort(403);
        }
        $newRole = Role::where('name', Role::Ministry_GUEST)->first();
        if ($request->input('role') === 'Admin') {
            $newRole = Role::where('name', Role::Ministry_ADMIN)->first();
        }
        if ($request->input('role') === 'User') {
            $newRole = Role::where('name', Role::Ministry_USER)->first();
        }

        //reset roles
        $roles = Role::whereIn('name', [Role::Ministry_ADMIN, Role::Ministry_USER, Role::Ministry_GUEST])->get();
        foreach ($roles as $role) {
            $user->roles()->detach($role);
        }

        $user->roles()->attach($newRole);
        //        event(new StaffRoleChanged1($user, $newRole));

        return Redirect::route('ministry.maintenance.staff.list');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response::render
     */
    public function utilList(Request $request): \Inertia\Response
    {
        $utils = Util::orderBy('field_name', 'asc')->get();

        $cat_utils = [];
        $cat_titles = [];
        foreach ($utils as $util) {
            $cat_utils[$util->field_type][] = $util;
        }
        foreach ($cat_utils as $k => $v) {
            $cat_titles[] = $k;
        }
        sort($cat_titles);

        return Inertia::render('Ministry::Maintenance', ['status' => true, 'results' => $cat_utils,
            'categories' => $cat_titles, 'page' => 'utils']);
    }

    /**
     * Update a utility resource.
     *
     * @return \Illuminate\Http\RedirectResponse::render
     */
    public function utilUpdate(UtilEditRequest $request, Util $util): \Illuminate\Http\RedirectResponse
    {
        $util->update($request->validated());
        $sortedUtils = Util::getSortedUtils();
        Cache::put('sorted_utils', $sortedUtils, 3600);

        return Redirect::route('ministry.maintenance.utils.list');
    }

    /**
     * Store a utility resource.
     *
     * @return \Illuminate\Http\RedirectResponse::render
     */
    public function utilStore(UtilStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        Util::create($request->validated());
        $sortedUtils = Util::getSortedUtils();
        Cache::put('sorted_utils', $sortedUtils, 3600);

        return Redirect::route('ministry.maintenance.utils.list');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response::render
     */
    public function pyList(Request $request): \Inertia\Response
    {
        $programYears = ProgramYear::orderBy('start_date', 'asc')->get();

        return Inertia::render('Ministry::Maintenance', ['status' => true, 'results' => $programYears,
            'page' => 'program_years']);
    }

    /**
     * Update a utility resource.
     *
     * @return \Illuminate\Http\RedirectResponse::render
     */
    public function pyUpdate(ProgramYearEditRequest $request, ProgramYear $programYear): \Illuminate\Http\RedirectResponse
    {
        $programYear->update($request->validated());
//        Cache::forget('global_program_years');

        event(new ProgramYearUpdated($programYear, $request->status));

        return Redirect::route('ministry.maintenance.program_years.list');
    }

    /**
     * Store a utility resource.
     *
     * @return \Illuminate\Http\RedirectResponse::render
     */
    public function pyStore(ProgramYearStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        ProgramYear::create($request->validated());
//        Cache::forget('global_program_years');

        return Redirect::route('ministry.maintenance.program_years.list');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response::render
     */
    public function faqList(Request $request): \Inertia\Response
    {
        $faqs = Faq::orderBy('order', 'asc')->get();

        return Inertia::render('Ministry::Maintenance', ['status' => true, 'results' => $faqs,
            'page' => 'faqs']);
    }

    /**
     * Update a utility resource.
     *
     * @return \Illuminate\Http\RedirectResponse::render
     */
    public function faqUpdate(FaqEditRequest $request, Faq $faq): \Illuminate\Http\RedirectResponse
    {
        $faq->update($request->validated());

        return Redirect::route('ministry.maintenance.faqs.list');
    }

    /**
     * Store a utility resource.
     *
     * @return \Illuminate\Http\RedirectResponse::render
     */
    public function faqStore(FaqStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        Faq::create($request->validated());

        return Redirect::route('ministry.maintenance.faqs.list');
    }
}
