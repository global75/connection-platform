<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic credentials
    |--------------------------------------------------------------------------
    |
    | Used by the Claude-backed lead qualifier. When no key is present the
    | platform transparently falls back to the deterministic heuristic
    | qualifier, so the feature still works out of the box.
    |
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Lead Qualification
    |--------------------------------------------------------------------------
    |
    | Every incoming application is scored against the job it targets, tiered
    | (hot / warm / cold) and given a recommended action so employers can work
    | their pipeline highest-intent first.
    |
    */

    'lead_qualification' => [

        // Master switch. When false, applications are never auto-qualified.
        'enabled' => env('AI_LEAD_QUALIFICATION_ENABLED', true),

        // auto | claude | heuristic — "auto" uses Claude when a key is configured.
        'driver' => env('AI_LEAD_QUALIFICATION_DRIVER', 'auto'),

        // Fall back to heuristic scoring when the Claude call fails.
        'fallback_to_heuristic' => env('AI_LEAD_QUALIFICATION_FALLBACK', true),

        'model'       => env('AI_LEAD_QUALIFICATION_MODEL', 'claude-opus-5'),
        'max_tokens'  => (int) env('AI_LEAD_QUALIFICATION_MAX_TOKENS', 8000),
        'effort'      => env('AI_LEAD_QUALIFICATION_EFFORT', 'medium'),
        'timeout'     => (int) env('AI_LEAD_QUALIFICATION_TIMEOUT', 120),

        // Inclusive lower bound of each tier; anything below "warm" is cold.
        'tiers' => [
            'hot'  => (int) env('AI_LEAD_QUALIFICATION_HOT_AT', 75),
            'warm' => (int) env('AI_LEAD_QUALIFICATION_WARM_AT', 50),
        ],

        // Notify the employer as soon as a hot lead lands.
        'notify_on_hot' => env('AI_LEAD_QUALIFICATION_NOTIFY_HOT', true),

        // Automatically move "shortlist" recommendations to the shortlisted status.
        'auto_shortlist' => env('AI_LEAD_QUALIFICATION_AUTO_SHORTLIST', false),

        // Truncation limits for free-text pulled into the prompt.
        'limits' => [
            'cover_letter'     => 4000,
            'job_description'  => 6000,
            'job_requirements' => 4000,
            'bio'              => 1500,
        ],
    ],

];
