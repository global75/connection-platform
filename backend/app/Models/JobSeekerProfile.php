<?php

namespace App\Models;

use App\Models\Concerns\HasVerifications;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSeekerProfile extends Model
{
    use HasFactory, HasVerifications;

    protected $fillable = [
        'user_id', 'headline', 'bio', 'resume', 'portfolio_url',
        'linkedin_url', 'github_url', 'current_city', 'current_country',
        'nationality', 'open_to_remote', 'willing_to_relocate',
        'experience_level', 'years_of_experience', 'current_job_title',
        'desired_job_title', 'desired_salary_min', 'desired_salary_max',
        'currency', 'availability', 'profile_complete', 'is_featured',
        'is_identity_verified', 'identity_verified_at', 'verified_badges',
    ];

    protected $casts = [
        'open_to_remote'      => 'boolean',
        'willing_to_relocate' => 'boolean',
        'profile_complete'    => 'boolean',
        'is_featured'         => 'boolean',
        'is_identity_verified' => 'boolean',
        'identity_verified_at' => 'datetime',
        'verified_badges'      => 'array',
        'years_of_experience' => 'integer',
        'desired_salary_min'  => 'integer',
        'desired_salary_max'  => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_seeker_skills')
                    ->withPivot('proficiency');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function savedJobs()
    {
        return $this->hasMany(SavedJob::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function completionPercentage(): int
    {
        $fields = [
            'headline', 'bio', 'resume', 'current_city',
            'experience_level', 'desired_job_title', 'desired_salary_min',
        ];

        $filled = collect($fields)->filter(fn ($f) => !empty($this->$f))->count();

        $hasSkills = $this->skills()->exists() ? 1 : 0;

        return (int) round(($filled + $hasSkills) / (count($fields) + 1) * 100);
    }
}
