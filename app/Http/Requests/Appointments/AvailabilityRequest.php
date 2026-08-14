<?php

declare(strict_types=1);

namespace App\Http\Requests\Appointments;

use App\Models\Appointment;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Appointment::class) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->id();

        return [
            'professional' => [
                'required',
                'uuid',
                Rule::exists('professionals', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')),
            ],
            'service' => [
                'required',
                'uuid',
                Rule::exists('services', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'exclude' => [
                'nullable',
                'uuid',
                Rule::exists('appointments', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')),
            ],
        ];
    }
}
