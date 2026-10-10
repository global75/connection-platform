<?php

namespace App\Providers;

use Anthropic\Client;
use Anthropic\ServiceContracts\MessagesContract;
use App\Services\LeadQualification\ClaudeLeadQualifier;
use App\Services\LeadQualification\Contracts\LeadQualifier;
use App\Services\LeadQualification\HeuristicLeadQualifier;
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
        $this->registerLeadQualification();
        $this->registerVerification();
    }

    private function registerLeadQualification(): void
    {
        $this->app->singleton(Client::class, fn () => new Client(
            apiKey: config('ai.anthropic.api_key'),
        ));

        // Bound separately so anything calling Claude depends on the messages
        // contract rather than the whole client — which keeps it mockable.
        $this->app->bind(MessagesContract::class, fn ($app) => $app->make(Client::class)->messages);

        $this->app->bind(LeadQualifier::class, fn ($app) => $app->make($this->qualifierClass()));
    }

    private function registerVerification(): void
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

    /**
     * Resolve which qualifier backs the platform.
     *
     * "auto" (the default) uses Claude when an Anthropic key is configured and
     * quietly falls back to heuristic scoring when it is not, so a fresh
     * install still qualifies leads without any credentials.
     */
    private function qualifierClass(): string
    {
        return match (config('ai.lead_qualification.driver')) {
            'claude'    => ClaudeLeadQualifier::class,
            'heuristic' => HeuristicLeadQualifier::class,
            default     => filled(config('ai.anthropic.api_key'))
                ? ClaudeLeadQualifier::class
                : HeuristicLeadQualifier::class,
        };
    }
}
