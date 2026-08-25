<?php

namespace App\Services;

use App\Models\EmployerProfile;
use App\Models\Job;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class JobService
{
    /** Search modes offered in the UI, in plain language. */
    public const MODES = ['anywhere', 'near_me', 'nationwide', 'remote', 'international'];

    public const RADIUS_OPTIONS = [5, 10, 25, 50, 100];

    public function __construct(private LocationService $locations) {}

    /**
     * Job search with location as a first-class dimension alongside keyword,
     * work arrangement and employment type.
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Job::active()
            ->with([
                'employer:id,company_name,company_slug,logo,headquarters_city,headquarters_state,headquarters_country',
                'skills:id,name,slug',
            ]);

        $this->applyKeyword($query, $filters);
        $this->applyLocation($query, $filters);
        $this->applyAttributes($query, $filters);
        $this->applySorting($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    private function applyKeyword(Builder $query, array $filters): void
    {
        if (!empty($filters['q'])) {
            $query->search($filters['q']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
    }

    /**
     * Location, work arrangement and hiring eligibility are applied separately —
     * they answer "where is it", "how is it worked" and "who can apply".
     */
    private function applyLocation(Builder $query, array $filters): void
    {
        $mode = $filters['mode'] ?? null;
        $mode = in_array($mode, self::MODES, true) ? $mode : null;

        $place = $this->resolvePlace($filters);

        // Mode is a shortcut over the same underlying filters.
        if ($mode === 'remote') {
            $query->remote();
        } elseif ($mode === 'international') {
            $query->international();
        }

        $radius = $this->resolveRadius($filters, $mode);

        $miles = $radius ?? Job::DEFAULT_LOCAL_RADIUS_MILES;

        if ($mode === 'near_me' && $place['latitude'] !== null) {
            // Jobs you could physically get to, plus remote roles open to that area.
            $query->where(function (Builder $q) use ($place, $miles) {
                $q->where(fn (Builder $sub) => $sub->whereWithinRadius($place['latitude'], $place['longitude'], $miles))
                  ->orWhere(fn (Builder $sub) => $sub->remote()->where(function (Builder $inner) use ($place) {
                      $inner->where('location_country', $place['country'])
                            ->orWhereIn('hiring_scope', ['international', 'north_america']);
                  }));
            });
        } elseif ($radius && $place['latitude'] !== null) {
            $query->whereWithinRadius($place['latitude'], $place['longitude'], $miles);
        } else {
            if ($mode === 'nationwide' && $place['country']) {
                $query->where('location_country', $place['country']);
            } else {
                if (!empty($place['city'])) {
                    $query->where('location_city', $place['city']);
                }
                if (!empty($place['state'])) {
                    $query->where('location_state', $place['state']);
                }
                if (!empty($place['country']) && $mode !== 'international') {
                    $query->where(fn (Builder $q) => $q
                        ->where('location_country', $place['country'])
                        ->orWhere('hiring_scope', 'international'));
                }
            }
        }

        if (!empty($filters['work_arrangement']) || !empty($filters['location_type'])) {
            $arrangements = $this->toArray($filters['work_arrangement'] ?? $filters['location_type']);
            $query->workArrangement($arrangements);
        }

        // "Jobs I'm actually allowed to apply to from where I live."
        if (!empty($filters['candidate_country'])) {
            $query->openToCandidatesFrom(
                $this->locations->normalizeCountry($filters['candidate_country']) ?? $filters['candidate_country'],
                $filters['candidate_state'] ?? null,
            );
        }

        if (!empty($filters['hiring_scope'])) {
            $query->whereIn('hiring_scope', $this->toArray($filters['hiring_scope']));
        }
    }

    private function applyAttributes(Builder $query, array $filters): void
    {
        if (!empty($filters['experience_level'])) {
            $query->whereIn('experience_level', $this->toArray($filters['experience_level']));
        }
        if (!empty($filters['employment_type'])) {
            $query->whereIn('employment_type', $this->toArray($filters['employment_type']));
        }
        if (!empty($filters['salary_min'])) {
            $query->where('salary_max', '>=', (int) $filters['salary_min']);
        }
        if (!empty($filters['salary_max'])) {
            $query->where('salary_min', '<=', (int) $filters['salary_max']);
        }
        if (filter_var($filters['visa_sponsorship'] ?? null, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('visa_sponsorship', true);
        }
        if (!empty($filters['skills'])) {
            $skillIds = $this->toArray($filters['skills']);
            $query->whereHas('skills', fn ($q) => $q->whereIn('skills.id', $skillIds));
        }
    }

    private function applySorting(Builder $query, array $filters): void
    {
        match ($filters['sort'] ?? 'recent') {
            'salary' => $query->orderByDesc('salary_max'),
            default  => $query->orderByDesc('is_featured')->orderByDesc('created_at'),
        };
    }

    /**
     * Resolve whatever the user typed or picked into a concrete place.
     *
     * @return array{city: ?string, state: ?string, country: ?string, latitude: ?float, longitude: ?float}
     */
    public function resolvePlace(array $filters): array
    {
        $place = ['city' => null, 'state' => null, 'country' => null, 'latitude' => null, 'longitude' => null];

        if (!empty($filters['location'])) {
            $parsed = $this->locations->parse($filters['location']);
            $place  = array_intersect_key($parsed, $place);
        }

        foreach (['city', 'state', 'country'] as $key) {
            if (!empty($filters[$key])) {
                $place[$key] = $filters[$key];
            }
        }

        $place['country'] = $this->locations->normalizeCountry($place['country']) ?? $place['country'];
        $place['state']   = $place['state'] ? $this->locations->normalizeState($place['state'], $place['country']) : null;

        if (isset($filters['latitude'], $filters['longitude'])) {
            $place['latitude']  = (float) $filters['latitude'];
            $place['longitude'] = (float) $filters['longitude'];
        } elseif ($place['latitude'] === null && $place['city']) {
            $coords = $this->locations->coordinatesFor($place['city'], $place['state'], $place['country']);
            $place['latitude']  = $coords['latitude'] ?? null;
            $place['longitude'] = $coords['longitude'] ?? null;
            $place['country']   = $place['country'] ?: ($coords['country'] ?? null);
            $place['state']     = $place['state'] ?: ($coords['state'] ?? null);
        }

        return $place;
    }

    private function resolveRadius(array $filters, ?string $mode): ?int
    {
        if (!empty($filters['radius'])) {
            return max(1, min(500, (int) $filters['radius']));
        }

        return $mode === 'near_me' ? Job::DEFAULT_LOCAL_RADIUS_MILES : null;
    }

    /** @param mixed $value @return array<int, string> */
    private function toArray($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    public function createJob(EmployerProfile $employer, array $data): Job
    {
        return DB::transaction(function () use ($employer, $data) {
            $job = $employer->jobs()->create($data);

            if (!empty($data['skills'])) {
                $skillData = collect($data['skills'])->mapWithKeys(fn ($s) => [
                    $s['id'] => ['is_required' => $s['is_required'] ?? true],
                ]);
                $job->skills()->sync($skillData);
            }

            $employer->decrementCredits();

            return $job->load('skills');
        });
    }

    public function updateJob(Job $job, array $data): Job
    {
        return DB::transaction(function () use ($job, $data) {
            $job->update($data);

            if (array_key_exists('skills', $data)) {
                $skillData = collect($data['skills'])->mapWithKeys(fn ($s) => [
                    $s['id'] => ['is_required' => $s['is_required'] ?? true],
                ]);
                $job->skills()->sync($skillData);
            }

            return $job->fresh('skills');
        });
    }

    public function getJobWithDetails(Job $job): Job
    {
        $job->incrementViews();

        return $job->load([
            'employer:id,company_name,company_slug,logo,description,website,headquarters_city,headquarters_state,headquarters_country,founded_year,company_size',
            'skills:id,name,slug,category',
        ]);
    }
}
