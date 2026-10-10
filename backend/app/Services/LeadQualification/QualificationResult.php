<?php

namespace App\Services\LeadQualification;

use App\Models\LeadQualification;

/**
 * Provider-agnostic qualification verdict.
 *
 * Everything reaching this object is normalised: scores are clamped to 0–100,
 * the tier is derived from the score, and the recommended action is restricted
 * to the values the database enum accepts. That keeps a hallucinated field in
 * a model response from ever reaching persistence.
 */
readonly class QualificationResult
{
    /** Per-dimension scores, each 0–100. */
    public const CRITERIA = ['skills', 'experience', 'compensation', 'logistics', 'intent'];

    public function __construct(
        public int $score,
        public string $tier,
        public string $recommendedAction,
        public string $summary,
        public array $strengths,
        public array $concerns,
        public array $criteria,
        public string $provider,
        public ?string $model = null,
    ) {}

    /**
     * Build a result from a raw (possibly untrusted) associative array.
     */
    public static function fromArray(array $data, string $provider, ?string $model = null): self
    {
        $score = self::clampScore($data['score'] ?? 0);

        return new self(
            score: $score,
            tier: LeadQualification::tierForScore($score),
            recommendedAction: self::normaliseAction($data['recommended_action'] ?? null, $score),
            summary: trim((string) ($data['summary'] ?? '')),
            strengths: self::normaliseList($data['strengths'] ?? []),
            concerns: self::normaliseList($data['concerns'] ?? []),
            criteria: self::normaliseCriteria($data['criteria'] ?? []),
            provider: $provider,
            model: $model,
        );
    }

    public function toAttributes(): array
    {
        return [
            'status'             => 'completed',
            'score'              => $this->score,
            'tier'               => $this->tier,
            'recommended_action' => $this->recommendedAction,
            'summary'            => $this->summary,
            'strengths'          => $this->strengths,
            'concerns'           => $this->concerns,
            'criteria'           => $this->criteria,
            'provider'           => $this->provider,
            'model'              => $this->model,
            'error'              => null,
            'qualified_at'       => now(),
        ];
    }

    private static function clampScore(mixed $value): int
    {
        return max(0, min(100, (int) round((float) $value)));
    }

    private static function normaliseAction(mixed $action, int $score): string
    {
        $action = is_string($action) ? strtolower(trim($action)) : null;

        if (in_array($action, LeadQualification::ACTIONS, true)) {
            return $action;
        }

        // No usable action from the provider — derive one from the tier.
        return match (LeadQualification::tierForScore($score)) {
            'hot'   => 'shortlist',
            'warm'  => 'review',
            default => 'reject',
        };
    }

    /**
     * @return list<string>
     */
    private static function normaliseList(mixed $items, int $max = 5): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item) => is_string($item) || is_numeric($item))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->take($max)
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private static function normaliseCriteria(mixed $criteria): array
    {
        if (! is_array($criteria)) {
            return [];
        }

        $normalised = [];

        foreach (self::CRITERIA as $key) {
            if (isset($criteria[$key]) && is_numeric($criteria[$key])) {
                $normalised[$key] = self::clampScore($criteria[$key]);
            }
        }

        return $normalised;
    }
}
