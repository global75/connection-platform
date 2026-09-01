<?php

namespace App\Jobs;

use App\Models\JobApplication;
use App\Models\LeadQualification;
use App\Notifications\HotLeadIdentified;
use App\Services\ApplicationService;
use App\Services\LeadQualification\LeadQualificationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Qualifies one application in the background.
 *
 * Dispatched automatically when an application lands, and on demand when an
 * employer asks for a re-run. Announcements (hot-lead notification, optional
 * auto-shortlisting) only fire on the automatic pass — a manual re-run should
 * not re-notify an employer who is already looking at the application.
 */
class QualifyApplicationLead implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /** Only one qualification per application may be in flight at a time. */
    public int $uniqueFor = 300;

    public function __construct(
        public JobApplication $application,
        public bool $force = false,
        public bool $announce = true,
    ) {}

    /**
     * Manual re-runs get their own lock key: an in-flight automatic pass must
     * not silently swallow an employer's explicit request for a fresh verdict.
     */
    public function uniqueId(): string
    {
        return $this->application->id.':'.($this->force ? 'manual' : 'auto');
    }

    /**
     * Retry with room for a rate limit to clear before giving up.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(LeadQualificationService $qualifications, ApplicationService $applications): void
    {
        // qualify() returns null when the verdict already stands. On a retry
        // that got this far, scoring succeeded and only the announcements
        // failed, so fall back to the stored verdict rather than giving up on
        // them — announce() is idempotent via announced_at.
        $qualification = $qualifications->qualify($this->application, $this->force)
            ?? $this->application->qualification()->first();

        if (! $qualification?->isCompleted() || ! $this->announce) {
            return;
        }

        $this->announce($qualification, $applications);
    }

    /**
     * Fire the one-off side effects of a completed verdict, at most once.
     */
    private function announce(LeadQualification $qualification, ApplicationService $applications): void
    {
        if ($qualification->announced_at !== null) {
            return;
        }

        $this->autoShortlist($qualification, $applications);
        $this->notifyEmployer($qualification);

        $qualification->update(['announced_at' => now()]);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('Lead qualification job exhausted its retries', [
            'application_id' => $this->application->id,
            'error'          => $e?->getMessage(),
        ]);

        $this->application->qualification()->update([
            'status' => 'failed',
            'error'  => $e?->getMessage() ?? 'Qualification job failed.',
        ]);
    }

    /**
     * Move clear-cut matches straight into the shortlist when the platform is
     * configured to do so. Anything an employer has already triaged is left be.
     */
    private function autoShortlist(LeadQualification $qualification, ApplicationService $applications): void
    {
        if (! config('ai.lead_qualification.auto_shortlist')
            || $qualification->recommended_action !== 'shortlist'
            || ! in_array($this->application->status, ['submitted', 'viewed'], true)) {
            return;
        }

        $applications->updateStatus($this->application, 'shortlisted');
    }

    private function notifyEmployer(LeadQualification $qualification): void
    {
        if (! config('ai.lead_qualification.notify_on_hot') || ! $qualification->isHot()) {
            return;
        }

        $employer = $this->application->job?->employer?->user;

        $employer?->notify(new HotLeadIdentified($this->application, $qualification));
    }
}
