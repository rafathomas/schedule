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
use App\Models\User;
use App\Services\Appointments\AppointmentEventRecorder;
use App\Services\Appointments\AppointmentTokenService;
use App\Services\Appointments\CustomerAppointmentMetrics;
use App\Services\AvailabilityService;
use App\Services\Messaging\AppointmentCommunicationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

readonly class CreateAppointmentAction
{
    public function __construct(
        private AvailabilityService $availability,
        private AppointmentEventRecorder $events,
        private CustomerAppointmentMetrics $metrics,
        private AppointmentTokenService $tokens,
        private AppointmentCommunicationDispatcher $communications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function execute(array $data, User $actor): Appointment
    {
        return DB::transaction(function () use ($data, $actor): Appointment {
            $professional = Professional::query()
                ->where('uuid', $data['professional_uuid'])
                ->lockForUpdate()
                ->firstOrFail();
            $service = Service::query()->where('uuid', $data['service_uuid'])->firstOrFail();

            $this->ensureServiceIsOffered($professional, $service);

            if (! $this->availability->isAvailable($service, $professional, $data['date'], $data['time'])) {
                throw new SlotUnavailableException;
            }

            $customer = $this->customer($data);
            $scheduledAt = $this->availability->scheduledAtUtc($data['date'], $data['time']);
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
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->getKey(),
            ]);

            $this->events->record($appointment, 'created', $actor, [
                'source' => 'admin',
                'scheduled_at' => $scheduledAt->toIso8601String(),
            ]);
            $this->metrics->refresh($customer);
            $this->communications->dispatch($appointment, CommunicationType::Created);

            return $appointment;
        }, 3);
    }

    private function ensureServiceIsOffered(Professional $professional, Service $service): void
    {
        if (! $professional->services()->whereKey($service->getKey())->exists()) {
            throw ValidationException::withMessages([
                'service_uuid' => 'Este profissional não realiza o serviço selecionado.',
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function customer(array $data): Customer
    {
        if ($data['customer_mode'] === 'existing') {
            return Customer::query()->where('uuid', $data['customer_uuid'])->firstOrFail();
        }

        /** @var array<string, mixed> $newCustomer */
        $newCustomer = $data['new_customer'];

        return Customer::query()->create([
            'name' => $newCustomer['name'],
            'phone' => $newCustomer['phone'],
            'whatsapp' => $newCustomer['whatsapp'] ?? $newCustomer['phone'],
            'email' => $newCustomer['email'] ?? null,
        ]);
    }
}
