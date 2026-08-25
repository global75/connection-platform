<?php

namespace App\Http\Requests\Employer;

use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * `location_type` was the old name for the work arrangement. Older clients
     * may still send it, so it is mapped onto the new field before validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->filled('work_arrangement') && $this->filled('location_type')) {
            $this->merge(['work_arrangement' => $this->input('location_type')]);
        }

        // Legacy clients that only know the boolean still get a sensible scope.
        if (!$this->filled('hiring_scope') && $this->has('open_to_international')) {
            $this->merge([
                'hiring_scope' => filter_var($this->input('open_to_international'), FILTER_VALIDATE_BOOLEAN)
                    ? 'international'
                    : 'national',
            ]);
        }
    }

    public function rules(): array
    {
        $needsAddress = in_array($this->input('work_arrangement'), ['on_site', 'hybrid'], true);

        return [
            'title'               => ['required', 'string', 'max:200'],
            'description'         => ['required', 'string', 'min:100'],
            'requirements'        => ['nullable', 'string'],
            'benefits'            => ['nullable', 'string'],
            'category'            => ['required', 'string', 'max:100'],
            'employment_type'     => ['required', 'in:full_time,part_time,contract,freelance,internship'],

            // How the work is performed.
            'work_arrangement'    => ['required', Rule::in(Job::WORK_ARRANGEMENTS)],

            // Where the job is. On-site and hybrid roles need a real place;
            // fully remote roles do not.
            'location_city'       => [$needsAddress ? 'required' : 'nullable', 'string', 'max:100'],
            'location_state'      => ['nullable', 'string', 'max:100'],
            'location_country'    => ['required', 'string', 'max:100'],
            'location_postal_code'=> ['nullable', 'string', 'max:20'],

            // Who may apply.
            'hiring_scope'        => ['required', Rule::in(Job::HIRING_SCOPES)],
            'eligible_countries'  => [Rule::requiredIf($this->input('hiring_scope') === 'specific_countries'), 'array'],
            'eligible_countries.*'=> ['string', 'max:100'],
            'local_radius_miles'  => ['nullable', 'integer', 'min:1', 'max:500'],

            'salary_min'          => ['nullable', 'integer', 'min:0'],
            'salary_max'          => ['nullable', 'integer', 'gte:salary_min'],
            'currency'            => ['nullable', 'string', 'size:3'],
            'salary_period'       => ['nullable', 'in:hourly,monthly,annual'],
            'salary_visible'      => ['boolean'],
            'experience_level'    => ['required', 'in:entry,mid,senior,lead,executive'],
            'visa_sponsorship'    => ['boolean'],
            'status'              => ['nullable', 'in:draft,active'],
            'expires_at'          => ['nullable', 'date', 'after:today'],
            'skills'              => ['nullable', 'array'],
            'skills.*.id'         => ['required', 'exists:skills,id'],
            'skills.*.is_required'=> ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_city.required' => 'On-site and hybrid roles need a city so candidates can find them locally.',
            'eligible_countries.required' => 'Select at least one country candidates may apply from.',
        ];
    }
}
