<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Models\Appointment;
use Illuminate\Support\Str;

class AppointmentTokenService
{
    /** @return array{hash: string, plain: string} */
    public function generate(): array
    {
        $plain = Str::random(64);

        return [
            'hash' => hash('sha256', $plain),
            'plain' => $plain,
        ];
    }

    public function getOrIssue(Appointment $appointment): string
    {
        if (is_string($appointment->confirmation_token_encrypted)
            && $appointment->confirmation_token_encrypted !== '') {
            return $appointment->confirmation_token_encrypted;
        }

        $token = $this->generate();
        $appointment->forceFill([
            'confirmation_token' => $token['hash'],
            'confirmation_token_encrypted' => $token['plain'],
        ])->save();

        return $token['plain'];
    }
}
