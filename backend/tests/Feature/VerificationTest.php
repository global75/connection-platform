<?php

namespace Tests\Feature;

use App\Models\EmployerProfile;
use App\Models\JobSeekerProfile;
use App\Models\User;
use App\Models\Verification;
use App\Services\Verification\Contracts\DnsResolver;
use App\Services\Verification\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDns(array $txt = [], array $mx = ['mail.acme.com']): void
    {
        $this->swap(DnsResolver::class, new class($txt, $mx) implements DnsResolver
        {
            public function __construct(private array $txtRecords, private array $mxRecords) {}

            public function txt(string $domain): array
            {
                return $this->txtRecords;
            }

            public function mx(string $domain): array
            {
                return $this->mxRecords;
            }
        });
    }

    private function employer(string $email = 'hiring@acme.com'): EmployerProfile
    {
        $user = User::factory()->employer()->create(['email' => $email]);

        return EmployerProfile::factory()->create([
            'user_id' => $user->id,
            'website' => 'https://acme.com',
        ]);
    }

    public function test_the_portal_reports_status_and_the_dns_record_to_publish(): void
    {
        $employer = $this->employer();

        $this->actingAs($employer->user)
            ->getJson('/api/employer/verification')
            ->assertOk()
            ->assertJsonPath('verification.is_verified', false)
            ->assertJsonPath('verification.dns_instructions.record', 'TXT')
            ->assertJsonPath('verification.dns_instructions.host', 'acme.com')
            ->assertJsonStructure(['verification' => ['available_types', 'records', 'dns_instructions' => ['value']]]);
    }

    public function test_publishing_the_token_verifies_the_employer(): void
    {
        $employer = $this->employer();
        $token    = app(\App\Services\Verification\Checkers\WorkEmailDomainChecker::class)->tokenFor($employer);

        $this->fakeDns(txt: [$token]);

        $this->actingAs($employer->user)
            ->postJson('/api/employer/verification', ['type' => 'work_email_domain'])
            ->assertOk()
            ->assertJsonPath('is_verified', true)
            ->assertJsonPath('verification.status', 'approved');

        $employer->refresh();
        $this->assertTrue((bool) $employer->is_verified);
        $this->assertSame('acme.com', $employer->work_email_domain);
        $this->assertNotNull($employer->verified_at);
    }

    public function test_a_missing_txt_record_leaves_the_check_pending_and_the_employer_unverified(): void
    {
        $employer = $this->employer();
        $this->fakeDns(txt: []);

        $this->actingAs($employer->user)
            ->postJson('/api/employer/verification', ['type' => 'work_email_domain'])
            ->assertStatus(202)
            ->assertJsonPath('verification.status', 'pending')
            ->assertJsonPath('is_verified', false);

        $this->assertFalse((bool) $employer->fresh()->is_verified);
    }

    public function test_a_free_email_domain_is_rejected(): void
    {
        $employer = $this->employer('founder@gmail.com');
        $employer->update(['website' => null]);
        $this->fakeDns();

        $this->actingAs($employer->user)
            ->postJson('/api/employer/verification', ['type' => 'work_email_domain'])
            ->assertStatus(202)
            ->assertJsonPath('verification.status', 'rejected');

        $this->assertFalse((bool) $employer->fresh()->is_verified);
    }

    public function test_an_unconfigured_provider_answers_503_rather_than_rejecting(): void
    {
        $employer = $this->employer();

        $this->actingAs($employer->user)
            ->postJson('/api/employer/verification', ['type' => 'company_registry'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'VERIFICATION_PROVIDER_UNAVAILABLE');

        // Nothing was recorded — the applicant has nothing to act on.
        $this->assertSame(0, Verification::where('status', 'rejected')->count());
    }

    public function test_an_unknown_verification_type_is_rejected_by_validation(): void
    {
        $employer = $this->employer();

        $this->actingAs($employer->user)
            ->postJson('/api/employer/verification', ['type' => 'astrology'])
            ->assertStatus(422);
    }

    public function test_an_expired_verification_stops_counting_as_verified(): void
    {
        $employer = $this->employer();
        $token    = app(\App\Services\Verification\Checkers\WorkEmailDomainChecker::class)->tokenFor($employer);
        $this->fakeDns(txt: [$token]);

        app(VerificationService::class)->verify($employer, 'work_email_domain');
        $this->assertTrue((bool) $employer->fresh()->is_verified);

        $employer->verifications()->update(['expires_at' => now()->subDay()]);

        $this->assertSame(1, app(VerificationService::class)->expireStale());
        $this->assertFalse((bool) $employer->fresh()->is_verified);
        $this->assertSame('expired', $employer->fresh()->verifications->first()->status);
    }

    public function test_an_admin_can_approve_a_pending_verification_by_hand(): void
    {
        $employer = $this->employer();
        $this->fakeDns(txt: []);
        app(VerificationService::class)->verify($employer, 'work_email_domain');

        $verification = Verification::sole();
        $this->assertSame('pending', $verification->status);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/verifications/{$verification->id}", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('verification.status', 'approved');

        $this->assertTrue((bool) $employer->fresh()->is_verified);
        $this->assertSame($admin->id, $verification->fresh()->reviewed_by);
        $this->assertSame('manual', $verification->fresh()->provider);
    }

    public function test_a_manual_rejection_requires_a_reason(): void
    {
        $employer = $this->employer();
        $this->fakeDns(txt: []);
        app(VerificationService::class)->verify($employer, 'work_email_domain');

        $this->actingAs(User::factory()->admin()->create())
            ->patchJson('/api/admin/verifications/'.Verification::sole()->id, ['status' => 'rejected'])
            ->assertStatus(422);
    }

    public function test_an_employer_cannot_reach_the_admin_review_queue(): void
    {
        $employer = $this->employer();

        $this->actingAs($employer->user)
            ->getJson('/api/admin/verifications')
            ->assertForbidden();
    }

    public function test_candidate_badges_are_derived_from_active_verifications(): void
    {
        $seeker = JobSeekerProfile::factory()->create();

        $seeker->verifications()->create([
            'type'        => 'government_id',
            'status'      => 'approved',
            'provider'    => 'manual',
            'verified_at' => now(),
        ]);

        app(VerificationService::class)->syncSubject($seeker);
        $seeker->refresh();

        $this->assertTrue((bool) $seeker->is_identity_verified);
        $this->assertSame(['id_verified'], $seeker->verified_badges);
    }

    public function test_the_middleware_blocks_unverified_employers_only_when_enforcement_is_on(): void
    {
        config(['verification.employer.require_for_posting' => false]);
        $employer = $this->employer();

        $middleware = new \App\Http\Middleware\EnsureIsVerified;
        $request    = \Illuminate\Http\Request::create('/api/employer/jobs', 'POST');
        $request->setUserResolver(fn () => $employer->user);

        $passthrough = fn () => response()->json(['ok' => true]);

        $this->assertSame(200, $middleware->handle($request, $passthrough)->getStatusCode());

        config(['verification.employer.require_for_posting' => true]);
        $blocked = $middleware->handle($request, $passthrough);

        $this->assertSame(403, $blocked->getStatusCode());
        $this->assertSame('VERIFICATION_REQUIRED', $blocked->getData(true)['code']);
    }
}
