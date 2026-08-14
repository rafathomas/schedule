<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\CommunicationType;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Appointments\AppointmentEventRecorder;
use App\Services\Appointments\CustomerAppointmentMetrics;
use App\Services\Messaging\AppointmentCommunicationDispatcher;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class TransitionAppointmentStatusAction
{
    public function __construct(
        private AppointmentEventRecorder $events,
        private CustomerAppointmentMetrics $metrics,
        private AppointmentCommunicationDispatcher $communications,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(Appointment $appointment,
        AppointmentStatus $target,
        ?User $actor,
        ?string $reason = null,
    ): Appointment {
        return DB::transaction(function () use ($appointment, $target, $actor, $reason): Appointment {
            $locked = Appointment::query()->whereKey($appointment->getKey())->lockForUpdate()->firstOrFail();
            $current = $locked->status;

            if (! $this->canTransition($current, $target)) {
                throw new DomainException('Esta alteração de status não é permitida.');
            }

            $attributes = [
                'status' => $target,
                'confirmed_at' => $target === AppointmentStatus::Confirmed ? now() : $locked->confirmed_at,
                'cancelled_at' => $target === AppointmentStatus::Cancelled ? now() : $locked->cancelled_at,
                'completed_at' => $target === AppointmentStatus::Completed ? now() : $locked->completed_at,
                'no_show_at' => $target === AppointmentStatus::NoShow ? now() : $locked->no_show_at,
                'cancellation_reason' => $target ===
                AppointmentStatus::Cancelled ? $reason : $locked->cancellation_reason,
            ];

            $locked->update($attributes);
            $this->events->record($locked, $target->value, $actor, $reason === null ? [] : ['reason' => $reason]);
            $this->metrics->refresh($locked->customer);

            if ($target === AppointmentStatus::Confirmed) {
                $this->communications->dispatch($locked, CommunicationType::Confirmed);
            }

            if ($target === AppointmentStatus::Cancelled) {
                $this->communications->dispatch($locked, CommunicationType::Cancelled);

                if ($actor === null) {
                    $this->communications->dispatchCompanyCancellation($locked);
                }
            }

            return $locked->refresh();
        }, 3);
    }

    private function canTransition(AppointmentStatus $current, AppointmentStatus $target): bool
    {
        return match ($current) {
            AppointmentStatus::Pending, AppointmentStatus::AwaitingConfirmation => in_array($target, [
                AppointmentStatus::Confirmed,
                AppointmentStatus::Cancelled,
            ], true),
            AppointmentStatus::Confirmed => in_array($target, [
                AppointmentStatus::Completed,
                AppointmentStatus::NoShow,
                AppointmentStatus::Cancelled,
            ], true),
            AppointmentStatus::Cancelled, AppointmentStatus::Completed, AppointmentStatus::NoShow => false,
        };
    }
}
