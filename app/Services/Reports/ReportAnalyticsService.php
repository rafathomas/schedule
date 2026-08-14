<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\ValueObjects\ReportPeriod;
use Carbon\CarbonImmutable;

class ReportAnalyticsService
{
    /** @return array<string, mixed> */
    public function build(
        ReportPeriod $period,
        ?int $professionalId = null,
        bool $restrictToProfessional = false,
    ): array {
        $statusCounts = array_fill_keys(array_map(
            fn (AppointmentStatus $status): string => $status->value,
            AppointmentStatus::cases(),
        ), 0);
        $timeline = $this->emptyTimeline($period);
        $services = [];
        $professionals = [];
        $newCustomers = [];
        $recurringCustomers = [];
        $appointments = 0;
        $revenueCents = 0;
        $confirmed = 0;

        Appointment::query()
            ->with([
                'customer:id,name,first_appointment_at',
                'service:id,name',
                'professional:id,name',
            ])
            ->whereBetween('scheduled_at', [$period->startsAt, $period->endsAt])
            ->when($professionalId !== null, fn ($query) => $query->where('professional_id', $professionalId))
            ->when($restrictToProfessional && $professionalId === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->lazyById(500)
            ->each(function (Appointment $appointment) use (
                $period,
                &$appointments,
                &$revenueCents,
                &$confirmed,
                &$statusCounts,
                &$timeline,
                &$services,
                &$professionals,
                &$newCustomers,
                &$recurringCustomers,
            ): void {
                $appointments++;
                $statusCounts[$appointment->status->value]++;
                $isRevenueEligible = ! in_array($appointment->status, [
                    AppointmentStatus::Cancelled,
                    AppointmentStatus::NoShow,
                ], true);
                $priceCents = (int) round(((float) $appointment->price) * 100);

                if ($isRevenueEligible) {
                    $revenueCents += $priceCents;
                }

                if (
                    $appointment->confirmed_at !== null
                    || in_array($appointment->status, [AppointmentStatus::Confirmed, AppointmentStatus::Completed], true)
                ) {
                    $confirmed++;
                }

                $bucket = $this->bucketKey($appointment->scheduled_at, $period);

                if (isset($timeline[$bucket])) {
                    $timeline[$bucket]['appointments']++;

                    if ($isRevenueEligible) {
                        $timeline[$bucket]['revenue_cents'] += $priceCents;
                    }
                }

                $firstAppointment = $appointment->customer->first_appointment_at;

                if ($firstAppointment !== null && $firstAppointment->betweenIncluded($period->startsAt, $period->endsAt)) {
                    $newCustomers[$appointment->customer_id] = true;
                } elseif ($firstAppointment !== null && $firstAppointment->lt($period->startsAt)) {
                    $recurringCustomers[$appointment->customer_id] = true;
                }

                if ($appointment->status === AppointmentStatus::Cancelled) {
                    return;
                }

                $service = $services[$appointment->service_id] ?? [
                    'name' => $appointment->service->name,
                    'appointments' => 0,
                    'revenue_cents' => 0,
                ];
                $service['appointments']++;
                $service['revenue_cents'] += $isRevenueEligible ? $priceCents : 0;
                $services[$appointment->service_id] = $service;

                $professional = $professionals[$appointment->professional_id] ?? [
                    'name' => $appointment->professional->name,
                    'appointments' => 0,
                    'completed' => 0,
                ];
                $professional['appointments']++;
                $professional['completed'] += $appointment->status === AppointmentStatus::Completed ? 1 : 0;
                $professionals[$appointment->professional_id] = $professional;
            });

        return [
            'period' => [
                'preset' => $period->preset,
                'label' => $period->label,
                'start_date' => $period->localStart()->format('Y-m-d'),
                'end_date' => $period->localEnd()->format('Y-m-d'),
            ],
            'metrics' => [
                'appointments' => $appointments,
                'expected_revenue_cents' => $revenueCents,
                'completed' => $statusCounts[AppointmentStatus::Completed->value],
                'cancelled' => $statusCounts[AppointmentStatus::Cancelled->value],
                'no_show' => $statusCounts[AppointmentStatus::NoShow->value],
                'confirmation_rate' => $appointments === 0 ? 0 : round(($confirmed / $appointments) * 100, 1),
                'new_customers' => count($newCustomers),
                'recurring_customers' => count($recurringCustomers),
            ],
            'timeline' => array_values($timeline),
            'status_distribution' => array_map(
                fn (AppointmentStatus $status): array => [
                    'status' => $status->value,
                    'label' => $status->label(),
                    'value' => $statusCounts[$status->value],
                ],
                AppointmentStatus::cases(),
            ),
            'top_services' => $this->rank($services, 'appointments'),
            'top_professionals' => $this->rank($professionals, 'appointments'),
        ];
    }

    /** @return array<string, array{key: string, label: string, appointments: int, revenue_cents: int}> */
    private function emptyTimeline(ReportPeriod $period): array
    {
        $timeline = [];
        $weekly = $period->dayCount() > 62;
        $cursor = $period->localStart()->startOfDay();
        $end = $period->localEnd()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $weekly ? $cursor->format('Y-m-d') : $cursor->format('Y-m-d');
            $timeline[$key] = [
                'key' => $key,
                'label' => $weekly ? 'Sem. '.$cursor->format('d/m') : $cursor->format('d/m'),
                'appointments' => 0,
                'revenue_cents' => 0,
            ];
            $cursor = $weekly ? $cursor->addDays(7) : $cursor->addDay();
        }

        return $timeline;
    }

    private function bucketKey(CarbonImmutable $scheduledAt, ReportPeriod $period): string
    {
        $localDate = $scheduledAt->setTimezone($period->timezone)->startOfDay();

        if ($period->dayCount() <= 62) {
            return $localDate->format('Y-m-d');
        }

        $days = (int) $period->localStart()->startOfDay()->diffInDays($localDate);

        return $period->localStart()->startOfDay()->addDays(intdiv($days, 7) * 7)->format('Y-m-d');
    }

    /**
     * @param  array<int, array<string, int|string>>  $items
     * @return array<int, array<string, int|string>>
     */
    private function rank(array $items, string $metric): array
    {
        usort($items, fn (array $left, array $right): int => ($right[$metric] <=> $left[$metric]) ?: ((string) $left['name'] <=> (string) $right['name']));

        return array_slice($items, 0, 5);
    }
}
