<?php

use App\Services\LocationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the job's single "location_type" idea into the three concepts the
 * marketplace actually needs:
 *
 *   location          — where the job is (city / state / country / coordinates)
 *   work_arrangement  — how the work happens (on_site / hybrid / remote)
 *   hiring eligibility— who may apply (hiring_scope + eligible_countries)
 *
 * Existing data is preserved: location_type is renamed (values untouched) and
 * hiring_scope is derived from the legacy open_to_international flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->renameColumn('location_type', 'work_arrangement');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->string('location_postal_code', 20)->nullable()->after('location_country');
            $table->decimal('latitude', 10, 7)->nullable()->after('location_postal_code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            // Who is eligible to apply. Independent of where the job is and of
            // how the work is performed.
            $table->string('hiring_scope', 32)->default('national')->after('longitude');
            $table->json('eligible_countries')->nullable()->after('hiring_scope');
            $table->unsignedSmallInteger('local_radius_miles')->nullable()->after('eligible_countries');

            $table->index(['work_arrangement', 'hiring_scope']);
            $table->index(['location_country', 'location_state']);
            $table->index(['latitude', 'longitude']);
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        $locations = new LocationService();

        DB::table('jobs')->orderBy('id')->chunkById(200, function ($jobs) use ($locations) {
            foreach ($jobs as $job) {
                $country = $locations->normalizeCountry($job->location_country) ?? $job->location_country;
                $state   = $locations->normalizeState($job->location_state, $country);
                $coords  = $locations->coordinatesFor($job->location_city, $state, $country);

                DB::table('jobs')->where('id', $job->id)->update([
                    'location_country'   => $country,
                    'location_state'     => $state,
                    'latitude'           => $coords['latitude'] ?? null,
                    'longitude'          => $coords['longitude'] ?? null,
                    // Existing jobs keep exactly the reach they already advertised.
                    'hiring_scope'       => $job->open_to_international ? 'international' : 'national',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['work_arrangement', 'hiring_scope']);
            $table->dropIndex(['location_country', 'location_state']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn([
                'location_postal_code', 'latitude', 'longitude',
                'hiring_scope', 'eligible_countries', 'local_radius_miles',
            ]);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->renameColumn('work_arrangement', 'location_type');
        });
    }
};
