<?php

namespace Tests\Unit;

use App\Services\LeadQualification\HeuristicLeadQualifier;
use App\Services\LeadQualification\LeadProfile;
use Tests\TestCase;

class HeuristicLeadQualifierTest extends TestCase
{
    private HeuristicLeadQualifier $qualifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qualifier = new HeuristicLeadQualifier;
        config(['ai.lead_qualification.tiers' => ['hot' => 75, 'warm' => 50]]);
    }

    public function test_a_well_matched_applicant_is_a_hot_lead(): void
    {
        $result = $this->qualifier->qualify($this->lead());

        $this->assertSame('hot', $result->tier);
        $this->assertSame('shortlist', $result->recommendedAction);
        $this->assertGreaterThanOrEqual(85, $result->criteria['skills']);
        $this->assertNotEmpty($result->summary);
        $this->assertSame('heuristic', $result->provider);
    }

    public function test_missing_required_skills_drag_the_score_down_and_are_named(): void
    {
        $result = $this->qualifier->qualify($this->lead(
            candidate: ['skills' => ['Python']],
        ));

        $this->assertSame(0, $result->criteria['skills']);
        $this->assertNotSame('hot', $result->tier);
        $this->assertStringContainsString('PHP', implode(' ', $result->concerns));
    }

    public function test_matching_skills_are_case_insensitive(): void
    {
        $result = $this->qualifier->qualify($this->lead(
            candidate: ['skills' => ['php', '  LARAVEL  ']],
        ));

        // Both required skills matched (80); no optional skills held.
        $this->assertSame(80, $result->criteria['skills']);
    }

    public function test_an_applicant_asking_above_budget_scores_poorly_on_compensation(): void
    {
        $result = $this->qualifier->qualify($this->lead(
            candidate: ['expected_salary' => 220_000],
        ));

        $this->assertLessThan(60, $result->criteria['compensation']);
        $this->assertStringContainsString('budget', implode(' ', $result->concerns));
    }

    public function test_an_on_site_role_without_sponsorship_blocks_an_overseas_applicant(): void
    {
        $result = $this->qualifier->qualify($this->lead(
            job: [
                'location_type'         => 'on_site',
                'visa_sponsorship'      => false,
                'open_to_international' => false,
            ],
        ));

        $this->assertSame(15, $result->criteria['logistics']);
        $this->assertStringContainsString('sponsorship', implode(' ', $result->concerns));
    }

    public function test_a_remote_role_has_no_logistics_friction_for_a_remote_applicant(): void
    {
        $result = $this->qualifier->qualify($this->lead());

        $this->assertSame(100, $result->criteria['logistics']);
    }

    public function test_an_under_qualified_applicant_is_penalised_on_experience(): void
    {
        $result = $this->qualifier->qualify($this->lead(
            candidate: ['experience_level' => 'entry', 'years_of_experience' => 1],
        ));

        $this->assertLessThan(60, $result->criteria['experience']);
        $this->assertStringContainsString('senior', implode(' ', $result->concerns));
    }

    public function test_a_thin_application_scores_low_on_intent(): void
    {
        $result = $this->qualifier->qualify($this->lead(
            candidate: [
                'cover_letter'       => null,
                'has_resume'         => false,
                'has_portfolio'      => false,
                'has_github'         => false,
                'has_linkedin'       => false,
                'profile_completion' => 20,
            ],
        ));

        $this->assertLessThan(55, $result->criteria['intent']);
        $this->assertStringContainsString('Thin application', implode(' ', $result->concerns));
    }

    public function test_unknown_data_scores_neutrally_rather_than_punitively(): void
    {
        $result = $this->qualifier->qualify($this->lead(
            job: ['experience_level' => null, 'salary_min' => null, 'salary_max' => null],
            candidate: ['experience_level' => null, 'expected_salary' => null, 'desired_salary_min' => null],
        ));

        $this->assertSame(60, $result->criteria['experience']);
        $this->assertSame(60, $result->criteria['compensation']);
    }

    public function test_scoring_is_deterministic(): void
    {
        $lead = $this->lead();

        $this->assertSame(
            $this->qualifier->qualify($lead)->score,
            $this->qualifier->qualify($lead)->score
        );
    }

    /**
     * A strong remote candidate, with overrides merged over the top.
     */
    private function lead(array $job = [], array $candidate = []): LeadProfile
    {
        return new LeadProfile(
            applicationId: 1,
            job: array_merge([
                'title'                 => 'Senior Laravel Developer',
                'company'               => 'TechCorp Inc',
                'category'              => 'Engineering',
                'employment_type'       => 'full_time',
                'experience_level'      => 'senior',
                'location_type'         => 'remote',
                'location'              => 'Austin, TX, US',
                'country'               => 'US',
                'salary_min'            => 100_000,
                'salary_max'            => 140_000,
                'salary_currency'       => 'USD',
                'salary_period'         => 'annual',
                'visa_sponsorship'      => false,
                'open_to_international' => true,
                'required_skills'       => ['PHP', 'Laravel'],
                'optional_skills'       => ['Vue.js', 'Redis'],
                'description'           => 'Build and scale our API.',
                'requirements'          => '5+ years of PHP.',
            ], $job),
            candidate: array_merge([
                'headline'            => 'Senior Backend Engineer',
                'bio'                 => 'Ten years of PHP.',
                'current_job_title'   => 'Backend Engineer',
                'desired_job_title'   => 'Backend Engineer',
                'experience_level'    => 'senior',
                'years_of_experience' => 8,
                'location'            => 'Lisbon, PT',
                'country'             => 'PT',
                'nationality'         => 'PT',
                'open_to_remote'      => true,
                'willing_to_relocate' => false,
                'availability'        => 'two_weeks',
                'desired_salary_min'  => 110_000,
                'desired_salary_max'  => 130_000,
                'salary_currency'     => 'USD',
                'expected_salary'     => 115_000,
                'expected_currency'   => 'USD',
                'skills'              => ['PHP', 'Laravel', 'Vue.js'],
                'cover_letter'        => str_repeat('I have shipped Laravel APIs at scale. ', 20),
                'has_resume'          => true,
                'has_portfolio'       => true,
                'has_linkedin'        => true,
                'has_github'          => true,
                'profile_completion'  => 90,
            ], $candidate),
        );
    }
}
