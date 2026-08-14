<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
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
            'period' => ['required', Rule::in(['today', 'last_7_days', 'last_30_days', 'current_month', 'custom'])],
            'start_date' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (
                $this->input('period') !== 'custom'
                || $validator->errors()->hasAny(['start_date', 'end_date'])
                || ! is_string($this->input('start_date'))
                || ! is_string($this->input('end_date'))
            ) {
                return;
            }

            $start = CarbonImmutable::createFromFormat('!Y-m-d', $this->input('start_date'));
            $end = CarbonImmutable::createFromFormat('!Y-m-d', $this->input('end_date'));

            if ($start->diffInDays($end) > 365) {
                $validator->errors()->add('end_date', 'O intervalo personalizado pode ter no máximo 366 dias.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('period')) {
            $this->merge(['period' => 'last_30_days']);
        }
    }
}
