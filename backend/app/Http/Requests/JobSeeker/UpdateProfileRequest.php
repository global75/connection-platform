<?php

namespace App\Http\Requests\JobSeeker;

use App\Models\JobSeekerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'headline'            => ['nullable', 'string', 'max:200'],
            'bio'                 => ['nullable', 'string', 'max:3000'],
            'resume'              => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'avatar'              => ['nullable', 'image', 'max:2048'],
            'portfolio_url'       => ['nullable', 'url', 'max:255'],
            'linkedin_url'        => ['nullable', 'url', 'max:255'],
            'github_url'          => ['nullable', 'url', 'max:255'],
            'current_city'        => ['nullable', 'string', 'max:100'],
            'current_state'       => ['nullable', 'string', 'max:100'],
            'current_country'     => ['nullable', 'string', 'max:100'],
            'nationality'         => ['nullable', 'string', 'max:100'],
            'open_to_remote'      => ['boolean'],
            'willing_to_relocate' => ['boolean'],

            // How they want to work, and how far they will look.
            'work_arrangements'   => ['nullable', 'array'],
            'work_arrangements.*' => [Rule::in(JobSeekerProfile::WORK_ARRANGEMENTS)],
            'location_scopes'     => ['nullable', 'array'],
            'location_scopes.*'   => [Rule::in(JobSeekerProfile::LOCATION_SCOPES)],
            'max_commute_miles'   => ['nullable', 'integer', 'min:1', 'max:500'],
            'employment_types'    => ['nullable', 'array'],
            'employment_types.*'  => ['in:full_time,part_time,contract,freelance,internship'],
            'experience_level'    => ['nullable', 'in:entry,mid,senior,lead,executive'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'current_job_title'   => ['nullable', 'string', 'max:150'],
            'desired_job_title'   => ['nullable', 'string', 'max:150'],
            'desired_salary_min'  => ['nullable', 'integer', 'min:0'],
            'desired_salary_max'  => ['nullable', 'integer', 'gte:desired_salary_min'],
            'currency'            => ['nullable', 'string', 'size:3'],
            'availability'        => ['nullable', 'in:immediately,two_weeks,one_month,negotiable'],
            'skills'              => ['nullable', 'array'],
            'skills.*.id'         => ['required', 'exists:skills,id'],
            'skills.*.proficiency'=> ['nullable', 'in:beginner,intermediate,advanced,expert'],
        ];
    }
}
