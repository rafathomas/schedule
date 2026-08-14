<?php

declare(strict_types=1);

namespace App\Actions\Hours;

use App\Models\BusinessHour;
use App\Models\Professional;
use App\Models\ProfessionalHour;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class UpdateWeeklyHoursAction
{
    public function __construct(private CurrentCompany $currentCompany) {}

    /** @param array<int, array<string, mixed>> $hours
     * @throws Throwable
     */
    public function forCompany(array $hours): void
    {
        DB::transaction(function () use ($hours): void {
            foreach ($hours as $hour) {
                BusinessHour::query()->updateOrCreate(
                    ['day_of_week' => $hour['day_of_week']],
                    $this->attributes($hour),
                );
            }
        });
    }

    /** @param array<int, array<string, mixed>> $hours
     * @throws Throwable
     */
    public function forProfessional(Professional $professional, array $hours): void
    {
        DB::transaction(function () use ($professional, $hours): void {
            foreach ($hours as $hour) {
                ProfessionalHour::query()->updateOrCreate(
                    [
                        'professional_id' => $professional->getKey(),
                        'day_of_week' => $hour['day_of_week'],
                    ],
                    $this->attributes($hour),
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>  $hour
     * @return array<string, mixed>
     */
    private function attributes(array $hour): array
    {
        $isClosed = (bool) $hour['is_closed'];

        return [
            'company_id' => $this->currentCompany->id(),
            'is_closed' => $isClosed,
            'starts_at' => $isClosed ? null : $hour['starts_at'],
            'ends_at' => $isClosed ? null : $hour['ends_at'],
            'break_starts_at' => $isClosed ? null : ($hour['break_starts_at'] ?? null),
            'break_ends_at' => $isClosed ? null : ($hour['break_ends_at'] ?? null),
        ];
    }
}
