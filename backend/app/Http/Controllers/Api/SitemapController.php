<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\JobDiscoveryService;
use Illuminate\Http\Response;

/**
 * Sitemap covering job pages and the location pages that have real content.
 * Empty locations are never emitted — no thin pages.
 */
class SitemapController extends Controller
{
    /** A location page must have at least this many live jobs to be indexed. */
    public const MIN_JOBS_FOR_INDEXING = 3;

    public function __construct(private JobDiscoveryService $discovery) {}

    public function index(): Response

    {
        $base = rtrim(env('FRONTEND_URL') ?: config('app.url'), '/');
        $urls = [
            ['loc' => "{$base}/", 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => "{$base}/jobs", 'priority' => '0.9', 'changefreq' => 'hourly'],
            ['loc' => "{$base}/for-employers", 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];

        foreach (['remote', 'local', 'national', 'international'] as $mode) {
            $urls[] = ['loc' => "{$base}/jobs/{$mode}", 'priority' => '0.8', 'changefreq' => 'daily'];
        }

        $locations = collect()
            ->merge($this->discovery->popularCities(200, self::MIN_JOBS_FOR_INDEXING))
            ->merge($this->discovery->popularStates(100, self::MIN_JOBS_FOR_INDEXING))
            ->merge($this->discovery->popularCountries(100, self::MIN_JOBS_FOR_INDEXING));

        foreach ($locations as $location) {
            if (!$location['slug']) {
                continue;
            }
            $urls[] = ['loc' => "{$base}/jobs/in/{$location['slug']}", 'priority' => '0.7', 'changefreq' => 'daily'];
        }

        Job::active()->select('slug', 'updated_at')->orderByDesc('updated_at')->chunk(500, function ($jobs) use (&$urls, $base) {
            foreach ($jobs as $job) {
                $urls[] = [
                    'loc'      => "{$base}/jobs/{$job->slug}",
                    'lastmod'  => $job->updated_at?->toAtomString(),
                    'priority' => '0.6',
                ];
            }
        });

        return response($this->render($urls), 200, ['Content-Type' => 'application/xml']);
    }

    /** @param array<int, array<string, string|null>> $urls */
    private function render(array $urls): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
             . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";

            if (!empty($url['lastmod'])) {
                $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            }
            if (!empty($url['changefreq'])) {
                $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            }
            if (!empty($url['priority'])) {
                $xml .= "    <priority>{$url['priority']}</priority>\n";
            }

            $xml .= "  </url>\n";
        }

        return $xml . "</urlset>\n";
    }
}
