<?php

namespace App\Services\Verification;

use Illuminate\Support\Carbon;

/**
 * The outcome of running one checker. Deliberately inert: it describes what was
 * found and never touches the database — VerificationService persists it.
 */
readonly class CheckResult
{
    private function __construct(
        public string $status,
        public string $provider,
        public array $metadata = [],
        public ?string $externalId = null,
        public ?Carbon $expiresAt = null,
        public ?string $rejectionReason = null,
    ) {}

    public static function approved(
        string $provider,
        array $metadata = [],
        ?string $externalId = null,
        ?Carbon $expiresAt = null,
    ): self {
        return new self('approved', $provider, $metadata, $externalId, $expiresAt);
    }

    public static function rejected(string $provider, string $reason, array $metadata = []): self
    {
        return new self('rejected', $provider, $metadata, rejectionReason: $reason);
    }

    /**
     * The check started but cannot conclude yet — an employer has to publish a
     * DNS record, or a vendor is still processing a document.
     */
    public static function pending(string $provider, array $metadata = [], ?string $externalId = null): self
    {
        return new self('pending', $provider, $metadata, $externalId);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function toAttributes(): array
    {
        return [
            'status'           => $this->status,
            'provider'         => $this->provider,
            'external_id'      => $this->externalId,
            'metadata'         => $this->metadata,
            'verified_at'      => $this->isApproved() ? now() : null,
            'expires_at'       => $this->expiresAt,
            'rejection_reason' => $this->rejectionReason,
        ];
    }
}
