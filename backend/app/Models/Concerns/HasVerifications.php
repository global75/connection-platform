<?php

namespace App\Models\Concerns;

use App\Models\Verification;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Shared behaviour for anything that can be verified (employer and job seeker
 * profiles today).
 */
trait HasVerifications
{
    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable');
    }

    /**
     * The single verification of a given type, whatever its state.
     */
    public function verification(string $type): ?Verification
    {
        return $this->verifications->firstWhere('type', $type)
            ?? $this->verifications()->ofType($type)->first();
    }

    /**
     * Approved and unexpired. This is what access decisions should ask.
     */
    public function hasActiveVerification(string $type): bool
    {
        return (bool) $this->verification($type)?->isActive();
    }

    /**
     * Badge slugs for every active verification, for display.
     *
     * @return list<string>
     */
    public function activeBadges(): array
    {
        return $this->verifications
            ->filter(fn (Verification $v) => $v->isActive())
            ->map(fn (Verification $v) => $v->badge())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Verification>
     */
    public function pendingVerifications(): Collection
    {
        return $this->verifications->filter(fn (Verification $v) => $v->isPending())->values();
    }
}
