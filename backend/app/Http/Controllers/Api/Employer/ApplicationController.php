<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Jobs\QualifyApplicationLead;
use App\Models\JobApplication;
use App\Models\LeadQualification;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(private ApplicationService $applications) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tier'               => ['nullable', 'in:'.implode(',', LeadQualification::TIERS)],
            'recommended_action' => ['nullable', 'in:'.implode(',', LeadQualification::ACTIONS)],
            'min_score'          => ['nullable', 'integer', 'between:0,100'],
            'sort'               => ['nullable', 'in:recent,score'],
        ]);

        $employer = $request->user()->employerProfile;

        $query = JobApplication::whereHas('job', fn ($q) => $q->where('employer_profile_id', $employer->id))
            ->with([
                'job:id,title,slug',
                'jobSeeker.user:id,name,avatar',
                'jobSeeker:id,user_id,headline,experience_level,current_country,is_identity_verified,verified_badges',
                'jobSeeker.skills:id,name',
                'qualification',
            ]);

        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('tier')) {
            $query->whereHas('qualification', fn ($q) => $q->where('tier', $request->tier));
        }
        if ($request->filled('recommended_action')) {
            $query->whereHas('qualification', fn ($q) => $q->where('recommended_action', $request->recommended_action));
        }
        if ($request->filled('min_score')) {
            $query->whereHas('qualification', fn ($q) => $q->where('score', '>=', (int) $request->min_score));
        }

        // Score-sorted pipelines put unqualified applications last (NULL scores
        // sort after real ones under DESC), then fall back to recency.
        $request->input('sort') === 'score'
            ? $query->orderByDesc($this->scoreSubquery())->latest()
            : $query->latest();

        return response()->json($query->paginate(15));
    }

    public function show(Request $request, JobApplication $application): JsonResponse
    {
        $this->authorize('view', $application);
        $application->markViewed();

        return response()->json([
            'application' => $application->load([
                'job', 'jobSeeker.user', 'jobSeeker.skills', 'latestInterview', 'qualification',
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

    /**
     * Re-run AI qualification for a single application.
     *
     * The work is queued so the request returns immediately; the client polls
     * `show` until the verdict's qualified_at moves.
     */
    public function qualify(Request $request, JobApplication $application): JsonResponse
    {
        $this->authorize('update', $application);

        if (! config('ai.lead_qualification.enabled')) {
            return response()->json(['message' => 'Lead qualification is disabled.'], 409);
        }

        // A re-run already in flight holds the job's unique lock, and a second
        // dispatch would be dropped on the floor — so say that rather than
        // reporting work that was never queued.
        if ($application->qualification?->status === 'processing') {
            return response()->json([
                'message'       => 'Qualification is already running for this application.',
                'qualification' => $application->qualification,
            ], 409);
        }

        QualifyApplicationLead::dispatch($application, force: true, announce: false);

        return response()->json([
            'message'       => 'Qualification queued.',
            'qualification' => $application->fresh()->qualification,
        ], 202);
    }

    /**
     * Pipeline breakdown across the employer's open applications.
     */
    public function qualificationSummary(Request $request): JsonResponse
    {
        $employer = $request->user()->employerProfile;

        $qualifications = LeadQualification::whereHas(
            'application.job',
            fn ($q) => $q->where('employer_profile_id', $employer->id)
        );

        $tiers = (clone $qualifications)->completed()
            ->selectRaw('tier, count(*) as total, avg(score) as average_score')
            ->groupBy('tier')
            ->get()
            ->keyBy('tier');

        return response()->json([
            'summary' => [
                'tiers' => collect(LeadQualification::TIERS)->mapWithKeys(fn ($tier) => [
                    $tier => [
                        'total'         => (int) ($tiers[$tier]->total ?? 0),
                        'average_score' => round((float) ($tiers[$tier]->average_score ?? 0), 1),
                    ],
                ]),
                'awaiting_qualification' => (clone $qualifications)
                    ->whereIn('status', ['pending', 'processing'])
                    ->count(),
                'failed' => (clone $qualifications)->where('status', 'failed')->count(),
            ],
        ]);
    }

    /**
     * Correlated subquery of each application's score, for ordering.
     */
    private function scoreSubquery()
    {
        return LeadQualification::select('score')
            ->whereColumn('lead_qualifications.job_application_id', 'job_applications.id')
            ->limit(1);
    }
}
