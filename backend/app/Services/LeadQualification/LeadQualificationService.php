<?php

namespace App\Services\LeadQualification;

use App\Models\JobApplication;
use App\Models\LeadQualification;
use App\Services\LeadQualification\Contracts\LeadQualifier;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Produces and persists the qualification verdict for an application.
 *
 * Nothing here decides *when* to qualify — that is the caller's job (the queued
 * listener for new applications, the employer's manual re-run, the backfill
 * command). This class only guarantees that a verdict is produced or the row is
 * left in a truthful failed state.
 */
class LeadQualificationService
{
    public function __construct(
        private LeadQualifier $qualifier,
        private HeuristicLeadQualifier $fallback,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('ai.lead_qualification.enabled');
    }

    /**
     * Qualify an application, returning the persisted verdict.
     *
     * Returns null when qualification is disabled or does not apply to this
     * application (already closed, already qualified and not forced).
     *
     * @throws RuntimeException when no verdict could be produced at all.
     */
    public function qualify(JobApplication $application, bool $force = false): ?LeadQualification
    {
        if (! $this->enabled()) {
            return null;
        }

        // Checked before any row is created: a closed or already-qualified
        // application must not be left with a permanent "pending" verdict.
        $existing = $application->qualification()->first();

        if (! $force && ($existing?->isCompleted() || $application->isClosed())) {
            return null;
        }

        $qualification = $existing ?? $this->record($application);

        $qualification->update([
            'status'   => 'processing',
            'attempts' => $qualification->attempts + 1,
        ]);

        $lead = LeadProfile::fromApplication($application);

        try {
            $result = $this->qualifier->qualify($lead);
            $note   = null;
        } catch (Throwable $e) {
            [$result, $note] = $this->recover($application, $lead, $e);
        }

        $qualification->update(array_merge($result->toAttributes(), ['error' => $note]));

        return $qualification->fresh();
    }

    /**
     * The primary qualifier failed. Fall back to heuristic scoring when that is
     * allowed, otherwise leave the row failed and let the caller decide whether
     * to retry.
     *
     * @return array{0: QualificationResult, 1: string}
     */
    private function recover(JobApplication $application, LeadProfile $lead, Throwable $e): array
    {
        Log::warning('Lead qualification failed', [
            'application_id' => $application->id,
            'qualifier'      => $this->qualifier->name(),
            'error'          => $e->getMessage(),
        ]);

        $canFallBack = config('ai.lead_qualification.fallback_to_heuristic')
            && $this->qualifier->name() !== $this->fallback->name();

        if (! $canFallBack) {
            $application->qualification()->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);

            throw new RuntimeException(
                "Could not qualify application {$application->id}: {$e->getMessage()}",
                previous: $e
            );
        }

        return [
            $this->fallback->qualify($lead),
            'Scored heuristically — '.$this->qualifier->name().' was unavailable: '.$e->getMessage(),
        ];
    }

    /**
     * Get (or start) the qualification row for an application.
     */
    private function record(JobApplication $application): LeadQualification
    {
        return $application->qualification()->firstOrCreate([], ['status' => 'pending']);
    }
}
