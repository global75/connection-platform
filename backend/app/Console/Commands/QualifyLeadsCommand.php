<?php

namespace App\Console\Commands;

use App\Jobs\QualifyApplicationLead;
use App\Models\JobApplication;
use Illuminate\Console\Command;

/**
 * Backfill for applications that predate the feature, or that failed to
 * qualify (e.g. while the Anthropic key was misconfigured).
 */
class QualifyLeadsCommand extends Command
{
    protected $signature = 'leads:qualify
                            {--job= : Only applications for this job id}
                            {--limit=200 : Maximum applications to queue}
                            {--retry-failed : Include applications whose last attempt failed}
                            {--force : Re-qualify even if a verdict already exists}';

    protected $description = 'Queue AI qualification for applications that have no usable verdict yet';

    public function handle(): int
    {
        if (! config('ai.lead_qualification.enabled')) {
            $this->error('Lead qualification is disabled (ai.lead_qualification.enabled).');

            return self::FAILURE;
        }

        $query = JobApplication::query()
            ->whereNotIn('status', ['hired', 'rejected', 'withdrawn'])
            ->when($this->option('job'), fn ($q, $jobId) => $q->where('job_id', $jobId))
            ->latest()
            ->limit((int) $this->option('limit'));

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereDoesntHave('qualification');

                if ($this->option('retry-failed')) {
                    $q->orWhereHas('qualification', fn ($sub) => $sub->where('status', 'failed'));
                }
            });
        }

        $applications = $query->get();

        if ($applications->isEmpty()) {
            $this->info('Nothing to qualify.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($applications->count());

        foreach ($applications as $application) {
            QualifyApplicationLead::dispatch($application, force: (bool) $this->option('force'), announce: false);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Queued {$applications->count()} application(s) for qualification.");

        return self::SUCCESS;
    }
}
