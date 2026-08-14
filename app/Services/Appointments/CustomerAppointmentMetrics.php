<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;

class CustomerAppointmentMetrics
{
    public function refresh(Customer $customer): void
    {
        $query = Appointment::query()
            ->where('customer_id', $customer->getKey())
            ->where('status', '!=', AppointmentStatus::Cancelled->value);

        $customer->update([
            'first_appointment_at' => (clone $query)->min('scheduled_at'),
            'last_appointment_at' => (clone $query)->max('scheduled_at'),
        ]);
    }
}
