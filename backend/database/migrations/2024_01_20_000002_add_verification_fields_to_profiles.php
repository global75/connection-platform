<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalised "is this subject verified" state, kept alongside the profiles so
 * job listings and application cards can render a badge without joining
 * `verifications` on every row. The `verifications` table stays the source of
 * truth; these columns are maintained by VerificationService.
 *
 * Note employer_profiles.is_verified already exists (see the original profiles
 * migration) and is reused rather than duplicated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->string('business_registration_number')->nullable()->after('verified_at');
            $table->string('work_email_domain')->nullable()->after('business_registration_number');

            $table->index('work_email_domain');
        });

        Schema::table('job_seeker_profiles', function (Blueprint $table) {
            $table->boolean('is_identity_verified')->default(false)->after('profile_complete');
            $table->timestamp('identity_verified_at')->nullable()->after('is_identity_verified');
            // e.g. ["github_verified", "id_verified"] — display only; the
            // verifications table is authoritative.
            $table->json('verified_badges')->nullable()->after('identity_verified_at');

            $table->index('is_identity_verified');
        });
    }

    public function down(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table) {
            $table->dropIndex(['work_email_domain']);
            $table->dropColumn(['verified_at', 'business_registration_number', 'work_email_domain']);
        });

        Schema::table('job_seeker_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_identity_verified']);
            $table->dropColumn(['is_identity_verified', 'identity_verified_at', 'verified_badges']);
        });
    }
};
