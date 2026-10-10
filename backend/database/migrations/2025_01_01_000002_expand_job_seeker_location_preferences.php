<?php

use App\Services\LocationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives professionals a real location plus explicit work preferences, instead
 * of a single "open_to_remote" boolean. The legacy flag is kept and stays in
 * sync so existing behaviour and data continue to work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seeker_profiles', function (Blueprint $table) {
            $table->string('current_state')->nullable()->after('current_city');
            $table->decimal('latitude', 10, 7)->nullable()->after('current_country');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            // How they want to work: subset of on_site / hybrid / remote.
            $table->json('work_arrangements')->nullable()->after('open_to_remote');
            // Where they will work: subset of near_me / national / international.
            $table->json('location_scopes')->nullable()->after('work_arrangements');
            $table->unsignedSmallInteger('max_commute_miles')->nullable()->after('location_scopes');
            $table->json('employment_types')->nullable()->after('max_commute_miles');

            $table->index(['latitude', 'longitude']);
        });

        $locations = new LocationService();

        DB::table('job_seeker_profiles')->orderBy('id')->chunkById(200, function ($profiles) use ($locations) {
            foreach ($profiles as $profile) {
                $country = $locations->normalizeCountry($profile->current_country) ?? $profile->current_country;
                $coords  = $locations->coordinatesFor($profile->current_city, null, $country);

                DB::table('job_seeker_profiles')->where('id', $profile->id)->update([
                    'current_country'   => $country,
                    'current_state'     => $coords['state'] ?? null,
                    'latitude'          => $coords['latitude'] ?? null,
                    'longitude'         => $coords['longitude'] ?? null,
                    // Preserve what the profile already said, and no more.
                    'work_arrangements' => json_encode($profile->open_to_remote
                        ? ['remote', 'hybrid', 'on_site']
                        : ['on_site', 'hybrid']),
                    'location_scopes'   => json_encode($profile->open_to_remote
                        ? ['near_me', 'national', 'international']
                        : ['near_me', 'national']),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_seeker_profiles', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn([
                'current_state', 'latitude', 'longitude', 'work_arrangements',
                'location_scopes', 'max_commute_miles', 'employment_types',
            ]);
        });
    }
};
