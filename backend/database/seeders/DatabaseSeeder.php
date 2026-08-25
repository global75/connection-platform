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
            'email'             => 'admin@connextion.io',
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
            'work_arrangement'      => 'remote',
            'location_country'      => 'US',
            'hiring_scope'          => 'international',
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
            'work_arrangements'   => ['remote'],
            'location_scopes'     => ['international'],
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

        $this->seedMarketplaceExamples();
    }

    /**
     * The marketplace is local, national and international at once. These
     * examples exist so every one of those shapes is represented from day one.
     */
    private function seedMarketplaceExamples(): void
    {
        $denver = EmployerProfile::create([
            'user_id' => User::create([
                'name'              => 'Front Range Retail Group',
                'email'             => 'hiring@frontrange.demo',
                'password'          => Hash::make('password'),
                'role'              => 'employer',
                'email_verified_at' => now(),
            ])->id,
            'company_name'         => 'Front Range Retail Group',
            'description'          => 'A Colorado retail group with stores across the Front Range.',
            'industry'             => 'Retail',
            'company_size'         => '201-500',
            'headquarters_city'    => 'Denver',
            'headquarters_state'   => 'CO',
            'headquarters_country' => 'US',
            'hiring_scopes'        => ['local', 'national'],
            'subscription_tier'    => 'basic',
        ]);

        $global = EmployerProfile::create([
            'user_id' => User::create([
                'name'              => 'Northwind Global',
                'email'             => 'hiring@northwind.demo',
                'password'          => Hash::make('password'),
                'role'              => 'employer',
                'email_verified_at' => now(),
            ])->id,
            'company_name'         => 'Northwind Global',
            'description'          => 'A distributed product company hiring across time zones.',
            'industry'             => 'Software',
            'company_size'         => '51-200',
            'headquarters_city'    => 'Toronto',
            'headquarters_state'   => 'ON',
            'headquarters_country' => 'CA',
            'hiring_scopes'        => ['remote', 'international'],
            'subscription_tier'    => 'pro',
        ]);

        // Local, on-site: the kind of role a remote-only board could never carry.
        $denver->jobs()->create([
            'title'            => 'Marketing Coordinator',
            'description'      => 'Own in-store campaigns across our Denver locations. You will coordinate seasonal promotions, work with store managers on merchandising, and report on campaign performance to the marketing director.',
            'requirements'     => "2+ years marketing experience\nComfortable working on-site in Denver\nStrong written communication",
            'category'         => 'Marketing',
            'employment_type'  => 'full_time',
            'work_arrangement' => 'on_site',
            'location_city'    => 'Denver',
            'location_state'   => 'CO',
            'location_country' => 'US',
            'hiring_scope'     => 'local',
            'salary_min'       => 58000,
            'salary_max'       => 72000,
            'experience_level' => 'mid',
            'status'           => 'active',
            'expires_at'       => now()->addDays(45),
        ]);

        // Local, on-site, entry level.
        $denver->jobs()->create([
            'title'            => 'Sales Associate',
            'description'      => 'Join the team at our Cherry Creek store. You will help customers find what they need, keep the floor merchandised, and hit team sales targets in a friendly, fast-moving environment.',
            'category'         => 'Sales',
            'employment_type'  => 'part_time',
            'work_arrangement' => 'on_site',
            'location_city'    => 'Denver',
            'location_state'   => 'CO',
            'location_country' => 'US',
            'hiring_scope'     => 'local',
            'salary_min'       => 20,
            'salary_max'       => 26,
            'salary_period'    => 'hourly',
            'experience_level' => 'entry',
            'status'           => 'active',
            'expires_at'       => now()->addDays(30),
        ]);

        // Statewide hybrid.
        $denver->jobs()->create([
            'title'            => 'Operations Analyst',
            'description'      => 'Analyse store performance across Colorado and turn it into decisions. Two days a week in our Boulder office, the rest wherever you work best, with occasional travel to store locations.',
            'category'         => 'Operations',
            'employment_type'  => 'full_time',
            'work_arrangement' => 'hybrid',
            'location_city'    => 'Boulder',
            'location_state'   => 'CO',
            'location_country' => 'US',
            'hiring_scope'     => 'state',
            'salary_min'       => 75000,
            'salary_max'       => 95000,
            'experience_level' => 'mid',
            'status'           => 'active',
            'expires_at'       => now()->addDays(60),
        ]);

        // National remote: anywhere in the US.
        $global->jobs()->create([
            'title'            => 'Customer Support Specialist',
            'description'      => 'Support our US customers over chat and email. Fully remote from anywhere in the United States, working a set schedule in your local time zone with a supportive team around you.',
            'category'         => 'Customer Success',
            'employment_type'  => 'full_time',
            'work_arrangement' => 'remote',
            'location_country' => 'US',
            'hiring_scope'     => 'national',
            'salary_min'       => 48000,
            'salary_max'       => 62000,
            'experience_level' => 'entry',
            'status'           => 'active',
            'expires_at'       => now()->addDays(60),
        ]);

        // International remote, restricted to a named set of countries.
        $global->jobs()->create([
            'title'              => 'Full-Stack Developer',
            'description'        => 'Build and ship features across our product with a distributed engineering team. We hire from a set of countries where we can contract directly, and we work asynchronously with a few overlapping hours.',
            'requirements'       => "4+ years building web applications\nComfortable working asynchronously\nStrong English communication",
            'category'           => 'Engineering',
            'employment_type'    => 'contract',
            'work_arrangement'   => 'remote',
            'location_country'   => 'CA',
            'hiring_scope'       => 'specific_countries',
            'eligible_countries' => ['CA', 'US', 'PT', 'EG', 'PH'],
            'salary_min'         => 70000,
            'salary_max'         => 110000,
            'experience_level'   => 'mid',
            'status'             => 'active',
            'expires_at'         => now()->addDays(60),
        ]);

        // Hybrid in Toronto: local hiring outside the US.
        $global->jobs()->create([
            'title'            => 'Product Designer',
            'description'      => 'Design end-to-end product experiences with our Toronto team. Three days a week together in our King Street studio, two wherever suits you, working closely with engineering and research.',
            'category'         => 'Design',
            'employment_type'  => 'full_time',
            'work_arrangement' => 'hybrid',
            'location_city'    => 'Toronto',
            'location_state'   => 'ON',
            'location_country' => 'CA',
            'hiring_scope'     => 'local',
            'salary_min'       => 95000,
            'salary_max'       => 125000,
            'currency'         => 'CAD',
            'experience_level' => 'senior',
            'status'           => 'active',
            'expires_at'       => now()->addDays(60),
        ]);

        // A local professional, to sit alongside the international one above.
        $denverSeeker = User::create([
            'name'              => 'Alex Rivera',
            'email'             => 'alex@demo.com',
            'password'          => Hash::make('password'),
            'role'              => 'job_seeker',
            'email_verified_at' => now(),
            'country'           => 'US',
        ]);

        JobSeekerProfile::create([
            'user_id'             => $denverSeeker->id,
            'headline'            => 'Marketing Coordinator',
            'bio'                 => 'Retail marketing specialist who likes being close to the stores I work with.',
            'current_city'        => 'Denver',
            'current_state'       => 'CO',
            'current_country'     => 'US',
            'work_arrangements'   => ['on_site', 'hybrid'],
            'location_scopes'     => ['near_me'],
            'max_commute_miles'   => 25,
            'employment_types'    => ['full_time'],
            'experience_level'    => 'mid',
            'years_of_experience' => 4,
            'desired_salary_min'  => 55000,
            'desired_salary_max'  => 75000,
            'availability'        => 'one_month',
            'profile_complete'    => true,
        ]);
    }
}
