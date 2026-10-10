<?php

namespace App\Services\LeadQualification;

use App\Services\LeadQualification\Contracts\LeadQualifier;
use Illuminate\Support\Str;

/**
 * Deterministic, dependency-free qualifier.
 *
 * Two jobs: it keeps the feature working when no Anthropic key is configured,
 * and it is the safety net when a Claude call fails. Scores are intentionally
 * on the same 0–100 scale as the Claude verdict so the two are comparable in
 * the employer UI.
 */
class HeuristicLeadQualifier implements LeadQualifier
{
    /** Weight each dimension contributes to the overall score. */
    private const WEIGHTS = [
        'skills'       => 0.35,
        'experience'   => 0.25,
        'compensation' => 0.15,
        'logistics'    => 0.15,
        'intent'       => 0.10,
    ];

    private const LEVELS = ['entry' => 1, 'mid' => 2, 'senior' => 3, 'lead' => 4, 'executive' => 5];

    /** Score used when a dimension cannot be assessed from the data we hold. */
    private const NEUTRAL = 60;

    public function name(): string
    {
        return 'heuristic';
    }

    public function qualify(LeadProfile $lead): QualificationResult
    {
        $job       = $lead->job;
        $candidate = $lead->candidate;

        $criteria = [
            'skills'       => $this->scoreSkills($job, $candidate),
            'experience'   => $this->scoreExperience($job, $candidate),
            'compensation' => $this->scoreCompensation($job, $candidate),
            'logistics'    => $this->scoreLogistics($job, $candidate),
            'intent'       => $this->scoreIntent($candidate),
        ];

        $score = 0.0;
        foreach (self::WEIGHTS as $dimension => $weight) {
            $score += $criteria[$dimension] * $weight;
        }

        return QualificationResult::fromArray([
            'score'     => $score,
            'criteria'  => $criteria,
            'strengths' => $this->strengths($criteria, $job, $candidate),
            'concerns'  => $this->concerns($criteria, $job, $candidate),
            'summary'   => $this->summary($criteria, $job, $candidate, (int) round($score)),
        ], $this->name());
    }

    // ── Dimensions ────────────────────────────────────────────────

    private function scoreSkills(array $job, array $candidate): int
    {
        $required = $this->normalise($job['required_skills']);
        $optional = $this->normalise($job['optional_skills']);
        $held     = $this->normalise($candidate['skills']);

        if (empty($required) && empty($optional)) {
            return self::NEUTRAL;
        }

        if (empty($held)) {
            return 10;
        }

        // Required skills carry the bulk of the weight; optional ones top it up.
        $score = empty($required)
            ? self::NEUTRAL
            : (count(array_intersect($required, $held)) / count($required)) * 80;

        if (! empty($optional)) {
            $score += min(count(array_intersect($optional, $held)) * 7, 20);
        }

        return (int) round(min($score, 100));
    }

    private function scoreExperience(array $job, array $candidate): int
    {
        $jobLevel  = self::LEVELS[$job['experience_level']] ?? null;
        $seekLevel = self::LEVELS[$candidate['experience_level']] ?? null;

        if ($jobLevel === null || $seekLevel === null) {
            return self::NEUTRAL;
        }

        // Under-qualified is penalised harder than over-qualified.
        $gap   = $seekLevel - $jobLevel;
        $score = match (true) {
            $gap === 0  => 100,
            $gap === 1  => 85,
            $gap >= 2   => 65,
            $gap === -1 => 60,
            default     => 25,
        };

        $years = $candidate['years_of_experience'];
        if (is_numeric($years)) {
            // Roughly two years of experience are expected per seniority step.
            $expected = max(0, ($jobLevel - 1) * 2);
            $score += $years >= $expected ? 5 : -min(20, ($expected - $years) * 5);
        }

        return (int) round(max(0, min($score, 100)));
    }

    private function scoreCompensation(array $job, array $candidate): int
    {
        $asking = $candidate['expected_salary'] ?? $candidate['desired_salary_min'];
        $budget = $job['salary_max'] ?? $job['salary_min'];

        if (! is_numeric($asking) || ! is_numeric($budget) || $budget <= 0) {
            return self::NEUTRAL;
        }

        $ratio = $asking / $budget;

        return match (true) {
            $ratio <= 0.85 => 100,
            $ratio <= 1.0  => 90,
            $ratio <= 1.15 => 60,
            $ratio <= 1.35 => 30,
            default        => 10,
        };
    }

    private function scoreLogistics(array $job, array $candidate): int
    {
        $sameCountry = filled($job['country']) && filled($candidate['country'])
            && Str::lower(trim($job['country'])) === Str::lower(trim($candidate['country']));

        if ($job['location_type'] === 'remote') {
            return $candidate['open_to_remote'] ? 100 : 55;
        }

        if ($sameCountry) {
            return $job['location_type'] === 'hybrid' && ! $candidate['willing_to_relocate'] ? 75 : 95;
        }

        // Cross-border hire for an on-site role: only viable with sponsorship.
        if (! $job['visa_sponsorship'] && ! $job['open_to_international']) {
            return 15;
        }

        return $candidate['willing_to_relocate'] ? 70 : 35;
    }

    private function scoreIntent(array $candidate): int
    {
        $score = 30;

        $coverLetterLength = Str::length((string) $candidate['cover_letter']);
        $score += match (true) {
            $coverLetterLength >= 600 => 30,
            $coverLetterLength >= 200 => 20,
            $coverLetterLength > 0    => 10,
            default                   => 0,
        };

        $score += $candidate['has_resume'] ? 20 : 0;
        $score += ($candidate['has_portfolio'] || $candidate['has_github']) ? 10 : 0;
        $score += $candidate['has_linkedin'] ? 5 : 0;
        $score += (int) round(((int) ($candidate['profile_completion'] ?? 0)) * 0.05);

        return (int) round(min($score, 100));
    }

    // ── Narrative ─────────────────────────────────────────────────

    private function strengths(array $criteria, array $job, array $candidate): array
    {
        $strengths = [];
        $matched   = array_intersect(
            $this->normalise($job['required_skills']),
            $this->normalise($candidate['skills'])
        );

        if ($criteria['skills'] >= 70 && ! empty($matched)) {
            $strengths[] = 'Covers '.count($matched).' of '.count($job['required_skills'])
                .' required skills ('.implode(', ', array_slice($job['required_skills'], 0, 4)).').';
        }

        if ($criteria['experience'] >= 80) {
            $strengths[] = 'Seniority lines up with the '.$job['experience_level'].'-level requirement'
                .(is_numeric($candidate['years_of_experience'])
                    ? ' with '.$candidate['years_of_experience'].' years of experience.'
                    : '.');
        }

        if ($criteria['compensation'] >= 90) {
            $strengths[] = 'Salary expectation sits inside the posted budget.';
        }

        if ($criteria['logistics'] >= 90) {
            $strengths[] = 'No location or work-authorisation friction for this role.';
        }

        if ($criteria['intent'] >= 75) {
            $strengths[] = 'Strong application effort — detailed cover letter and a complete profile.';
        }

        return $strengths;
    }

    private function concerns(array $criteria, array $job, array $candidate): array
    {
        $concerns = [];
        $held     = $this->normalise($candidate['skills']);

        // Compare normalised, but report the skill names as the employer wrote them.
        $missing = collect($job['required_skills'])
            ->reject(fn ($skill) => in_array(Str::lower(trim((string) $skill)), $held, true))
            ->values();

        if ($criteria['skills'] < 70 && $missing->isNotEmpty()) {
            $concerns[] = 'Missing required skills: '.$missing->take(4)->implode(', ').'.';
        }

        if ($criteria['experience'] < 60) {
            $concerns[] = 'Experience level ('.($candidate['experience_level'] ?? 'unknown')
                .') is below the '.$job['experience_level'].' level this role targets.';
        }

        if ($criteria['compensation'] < 60) {
            $concerns[] = 'Salary expectation exceeds the posted budget.';
        }

        if ($criteria['logistics'] < 60) {
            $concerns[] = match (true) {
                // A remote posting has no relocation or sponsorship question —
                // the only blocker is the candidate not wanting remote work.
                $job['location_type'] === 'remote' => 'Role is remote but the candidate has not marked themselves open to remote work.',
                $job['visa_sponsorship'] || $job['open_to_international'] => 'Relocation would be required and the candidate has not marked themselves open to it.',
                default => 'Candidate is outside the hiring country and this role offers no sponsorship.',
            };
        }

        if ($criteria['intent'] < 55) {
            $concerns[] = 'Thin application — little supporting material to assess.';
        }

        return $concerns;
    }

    private function summary(array $criteria, array $job, array $candidate, int $score): string
    {
        $headline = $candidate['headline'] ?: $candidate['current_job_title'] ?: 'The applicant';
        $verdict  = match (true) {
            $score >= 75 => 'a strong match worth fast-tracking',
            $score >= 50 => 'a plausible match that needs a human review',
            default      => 'a weak match on the posted requirements',
        };

        return "{$headline} scores {$score}/100 against {$job['title']} — {$verdict}. "
            ."Skills {$criteria['skills']}, experience {$criteria['experience']}, "
            ."compensation {$criteria['compensation']}, logistics {$criteria['logistics']}.";
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * @return list<string>
     */
    private function normalise(array $names): array
    {
        return collect($names)
            ->map(fn ($name) => Str::lower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
