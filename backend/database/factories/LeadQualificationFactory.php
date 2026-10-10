<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\LeadQualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadQualification>
 */
class LeadQualificationFactory extends Factory
{
    protected $model = LeadQualification::class;

    public function definition(): array
    {
        $score = fake()->numberBetween(0, 100);

        return [
            'job_application_id' => JobApplication::factory(),
            'status'             => 'completed',
            'score'              => $score,
            'tier'               => LeadQualification::tierForScore($score),
            'recommended_action' => 'review',
            'summary'            => 'Solid overlap with the posted requirements.',
            'strengths'          => ['Covers the required skills.'],
            'concerns'           => ['No recent Laravel work shown.'],
            'criteria'           => ['skills' => $score, 'experience' => $score],
            'provider'           => 'heuristic',
            'qualified_at'       => now(),
        ];
    }

    public function scored(int $score): static
    {
        return $this->state(fn () => [
            'score' => $score,
            'tier'  => LeadQualification::tierForScore($score),
        ]);
    }
}
