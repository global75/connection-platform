<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\LocationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_name', 'company_slug', 'description', 'industry',
        'company_size', 'website', 'logo', 'headquarters_city', 'headquarters_state',
        'headquarters_country', 'headquarters_postal_code', 'latitude', 'longitude',
        'hiring_scopes', 'linkedin_url', 'twitter_url', 'founded_year',
        'is_verified', 'is_featured', 'subscription_tier', 'job_post_credits',
    ];

    protected $casts = [
        'is_verified'  => 'boolean',
        'is_featured'  => 'boolean',
        'founded_year' => 'integer',
        'job_post_credits' => 'integer',
        'latitude'     => 'float',
        'longitude'    => 'float',
        'hiring_scopes'=> 'array',
    ];

    /** Where this company hires. Seeds the defaults on the job posting form. */
    public const HIRING_SCOPES = ['local', 'national', 'remote', 'international'];

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

        static::saving(function (self $profile) {
            $profile->normalizeLocation();
        });
    }

    /** Normalises headquarters data and attaches real coordinates when known. */
    public function normalizeLocation(): void
    {
        $locations = app(LocationService::class);

        if (filled($this->headquarters_country)) {
            $this->headquarters_country = $locations->normalizeCountry($this->headquarters_country) ?? $this->headquarters_country;
        }
        if (filled($this->headquarters_state)) {
            $this->headquarters_state = $locations->normalizeState($this->headquarters_state, $this->headquarters_country);
        }

        $coords = $locations->coordinatesFor($this->headquarters_city, $this->headquarters_state, $this->headquarters_country);
        if ($coords) {
            $this->latitude  = $coords['latitude'];
            $this->longitude = $coords['longitude'];
            $this->headquarters_state ??= $coords['state'];
        } elseif (blank($this->headquarters_city)) {
            $this->latitude  = null;
            $this->longitude = null;
        }

        if (is_array($this->hiring_scopes)) {
            $this->hiring_scopes = array_values(array_intersect(self::HIRING_SCOPES, $this->hiring_scopes));
        }
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
