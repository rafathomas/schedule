<?php

declare(strict_types=1);

namespace App\Http\Requests\Appointments;

use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCompanyRole(app(CurrentCompany::class)->id(), ['owner', 'admin']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->id();

        return [
            'customer_uuid' => [
                'required',
                'uuid',
                Rule::exists('customers', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')),
            ],
            'professional_uuid' => [
                'required',
                'uuid',
                Rule::exists('professionals', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')),
            ],
            'service_uuid' => [
                'required',
                'uuid',
                Rule::exists('services', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
