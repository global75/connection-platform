<?php

namespace Tests\Feature;

use App\Jobs\QualifyApplicationLead;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobSeekerProfile;
use App\Models\LeadQualification;
use App\Models\Skill;
use App\Notifications\HotLeadIdentified;
use App\Services\LeadQualification\Contracts\LeadQualifier;
use App\Services\LeadQualification\LeadProfile;
use App\Services\LeadQualification\LeadQualificationService;
use App\Services\LeadQualification\QualificationResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class LeadQualificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'ai.lead_qualification.enabled'        => true,
            'ai.lead_qualification.driver'         => 'heuristic',
            'ai.lead_qualification.tiers'          => ['hot' => 75, 'warm' => 50],
            'ai.lead_qualification.auto_shortlist' => false,
        ]);
    }

    public function test_a_new_application_is_qualified_automatically(): void
    {
        [$job, $seeker] = $this->jobAndMatchingSeeker();

        $this->actingAs($seeker->user)
            ->postJson("/api/job-seeker/jobs/{$job->id}/apply", [
                'cover_letter'    => str_repeat('I have shipped Laravel APIs at scale. ', 5),
                'expected_salary' => 115000,
            ])
            ->assertCreated();

        $qualification = LeadQualification::sole();

        $this->assertSame('completed', $qualification->status);
        $this->assertSame('heuristic', $qualification->provider);
        $this->assertGreaterThan(0, $qualification->score);
        $this->assertContains($qualification->tier, LeadQualification::TIERS);
        $this->assertNotNull($qualification->qualified_at);
        $this->assertSame(1, $qualification->attempts);
    }

    public function test_no_qualification_is_produced_when_the_feature_is_disabled(): void
    {
        config(['ai.lead_qualification.enabled' => false]);

        [$job, $seeker] = $this->jobAndMatchingSeeker();

        $this->actingAs($seeker->user)
            ->postJson("/api/job-seeker/jobs/{$job->id}/apply", ['expected_salary' => 115000])
            ->assertCreated();

        $this->assertSame(0, LeadQualification::count());
    }

    public function test_a_hot_lead_notifies_the_employer(): void
    {
        [$job, $seeker] = $this->jobAndMatchingSeeker();
        $application    = JobApplication::factory()->create([
            'job_id'                => $job->id,
            'job_seeker_profile_id' => $seeker->id,
        ]);

        // Force a hot verdict so the test doesn't depend on the scoring weights.
        $this->swap(LeadQualifier::class, $this->stubQualifier(92));

        (new QualifyApplicationLead($application))->handle(
            app(LeadQualificationService::class),
            app(\App\Services\ApplicationService::class),
        );

        Notification::assertSentTo($job->employer->user, HotLeadIdentified::class);
    }

    public function test_a_manual_re_run_does_not_re_notify_the_employer(): void
    {
        [$job, $seeker] = $this->jobAndMatchingSeeker();
        $application    = JobApplication::factory()->create([
            'job_id'                => $job->id,
            'job_seeker_profile_id' => $seeker->id,
        ]);

        $this->swap(LeadQualifier::class, $this->stubQualifier(92));

        (new QualifyApplicationLead($application, force: true, announce: false))->handle(
            app(LeadQualificationService::class),
            app(\App\Services\ApplicationService::class),
        );

        Notification::assertNothingSent();
    }

    public function test_it_falls_back_to_heuristic_scoring_when_the_ai_call_fails(): void
    {
        [$job, $seeker] = $this->jobAndMatchingSeeker();
        $application    = JobApplication::factory()->create([
            'job_id'                => $job->id,
            'job_seeker_profile_id' => $seeker->id,
        ]);

        $this->swap(LeadQualifier::class, new class implements LeadQualifier
        {
            public function name(): string
            {
                return 'claude';
            }

            public function qualify(LeadProfile $lead): QualificationResult
            {
                throw new RuntimeException('overloaded_error');
            }
        });

        $qualification = app(LeadQualificationService::class)->qualify($application);

        $this->assertSame('completed', $qualification->status);
        $this->assertSame('heuristic', $qualification->provider);
        $this->assertStringContainsString('overloaded_error', $qualification->error);
    }

    public function test_it_records_a_failure_when_no_fallback_is_allowed(): void
    {
        config(['ai.lead_qualification.fallback_to_heuristic' => false]);

        [$job, $seeker] = $this->jobAndMatchingSeeker();
        $application    = JobApplication::factory()->create([
            'job_id'                => $job->id,
            'job_seeker_profile_id' => $seeker->id,
        ]);

        $this->swap(LeadQualifier::class, new class implements LeadQualifier
        {
            public function name(): string
            {
                return 'claude';
            }

            public function qualify(LeadProfile $lead): QualificationResult
            {
                throw new RuntimeException('rate_limit_error');
            }
        });

        $this->expectException(RuntimeException::class);

        try {
            app(LeadQualificationService::class)->qualify($application);
        } finally {
            $this->assertSame('failed', LeadQualification::sole()->status);
        }
    }

    public function test_a_closed_application_is_not_re_qualified(): void
    {
        [$job, $seeker] = $this->jobAndMatchingSeeker();
        $application    = JobApplication::factory()->create([
            'job_id'                => $job->id,
            'job_seeker_profile_id' => $seeker->id,
            'status'                => 'withdrawn',
        ]);

        $this->assertNull(app(LeadQualificationService::class)->qualify($application));
        $this->assertSame('pending', LeadQualification::sole()->status);
    }

    /**
     * A stub qualifier that always returns the given score.
     */
    private function stubQualifier(int $score): LeadQualifier
    {
        return new class($score) implements LeadQualifier
        {
            public function __construct(private int $score) {}

            public function name(): string
            {
                return 'claude';
            }

            public function qualify(LeadProfile $lead): QualificationResult
            {
                return QualificationResult::fromArray([
                    'score'              => $this->score,
                    'summary'            => 'Strong match.',
                    'strengths'          => ['Covers every required skill.'],
                    'concerns'           => [],
                    'recommended_action' => 'shortlist',
                    'criteria'           => ['skills' => $this->score],
                ], 'claude', 'claude-opus-5');
            }
        };
    }

    /**
     * @return array{0: Job, 1: JobSeekerProfile}
     */
    private function jobAndMatchingSeeker(): array
    {
        $php     = Skill::factory()->create(['name' => 'PHP']);
        $laravel = Skill::factory()->create(['name' => 'Laravel']);

        $job = Job::factory()->create([
            'employer_profile_id' => EmployerProfile::factory()->create()->id,
        ]);
        $job->skills()->attach([$php->id => ['is_required' => true], $laravel->id => ['is_required' => true]]);

        $seeker = JobSeekerProfile::factory()->create();
        $seeker->skills()->attach([$php->id, $laravel->id]);

        return [$job->fresh(), $seeker];
    }
}
