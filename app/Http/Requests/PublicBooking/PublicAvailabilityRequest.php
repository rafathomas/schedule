<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicBooking;

use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->id();

        return [
            'service' => [
                'required',
                'uuid',
                Rule::exists('services', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'professional' => [
                'nullable',
                'uuid',
                Rule::exists('professionals', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
