<?php

namespace App\Providers;

use Anthropic\Client;
use Anthropic\ServiceContracts\MessagesContract;
use App\Services\LeadQualification\ClaudeLeadQualifier;
use App\Services\LeadQualification\Contracts\LeadQualifier;
use App\Services\LeadQualification\HeuristicLeadQualifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, fn () => new Client(
            apiKey: config('ai.anthropic.api_key'),
        ));

        // Bound separately so anything calling Claude depends on the messages
        // contract rather than the whole client — which keeps it mockable.
        $this->app->bind(MessagesContract::class, fn ($app) => $app->make(Client::class)->messages);

        $this->app->bind(LeadQualifier::class, fn ($app) => $app->make($this->qualifierClass()));
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
