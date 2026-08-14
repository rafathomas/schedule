<?php

declare(strict_types=1);

namespace App\Http\Requests\Appointments;

use App\Models\Appointment;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->id();

        return [
            'customer_mode' => ['required', Rule::in(['existing', 'new'])],
            'customer_uuid' => [
                'nullable',
                'required_if:customer_mode,existing',
                'uuid',
                Rule::exists('customers', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')),
            ],
            'new_customer' => ['nullable', 'required_if:customer_mode,new', 'array'],
            'new_customer.name' => ['nullable', 'required_if:customer_mode,new', 'string', 'max:120'],
            'new_customer.phone' => ['nullable', 'required_if:customer_mode,new', 'string', 'max:32'],
            'new_customer.whatsapp' => ['nullable', 'string', 'max:32'],
            'new_customer.email' => ['nullable', 'email:rfc', 'max:255'],
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
