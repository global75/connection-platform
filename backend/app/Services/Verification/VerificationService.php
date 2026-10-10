<?php

namespace App\Services\Verification;

use App\Models\EmployerProfile;
use App\Models\JobSeekerProfile;
use App\Models\User;
use App\Models\Verification;
use App\Services\Verification\Contracts\VerificationChecker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Runs a verification and records the outcome.
 *
 * Checkers decide; this class owns persistence, the audit trail, and the
 * denormalised flags the UI reads (employer_profiles.is_verified,
 * job_seeker_profiles.verified_badges).
 */
class VerificationService
{
    /** @var array<string, VerificationChecker> */
    private array $checkers = [];

    /**
     * @param  iterable<VerificationChecker>  $checkers
     */
    public function __construct(iterable $checkers = [])
    {
        foreach ($checkers as $checker) {
            $this->extend($checker);
        }
    }

    public function extend(VerificationChecker $checker): self
    {
        $this->checkers[$checker->type()] = $checker;

        return $this;
    }

    public function checker(string $type): VerificationChecker
    {
        return $this->checkers[$type]
            ?? throw new VerificationUnavailable("No checker is registered for \"{$type}\".");
    }

    public function supports(string $type): bool
    {
        return isset($this->checkers[$type]) && $this->checkers[$type]->available();
    }

    /**
     * Types this deployment can actually perform for a given subject.
     *
     * @return list<string>
     */
    public function availableTypes(Model $subject): array
    {
        $allowed = $subject instanceof EmployerProfile
            ? Verification::EMPLOYER_TYPES
            : Verification::CANDIDATE_TYPES;

        return collect($allowed)->filter(fn ($type) => $this->supports($type))->values()->all();
    }

    /**
     * Run a check and persist the verdict.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws VerificationUnavailable when the type cannot be performed here.
     */
    public function verify(Model $subject, string $type, array $input = []): Verification
    {
        $this->assertVerifiable($subject);

        $checker = $this->checker($type);

        if (! $checker->available()) {
            throw new VerificationUnavailable("The {$type} check is not configured on this deployment.");
        }

        $verification = $this->record($subject, $type);
        $verification->update(['status' => 'processing']);

        $result = $checker->check($subject, $input);

        return DB::transaction(function () use ($subject, $verification, $result) {
            $verification->update($result->toAttributes());
            $this->syncSubject($subject);

            return $verification->fresh();
        });
    }

    /**
     * Admin override — approve or reject by hand, with the reviewer recorded.
     */
    public function review(Verification $verification, string $status, User $reviewer, ?string $reason = null): Verification
    {
        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('A manual review can only approve or reject.');
        }

        return DB::transaction(function () use ($verification, $status, $reviewer, $reason) {
            $verification->update([
                'status'           => $status,
                'provider'         => 'manual',
                'reviewed_by'      => $reviewer->id,
                'rejection_reason' => $status === 'rejected' ? $reason : null,
                'verified_at'      => $status === 'approved' ? now() : null,
                'metadata'         => array_merge($verification->metadata ?? [], [
                    'manual_review' => ['by' => $reviewer->id, 'at' => now()->toIso8601String()],
                ]),
            ]);

            $subject = $verification->verifiable;

            if ($subject !== null) {
                $this->syncSubject($subject);
            }

            return $verification->fresh();
        });
    }

    /**
     * Mark anything past its expiry as expired and refresh the affected
     * subjects, so a lapsed badge stops displaying.
     */
    public function expireStale(): int
    {
        $stale = Verification::where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($stale as $verification) {
            $verification->update(['status' => 'expired']);

            if ($subject = $verification->verifiable) {
                $this->syncSubject($subject);
            }
        }

        return $stale->count();
    }

    /**
     * Recompute a subject's denormalised verification state from its rows.
     */
    public function syncSubject(Model $subject): void
    {
        $subject->load('verifications');

        if ($subject instanceof EmployerProfile) {
            // An employer counts as verified once any of the employer-grade
            // checks is active — domain control, registry match, or owner ID.
            $verified = collect(Verification::EMPLOYER_TYPES)
                ->contains(fn (string $type) => $subject->hasActiveVerification($type));

            $subject->update([
                'is_verified' => $verified,
                'verified_at' => $verified ? ($subject->verified_at ?? now()) : null,
            ]);

            return;
        }

        if ($subject instanceof JobSeekerProfile) {
            $identity = $subject->hasActiveVerification('government_id');

            $subject->update([
                'is_identity_verified' => $identity,
                'identity_verified_at' => $identity ? ($subject->identity_verified_at ?? now()) : null,
                'verified_badges'      => $subject->activeBadges(),
            ]);
        }
    }

    /**
     * Get (or open) the single verification row for this subject and type.
     */
    private function record(Model $subject, string $type): Verification
    {
        return $subject->verifications()->firstOrCreate(
            ['type' => $type],
            ['status' => 'pending'],
        );
    }

    private function assertVerifiable(Model $subject): void
    {
        if (! $subject instanceof EmployerProfile && ! $subject instanceof JobSeekerProfile) {
            throw new InvalidArgumentException('Only employer and job seeker profiles can be verified.');
        }
    }
}
