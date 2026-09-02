<?php

namespace Database\Seeders;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobSeekerProfile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name'              => 'Platform Admin',
            'email'             => 'admin@remotearena.io',
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);

        // Seed skills
        $skills = [
            ['name' => 'PHP',          'category' => 'Programming'],
            ['name' => 'Laravel',      'category' => 'Programming'],
            ['name' => 'JavaScript',   'category' => 'Programming'],
            ['name' => 'Vue.js',       'category' => 'Frontend'],
            ['name' => 'React',        'category' => 'Frontend'],
            ['name' => 'Node.js',      'category' => 'Backend'],
            ['name' => 'Python',       'category' => 'Programming'],
            ['name' => 'MySQL',        'category' => 'Database'],
            ['name' => 'PostgreSQL',   'category' => 'Database'],
            ['name' => 'AWS',          'category' => 'Cloud'],
            ['name' => 'Docker',       'category' => 'DevOps'],
            ['name' => 'TypeScript',   'category' => 'Programming'],
            ['name' => 'Tailwind CSS', 'category' => 'Frontend'],
            ['name' => 'GraphQL',      'category' => 'API'],
            ['name' => 'Redis',        'category' => 'Database'],
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }

        // Demo employer
        $employer = User::create([
            'name'              => 'TechCorp Recruiter',
            'email'             => 'employer@demo.com',
            'password'          => Hash::make('password'),
            'role'              => 'employer',
            'email_verified_at' => now(),
        ]);

        $profile = EmployerProfile::create([
            'user_id'          => $employer->id,
            'company_name'     => 'TechCorp Inc',
            'description'      => 'We build great software products.',
            'industry'         => 'Software',
            'company_size'     => '51-200',
            'website'          => 'https://techcorp.example.com',
            'headquarters_city'   => 'San Francisco',
            'headquarters_state'  => 'CA',
            'is_verified'      => true,
            'subscription_tier'=> 'pro',
        ]);

        // Demo job
        $job = $profile->jobs()->create([
            'title'                 => 'Senior Laravel Developer (Remote)',
            'description'           => 'We are looking for an experienced Laravel developer to join our distributed team. You will build APIs and maintain our core platform.',
            'requirements'          => "5+ years PHP experience\nLaravel proficiency\nMySQL / PostgreSQL\nREST API design",
            'benefits'              => "Competitive salary\nFlexible hours\nEquipment budget\nAnnual retreat",
            'category'              => 'Engineering',
            'employment_type'       => 'full_time',
            'location_type'         => 'remote',
            'salary_min'            => 90000,
            'salary_max'            => 130000,
            'experience_level'      => 'senior',
            'visa_sponsorship'      => false,
            'open_to_international' => true,
            'status'                => 'active',
            'expires_at'            => now()->addDays(60),
        ]);

        $job->skills()->attach([1 => ['is_required' => true], 2 => ['is_required' => true], 8 => ['is_required' => false]]);

        // Demo seeker
        $seeker = User::create([
            'name'              => 'Maria Santos',
            'email'             => 'seeker@demo.com',
            'password'          => Hash::make('password'),
            'role'              => 'job_seeker',
            'email_verified_at' => now(),
            'country'           => 'Philippines',
        ]);

        $seekerProfile = JobSeekerProfile::create([
            'user_id'             => $seeker->id,
            'headline'            => 'Senior PHP / Laravel Developer',
            'bio'                 => 'Passionate developer with 7 years of experience building scalable web apps.',
            'current_city'        => 'Manila',
            'current_country'     => 'Philippines',
            'nationality'         => 'Filipino',
            'open_to_remote'      => true,
            'experience_level'    => 'senior',
            'years_of_experience' => 7,
            'desired_salary_min'  => 80000,
            'desired_salary_max'  => 120000,
            'availability'        => 'two_weeks',
            'profile_complete'    => true,
        ]);

        $seekerProfile->skills()->attach([
            1 => ['proficiency' => 'expert'],
            2 => ['proficiency' => 'expert'],
            3 => ['proficiency' => 'advanced'],
            8 => ['proficiency' => 'intermediate'],
        ]);
    }
}
