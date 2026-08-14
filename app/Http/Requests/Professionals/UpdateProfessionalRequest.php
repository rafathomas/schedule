<?php

declare(strict_types=1);

namespace App\Http\Requests\Professionals;

use App\Models\Professional;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfessionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCompanyRole(app(CurrentCompany::class)->id(), ['owner', 'admin']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->id();
        $professional = Professional::query()->where('uuid', $this->route('professional'))->first();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique('professionals')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'))->ignore($professional?->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['boolean'],
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
