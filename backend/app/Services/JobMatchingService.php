<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobSeekerProfile;
use Illuminate\Support\Collection;

/**
 * Deterministic job matching. Scores the things the marketplace actually knows:
 * eligibility, location, work arrangement, skills, experience, employment type
 * and salary. No AI is involved — an AI ranker can be layered on later without
 * changing this contract.
 */
class JobMatchingService
{
    public function __construct(private LocationService $locations) {}

    public function recommendJobsFor(JobSeekerProfile $seeker, int $limit = 10): Collection
    {
        $seekerSkillIds = $seeker->skills()->pluck('skills.id');

        $candidates = Job::active()
            ->with(['employer:id,company_name,logo,headquarters_city,headquarters_state', 'skills:id,name'])
            // Only surface jobs this person is actually allowed to apply to.
            ->openToCandidatesFrom($seeker->current_country, $seeker->current_state)
            // And only the ways of working they said they want.
            ->when(
                $seeker->work_arrangements,
                fn ($q) => $q->workArrangement($seeker->work_arrangements)
            )
            ->limit(500)
            ->get();

        return $candidates
            ->map(fn (Job $job) => ['job' => $job, 'score' => $this->score($job, $seeker, $seekerSkillIds)])
            ->filter(fn ($r) => $r['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->map(fn ($r) => $r['job']);
    }

    /**
     * 0–100. Location and work arrangement carry real weight, so a Denver
     * on-site role ranks for a Denver candidate and not for one in Manila.
     */
    private function score(Job $job, JobSeekerProfile $seeker, Collection $seekerSkillIds): float
    {
        return $this->skillScore($job, $seekerSkillIds)          // 0–40
             + $this->experienceScore($job, $seeker)             // 0–15
             + $this->salaryScore($job, $seeker)                 // 0–15
             + $this->locationScore($job, $seeker)               // 0–20
             + $this->arrangementScore($job, $seeker)            // 0–10
             + $this->employmentTypeScore($job, $seeker);        // 0–5
    }

    private function skillScore(Job $job, Collection $seekerSkillIds): float
    {
        $jobSkillIds     = $job->skills->pluck('id');
        $requiredIds     = $job->skills->where('pivot.is_required', true)->pluck('id');
        $matchedRequired = $seekerSkillIds->intersect($requiredIds)->count();
        $matchedOptional = $seekerSkillIds->intersect($jobSkillIds->diff($requiredIds))->count();

        $score = $requiredIds->count() > 0
            ? ($matchedRequired / $requiredIds->count()) * 30
            : 0.0;

        return $score + min($matchedOptional * 3, 10);
    }

    private function experienceScore(Job $job, JobSeekerProfile $seeker): float
    {
        $levels = ['entry' => 1, 'mid' => 2, 'senior' => 3, 'lead' => 4, 'executive' => 5];
        $diff   = abs(($levels[$seeker->experience_level] ?? 2) - ($levels[$job->experience_level] ?? 2));

        return max(0, 15 - $diff * 6);
    }

    private function salaryScore(Job $job, JobSeekerProfile $seeker): float
    {
        $wantMin = $seeker->desired_salary_min ?? 0;
        $wantMax = $seeker->desired_salary_max ?? PHP_INT_MAX;
        $jobMin  = $job->salary_min ?? 0;
        $jobMax  = $job->salary_max ?? PHP_INT_MAX;

        if ($jobMin === 0 && $jobMax === PHP_INT_MAX) {
            return 5; // Unstated salary is neutral, not disqualifying.
        }

        return min($wantMax, $jobMax) - max($wantMin, $jobMin) > 0 ? 15 : 0;
    }

    /**
     * Distance for placed jobs; country / scope alignment for remote ones.
     */
    private function locationScore(Job $job, JobSeekerProfile $seeker): float
    {
        if ($job->isRemote()) {
            if ($seeker->wantsScope('international') || $job->location_country === $seeker->current_country) {
                return 18;
            }

            return 12;
        }

        if (!$seeker->hasCoordinates() || $job->latitude === null || $job->longitude === null) {
            // Fall back to administrative match rather than inventing a distance.
            return match (true) {
                $job->location_city && $job->location_city === $seeker->current_city       => 20,
                (bool) ($job->location_state && $job->location_state === $seeker->current_state) => 14,
                $job->location_country === $seeker->current_country                        => 8,
                default                                                                    => $seeker->willing_to_relocate ? 4 : 0,
            };
        }

        $miles  = $this->locations->distanceMiles(
            $seeker->latitude, $seeker->longitude, $job->latitude, $job->longitude
        );
        $commute = $seeker->max_commute_miles ?: JobSeekerProfile::DEFAULT_COMMUTE_MILES;

        return match (true) {
            $miles <= $commute      => 20,
            $miles <= $commute * 2  => 14,
            $miles <= 100           => 8,
            default                 => $seeker->willing_to_relocate ? 4 : 0,
        };
    }

    private function arrangementScore(Job $job, JobSeekerProfile $seeker): float
    {
        return $seeker->wantsArrangement($job->work_arrangement) ? 10 : 0;
    }

    private function employmentTypeScore(Job $job, JobSeekerProfile $seeker): float
    {
        $wanted = $seeker->employment_types;

        if (empty($wanted)) {
            return 5;
        }

        return in_array($job->employment_type, $wanted, true) ? 5 : 0;
    }
}
