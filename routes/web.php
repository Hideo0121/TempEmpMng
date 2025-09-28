<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateSkillSheetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\AgencyController;
use App\Http\Controllers\Master\CandidateStatusController;
use App\Http\Controllers\Master\JobCategoryController;
use App\Http\Controllers\Master\UserController;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/candidates', [CandidateController::class, 'index'])->name('candidates.index');
    Route::get('/candidates/create', [CandidateController::class, 'create'])->name('candidates.create');
    Route::post('/candidates', [CandidateController::class, 'store'])->name('candidates.store');
    Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])->name('candidates.edit');
    Route::put('/candidates/{candidate}', [CandidateController::class, 'update'])->name('candidates.update');
    Route::patch('/candidates/{candidate}/status', [CandidateController::class, 'changeStatus'])->name('candidates.status.update');
    Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])->name('candidates.show');
    Route::get('/candidates/{candidate}/skill-sheets/{skillSheet}', [CandidateSkillSheetController::class, 'download'])
        ->name('candidates.skill-sheets.download');
    Route::get('/candidates/{candidate}/skill-sheets/{skillSheet}/preview', [CandidateSkillSheetController::class, 'preview'])
        ->name('candidates.skill-sheets.preview');

    Route::middleware('manager')->prefix('masters')->name('masters.')->group(function () {
        Route::view('/', 'masters.index')->name('index');
        Route::get('job-categories/export', [JobCategoryController::class, 'export'])->name('job-categories.export');
        Route::post('job-categories/import', [JobCategoryController::class, 'import'])->name('job-categories.import');
        Route::resource('job-categories', JobCategoryController::class)->except(['show', 'destroy']);
        Route::get('candidate-statuses/export', [CandidateStatusController::class, 'export'])->name('candidate-statuses.export');
        Route::post('candidate-statuses/import', [CandidateStatusController::class, 'import'])->name('candidate-statuses.import');
        Route::resource('candidate-statuses', CandidateStatusController::class)->except(['show', 'destroy']);
        Route::get('agencies/export', [AgencyController::class, 'export'])->name('agencies.export');
        Route::post('agencies/import', [AgencyController::class, 'import'])->name('agencies.import');
        Route::resource('agencies', AgencyController::class)->except(['show', 'destroy']);
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::post('users/{user}/test-email', [UserController::class, 'sendTestEmail'])->name('users.test-email');
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
    });
});
