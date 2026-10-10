<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'verifiable_type', 'verifiable_id',
        'type', 'status', 'provider', 'external_id',
        'metadata', 'verified_at', 'expires_at',
        'reviewed_by', 'rejection_reason',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'verified_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public const TYPES = [
        'work_email_domain',
        'company_registry',
        'government_id',
        'github_oauth',
        'linkedin_oauth',
        'skill_badge',
    ];

    public const STATUSES = ['pending', 'processing', 'approved', 'rejected', 'expired'];

    /** Types that, once approved, make an employer "verified". */
    public const EMPLOYER_TYPES = ['work_email_domain', 'company_registry', 'government_id'];

    /** Types a job seeker can hold. */
    public const CANDIDATE_TYPES = ['government_id', 'github_oauth', 'linkedin_oauth', 'skill_badge'];

    // ── Relationships ──────────────────────────────────────────────

    public function verifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Approved and not past its expiry — the only state that should grant
     * anything. An approved-but-expired row must not read as verified.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'approved')
                     ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Approved *and* still in date. Guard every access decision with this
     * rather than reading `status` directly.
     */
    public function isActive(): bool
    {
        return $this->status === 'approved' && ! $this->isExpired();
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }

    /**
     * The badge slug this verification contributes when active.
     */
    public function badge(): string
    {
        return match ($this->type) {
            'work_email_domain' => 'domain_verified',
            'company_registry'  => 'registry_verified',
            'government_id'     => 'id_verified',
            'github_oauth'      => 'github_verified',
            'linkedin_oauth'    => 'linkedin_verified',
            'skill_badge'       => 'skill_verified',
        };
    }
}
