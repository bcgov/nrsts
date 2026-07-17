<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Modules\Student\Http\Controllers\ApplicationController;
use Modules\Student\Http\Controllers\StudentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::group(
    [
        'middleware' => [Authenticate::class],
        'as' => 'student.',
    ], function () {
        Route::get('/applications', [ApplicationController::class, 'applications'])->name('home');
        Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::put('/applications', [ApplicationController::class, 'update'])->name('applications.update');
        Route::put('/applications/transition', [ApplicationController::class, 'transition'])->name('applications.transition');

        Route::get('/student/api/fetch/students/applications', [ApplicationController::class, 'fetchApplications'])->name('claims.fetchApplications');
        Route::get('/student/api/fetch/institutions/{institution?}', [StudentController::class, 'fetchInstitutions'])->name('claims.fetchInstitutions');

        Route::get('/faqs', [StudentController::class, 'faqList'])->name('faqs.index');

    });
