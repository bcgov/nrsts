<?php

namespace App\Http\Middleware;

use App\Models\ProgramYear;
use App\Models\User;
use App\Models\Util;
use App\Services\PdexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = null;
        $globalProgramYears = ['list' => [], 'default' => null, 'programs' => []];
        if (Auth::check()) {
            $user = User::find(Auth::user()->id);
        }

        if(!is_null($user) && !is_null($user->institution)){
            $globalProgramYears = Cache::remember('global_program_years_' . $user->institution->guid, now()->addHours(10), function () use ($user) {
                $programYears = ProgramYear::orderBy('id')->get();
                if ($programYears->isEmpty()) {
                    return [
                        'list' => [],
                        'default' => null,
                        'programs' => [],
                    ];
                }

                // Find the program year with status 'active'
                $activeProgramYear = $programYears->firstWhere('status', 'active');
                $programs = $user->institution->activePrograms
                    ->sortBy('program_name') // Sort by program_name in ascending order
                    ->pluck('program_name', 'guid')
                    ->toArray();

                return [
                    'list' => $programYears,
                    'default' => $activeProgramYear ? $activeProgramYear->guid : $programYears[0]->guid,
                    'programs' => $programs,
                ];
            });
        }


        $sortedUtils = Cache::remember('sorted_utils', 3600, function () { //for an hour
            return Util::getSortedUtils();
        });

        // PDEX student profile option lists (province, gender, etc.). Shared globally so the
        // claim/applicant profile fields render their selected labels wherever they appear
        // (student application forms and the institution claim editor). Cached in the service.
        $studentUtils = is_null($user) ? [] : app(PdexService::class)->studentUtils();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'roles' => is_null($user) ? null : $user->roles,
                'readOnly' => Session::has('read-only'),
            ],
            'utils' => $sortedUtils,
            'studentUtils' => $studentUtils,
            'programYearsData' => [
                'list' => $globalProgramYears['list'],
                'default' => $globalProgramYears['default'],
                'programs' => $globalProgramYears['programs'],
            ],

            'ziggy' => function () {
                return (new Ziggy)->toArray();
            },
            'flash' => [
                'success' => Session::get('success'),
                'error' => Session::get('error'),
            ],
            'logoutUrl' => Session::get('kc_logout_uri'),
            'logoutBcscUrl' => Session::get('bcsc_logout_uri'),
        ]);

    }
}
