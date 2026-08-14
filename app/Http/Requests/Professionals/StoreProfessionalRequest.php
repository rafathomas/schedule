<?php

declare(strict_types=1);

namespace App\Http\Requests\Professionals;

use App\Models\Professional;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfessionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Professional::class) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->id();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique('professionals')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'service_uuids' => ['array'],
            'service_uuids.*' => [
                'uuid',
                Rule::exists('services', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')),
            ],
        ];
    }
}
