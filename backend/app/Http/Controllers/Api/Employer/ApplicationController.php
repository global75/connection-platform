<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(private ApplicationService $applications) {}

    public function index(Request $request): JsonResponse
    {
        $employer = $request->user()->employerProfile;

        $query = JobApplication::whereHas('job', fn ($q) => $q->where('employer_profile_id', $employer->id))
            ->with([
                'job:id,title,slug',
                'jobSeeker.user:id,name,avatar',
                'jobSeeker:id,user_id,headline,experience_level,current_country,is_identity_verified,verified_badges',
                'jobSeeker.skills:id,name',
            ])
            ->latest();

        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(15));
    }

    public function show(Request $request, JobApplication $application): JsonResponse
    {
        $this->authorize('view', $application);
        $application->markViewed();

        return response()->json([
            'application' => $application->load([
                'job', 'jobSeeker.user', 'jobSeeker.skills', 'latestInterview',
            ]),
        ]);
    }

    public function updateStatus(Request $request, JobApplication $application): JsonResponse
    {
        $this->authorize('update', $application);

        $request->validate([
            'status' => ['required', 'in:' . implode(',', JobApplication::STATUSES)],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        $application = $this->applications->updateStatus(
            $application,
            $request->status,
            $request->notes
        );

        return response()->json(['application' => $application]);
    }
}
