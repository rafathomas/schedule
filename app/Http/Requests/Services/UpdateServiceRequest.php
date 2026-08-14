<?php

declare(strict_types=1);

namespace App\Http\Requests\Services;

use App\Models\Service;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends StoreServiceRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCompanyRole(app(CurrentCompany::class)->id(), ['owner', 'admin']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $companyId = app(CurrentCompany::class)->id();
        $service = Service::query()->where('uuid', $this->route('service'))->first();

        $rules['name'] = [
            'required',
            'string',
            'max:120',
            Rule::unique('services')->where(fn ($query) => $query
                ->where('company_id', $companyId)
                ->whereNull('deleted_at'))->ignore($service?->getKey()),
        ];

        return $rules;
    }
}
