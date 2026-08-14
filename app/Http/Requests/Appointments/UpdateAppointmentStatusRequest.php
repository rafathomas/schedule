<?php

declare(strict_types=1);

namespace App\Http\Requests\Appointments;

use App\Enums\AppointmentStatus;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCompanyRole(app(CurrentCompany::class)->id(), ['owner', 'admin', 'professional']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                AppointmentStatus::Confirmed->value,
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::Completed->value,
                AppointmentStatus::NoShow->value,
            ])],
            'reason' => ['nullable', 'required_if:status,'.AppointmentStatus::Cancelled->value, 'string', 'max:500'],
        ];
    }
}
