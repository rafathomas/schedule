<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CommunicationChannel;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommunicationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCompanyRole(
            app(CurrentCompany::class)->id(),
            ['owner', 'admin'],
        ) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirmation_enabled' => ['required', 'boolean'],
            'confirmation_minutes_before' => ['required', 'integer', 'min:30', 'max:10080'],
            'reminders_enabled' => ['required', 'boolean'],
            'reminder_offsets' => ['required', 'array', 'min:1', 'max:5'],
            'reminder_offsets.*' => ['required', 'integer', 'distinct', 'min:30', 'max:10080'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'string', 'distinct', Rule::enum(CommunicationChannel::class)],
        ];
    }
}
