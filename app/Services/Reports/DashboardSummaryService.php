<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\ValueObjects\ReportPeriod;
use Carbon\CarbonImmutable;

class DashboardSummaryService
{
    public function __construct(private readonly ReportAnalyticsService $analytics) {}

    /** @return array<string, mixed> */
    public function build(
        Company $company,
        ?int $professionalId = null,
        bool $restrictToProfessional = false,
    ): array {
        $timezone = (string) $company->timezone;
        $today = CarbonImmutable::today($timezone);
        $period = ReportPeriod::fromInput('today', null, null, $timezone);
        $todayAnalytics = $this->analytics->build($period, $professionalId, $restrictToProfessional);
        $todayMetrics = $todayAnalytics['metrics'];

        foreach ($todayAnalytics['status_distribution'] as $status) {
            $todayMetrics[$status['status']] = $status['value'];
        }

        $upcoming = Appointment::query()
            ->with(['customer:id,name', 'professional:id,name', 'service:id,name'])
            ->whereIn('status', [
                AppointmentStatus::Pending->value,
                AppointmentStatus::AwaitingConfirmation->value,
                AppointmentStatus::Confirmed->value,
            ])
            ->where('scheduled_at', '>=', CarbonImmutable::now())
            ->when($professionalId !== null, fn ($query) => $query->where('professional_id', $professionalId))
            ->when($restrictToProfessional && $professionalId === null, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(function (Appointment $appointment) use ($timezone, $today): array {
                $startsAt = $appointment->scheduled_at->setTimezone($timezone);

                return [
                    'uuid' => $appointment->uuid,
                    'customer' => $appointment->customer->name,
                    'service' => $appointment->service->name,
                    'professional' => $appointment->professional->name,
                    'date' => $startsAt->isSameDay($today) ? 'Hoje' : $startsAt->format('d/m'),
                    'time' => $startsAt->format('H:i'),
                    'status' => $appointment->status->value,
                    'status_label' => $appointment->status->label(),
                ];
            });

        return [
            'today' => $todayMetrics,
            'upcoming' => $upcoming,
        ];
    }
}
