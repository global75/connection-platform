<?php

namespace Tests\Feature;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\LeadQualification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployerLeadPipelineTest extends TestCase
{
    use RefreshDatabase;

    private EmployerProfile $employer;

    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'ai.lead_qualification.enabled' => true,
            'ai.lead_qualification.driver'  => 'heuristic',
            'ai.lead_qualification.tiers'   => ['hot' => 75, 'warm' => 50],
        ]);

        $this->employer = EmployerProfile::factory()->create();
        $this->job      = Job::factory()->create(['employer_profile_id' => $this->employer->id]);
    }

    public function test_the_application_list_carries_each_lead_verdict(): void
    {
        $application = $this->applicationScored(88);

        $this->actingAs($this->employer->user)
            ->getJson('/api/employer/applications')
            ->assertOk()
            ->assertJsonPath('data.0.id', $application->id)
            ->assertJsonPath('data.0.qualification.score', 88)
            ->assertJsonPath('data.0.qualification.tier', 'hot');
    }

    public function test_leads_can_be_filtered_by_tier(): void
    {
        $hot = $this->applicationScored(90);
        $this->applicationScored(20);

        $this->actingAs($this->employer->user)
            ->getJson('/api/employer/applications?tier=hot')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $hot->id);
    }

    public function test_leads_can_be_filtered_by_minimum_score(): void
    {
        $this->applicationScored(40);
        $strong = $this->applicationScored(80);

        $this->actingAs($this->employer->user)
            ->getJson('/api/employer/applications?min_score=70')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $strong->id);
    }

    public function test_leads_can_be_sorted_by_score_with_unqualified_applications_last(): void
    {
        $low         = $this->applicationScored(30);
        $high        = $this->applicationScored(95);
        $unqualified = JobApplication::factory()->create(['job_id' => $this->job->id]);

        $this->actingAs($this->employer->user)
            ->getJson('/api/employer/applications?sort=score')
            ->assertOk()
            ->assertJsonPath('data.0.id', $high->id)
            ->assertJsonPath('data.1.id', $low->id)
            ->assertJsonPath('data.2.id', $unqualified->id);
    }

    public function test_an_unknown_tier_is_rejected(): void
    {
        $this->actingAs($this->employer->user)
            ->getJson('/api/employer/applications?tier=lukewarm')
            ->assertStatus(422);
    }

    public function test_an_employer_can_re_run_qualification(): void
    {
        $application = $this->applicationScored(10);

        $this->actingAs($this->employer->user)
            ->postJson("/api/employer/applications/{$application->id}/qualify")
            ->assertStatus(202);

        $qualification = $application->fresh()->qualification;

        $this->assertSame('completed', $qualification->status);
        $this->assertNotSame(10, $qualification->score);
        $this->assertSame(1, $qualification->attempts);
    }

    public function test_an_employer_cannot_re_run_qualification_on_someone_elses_application(): void
    {
        $otherJob    = Job::factory()->create();
        $application = JobApplication::factory()->create(['job_id' => $otherJob->id]);

        $this->actingAs($this->employer->user)
            ->postJson("/api/employer/applications/{$application->id}/qualify")
            ->assertForbidden();
    }

    public function test_re_running_is_rejected_when_the_feature_is_disabled(): void
    {
        config(['ai.lead_qualification.enabled' => false]);
        $application = $this->applicationScored(60);

        $this->actingAs($this->employer->user)
            ->postJson("/api/employer/applications/{$application->id}/qualify")
            ->assertStatus(409);
    }

    public function test_the_summary_breaks_the_pipeline_down_by_tier(): void
    {
        $this->applicationScored(90);
        $this->applicationScored(80);
        $this->applicationScored(60);

        LeadQualification::factory()->create([
            'job_application_id' => JobApplication::factory()->create(['job_id' => $this->job->id])->id,
            'status'             => 'pending',
            'score'              => null,
            'tier'               => null,
            'qualified_at'       => null,
        ]);

        $this->actingAs($this->employer->user)
            ->getJson('/api/employer/applications/qualification-summary')
            ->assertOk()
            ->assertJsonPath('summary.tiers.hot.total', 2)
            ->assertJsonPath('summary.tiers.hot.average_score', 85)
            ->assertJsonPath('summary.tiers.warm.total', 1)
            ->assertJsonPath('summary.tiers.cold.total', 0)
            ->assertJsonPath('summary.awaiting_qualification', 1);
    }

    public function test_the_summary_excludes_other_employers_leads(): void
    {
        $this->applicationScored(90);

        LeadQualification::factory()->scored(95)->create([
            'job_application_id' => JobApplication::factory()->create([
                'job_id' => Job::factory()->create()->id,
            ])->id,
        ]);

        $this->actingAs($this->employer->user)
            ->getJson('/api/employer/applications/qualification-summary')
            ->assertOk()
            ->assertJsonPath('summary.tiers.hot.total', 1);
    }

    private function applicationScored(int $score): JobApplication
    {
        $application = JobApplication::factory()->create(['job_id' => $this->job->id]);

        LeadQualification::factory()->scored($score)->create([
            'job_application_id' => $application->id,
        ]);

        return $application;
    }
}
