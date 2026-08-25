<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\LocationService;
use Illuminate\Database\Eloquent\Model;

class JobSeekerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'headline', 'bio', 'resume', 'portfolio_url',
        'linkedin_url', 'github_url', 'current_city', 'current_state', 'current_country',
        'latitude', 'longitude', 'nationality', 'open_to_remote', 'willing_to_relocate',
        'work_arrangements', 'location_scopes', 'max_commute_miles', 'employment_types',
        'experience_level', 'years_of_experience', 'current_job_title',
        'desired_job_title', 'desired_salary_min', 'desired_salary_max',
        'currency', 'availability', 'profile_complete', 'is_featured',
    ];

    protected $casts = [
        'open_to_remote'      => 'boolean',
        'willing_to_relocate' => 'boolean',
        'profile_complete'    => 'boolean',
        'is_featured'         => 'boolean',
        'years_of_experience' => 'integer',
        'desired_salary_min'  => 'integer',
        'desired_salary_max'  => 'integer',
        'latitude'            => 'float',
        'longitude'           => 'float',
        'work_arrangements'   => 'array',
        'location_scopes'     => 'array',
        'employment_types'    => 'array',
        'max_commute_miles'   => 'integer',
    ];

    /** How the professional is willing to work. */
    public const WORK_ARRANGEMENTS = ['on_site', 'hybrid', 'remote'];

    /** How far from home they are willing to look. */
    public const LOCATION_SCOPES = ['near_me', 'national', 'international'];

    public const DEFAULT_COMMUTE_MILES = 25;

    // ── Relationships ──────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_seeker_skills')
                    ->withPivot('proficiency');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function savedJobs()
    {
        return $this->hasMany(SavedJob::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // ── Lifecycle ─────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $profile) {
            $profile->normalizeLocation();
        });
    }

    /**
     * Normalises the professional's location, attaches real coordinates when the
     * city is known, and keeps the legacy open_to_remote flag in sync with the
     * richer work_arrangements preference.
     */
    public function normalizeLocation(): void
    {
        $locations = app(LocationService::class);

        if (filled($this->current_country)) {
            $this->current_country = $locations->normalizeCountry($this->current_country) ?? $this->current_country;
        }
        if (filled($this->current_state)) {
            $this->current_state = $locations->normalizeState($this->current_state, $this->current_country);
        }

        $coords = $locations->coordinatesFor($this->current_city, $this->current_state, $this->current_country);
        if ($coords) {
            $this->latitude      = $coords['latitude'];
            $this->longitude     = $coords['longitude'];
            $this->current_state ??= $coords['state'];
        } elseif (blank($this->current_city)) {
            $this->latitude  = null;
            $this->longitude = null;
        }

        if (is_array($this->work_arrangements)) {
            $this->work_arrangements = array_values(array_intersect(self::WORK_ARRANGEMENTS, $this->work_arrangements));
            $this->open_to_remote    = in_array('remote', $this->work_arrangements, true);
        }

        if (is_array($this->location_scopes)) {
            $this->location_scopes = array_values(array_intersect(self::LOCATION_SCOPES, $this->location_scopes));
        }
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function wantsArrangement(string $arrangement): bool
    {
        $wanted = $this->work_arrangements ?: ($this->open_to_remote ? self::WORK_ARRANGEMENTS : ['on_site', 'hybrid']);

        return in_array($arrangement, $wanted, true);
    }

    public function wantsScope(string $scope): bool
    {
        return in_array($scope, $this->location_scopes ?: self::LOCATION_SCOPES, true);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function completionPercentage(): int
    {
        $fields = [
            'headline', 'bio', 'resume', 'current_city',
            'experience_level', 'desired_job_title', 'desired_salary_min',
        ];

        $filled = collect($fields)->filter(fn ($f) => !empty($this->$f))->count();

        $hasSkills = $this->skills()->exists() ? 1 : 0;

        return (int) round(($filled + $hasSkills) / (count($fields) + 1) * 100);
    }
}
