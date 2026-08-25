<?php

namespace App\Models;

use App\Services\LocationService;
use App\Support\Location\LocationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    /** How the work is performed. */
    public const WORK_ARRANGEMENTS = ['on_site', 'hybrid', 'remote'];

    /** Who is eligible to apply — independent of where the job is. */
    public const HIRING_SCOPES = [
        'local',              // people who can reach this city
        'state',              // people in this state / province
        'national',           // people anywhere in this country
        'north_america',      // US, Canada, Mexico
        'international',      // anyone, anywhere
        'specific_countries', // an explicit list (eligible_countries)
    ];

    public const DEFAULT_LOCAL_RADIUS_MILES = 50;

    protected $fillable = [
        'employer_profile_id', 'title', 'slug', 'description', 'requirements',
        'benefits', 'category', 'employment_type', 'work_arrangement',
        'location_city', 'location_state', 'location_country', 'location_postal_code',
        'latitude', 'longitude', 'hiring_scope', 'eligible_countries', 'local_radius_miles',
        'salary_min', 'salary_max', 'currency', 'salary_period', 'salary_visible',
        'experience_level', 'visa_sponsorship', 'open_to_international',
        'status', 'is_featured', 'expires_at',
    ];

    protected $casts = [
        'salary_min'           => 'integer',
        'salary_max'           => 'integer',
        'salary_visible'       => 'boolean',
        'visa_sponsorship'     => 'boolean',
        'open_to_international'=> 'boolean',
        'is_featured'          => 'boolean',
        'expires_at'           => 'date',
        'views_count'          => 'integer',
        'applications_count'   => 'integer',
        'latitude'             => 'float',
        'longitude'            => 'float',
        'eligible_countries'   => 'array',
        'local_radius_miles'   => 'integer',
    ];

    protected $appends = ['location_type', 'location_label', 'hiring_scope_label'];

    // ── Relationships ──────────────────────────────────────────────

    public function employer()
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_skills')
                    ->withPivot('is_required');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function savedBy()
    {
        return $this->hasMany(SavedJob::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // ── Backwards compatibility ────────────────────────────────────
    // `location_type` was the old name for the work arrangement. Existing API
    // clients keep working: they can still read and write it.

    public function getLocationTypeAttribute(): ?string
    {
        return $this->attributes['work_arrangement'] ?? null;
    }

    public function setLocationTypeAttribute($value): void
    {
        $this->attributes['work_arrangement'] = $value;
    }

    // ── Presentation helpers ───────────────────────────────────────

    /** "Denver, CO" for placed jobs, "Remote — United States" for remote ones. */
    public function getLocationLabelAttribute(): string
    {
        $locations = app(LocationService::class);

        if ($this->work_arrangement === 'remote') {
            return match ($this->hiring_scope) {
                'international'      => 'Remote — International',
                'north_america'      => 'Remote — North America',
                'specific_countries' => 'Remote — ' . $this->eligibleCountryNames()->implode(', '),
                'state'              => 'Remote — ' . ($locations->stateName($this->location_state, $this->location_country) ?? ''),
                'local'              => 'Remote — ' . $locations->labelFor($this->location_city, $this->location_state, $this->location_country),
                default              => 'Remote — ' . ($locations->countryName($this->location_country) ?? 'Nationwide'),
            };
        }

        $label = $locations->labelFor($this->location_city, $this->location_state, $this->location_country);

        if ($label === 'Anywhere') {
            return $locations->countryName($this->location_country) ?? 'Location not specified';
        }

        return $label;
    }

    public function getHiringScopeLabelAttribute(): string
    {
        $locations = app(LocationService::class);

        return match ($this->hiring_scope) {
            'local'              => 'Local candidates',
            'state'              => ($locations->stateName($this->location_state, $this->location_country) ?? 'State') . ' candidates',
            'north_america'      => 'North America',
            'international'      => 'International',
            'specific_countries' => $this->eligibleCountryNames()->implode(', '),
            default              => ($locations->countryName($this->location_country) ?? 'National') . ' candidates',
        };
    }

    public function eligibleCountryNames(): \Illuminate\Support\Collection
    {
        return collect($this->eligible_countries ?? [])
            ->map(fn ($code) => LocationCatalog::COUNTRIES[$code] ?? $code);
    }

    public function isRemote(): bool
    {
        return $this->work_arrangement === 'remote';
    }

    /** The countries a candidate may apply from, or null when unrestricted. */
    public function eligibleCountryCodes(): ?array
    {
        return match ($this->hiring_scope) {
            'international'      => null,
            'north_america'      => LocationCatalog::NORTH_AMERICA,
            'specific_countries' => $this->eligible_countries ?: [$this->location_country],
            default              => [$this->location_country],
        };
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn ($q) => $q
            ->where('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
        );
    }

    public function scopeForExperienceLevel($query, string $level)
    {
        return $query->where('experience_level', $level);
    }

    /** @param string|array<int, string> $arrangement */
    public function scopeWorkArrangement($query, $arrangement)
    {
        return $query->whereIn('work_arrangement', (array) $arrangement);
    }

    public function scopeRemote($query)
    {
        return $query->where('work_arrangement', 'remote');
    }

    /** Jobs anyone abroad can take: remote and open beyond a single country. */
    public function scopeInternational($query)
    {
        return $query->whereIn('hiring_scope', ['international', 'north_america', 'specific_countries']);
    }

    /**
     * Jobs physically placed in a city / state / country. Remote jobs are
     * included when their hiring scope still covers that place.
     */
    public function scopeInPlace($query, ?string $city = null, ?string $state = null, ?string $country = null)
    {
        return $query->where(function (Builder $q) use ($city, $state, $country) {
            if ($city) {
                $q->where('location_city', $city);
            }
            if ($state) {
                $q->where('location_state', $state);
            }
            if ($country) {
                $q->where('location_country', $country);
            }
        });
    }

    /**
     * Jobs whose location falls within $miles of a point. Only jobs that have
     * real coordinates can match — a job without coordinates is never guessed
     * into a radius result.
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, float $miles)
    {
        return $query->where(fn (Builder $q) => $q->whereWithinRadius($latitude, $longitude, $miles))
                     ->selectRaw('jobs.*, ' . self::haversineSql() . ' as distance_miles', [$latitude, $longitude, $latitude]);
    }

    /**
     * Radius condition on its own, so it can be combined with OR branches
     * (e.g. "near me OR remote and open to my country").
     */
    public function scopeWhereWithinRadius($query, float $latitude, float $longitude, float $miles)
    {
        // The radius is inlined as a float literal on purpose: bound values
        // arrive as strings on SQLite, and comparing a number to a string there
        // is always true. $miles is cast to float, so this cannot be injected.
        $limit = sprintf('%.4F', $miles);

        return $query->whereNotNull('latitude')
                     ->whereNotNull('longitude')
                     ->whereRaw(self::haversineSql() . " <= {$limit}", [$latitude, $longitude, $latitude]);
    }

    /** Great-circle distance in miles, as SQL. Placeholders: lat, lng, lat. */
    public static function haversineSql(): string
    {
        $radius = LocationService::EARTH_RADIUS_MILES;

        return "({$radius} * acos(max(-1, min(1,
            cos(radians(?)) * cos(radians(jobs.latitude)) * cos(radians(jobs.longitude) - radians(?))
            + sin(radians(?)) * sin(radians(jobs.latitude))
        ))))";
    }

    /**
     * Jobs a candidate in $country (optionally $state) is allowed to apply to.
     * This is hiring eligibility, not location: a Denver on-site job and a
     * US-only remote job both exclude a candidate in Egypt.
     */
    public function scopeOpenToCandidatesFrom($query, ?string $country, ?string $state = null)
    {
        if (blank($country)) {
            return $query;
        }

        $inNorthAmerica = in_array($country, LocationCatalog::NORTH_AMERICA, true);

        return $query->where(function (Builder $q) use ($country, $state, $inNorthAmerica) {
            $q->where('hiring_scope', 'international');

            if ($inNorthAmerica) {
                $q->orWhere('hiring_scope', 'north_america');
            }

            $q->orWhere(fn (Builder $sub) => $sub
                ->whereIn('hiring_scope', ['national', 'local', 'state'])
                ->where('location_country', $country)
                ->when($state, fn (Builder $s) => $s->where(fn (Builder $inner) => $inner
                    ->where('hiring_scope', 'national')
                    ->orWhere('location_state', $state)
                ))
            );

            $q->orWhere(fn (Builder $sub) => $sub
                ->where('hiring_scope', 'specific_countries')
                ->whereJsonContains('eligible_countries', $country)
            );
        });
    }

    // ── Lifecycle ─────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $job) {
            if (empty($job->slug)) {
                $job->slug = Str::slug($job->title) . '-' . Str::random(6);
            }
        });

        static::saving(function (self $job) {
            $job->normalizeLocation();
        });
    }

    /**
     * Keeps location fields normalised, attaches real coordinates when the city
     * is known, and keeps the legacy open_to_international flag consistent with
     * the hiring scope so older code paths stay correct.
     */
    public function normalizeLocation(): void
    {
        $locations = app(LocationService::class);

        if (filled($this->location_country)) {
            $this->location_country = $locations->normalizeCountry($this->location_country) ?? $this->location_country;
        }
        if (filled($this->location_state)) {
            $this->location_state = $locations->normalizeState($this->location_state, $this->location_country);
        }

        if (!in_array($this->hiring_scope, self::HIRING_SCOPES, true)) {
            $this->hiring_scope = 'national';
        }

        // A fully remote job open beyond one area does not belong to a city, so
        // a city left over from an earlier edit is dropped rather than kept —
        // otherwise the role would keep surfacing in that city's local search.
        if ($this->isRemote() && in_array($this->hiring_scope, ['international', 'north_america', 'specific_countries', 'national'], true)) {
            $this->location_city        = null;
            $this->location_state       = null;
            $this->location_postal_code = null;
        }

        $coords = $locations->coordinatesFor($this->location_city, $this->location_state, $this->location_country);
        if ($coords) {
            $this->latitude  = $coords['latitude'];
            $this->longitude = $coords['longitude'];
            $this->location_state ??= $coords['state'];
        } elseif (blank($this->location_city)) {
            $this->latitude  = null;
            $this->longitude = null;
        }

        if ($this->hiring_scope === 'local' && !$this->local_radius_miles) {
            $this->local_radius_miles = self::DEFAULT_LOCAL_RADIUS_MILES;
        }

        if ($this->hiring_scope === 'specific_countries') {
            $this->eligible_countries = collect($this->eligible_countries ?? [])
                ->map(fn ($c) => $locations->normalizeCountry($c) ?? $c)
                ->filter()
                ->unique()
                ->values()
                ->all();
        } else {
            $this->eligible_countries = null;
        }

        $this->open_to_international = match ($this->hiring_scope) {
            'international', 'north_america' => true,
            'specific_countries' => collect($this->eligible_countries ?? [])
                ->reject(fn ($c) => $c === $this->location_country)->isNotEmpty(),
            default => false,
        };
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
