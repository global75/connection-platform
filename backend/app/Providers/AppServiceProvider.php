<?php

namespace App\Providers;

use App\Services\Verification\Checkers\GithubOauthChecker;
use App\Services\Verification\Checkers\UnconfiguredChecker;
use App\Services\Verification\Checkers\WorkEmailDomainChecker;
use App\Services\Verification\Contracts\DnsResolver;
use App\Services\Verification\SystemDnsResolver;
use App\Services\Verification\VerificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bound behind a contract so verification logic is testable without DNS.
        $this->app->bind(DnsResolver::class, SystemDnsResolver::class);

        $this->app->singleton(VerificationService::class, fn ($app) => new VerificationService([
            $app->make(WorkEmailDomainChecker::class),
            $app->make(GithubOauthChecker::class),
            // No vendor account is wired up for these on a default install, so
            // they report themselves unavailable rather than silently failing
            // applicants. Bind a real checker here once credentials exist.
            new UnconfiguredChecker('company_registry', (string) config('verification.providers.company_registry.driver')),
            new UnconfiguredChecker('government_id', (string) config('verification.providers.government_id.driver')),
        ]));
    }
}
