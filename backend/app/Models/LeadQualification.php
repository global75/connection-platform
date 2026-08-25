<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AI-generated qualification of an application ("lead") against the job it targets.
 *
 * One row per application — re-running qualification overwrites it in place.
 */
class LeadQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_application_id', 'status', 'score', 'tier', 'recommended_action',
        'summary', 'strengths', 'concerns', 'criteria',
        'provider', 'model', 'attempts', 'error', 'qualified_at',
    ];

    protected $casts = [
        'score'        => 'integer',
        'attempts'     => 'integer',
        'strengths'    => 'array',
        'concerns'     => 'array',
        'criteria'     => 'array',
        'qualified_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'processing', 'completed', 'failed'];
    public const TIERS    = ['hot', 'warm', 'cold'];
    public const ACTIONS  = ['shortlist', 'review', 'reject'];

    // ── Relationships ──────────────────────────────────────────────

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Map a 0–100 score onto a tier using the configured thresholds.
     */
    public static function tierForScore(int $score): string
    {
        $thresholds = config('ai.lead_qualification.tiers');

        return match (true) {
            $score >= (int) $thresholds['hot']  => 'hot',
            $score >= (int) $thresholds['warm'] => 'warm',
            default                             => 'cold',
        };
    }

    public function isHot(): bool       { return $this->tier === 'hot'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
}
