<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = app(CurrentCompany::class)->get();

        return $company !== null && $this->user()?->can('update', $company) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('primary_color'))) {
            $this->merge([
                'primary_color' => strtoupper(trim($this->string('primary_color')->toString())),
            ]);
        }
    }
}
