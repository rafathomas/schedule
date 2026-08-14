<?php

declare(strict_types=1);

namespace App\Http\Requests\BlockedPeriods;

use App\Models\BlockedPeriod;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlockedPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BlockedPeriod::class) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->id();

        return [
            'professional_uuid' => [
                'nullable',
                'uuid',
                Rule::exists('professionals', 'uuid')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')),
            ],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'reason' => ['required', 'string', 'max:180'],
        ];
    }
}
