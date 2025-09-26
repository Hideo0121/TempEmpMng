<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\Master\AgencyController;
use App\Http\Controllers\Master\CandidateStatusController;
use App\Http\Controllers\Master\JobCategoryController;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/candidates', [CandidateController::class, 'index'])->name('candidates.index');
    Route::get('/candidates/create', [CandidateController::class, 'create'])->name('candidates.create');
    Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])->name('candidates.edit');
    Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])->name('candidates.show');

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
    });
});
