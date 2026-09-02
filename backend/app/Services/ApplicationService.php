<?php

namespace App\Services;

use App\Jobs\QualifyApplicationLead;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobSeekerProfile;
use App\Notifications\ApplicationReceived;
use App\Notifications\ApplicationStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(private FileUploadService $fileUpload) {}

    public function apply(Job $job, JobSeekerProfile $seeker, array $data): JobApplication
    {
        if ($job->applications()->where('job_seeker_profile_id', $seeker->id)->exists()) {
            throw ValidationException::withMessages([
                'job_id' => ['You have already applied to this job.'],
            ]);
        }

        if ($job->status !== 'active') {
            throw ValidationException::withMessages([
                'job_id' => ['This job is no longer accepting applications.'],
            ]);
        }

        return DB::transaction(function () use ($job, $seeker, $data) {
            // Snapshot resume at time of application
            $resumeSnapshot = $seeker->resume;
            if (!empty($data['resume'])) {
                $resumeSnapshot = $this->fileUpload->uploadResume($data['resume'], 'applications');
            }

            $application = JobApplication::create([
                'job_id'               => $job->id,
                'job_seeker_profile_id'=> $seeker->id,
                'cover_letter'         => $data['cover_letter'] ?? null,
                'resume_snapshot'      => $resumeSnapshot,
                'expected_salary'      => $data['expected_salary'] ?? null,
                'currency'             => $data['currency'] ?? 'USD',
            ]);

            $job->increment('applications_count');

            // Notify employer
            $job->employer->user->notify(new ApplicationReceived($application));

            // Qualify the lead in the background so the applicant isn't kept waiting
            // on an AI call; afterCommit keeps the worker from racing this transaction.
            QualifyApplicationLead::dispatch($application)->afterCommit();

            return $application->load('job.employer', 'jobSeeker.user');
        });
    }

    public function updateStatus(JobApplication $application, string $status, ?string $notes = null): JobApplication
    {
        $application->update([
            'status'         => $status,
            'employer_notes' => $notes ?? $application->employer_notes,
        ]);

        // Notify job seeker
        $application->jobSeeker->user->notify(new ApplicationStatusUpdated($application));

        return $application->fresh();
    }

    public function withdraw(JobApplication $application): void
    {
        if (in_array($application->status, ['hired', 'rejected'])) {
            throw ValidationException::withMessages([
                'status' => ['Cannot withdraw a closed application.'],
            ]);
        }

        $application->update(['status' => 'withdrawn']);
    }
}
