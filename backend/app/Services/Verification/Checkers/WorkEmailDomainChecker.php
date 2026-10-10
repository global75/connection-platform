<?php

namespace App\Services\Verification\Checkers;

use App\Models\EmployerProfile;
use App\Services\Verification\CheckResult;
use App\Services\Verification\Contracts\DnsResolver;
use App\Services\Verification\Contracts\VerificationChecker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Proves an employer controls the domain they claim to hire for.
 *
 * Two stages, both run here:
 *
 *  1. Preconditions — the account email is on a company domain (not a free or
 *     disposable provider), and that domain matches the company website when
 *     one is set. Cheap, and rejects the obvious cases immediately.
 *  2. Proof of control — the employer publishes a TXT record containing a token
 *     we issued. Passing this is what actually grants the verification; an MX
 *     lookup is recorded alongside as evidence the domain receives mail.
 *
 * Needs no credentials, so it works on a fresh install.
 */
class WorkEmailDomainChecker implements VerificationChecker
{
    public function __construct(private DnsResolver $dns) {}

    public function type(): string
    {
        return 'work_email_domain';
    }

    public function available(): bool
    {
        return true;
    }

    /**
     * The token an employer publishes at `TXT <domain>`. Derived from the
     * profile so it is stable across attempts and unguessable across tenants.
     */
    public function tokenFor(EmployerProfile $employer): string
    {
        $prefix = config('verification.employer.dns_token_prefix');
        $digest = hash_hmac('sha256', "employer:{$employer->id}", (string) config('app.key'));

        return $prefix.'='.substr($digest, 0, 32);
    }

    public function check(Model $subject, array $input = []): CheckResult
    {
        if (! $subject instanceof EmployerProfile) {
            throw new InvalidArgumentException('Domain verification applies to employer profiles.');
        }

        $email = $subject->user?->email;

        if (blank($email) || ! str_contains($email, '@')) {
            return CheckResult::rejected('dns', 'The account has no usable email address.');
        }

        $domain = Str::lower(Str::after($email, '@'));

        if ($this->isBlocked($domain)) {
            return CheckResult::rejected(
                'dns',
                "Register with your company email address — {$domain} is a public mailbox provider.",
                ['domain' => $domain, 'reason' => 'blocked_domain'],
            );
        }

        if ($mismatch = $this->websiteMismatch($subject, $domain)) {
            return CheckResult::rejected('dns', $mismatch, ['domain' => $domain, 'reason' => 'website_mismatch']);
        }

        $mx = $this->dns->mx($domain);

        if (empty($mx)) {
            return CheckResult::rejected(
                'dns',
                "No mail exchanger is published for {$domain}, so it cannot receive company email.",
                ['domain' => $domain, 'reason' => 'no_mx'],
            );
        }

        $token   = $this->tokenFor($subject);
        $records = $this->dns->txt($domain);

        if (! $this->published($records, $token)) {
            return CheckResult::pending('dns', [
                'domain'         => $domain,
                'mx'             => array_slice($mx, 0, 5),
                'expected_txt'   => $token,
                'txt_found'      => count($records),
                'awaiting'       => 'dns_txt_record',
            ]);
        }

        return CheckResult::approved(
            'dns',
            [
                'domain'      => $domain,
                'mx'          => array_slice($mx, 0, 5),
                'proved_with' => 'dns_txt_record',
            ],
            expiresAt: now()->addDays((int) config('verification.employer.domain_ttl_days')),
        );
    }

    private function isBlocked(string $domain): bool
    {
        return in_array($domain, config('verification.employer.blocked_email_domains', []), true);
    }

    /**
     * When a website is on file, the email domain has to correspond to it —
     * registering as "Acme" on a website you don't own shouldn't verify.
     * A subdomain of the site (mail.acme.com for acme.com) is accepted.
     */
    private function websiteMismatch(EmployerProfile $employer, string $domain): ?string
    {
        if (blank($employer->website)) {
            return null;
        }

        $host = Str::lower((string) parse_url(
            Str::startsWith($employer->website, ['http://', 'https://'])
                ? $employer->website
                : 'https://'.$employer->website,
            PHP_URL_HOST
        ));

        if (blank($host)) {
            return null;
        }

        $host = Str::after($host, 'www.');

        if ($domain === $host || Str::endsWith($domain, '.'.$host) || Str::endsWith($host, '.'.$domain)) {
            return null;
        }

        return "Your email domain ({$domain}) does not match the company website ({$host}).";
    }

    /**
     * @param  list<string>  $records
     */
    private function published(array $records, string $token): bool
    {
        foreach ($records as $record) {
            if (str_contains(trim($record), $token)) {
                return true;
            }
        }

        return false;
    }
}
