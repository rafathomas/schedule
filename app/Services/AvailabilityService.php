<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\BusinessHour;
use App\Models\Professional;
use App\Models\ProfessionalHour;
use App\Models\Service;
use App\Support\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    /** @return array<int, array{value: string, label: string, starts_at: string}> */
    public function slots(Service $service, Professional $professional, string $date, ?Appointment $excluding = null): array
    {
        $company = $this->currentCompany->getOrFail();
        $timezone = (string) $company->timezone;
        $localDate = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);

        if ($localDate === null) {
            return [];
        }

        $workingHour = $this->workingHour($professional, $localDate->dayOfWeek);

        if ($workingHour === null || (bool) $workingHour->getAttribute('is_closed')) {
            return [];
        }

        $ranges = $this->workingRanges($localDate, $workingHour, $timezone);
        $dayStartUtc = $localDate->startOfDay()->utc();
        $dayEndUtc = $localDate->endOfDay()->utc();
        $appointments = $this->appointmentsForDay($professional, $dayStartUtc, $dayEndUtc, $excluding);
        $blocks = $this->blocksForDay($professional, $dayStartUtc, $dayEndUtc);
        $noticeStartsAt = CarbonImmutable::now('UTC')->addMinutes((int) $company->minimum_notice_minutes);
        $bookingLimit = CarbonImmutable::now($timezone)
            ->addDays((int) $company->maximum_notice_days)
            ->endOfDay()
            ->utc();
        $interval = max(5, (int) $company->appointment_interval_minutes);
        $duration = (int) $service->duration_minutes;
        $buffer = (int) $service->buffer_minutes;
        $slots = [];

        foreach ($ranges as [$rangeStart, $rangeEnd]) {
            for ($candidate = $rangeStart; $candidate->addMinutes($duration + $buffer)->lte($rangeEnd); $candidate = $candidate->addMinutes($interval)) {
                $candidateUtc = $candidate->utc();
                $blocksUntilUtc = $candidate->addMinutes($duration + $buffer)->utc();

                if ($candidateUtc->lt($noticeStartsAt) || $candidateUtc->gt($bookingLimit)) {
                    continue;
                }

                if ($this->overlapsAppointments($appointments, $candidateUtc, $blocksUntilUtc)
                    || $this->overlapsBlocks($blocks, $candidateUtc, $blocksUntilUtc)) {
                    continue;
                }

                $slots[] = [
                    'value' => $candidate->format('H:i'),
                    'label' => $candidate->format('H:i'),
                    'starts_at' => $candidateUtc->toIso8601String(),
                ];
            }
        }

        return $slots;
    }

    public function isAvailable(
        Service $service,
        Professional $professional,
        string $date,
        string $time,
        ?Appointment $excluding = null,
    ): bool {
        return collect($this->slots($service, $professional, $date, $excluding))
            ->contains(fn (array $slot): bool => $slot['value'] === $time);
    }

    public function scheduledAtUtc(string $date, string $time): CarbonImmutable
    {
        $timezone = (string) $this->currentCompany->getOrFail()->timezone;
        $scheduledAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$date} {$time}", $timezone);

        if ($scheduledAt === null) {
            throw new \InvalidArgumentException('Data ou horário inválido.');
        }

        return $scheduledAt->utc();
    }

    private function workingHour(Professional $professional, int $dayOfWeek): BusinessHour|ProfessionalHour|null
    {
        $professionalHour = ProfessionalHour::query()
            ->where('professional_id', $professional->getKey())
            ->where('day_of_week', $dayOfWeek)
            ->first();

        return $professionalHour ?? BusinessHour::query()->where('day_of_week', $dayOfWeek)->first();
    }

    /**
     * @return array<int, array{CarbonImmutable, CarbonImmutable}>
     */
    private function workingRanges(CarbonImmutable $date, BusinessHour|ProfessionalHour $hour, string $timezone): array
    {
        $startsAt = $this->atTime($date, $hour->getAttribute('starts_at'), $timezone);
        $endsAt = $this->atTime($date, $hour->getAttribute('ends_at'), $timezone);

        if ($startsAt === null || $endsAt === null || $endsAt->lte($startsAt)) {
            return [];
        }

        $breakStartsAt = $this->atTime($date, $hour->getAttribute('break_starts_at'), $timezone);
        $breakEndsAt = $this->atTime($date, $hour->getAttribute('break_ends_at'), $timezone);

        if ($breakStartsAt === null || $breakEndsAt === null
            || $breakStartsAt->lte($startsAt) || $breakEndsAt->gte($endsAt)
            || $breakEndsAt->lte($breakStartsAt)) {
            return [[$startsAt, $endsAt]];
        }

        return [[$startsAt, $breakStartsAt], [$breakEndsAt, $endsAt]];
    }

    private function atTime(CarbonImmutable $date, mixed $value, string $timezone): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $time = substr($value, 0, 5);
        $result = CarbonImmutable::createFromFormat('!Y-m-d H:i', $date->format('Y-m-d').' '.$time, $timezone);

        return $result;
    }

    /** @return Collection<int, Appointment> */
    private function appointmentsForDay(
        Professional $professional,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?Appointment $excluding,
    ): Collection {
        return Appointment::query()
            ->where('professional_id', $professional->getKey())
            ->whereIn('status', AppointmentStatus::occupyingValues())
            ->where('scheduled_at', '<', $end)
            ->where('blocks_until', '>', $start)
            ->when($excluding !== null, fn ($query) => $query->whereKeyNot($excluding->getKey()))
            ->get();
    }

    /** @return Collection<int, BlockedPeriod> */
    private function blocksForDay(Professional $professional, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return BlockedPeriod::query()
            ->where(function ($query) use ($professional): void {
                $query->whereNull('professional_id')->orWhere('professional_id', $professional->getKey());
            })
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->get();
    }

    /** @param Collection<int, Appointment> $appointments */
    private function overlapsAppointments(Collection $appointments, CarbonImmutable $startsAt, CarbonImmutable $endsAt): bool
    {
        return $appointments->contains(fn (Appointment $appointment): bool => $appointment->scheduled_at->lt($endsAt)
            && $appointment->blocks_until->gt($startsAt));
    }

    /** @param Collection<int, BlockedPeriod> $blocks */
    private function overlapsBlocks(Collection $blocks, CarbonImmutable $startsAt, CarbonImmutable $endsAt): bool
    {
        return $blocks->contains(fn (BlockedPeriod $block): bool => $block->starts_at->lt($endsAt)
            && $block->ends_at->gt($startsAt));
    }
}
