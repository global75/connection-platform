<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiscoveryController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\SitemapController;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Employer;
use App\Http\Controllers\Api\JobSeeker;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────────────────────────────────
// Public
// ──────────────────────────────────────────────────────────────────────────────

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// Public job listings
Route::get('/jobs',       [JobController::class, 'index']);
Route::get('/jobs/{slug}',[JobController::class, 'show']);

// Marketplace discovery: real locations, categories and counts
Route::get('/locations',            [DiscoveryController::class, 'locations']);
Route::get('/locations/suggest',    [DiscoveryController::class, 'locationSuggest']);
Route::get('/locations/{slug}',     [DiscoveryController::class, 'location']);
Route::get('/categories',           [DiscoveryController::class, 'categories']);
Route::get('/stats',                [DiscoveryController::class, 'stats']);
Route::get('/search-filters',       [DiscoveryController::class, 'filters']);

// SEO
Route::get('/sitemap.xml',          [SitemapController::class, 'index']);

// ──────────────────────────────────────────────────────────────────────────────
// Authenticated (any role)
// ──────────────────────────────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// ──────────────────────────────────────────────────────────────────────────────
// Employer routes
// ──────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'role:employer'])
     ->prefix('employer')
     ->name('employer.')
     ->group(function () {

    // Profile
    Route::get('/profile',    [Employer\ProfileController::class, 'show']);
    Route::put('/profile',    [Employer\ProfileController::class, 'update']);

    // Jobs
    Route::get('/jobs',                          [Employer\JobController::class, 'index']);
    Route::post('/jobs',                         [Employer\JobController::class, 'store']);
    Route::get('/jobs/{job}',                    [Employer\JobController::class, 'show']);
    Route::put('/jobs/{job}',                    [Employer\JobController::class, 'update']);
    Route::delete('/jobs/{job}',                 [Employer\JobController::class, 'destroy']);
    Route::patch('/jobs/{job}/toggle-status',    [Employer\JobController::class, 'toggleStatus']);

    // Applications
    Route::get('/applications',                          [Employer\ApplicationController::class, 'index']);
    Route::get('/applications/{application}',            [Employer\ApplicationController::class, 'show']);
    Route::patch('/applications/{application}/status',   [Employer\ApplicationController::class, 'updateStatus']);

    // Messaging
    Route::get('/conversations',                                [Employer\MessageController::class, 'conversations']);
    Route::post('/conversations',                               [Employer\MessageController::class, 'initiate']);
    Route::get('/conversations/{conversation}/messages',        [Employer\MessageController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages',       [Employer\MessageController::class, 'send']);
});

// ──────────────────────────────────────────────────────────────────────────────
// Job Seeker routes
// ──────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'role:job_seeker'])
     ->prefix('job-seeker')
     ->name('job_seeker.')
     ->group(function () {

    // Profile
    Route::get('/profile',         [JobSeeker\ProfileController::class, 'show']);
    Route::put('/profile',         [JobSeeker\ProfileController::class, 'update']);
    Route::get('/profile/resume',  [JobSeeker\ProfileController::class, 'resume']);

    // Job discovery
    Route::get('/jobs/recommended', [JobSeeker\JobController::class, 'recommended']);
    Route::get('/jobs/saved',        [JobSeeker\JobController::class, 'saved']);
    Route::post('/jobs/{job}/save',  [JobSeeker\JobController::class, 'save']);
    Route::delete('/jobs/{job}/save',[JobSeeker\JobController::class, 'unsave']);

    // Applications
    Route::get('/applications',                      [JobSeeker\ApplicationController::class, 'index']);
    Route::post('/jobs/{job}/apply',                 [JobSeeker\ApplicationController::class, 'store']);
    Route::get('/applications/{application}',        [JobSeeker\ApplicationController::class, 'show']);
    Route::patch('/applications/{application}/withdraw', [JobSeeker\ApplicationController::class, 'withdraw']);

    // Messaging
    Route::get('/conversations',                            [JobSeeker\MessageController::class, 'conversations']);
    Route::get('/conversations/{conversation}/messages',    [JobSeeker\MessageController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages',   [JobSeeker\MessageController::class, 'send']);
});

// ──────────────────────────────────────────────────────────────────────────────
// Admin routes
// ──────────────────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'role:admin'])
     ->prefix('admin')
     ->name('admin.')
     ->group(function () {

    Route::get('/dashboard',        [Admin\DashboardController::class, 'stats']);
    Route::get('/dashboard/growth', [Admin\DashboardController::class, 'userGrowth']);

    Route::get('/users',                     [Admin\UserController::class, 'index']);
    Route::get('/users/{user}',              [Admin\UserController::class, 'show']);
    Route::patch('/users/{user}/toggle-active', [Admin\UserController::class, 'toggleActive']);
    Route::delete('/users/{user}',           [Admin\UserController::class, 'destroy']);

    Route::get('/jobs',                  [Admin\JobController::class, 'index']);
    Route::patch('/jobs/{job}/feature',  [Admin\JobController::class, 'feature']);
    Route::delete('/jobs/{job}',         [Admin\JobController::class, 'destroy']);
    Route::post('/jobs/{id}/restore',    [Admin\JobController::class, 'restore']);

    Route::get('/reports',                       [Admin\ReportController::class, 'index']);
    Route::patch('/reports/{report}/resolve',    [Admin\ReportController::class, 'resolve']);
});
