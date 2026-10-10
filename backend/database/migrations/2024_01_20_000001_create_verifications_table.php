<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per verification attempt, against any verifiable subject
 * (EmployerProfile or JobSeekerProfile today).
 *
 * Kept off the profile tables so a subject can hold several verifications of
 * different types at once, and so the audit trail survives a re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifiable');

            $table->enum('type', [
                'work_email_domain',
                'company_registry',
                'government_id',
                'github_oauth',
                'linkedin_oauth',
                'skill_badge',
            ]);
            $table->enum('status', ['pending', 'processing', 'approved', 'rejected', 'expired'])
                  ->default('pending');

            $table->string('provider')->nullable();      // dns, opencorporates, stripe_identity, github, manual
            $table->string('external_id')->nullable();   // vendor-side id, for reconciliation

            // Evidence and diagnostics. Never store raw documents or ID numbers
            // here — vendors hold those; we keep references and outcomes.
            $table->json('metadata')->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // One live verification per subject per type; history is preserved
            // by re-using the row rather than accumulating duplicates.
            $table->unique(['verifiable_type', 'verifiable_id', 'type'], 'verifications_subject_type_unique');
            $table->index(['type', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
