<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobSeekerProfile;
use Illuminate\Support\Collection;

/**
 * Simple skill-overlap + experience-level scoring.
 * Replace score() with an AI/ML call (OpenAI embeddings, etc.) in production.
 */
class JobMatchingService
{
    public function recommendJobsFor(JobSeekerProfile $seeker, int $limit = 10): Collection
    {
        $seekerSkillIds    = $seeker->skills()->pluck('skills.id');
        $seekerLevel       = $seeker->experience_level;
        $salaryMin         = $seeker->desired_salary_min ?? 0;
        $salaryMax         = $seeker->desired_salary_max ?? PHP_INT_MAX;
        $openToRemote      = $seeker->open_to_remote;

        return Job::active()
            ->with(['employer:id,company_name,logo,is_verified', 'skills:id,name'])
            ->when($openToRemote, fn ($q) => $q->where('location_type', 'remote'))
            ->get()
            ->map(fn (Job $job) => [
                'job'   => $job,
                'score' => $this->score($job, $seekerSkillIds, $seekerLevel, $salaryMin, $salaryMax),
            ])
            ->filter(fn ($r) => $r['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->map(fn ($r) => $r['job']);
    }

    private function score(
        Job $job,
        Collection $seekerSkillIds,
        string $seekerLevel,
        int $salaryMin,
        int $salaryMax
    ): float {
        $score = 0.0;

        // Skill overlap (0–60 pts)
        $jobSkillIds     = $job->skills->pluck('id');
        $requiredIds     = $job->skills->where('pivot.is_required', true)->pluck('id');
        $matchedRequired = $seekerSkillIds->intersect($requiredIds)->count();
        $matchedOptional = $seekerSkillIds->intersect($jobSkillIds->diff($requiredIds))->count();

        if ($requiredIds->count() > 0) {
            $score += ($matchedRequired / $requiredIds->count()) * 60;
        }
        $score += min($matchedOptional * 5, 15);

        // Experience level (0–20 pts)
        $levels = ['entry' => 1, 'mid' => 2, 'senior' => 3, 'lead' => 4, 'executive' => 5];
        $diff   = abs(($levels[$seekerLevel] ?? 2) - ($levels[$job->experience_level] ?? 2));
        $score += max(0, 20 - $diff * 8);

        // Salary range overlap (0–20 pts)
        $jobMin = $job->salary_min ?? 0;
        $jobMax = $job->salary_max ?? PHP_INT_MAX;
        $overlap = max(0, min($salaryMax, $jobMax) - max($salaryMin, $jobMin));
        if ($overlap > 0) {
            $score += 20;
        }

        return $score;
    }
}
