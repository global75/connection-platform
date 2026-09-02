<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocalizationLeadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'max:255'],
            'app_url'            => ['required', 'url', 'max:255'],
            'target_languages'   => ['required', 'array', 'min:1'],
            'target_languages.*' => ['string', 'in:french,arabic,spanish,german,portuguese,japanese,mandarin'],
            'message'            => ['nullable', 'string', 'max:2000'],
        ];
    }
}
