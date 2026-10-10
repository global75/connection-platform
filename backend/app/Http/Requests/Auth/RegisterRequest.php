<?php

namespace App\Http\Requests\Auth;

use App\Models\EmployerProfile;
use App\Models\JobSeekerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'unique:users,email', 'max:255'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'role'         => ['required', 'in:employer,job_seeker'],
            'company_name' => ['required_if:role,employer', 'string', 'max:150'],

            // Onboarding: where they are, and how / where they want to work or hire.
            'country'      => ['nullable', 'string', 'max:100'],
            'state'        => ['nullable', 'string', 'max:100'],
            'city'         => ['nullable', 'string', 'max:100'],
            'work_arrangements'   => ['nullable', 'array'],
            'work_arrangements.*' => [Rule::in(JobSeekerProfile::WORK_ARRANGEMENTS)],
            'location_scopes'     => ['nullable', 'array'],
            'location_scopes.*'   => [Rule::in(JobSeekerProfile::LOCATION_SCOPES)],
            'hiring_scopes'       => ['nullable', 'array'],
            'hiring_scopes.*'     => [Rule::in(EmployerProfile::HIRING_SCOPES)],
        ];
    }
}
