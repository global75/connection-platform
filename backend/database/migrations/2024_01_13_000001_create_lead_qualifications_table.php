<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->unique()->constrained()->cascadeOnDelete();

            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');

            $table->unsignedTinyInteger('score')->nullable();          // 0–100
            $table->enum('tier', ['hot', 'warm', 'cold'])->nullable();
            $table->enum('recommended_action', ['shortlist', 'review', 'reject'])->nullable();

            $table->text('summary')->nullable();
            $table->json('strengths')->nullable();                     // ["…", "…"]
            $table->json('concerns')->nullable();                      // ["…", "…"]
            $table->json('criteria')->nullable();                      // {"skills": 0-100, "experience": …}

            $table->string('provider')->nullable();                    // claude | heuristic
            $table->string('model')->nullable();                       // e.g. claude-opus-5
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();

            $table->index(['tier', 'score']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_qualifications');
    }
};
