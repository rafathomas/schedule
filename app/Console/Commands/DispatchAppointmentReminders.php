<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Enums\CommunicationType;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\ReminderSetting;
use App\Services\Messaging\AppointmentCommunicationDispatcher;
use App\Support\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class DispatchAppointmentReminders extends Command
{
    protected $signature = 'appointments:dispatch-reminders';

    protected $description = 'Enfileira confirmações e lembretes de agendamentos no horário configurado.';

    public function handle(
        CurrentCompany $currentCompany,
        AppointmentCommunicationDispatcher $dispatcher,
    ): int {
        $now = CarbonImmutable::now();
        $queued = 0;

        Company::query()
            ->where('status', 'active')
            ->lazyById(100)
            ->each(function (Company $company) use ($currentCompany, $dispatcher, $now, &$queued): void {
                $currentCompany->set($company);

                try {
                    $settings = ReminderSetting::query()->firstOrCreate(['company_id' => $company->getKey()]);
                    $offsets = $settings->normalizedOffsets();
                    $candidateOffsets = $offsets;

                    if ($settings->confirmation_enabled) {
                        $candidateOffsets[] = $settings->confirmation_minutes_before;
                    }

                    $maximumOffset = max($candidateOffsets === [] ? [0] : $candidateOffsets);

                    Appointment::query()
                        ->whereIn('status', [
                            AppointmentStatus::Pending->value,
                            AppointmentStatus::AwaitingConfirmation->value,
                            AppointmentStatus::Confirmed->value,
                        ])
                        ->where('scheduled_at', '>', $now)
                        ->where('scheduled_at', '<=', $now->addMinutes($maximumOffset + 5))
                        ->lazyById(100)
                        ->each(
                            function (Appointment $appointment) use (
                                $settings,
                                $dispatcher,
                                $now,
                                $offsets,
                                &$queued,
                            ): void {
                                if (
                                    $settings->confirmation_enabled
                                    && in_array(
                                        $appointment->status,
                                        [AppointmentStatus::Pending, AppointmentStatus::AwaitingConfirmation],
                                        true,
                                    )
                                    && $this->isDue($appointment, $settings->confirmation_minutes_before, $now)
                                ) {
                                    $queued += $dispatcher->dispatch(
                                        $appointment,
                                        CommunicationType::ConfirmationRequest,
                                        $settings->confirmation_minutes_before,
                                    );
                                }

                                if (! $settings->reminders_enabled) {
                                    return;
                                }

                                foreach ($offsets as $offset) {
                                    $confirmationAlreadyCoversOffset = $settings->confirmation_enabled
                                        && $offset === $settings->confirmation_minutes_before
                                        && in_array(
                                            $appointment->status,
                                            [AppointmentStatus::Pending, AppointmentStatus::AwaitingConfirmation],
                                            true,
                                        );

                                    if ($confirmationAlreadyCoversOffset) {
                                        continue;
                                    }

                                    if ($this->isDue($appointment, $offset, $now)) {
                                        $queued += $dispatcher->dispatch(
                                            $appointment,
                                            CommunicationType::Reminder,
                                            $offset,
                                        );
                                    }
                                }
                            },
                        );
                } finally {
                    $currentCompany->forget();
                }
            });

        $this->components->info("{$queued} comunicação(ões) enfileirada(s).");

        return self::SUCCESS;
    }

    private function isDue(Appointment $appointment, int $offsetMinutes, CarbonImmutable $now): bool
    {
        $dueAt = $appointment->scheduled_at->subMinutes($offsetMinutes);

        return $dueAt->lte($now) && $dueAt->gt($now->subMinutes(5));
    }
}
