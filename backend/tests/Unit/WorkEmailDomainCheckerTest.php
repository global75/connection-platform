<?php

namespace Tests\Unit;

use App\Models\EmployerProfile;
use App\Models\User;
use App\Services\Verification\Checkers\WorkEmailDomainChecker;
use App\Services\Verification\Contracts\DnsResolver;
use Tests\TestCase;

class WorkEmailDomainCheckerTest extends TestCase
{
    /**
     * An in-memory resolver, so none of this touches the network.
     */
    private function resolver(array $txt = [], array $mx = ['mail.acme.com']): DnsResolver
    {
        return new class($txt, $mx) implements DnsResolver
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
        };
    }

    /**
     * Built without touching the database — the checker only reads attributes.
     */
    private function employer(string $email = 'hiring@acme.com', ?string $website = 'https://acme.com'): EmployerProfile
    {
        $employer = new EmployerProfile(['company_name' => 'Acme', 'website' => $website]);
        $employer->id = 42;
        $employer->setRelation('user', new User(['email' => $email]));

        return $employer;
    }

    public function test_it_rejects_free_mailbox_providers(): void
    {
        $checker = new WorkEmailDomainChecker($this->resolver());
        $result  = $checker->check($this->employer('someone@gmail.com', null));

        $this->assertSame('rejected', $result->status);
        $this->assertStringContainsString('gmail.com', $result->rejectionReason);
        $this->assertSame('blocked_domain', $result->metadata['reason']);
    }

    public function test_it_rejects_a_disposable_address(): void
    {
        $checker = new WorkEmailDomainChecker($this->resolver());
        $result  = $checker->check($this->employer('nope@mailinator.com', null));

        $this->assertSame('rejected', $result->status);
        $this->assertSame('blocked_domain', $result->metadata['reason']);
    }

    public function test_it_rejects_an_email_domain_that_does_not_match_the_website(): void
    {
        $checker = new WorkEmailDomainChecker($this->resolver());
        $result  = $checker->check($this->employer('hiring@other-company.com', 'https://acme.com'));

        $this->assertSame('rejected', $result->status);
        $this->assertSame('website_mismatch', $result->metadata['reason']);
    }

    public function test_a_subdomain_of_the_website_is_accepted_as_matching(): void
    {
        $checker  = new WorkEmailDomainChecker($this->resolver());
        $employer = $this->employer('hiring@careers.acme.com', 'https://www.acme.com');

        // Not rejected for mismatch — it stops at the DNS stage instead.
        $result = $checker->check($employer);

        $this->assertNotSame('website_mismatch', $result->metadata['reason'] ?? null);
    }

    public function test_it_rejects_a_domain_with_no_mail_exchanger(): void
    {
        $checker = new WorkEmailDomainChecker($this->resolver(mx: []));
        $result  = $checker->check($this->employer());

        $this->assertSame('rejected', $result->status);
        $this->assertSame('no_mx', $result->metadata['reason']);
    }

    public function test_it_stays_pending_until_the_txt_record_is_published(): void
    {
        $checker = new WorkEmailDomainChecker($this->resolver(txt: ['v=spf1 include:_spf.google.com ~all']));
        $result  = $checker->check($this->employer());

        $this->assertSame('pending', $result->status);
        $this->assertSame('dns_txt_record', $result->metadata['awaiting']);
        $this->assertStringStartsWith('remotearena-verification=', $result->metadata['expected_txt']);
    }

    public function test_it_approves_once_the_token_is_published(): void
    {
        $employer = $this->employer();
        $token    = (new WorkEmailDomainChecker($this->resolver()))->tokenFor($employer);

        $checker = new WorkEmailDomainChecker($this->resolver(txt: ['unrelated', $token]));
        $result  = $checker->check($employer);

        $this->assertSame('approved', $result->status);
        $this->assertSame('acme.com', $result->metadata['domain']);
        $this->assertSame('dns_txt_record', $result->metadata['proved_with']);
        $this->assertNotNull($result->expiresAt);
    }

    public function test_a_token_from_another_employer_does_not_verify(): void
    {
        $mine   = $this->employer();
        $theirs = $this->employer();
        $theirs->id = 99;

        $otherToken = (new WorkEmailDomainChecker($this->resolver()))->tokenFor($theirs);

        $checker = new WorkEmailDomainChecker($this->resolver(txt: [$otherToken]));

        $this->assertSame('pending', $checker->check($mine)->status);
    }

    public function test_it_needs_no_credentials(): void
    {
        $this->assertTrue((new WorkEmailDomainChecker($this->resolver()))->available());
    }
}
