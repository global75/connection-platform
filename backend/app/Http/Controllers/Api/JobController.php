<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public-facing job listing endpoints (no auth required).
 */
class JobController extends Controller
{
    public function __construct(private JobService $jobs) {}

    public function index(Request $request): JsonResponse
    {
        $jobs = $this->jobs->search($request->only([
            // keyword
            'q', 'category', 'skills',
            // where the job is
            'location', 'city', 'state', 'country', 'latitude', 'longitude', 'radius', 'mode',
            // how the work happens ('location_type' kept for older clients)
            'work_arrangement', 'location_type',
            // who may apply
            'hiring_scope', 'candidate_country', 'candidate_state',
            // everything else
            'experience_level', 'employment_type', 'salary_min', 'salary_max',
            'visa_sponsorship', 'sort',
        ]), perPage: min(50, max(5, (int) $request->input('per_page', 15))));

        return response()->json($jobs);
    }

    public function show(string $slug): JsonResponse
    {
        $job = Job::where('slug', $slug)->firstOrFail();
        $job = $this->jobs->getJobWithDetails($job);

        return response()->json(['job' => $job]);
    }
}
