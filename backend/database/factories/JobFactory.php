<?php

namespace Database\Factories;

use App\Models\EmployerProfile;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'employer_profile_id'   => EmployerProfile::factory(),
            'title'                 => 'Senior Laravel Developer',
            'description'           => fake()->paragraphs(3, true),
            'requirements'          => '5+ years of PHP, strong Laravel background.',
            'category'              => 'Engineering',
            'employment_type'       => 'full_time',
            'location_type'         => 'remote',
            'location_country'      => 'US',
            'salary_min'            => 100000,
            'salary_max'            => 140000,
            'currency'              => 'USD',
            'experience_level'      => 'senior',
            'visa_sponsorship'      => false,
            'open_to_international' => true,
            'status'                => 'active',
        ];
    }
}
