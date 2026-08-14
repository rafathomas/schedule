<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'segment' => ['required', Rule::in(['beauty', 'barbershop', 'health', 'wellness', 'fitness', 'other'])],
            'phone' => ['required', 'string', 'max:32'],
            'whatsapp' => ['required', 'string', 'max:32'],
            'postal_code' => ['required', 'string', 'max:16'],
            'address' => ['required', 'string', 'max:180'],
            'address_number' => ['required', 'string', 'max:32'],
            'address_complement' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'timezone:all'],
            'appointment_interval_minutes' => ['required', 'integer', Rule::in([15, 30, 60])],
            'minimum_notice_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'maximum_notice_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
