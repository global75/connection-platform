<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id', 'job_seeker_profile_id', 'cover_letter', 'resume_snapshot',
        'expected_salary', 'currency', 'status', 'employer_notes', 'viewed_at',
    ];

    protected $casts = [
        'expected_salary' => 'integer',
        'viewed_at'       => 'datetime',
    ];

    public const STATUSES = [
        'submitted', 'viewed', 'shortlisted',
        'interview_scheduled', 'offer_extended',
        'hired', 'rejected', 'withdrawn',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function jobSeeker()
    {
        return $this->belongsTo(JobSeekerProfile::class, 'job_seeker_profile_id');
    }

    public function interviewSchedules()
    {
        return $this->hasMany(InterviewSchedule::class);
    }

    public function latestInterview()
    {
        return $this->hasOne(InterviewSchedule::class)->latestOfMany('scheduled_at');
    }

    public function qualification()
    {
        return $this->hasOne(LeadQualification::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function markViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update(['viewed_at' => now(), 'status' => 'viewed']);
        }
    }

    public function isWithdrawn(): bool { return $this->status === 'withdrawn'; }
    public function isHired(): bool     { return $this->status === 'hired'; }

    /**
     * Closed applications are never (re)qualified — the outcome is already known.
     */
    public function isClosed(): bool
    {
        return in_array($this->status, ['hired', 'rejected', 'withdrawn'], true);
    }
}
