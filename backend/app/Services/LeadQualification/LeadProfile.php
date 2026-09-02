<?php

namespace App\Services\LeadQualification;

use App\Models\JobApplication;
use Illuminate\Support\Str;

/**
 * Normalised, provider-agnostic snapshot of a single lead: the job being applied
 * for and everything we know about the applicant.
 *
 * Both qualifiers consume this — Claude renders it into a prompt, the heuristic
 * qualifier scores the arrays directly — so neither one touches Eloquent.
 */
readonly class LeadProfile
{
    public function __construct(
        public int $applicationId,
        public array $job,
        public array $candidate,
    ) {}

    public static function fromApplication(JobApplication $application): self
    {
        $application->loadMissing(['job.skills', 'job.employer', 'jobSeeker.user', 'jobSeeker.skills']);

        $job    = $application->job;
        $seeker = $application->jobSeeker;
        $limits = config('ai.lead_qualification.limits');

        $requiredSkills = $job->skills->where('pivot.is_required', true)->pluck('name')->values()->all();
        $optionalSkills = $job->skills->where('pivot.is_required', false)->pluck('name')->values()->all();

        return new self(
            applicationId: $application->id,
            job: [
                'title'                 => $job->title,
                'company'               => $job->employer?->company_name,
                'category'              => $job->category,
                'employment_type'       => $job->employment_type,
                'experience_level'      => $job->experience_level,
                'location_type'         => $job->location_type,
                'location'              => collect([$job->location_city, $job->location_state, $job->location_country])
                                            ->filter()->implode(', ') ?: null,
                'country'               => $job->location_country,
                'salary_min'            => $job->salary_min,
                'salary_max'            => $job->salary_max,
                'salary_currency'       => $job->currency,
                'salary_period'         => $job->salary_period,
                'visa_sponsorship'      => (bool) $job->visa_sponsorship,
                'open_to_international' => (bool) $job->open_to_international,
                'required_skills'       => $requiredSkills,
                'optional_skills'       => $optionalSkills,
                'description'           => self::clip($job->description, $limits['job_description']),
                'requirements'          => self::clip($job->requirements, $limits['job_requirements']),
            ],
            candidate: [
                'headline'            => $seeker?->headline,
                'bio'                 => self::clip($seeker?->bio, $limits['bio']),
                'current_job_title'   => $seeker?->current_job_title,
                'desired_job_title'   => $seeker?->desired_job_title,
                'experience_level'    => $seeker?->experience_level,
                'years_of_experience' => $seeker?->years_of_experience,
                'location'            => collect([$seeker?->current_city, $seeker?->current_country])
                                            ->filter()->implode(', ') ?: null,
                'country'             => $seeker?->current_country,
                'nationality'         => $seeker?->nationality,
                'open_to_remote'      => (bool) $seeker?->open_to_remote,
                'willing_to_relocate' => (bool) $seeker?->willing_to_relocate,
                'availability'        => $seeker?->availability,
                'desired_salary_min'  => $seeker?->desired_salary_min,
                'desired_salary_max'  => $seeker?->desired_salary_max,
                'salary_currency'     => $seeker?->currency,
                'expected_salary'     => $application->expected_salary,
                'expected_currency'   => $application->currency,
                'skills'              => $seeker?->skills->pluck('name')->values()->all() ?? [],
                'cover_letter'        => self::clip($application->cover_letter, $limits['cover_letter']),
                'has_resume'          => filled($application->resume_snapshot),
                'has_portfolio'       => filled($seeker?->portfolio_url),
                'has_linkedin'        => filled($seeker?->linkedin_url),
                'has_github'          => filled($seeker?->github_url),
                'profile_completion'  => $seeker?->completionPercentage(),
            ],
        );
    }

    /**
     * Long free-text is clipped rather than dropped so the prompt stays bounded
     * without silently losing the beginning of a cover letter.
     */
    private static function clip(?string $text, int $limit): ?string
    {
        if (blank($text)) {
            return null;
        }

        return Str::limit(trim($text), $limit);
    }

    public function toArray(): array
    {
        return [
            'job'       => $this->job,
            'candidate' => $this->candidate,
        ];
    }
}
