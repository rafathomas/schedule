<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Enums\CommunicationType;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Services\Appointments\AppointmentEventRecorder;
use App\Services\Appointments\CustomerAppointmentMetrics;
use App\Services\AvailabilityService;
use App\Services\Messaging\AppointmentCommunicationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

readonly class UpdateAppointmentAction
{
    public function __construct(
        private AvailabilityService $availability,
        private AppointmentEventRecorder $events,
        private CustomerAppointmentMetrics $metrics,
        private AppointmentCommunicationDispatcher $communications,
    ) {}

    /** @param array<string, mixed> $data
     * @throws \Throwable
     */
    public function execute(Appointment $appointment, array $data, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $data, $actor): Appointment {
            $lockedAppointment = Appointment::query()->whereKey($appointment->getKey())->lockForUpdate()->firstOrFail();
            $targetProfessional = Professional::query()->where('uuid', $data['professional_uuid'])->firstOrFail();
            Professional::query()
                ->whereIn(
                    'id',
                    array_unique([(int) $lockedAppointment->professional_id, (int) $targetProfessional->getKey()]),
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $service = Service::query()->where('uuid', $data['service_uuid'])->firstOrFail();
            $customer = Customer::query()->where('uuid', $data['customer_uuid'])->firstOrFail();

            if (! $targetProfessional->services()->whereKey($service->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'service_uuid' => 'Este profissional não realiza o serviço selecionado.',
                ]);
            }

            if (! $this->availability->isAvailable(
                $service,
                $targetProfessional,
                $data['date'],
                $data['time'],
                $lockedAppointment,
            )) {
                throw new SlotUnavailableException;
            }

            $previousCustomer = $lockedAppointment->customer;
            $previousScheduledAt = $lockedAppointment->scheduled_at;
            $scheduledAt = $this->availability->scheduledAtUtc($data['date'], $data['time']);
            $duration = (int) $service->duration_minutes;
            $buffer = (int) $service->buffer_minutes;

            $lockedAppointment->update([
                'customer_id' => $customer->getKey(),
                'professional_id' => $targetProfessional->getKey(),
                'service_id' => $service->getKey(),
                'scheduled_at' => $scheduledAt,
                'ends_at' => $scheduledAt->addMinutes($duration),
                'blocks_until' => $scheduledAt->addMinutes($duration + $buffer),
                'duration_minutes' => $duration,
                'price' => $service->price,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->events->record($lockedAppointment, 'rescheduled', $actor, [
                'from' => $previousScheduledAt->toIso8601String(),
                'to' => $scheduledAt->toIso8601String(),
            ]);
            $this->metrics->refresh($previousCustomer);

            if ($previousCustomer->isNot($customer)) {
                $this->metrics->refresh($customer);
            }

            $this->communications->dispatch($lockedAppointment, CommunicationType::Rescheduled);

            return $lockedAppointment->refresh();
        }, 3);
    }
}
