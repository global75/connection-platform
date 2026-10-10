<?php

namespace Database\Factories;

use App\Models\JobSeekerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobSeekerProfile>
 */
class JobSeekerProfileFactory extends Factory
{
    protected $model = JobSeekerProfile::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory()->jobSeeker(),
            'headline'            => 'Senior Backend Engineer',
            'bio'                 => fake()->paragraph(),
            'resume'              => 'resumes/example.pdf',
            'current_city'        => 'Lisbon',
            'current_country'     => 'PT',
            'nationality'         => 'PT',
            'open_to_remote'      => true,
            'willing_to_relocate' => false,
            'experience_level'    => 'senior',
            'years_of_experience' => 8,
            'desired_job_title'   => 'Backend Engineer',
            'desired_salary_min'  => 90000,
            'desired_salary_max'  => 120000,
            'currency'            => 'USD',
        ];
    }
}
