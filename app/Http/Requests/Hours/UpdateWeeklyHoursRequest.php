<?php

declare(strict_types=1);

namespace App\Http\Requests\Hours;

use App\Models\BusinessHour;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWeeklyHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateAny', BusinessHour::class) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'hours.*.is_closed' => ['required', 'boolean'],
            'hours.*.starts_at' => ['nullable', 'date_format:H:i'],
            'hours.*.ends_at' => ['nullable', 'date_format:H:i'],
            'hours.*.break_starts_at' => ['nullable', 'date_format:H:i'],
            'hours.*.break_ends_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var array<int, array<string, mixed>> $hours */
            $hours = $this->input('hours', []);

            foreach ($hours as $index => $hour) {
                if ((bool) ($hour['is_closed'] ?? false)) {
                    continue;
                }

                $startsAt = $hour['starts_at'] ?? null;
                $endsAt = $hour['ends_at'] ?? null;
                $breakStartsAt = $hour['break_starts_at'] ?? null;
                $breakEndsAt = $hour['break_ends_at'] ?? null;

                if ($startsAt === null) {
                    $validator->errors()->add("hours.{$index}.starts_at", 'Informe o início do atendimento.');
                }

                if ($endsAt === null) {
                    $validator->errors()->add("hours.{$index}.ends_at", 'Informe o fim do atendimento.');
                } elseif ($startsAt !== null && $endsAt <= $startsAt) {
                    $validator->errors()->add("hours.{$index}.ends_at", 'O fim deve ser posterior ao início.');
                }

                if (($breakStartsAt === null) !== ($breakEndsAt === null)) {
                    $validator->errors()->add("hours.{$index}.break_starts_at", 'Preencha o início e o fim do intervalo.');

                    continue;
                }

                if ($breakStartsAt !== null && $breakEndsAt !== null) {
                    if ($breakEndsAt <= $breakStartsAt) {
                        $validator->errors()->add("hours.{$index}.break_ends_at", 'O fim do intervalo deve ser posterior ao início.');
                    }

                    if ($startsAt !== null && $breakStartsAt < $startsAt) {
                        $validator->errors()->add("hours.{$index}.break_starts_at", 'O intervalo deve ocorrer durante o atendimento.');
                    }

                    if ($endsAt !== null && $breakEndsAt > $endsAt) {
                        $validator->errors()->add("hours.{$index}.break_ends_at", 'O intervalo deve ocorrer durante o atendimento.');
                    }
                }
            }
        }];
    }
}
