<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\CommunicationType;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Services\Appointments\AppointmentEventRecorder;
use App\Services\Appointments\AppointmentTokenService;
use App\Services\Appointments\CustomerAppointmentMetrics;
use App\Services\AvailabilityService;
use App\Services\Messaging\AppointmentCommunicationDispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class CreatePublicAppointmentAction
{
    public function __construct(
        private AvailabilityService $availability,
        private AppointmentEventRecorder $events,
        private CustomerAppointmentMetrics $metrics,
        private AppointmentTokenService $tokens,
        private AppointmentCommunicationDispatcher $communications,
    ) {}

    /** @param array<string, mixed> $data
     * @throws Throwable
     */
    public function execute(array $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            $service = Service::query()->where('uuid', $data['service_uuid'])->firstOrFail();
            $professionals = $this->lockEligibleProfessionals($service, $data['professional_uuid'] ?? null);
            $professional = $professionals->first(fn (Professional $candidate): bool => $this->availability->isAvailable(
                $service,
                $candidate,
                (string) $data['date'],
                (string) $data['time'],
            ));

            if (! $professional instanceof Professional) {
                throw new SlotUnavailableException;
            }

            $customer = $this->customer($data);
            $scheduledAt = $this->availability->scheduledAtUtc((string) $data['date'], (string) $data['time']);
            $duration = (int) $service->duration_minutes;
            $buffer = (int) $service->buffer_minutes;
            $token = $this->tokens->generate();
            $appointment = Appointment::query()->create([
                'customer_id' => $customer->getKey(),
                'professional_id' => $professional->getKey(),
                'service_id' => $service->getKey(),
                'scheduled_at' => $scheduledAt,
                'ends_at' => $scheduledAt->addMinutes($duration),
                'blocks_until' => $scheduledAt->addMinutes($duration + $buffer),
                'duration_minutes' => $duration,
                'price' => $service->price,
                'status' => AppointmentStatus::Pending,
                'confirmation_token' => $token['hash'],
                'confirmation_token_encrypted' => $token['plain'],
                'created_by' => null,
            ]);

            $this->events->record($appointment, 'created', null, [
                'source' => 'public',
                'scheduled_at' => $scheduledAt->toIso8601String(),
            ]);
            $this->metrics->refresh($customer);
            $this->communications->dispatch($appointment, CommunicationType::Created);

            return $appointment;
        }, 3);
    }

    /** @return Collection<int, Professional> */
    private function lockEligibleProfessionals(Service $service, mixed $professionalUuid): Collection
    {
        return Professional::query()
            ->where('status', 'active')
            ->whereHas('services', fn ($query) => $query->whereKey($service->getKey()))
            ->when(is_string($professionalUuid) && $professionalUuid !== '', fn ($query) => $query->where('uuid', $professionalUuid))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /** @param array<string, mixed> $data */
    private function customer(array $data): Customer
    {
        $whatsapp = (string) $data['whatsapp'];
        $customer = Customer::query()
            ->where(fn ($query) => $query->where('whatsapp', $whatsapp)->orWhere('phone', $whatsapp))
            ->first();

        if ($customer === null) {
            return Customer::query()->create([
                'name' => $data['name'],
                'phone' => $whatsapp,
                'whatsapp' => $whatsapp,
                'email' => $data['email'] ?? null,
            ]);
        }

        $customer->update([
            'name' => $data['name'],
            'whatsapp' => $whatsapp,
            'email' => ($data['email'] ?? null) ?: $customer->email,
        ]);

        return $customer;
    }
}
