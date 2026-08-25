<?php

namespace App\Services;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobSeekerProfile;
use Illuminate\Support\Collection;

/**
 * Aggregates what the marketplace actually contains: real locations, real
 * categories, real counts. Nothing here is hardcoded or padded — every number
 * comes from the database, so the marketing surfaces can never overstate it.
 */
class JobDiscoveryService
{
    /** A location page needs at least this many live jobs to be worth linking. */
    public const MIN_JOBS_FOR_LOCATION_PAGE = 1;

    public function __construct(private LocationService $locations) {}

    /**
     * Cities that actually have live jobs, most jobs first.
     *
     * @return Collection<int, array{slug: string, label: string, city: ?string, state: ?string, country: string, jobs_count: int}>
     */
    public function popularCities(int $limit = 12, int $minJobs = self::MIN_JOBS_FOR_LOCATION_PAGE): Collection
    {
        return Job::active()
            ->whereNotNull('location_city')
            ->selectRaw('location_city, location_state, location_country, count(*) as jobs_count')
            ->groupBy('location_city', 'location_state', 'location_country')
            ->havingRaw('count(*) >= ?', [$minJobs])
            ->orderByDesc('jobs_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'slug'       => $this->locations->slugFor($row->location_city, $row->location_state, $row->location_country),
                'label'      => $this->locations->labelFor($row->location_city, $row->location_state, $row->location_country),
                'city'       => $row->location_city,
                'state'      => $row->location_state,
                'country'    => $row->location_country,
                'jobs_count' => (int) $row->jobs_count,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function popularStates(int $limit = 12, int $minJobs = self::MIN_JOBS_FOR_LOCATION_PAGE): Collection
    {
        return Job::active()
            ->whereNotNull('location_state')
            ->selectRaw('location_state, location_country, count(*) as jobs_count')
            ->groupBy('location_state', 'location_country')
            ->havingRaw('count(*) >= ?', [$minJobs])
            ->orderByDesc('jobs_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'slug'       => $this->locations->slugFor(null, $row->location_state, $row->location_country),
                'label'      => $this->locations->stateName($row->location_state, $row->location_country),
                'state'      => $row->location_state,
                'country'    => $row->location_country,
                'jobs_count' => (int) $row->jobs_count,
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function popularCountries(int $limit = 12, int $minJobs = self::MIN_JOBS_FOR_LOCATION_PAGE): Collection
    {
        return Job::active()
            ->whereNotNull('location_country')
            ->selectRaw('location_country, count(*) as jobs_count')
            ->groupBy('location_country')
            ->havingRaw('count(*) >= ?', [$minJobs])
            ->orderByDesc('jobs_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'slug'       => $this->locations->slugFor(null, null, $row->location_country),
                'label'      => $this->locations->countryName($row->location_country),
                'country'    => $row->location_country,
                'jobs_count' => (int) $row->jobs_count,
            ]);
    }

    /** @return Collection<int, array{name: string, jobs_count: int}> */
    public function categories(int $limit = 16): Collection
    {
        return Job::active()
            ->selectRaw('category, count(*) as jobs_count')
            ->groupBy('category')
            ->orderByDesc('jobs_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->category, 'jobs_count' => (int) $row->jobs_count]);
    }

    /** Counts per work arrangement, for the "how do you want to work" facets. */
    public function workArrangementCounts(): array
    {
        return Job::active()
            ->selectRaw('work_arrangement, count(*) as jobs_count')
            ->groupBy('work_arrangement')
            ->pluck('jobs_count', 'work_arrangement')
            ->map(fn ($c) => (int) $c)
            ->all();
    }

    /** Real marketplace totals. Used verbatim on the homepage — no rounding up. */
    public function stats(): array
    {
        return [
            'active_jobs'         => Job::active()->count(),
            'hiring_companies'    => Job::active()->distinct('employer_profile_id')->count('employer_profile_id'),
            'companies'           => EmployerProfile::count(),
            'professionals'       => JobSeekerProfile::count(),
            'countries_with_jobs' => Job::active()->whereNotNull('location_country')->distinct()->count('location_country'),
            'remote_jobs'         => Job::active()->remote()->count(),
            'international_jobs'  => Job::active()->international()->count(),
            'local_jobs'          => Job::active()->workArrangement(['on_site', 'hybrid'])->count(),
        ];
    }
}
