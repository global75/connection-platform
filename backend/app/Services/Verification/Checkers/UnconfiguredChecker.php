<?php

namespace App\Services\Verification\Checkers;

use App\Services\Verification\CheckResult;
use App\Services\Verification\Contracts\VerificationChecker;
use App\Services\Verification\VerificationUnavailable;
use Illuminate\Database\Eloquent\Model;

/**
 * Stands in for a verification type whose vendor is not wired up on this
 * install — company registry lookups and government-ID checks, which need an
 * OpenCorporates / Stripe Identity account.
 *
 * It reports itself unavailable and refuses to run, so the API answers 503
 * instead of recording a rejection the applicant cannot act on. Swap a real
 * checker in via VerificationService::extend() (or bind one in the provider)
 * once credentials exist.
 */
class UnconfiguredChecker implements VerificationChecker
{
    public function __construct(
        private string $type,
        private string $provider,
    ) {}

    public function type(): string
    {
        return $this->type;
    }

    public function available(): bool
    {
        return false;
    }

    public function check(Model $subject, array $input = []): CheckResult
    {
        throw new VerificationUnavailable(
            "The {$this->type} check is not configured on this deployment (expected provider: {$this->provider})."
        );
    }
}
