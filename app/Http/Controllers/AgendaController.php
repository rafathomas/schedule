<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function __invoke(Request $request, CurrentCompany $currentCompany): Response
    {
        Gate::authorize('viewAny', Appointment::class);
        $company = $currentCompany->getOrFail();
        $timezone = (string) $company->timezone;
        $view = in_array($request->string('view')->toString(), ['day', 'week'], true)
            ? $request->string('view')->toString()
            : 'week';
        $selectedDate = $this->date($request->string('date')->toString(), $timezone);
        CarbonImmutable::setLocale('pt_BR');
        $periodStart = ($view === 'day' ? $selectedDate->startOfDay() : $selectedDate->startOfWeek())->utc();
        $periodEnd = ($view === 'day' ? $selectedDate->endOfDay() : $selectedDate->endOfWeek())->utc();
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $canManage = $user->hasCompanyRole($company->getKey(), ['owner', 'admin']);
        $professionalId = $canManage
            ? $this->selectedProfessionalId($request->string('professional')->toString())
            : Professional::query()->where('user_id', $user->getKey())->value('id');

        $appointments = Appointment::query()
            ->with(['customer:id,uuid,name,phone,whatsapp,email', 'professional:id,uuid,name', 'service:id,uuid,name,category', 'events.actor:id,name'])
            ->whereBetween('scheduled_at', [$periodStart, $periodEnd])
            ->when($professionalId !== null, fn (Builder $query) => $query->where('professional_id', $professionalId))
            ->when(! $canManage && $professionalId === null, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Appointment $appointment): array => $this->appointment($appointment, $timezone, $canManage, $user));

        $blocks = BlockedPeriod::query()
            ->with('professional:id,uuid,name')
            ->where('starts_at', '<=', $periodEnd)
            ->where('ends_at', '>=', $periodStart)
            ->when($professionalId !== null, fn (Builder $query) => $query->where(function (Builder $query) use ($professionalId): void {
                $query->whereNull('professional_id')->orWhere('professional_id', $professionalId);
            }))
            ->when(! $canManage && $professionalId === null, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (BlockedPeriod $block): array => $this->block($block, $timezone));

        $professionals = Professional::query()
            ->where('status', 'active')
            ->when(! $canManage, fn (Builder $query) => $query->where('user_id', $user->getKey()))
            ->with('services:id,uuid,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Professional $professional): array => [
                'uuid' => $professional->uuid,
                'name' => $professional->name,
                'service_uuids' => $professional->services->pluck('uuid')->values(),
            ]);

        $days = collect(range(0, $view === 'day' ? 0 : 6))->map(function (int $offset) use ($selectedDate, $view): array {
            $date = ($view === 'day' ? $selectedDate : $selectedDate->startOfWeek())->addDays($offset);

            return [
                'date' => $date->format('Y-m-d'),
                'weekday' => Str::ucfirst($date->translatedFormat('D')),
                'day' => $date->format('d'),
                'month' => $date->translatedFormat('M'),
                'is_today' => $date->isToday(),
            ];
        });

        return Inertia::render('agenda/index', [
            'view' => $view,
            'selectedDate' => $selectedDate->format('Y-m-d'),
            'periodLabel' => $this->periodLabel($selectedDate, $view),
            'days' => $days,
            'appointments' => $appointments,
            'blocks' => $blocks,
            'professionals' => $professionals,
            'services' => Service::query()->where('is_active', true)->orderBy('name')->get(['uuid', 'name', 'duration_minutes', 'price']),
            'customers' => $canManage ? Customer::query()->orderBy('name')->get(['uuid', 'name', 'phone']) : [],
            'selectedProfessional' => $request->string('professional')->toString() ?: null,
            'canManage' => $canManage,
            'timezone' => $timezone,
        ]);
    }

    private function date(string $value, string $timezone): CarbonImmutable
    {
        if ($value === '') {
            return CarbonImmutable::today($timezone);
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        } catch (InvalidFormatException) {
            return CarbonImmutable::today($timezone);
        }
    }

    private function selectedProfessionalId(string $uuid): ?int
    {
        if ($uuid === '') {
            return null;
        }

        return Professional::query()->where('uuid', $uuid)->value('id');
    }

    /** @return array<string, mixed> */
    private function appointment(Appointment $appointment, string $timezone, bool $canManage, User $user): array
    {
        $startsAt = $appointment->scheduled_at->setTimezone($timezone);
        $endsAt = $appointment->ends_at->setTimezone($timezone);
        $status = $appointment->status;

        return [
            'uuid' => $appointment->uuid,
            'date' => $startsAt->format('Y-m-d'),
            'time' => $startsAt->format('H:i'),
            'end_time' => $endsAt->format('H:i'),
            'scheduled_at' => $startsAt->toIso8601String(),
            'duration_minutes' => $appointment->duration_minutes,
            'price' => $appointment->price,
            'status' => $status->value,
            'status_label' => $status->label(),
            'notes' => $appointment->notes,
            'cancellation_reason' => $appointment->cancellation_reason,
            'customer' => $appointment->customer->only(['uuid', 'name', 'phone', 'whatsapp', 'email']),
            'professional' => $appointment->professional->only(['uuid', 'name']),
            'service' => $appointment->service->only(['uuid', 'name', 'category']),
            'can_edit' => $canManage && $status !== AppointmentStatus::Cancelled,
            'can_update_status' => $canManage || $appointment->professional->user_id === $user->getKey(),
            'available_statuses' => $this->availableStatuses($status),
            'events' => $appointment->events->map(fn ($event): array => [
                'type' => $event->type,
                'actor' => $event->actor?->name,
                'created_at' => $event->created_at->setTimezone($timezone)->format('d/m/Y H:i'),
            ])->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function block(BlockedPeriod $block, string $timezone): array
    {
        $startsAt = $block->starts_at->setTimezone($timezone);
        $endsAt = $block->ends_at->setTimezone($timezone);

        return [
            'uuid' => $block->uuid,
            'date' => $startsAt->format('Y-m-d'),
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'ends_at' => $endsAt->format('Y-m-d\TH:i'),
            'time' => $startsAt->format('H:i'),
            'end_time' => $endsAt->format('H:i'),
            'reason' => $block->reason,
            'professional' => $block->professional?->only(['uuid', 'name']),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function availableStatuses(AppointmentStatus $status): array
    {
        $targets = match ($status) {
            AppointmentStatus::Pending, AppointmentStatus::AwaitingConfirmation => [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled],
            AppointmentStatus::Confirmed => [AppointmentStatus::Completed, AppointmentStatus::NoShow, AppointmentStatus::Cancelled],
            default => [],
        };

        return array_map(fn (AppointmentStatus $target): array => [
            'value' => $target->value,
            'label' => $target->label(),
        ], $targets);
    }

    private function periodLabel(CarbonImmutable $date, string $view): string
    {
        if ($view === 'day') {
            return Str::ucfirst($date->translatedFormat('l, d \d\e F'));
        }

        $start = $date->startOfWeek();
        $end = $date->endOfWeek();

        return $start->format('d/m').' – '.$end->format('d/m/Y');
    }
}
