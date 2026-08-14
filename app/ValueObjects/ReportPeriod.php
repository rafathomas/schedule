<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class ReportPeriod
{
    public function __construct(
        public string $preset,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
        public string $label,
        public string $timezone,
    ) {}

    public static function fromInput(
        string $preset,
        ?string $startDate,
        ?string $endDate,
        string $timezone,
    ): self {
        $today = CarbonImmutable::today($timezone);
        [$start, $end, $label] = match ($preset) {
            'today' => [$today, $today, 'Hoje'],
            'last_7_days' => [$today->subDays(6), $today, 'Últimos 7 dias'],
            'current_month' => [$today->startOfMonth(), $today, 'Mês atual'],
            'custom' => [
                CarbonImmutable::createFromFormat('!Y-m-d', (string) $startDate, $timezone),
                CarbonImmutable::createFromFormat('!Y-m-d', (string) $endDate, $timezone),
                'Período personalizado',
            ],
            default => [$today->subDays(29), $today, 'Últimos 30 dias'],
        };

        return new self(
            preset: $preset,
            startsAt: $start->startOfDay()->utc(),
            endsAt: $end->endOfDay()->utc(),
            label: $label,
            timezone: $timezone,
        );
    }

    public function localStart(): CarbonImmutable
    {
        return $this->startsAt->setTimezone($this->timezone);
    }

    public function localEnd(): CarbonImmutable
    {
        return $this->endsAt->setTimezone($this->timezone);
    }

    public function dayCount(): int
    {
        return (int) $this->localStart()->startOfDay()->diffInDays($this->localEnd()->startOfDay()) + 1;
    }
}
