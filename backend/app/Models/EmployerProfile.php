<?php

namespace App\Models;

use App\Models\Concerns\HasVerifications;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployerProfile extends Model
{
    use HasFactory, HasVerifications;

    protected $fillable = [
        'user_id', 'company_name', 'company_slug', 'description', 'industry',
        'company_size', 'website', 'logo', 'headquarters_city', 'headquarters_state',
        'headquarters_country', 'linkedin_url', 'twitter_url', 'founded_year',
        'is_verified', 'is_featured', 'subscription_tier', 'job_post_credits',
        'verified_at', 'business_registration_number', 'work_email_domain',
    ];

    protected $casts = [
        'is_verified'  => 'boolean',
        'is_featured'  => 'boolean',
        'verified_at'  => 'datetime',
        'founded_year' => 'integer',
        'job_post_credits' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function activeJobs()
    {
        return $this->hasMany(Job::class)->where('status', 'active');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function subscription()
    {
        return $this->hasOne(EmployerSubscription::class)->latestOfMany();
    }

    // ── Lifecycle ─────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $profile) {
            if (empty($profile->company_slug)) {
                $profile->company_slug = Str::slug($profile->company_name);
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function hasCredits(): bool
    {
        return $this->subscription_tier !== 'free' || $this->job_post_credits > 0;
    }

    public function decrementCredits(): void
    {
        if ($this->subscription_tier === 'free') {
            $this->decrement('job_post_credits');
        }
    }
}
