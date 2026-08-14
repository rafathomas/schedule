<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\User;

class AppointmentEventRecorder
{
    /** @param array<string, mixed> $metadata */
    public function record(Appointment $appointment, string $type, ?User $actor, array $metadata = []): AppointmentEvent
    {
        return $appointment->events()->create([
            'company_id' => $appointment->company_id,
            'actor_id' => $actor?->getKey(),
            'type' => $type,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
