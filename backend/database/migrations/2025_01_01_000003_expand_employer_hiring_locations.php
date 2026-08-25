<?php

use App\Services\LocationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Employers get precise headquarters data plus the regions they hire in, which
 * seeds sensible defaults when they post a job.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->string('headquarters_postal_code', 20)->nullable()->after('headquarters_country');
            $table->decimal('latitude', 10, 7)->nullable()->after('headquarters_postal_code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            // Subset of local / national / remote / international.
            $table->json('hiring_scopes')->nullable()->after('longitude');
        });

        $locations = new LocationService();

        DB::table('employer_profiles')->orderBy('id')->chunkById(200, function ($employers) use ($locations) {
            foreach ($employers as $employer) {
                $country = $locations->normalizeCountry($employer->headquarters_country) ?? $employer->headquarters_country;
                $state   = $locations->normalizeState($employer->headquarters_state, $country);
                $coords  = $locations->coordinatesFor($employer->headquarters_city, $state, $country);

                DB::table('employer_profiles')->where('id', $employer->id)->update([
                    'headquarters_country' => $country,
                    'headquarters_state'   => $state,
                    'latitude'             => $coords['latitude'] ?? null,
                    'longitude'            => $coords['longitude'] ?? null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'headquarters_postal_code', 'latitude', 'longitude', 'hiring_scopes',
            ]);
        });
    }
};
