<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobSeekerProfile;
use App\Services\JobDiscoveryService;
use App\Services\JobService;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public discovery endpoints: what locations, categories and filters actually
 * exist in this marketplace right now.
 */
class DiscoveryController extends Controller
{
    public function __construct(
        private JobDiscoveryService $discovery,
        private LocationService $locations,
    ) {}

    /** Location pages worth showing, built from live job data only. */
    public function locations(Request $request): JsonResponse
    {
        $limit = min(50, max(1, (int) $request->input('limit', 12)));

        return response()->json([
            'cities'    => $this->discovery->popularCities($limit),
            'states'    => $this->discovery->popularStates($limit),
            'countries' => $this->discovery->popularCountries($limit),
        ]);
    }

    /** Resolve a location page slug ("denver-co", "colorado", "canada"). */
    public function location(string $slug): JsonResponse
    {
        $place = $this->locations->fromSlug($slug);

        if (!$place) {
            return response()->json(['message' => 'Unknown location.'], 404);
        }

        $count = Job::active()
            ->inPlace($place['city'], $place['state'], $place['country'])
            ->count();

        return response()->json([
            'location' => array_merge($place, [
                'slug'       => $slug,
                'label'      => $this->locations->labelFor($place['city'], $place['state'], $place['country']),
                'jobs_count' => $count,
            ]),
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(['categories' => $this->discovery->categories()]);
    }

    /** Real totals for the homepage. Zero is reported as zero. */
    public function stats(): JsonResponse
    {
        return response()->json(['stats' => $this->discovery->stats()]);
    }

    /** Everything the search UI needs to render its controls. */
    public function filters(Request $request): JsonResponse
    {
        return response()->json([
            'modes'              => JobService::MODES,
            'radius_options'     => JobService::RADIUS_OPTIONS,
            'work_arrangements'  => Job::WORK_ARRANGEMENTS,
            'hiring_scopes'      => Job::HIRING_SCOPES,
            'employment_types'   => ['full_time', 'part_time', 'contract', 'freelance', 'internship'],
            'experience_levels'  => ['entry', 'mid', 'senior', 'lead', 'executive'],
            'seeker_scopes'      => JobSeekerProfile::LOCATION_SCOPES,
            'countries'          => $this->locations->countries(),
            'states'             => $this->locations->statesFor($request->input('country', 'US')),
            'arrangement_counts' => $this->discovery->workArrangementCounts(),
        ]);
    }

    /** Type-ahead for the location box. Only returns places we can locate. */
    public function locationSuggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));

        if (strlen($term) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = collect(\App\Support\Location\LocationCatalog::CITIES)
            ->filter(fn ($c) => str_starts_with(strtolower($c[0]), strtolower($term)))
            ->take(8)
            ->map(fn ($c) => [
                'label'     => $this->locations->labelFor($c[0], $c[1], $c[2]),
                'city'      => $c[0],
                'state'     => $c[1],
                'country'   => $c[2],
                'latitude'  => $c[3],
                'longitude' => $c[4],
            ])
            ->values();

        return response()->json(['suggestions' => $suggestions]);
    }
}
