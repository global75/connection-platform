<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobSeekerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        return [
            'job_id'                => Job::factory(),
            'job_seeker_profile_id' => JobSeekerProfile::factory(),
            'cover_letter'          => fake()->paragraphs(2, true),
            'resume_snapshot'       => 'applications/example.pdf',
            'expected_salary'       => 110000,
            'currency'              => 'USD',
            'status'                => 'submitted',
        ];
    }
}
