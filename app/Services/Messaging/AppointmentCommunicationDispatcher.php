<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Enums\NotificationLogStatus;
use App\Jobs\SendAppointmentMessageJob;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Models\ReminderSetting;
use App\ValueObjects\AppointmentMessage;

class AppointmentCommunicationDispatcher
{
    public function __construct(private readonly AppointmentMessageFactory $messages) {}

    public function dispatch(
        Appointment $appointment,
        CommunicationType $type,
        ?int $offsetMinutes = null,
    ): int {
        $appointment->loadMissing(['company', 'customer', 'professional', 'service']);
        $settings = ReminderSetting::query()->firstOrCreate(
            ['company_id' => $appointment->company_id],
            [
                'confirmation_enabled' => true,
                'confirmation_minutes_before' => 1440,
                'reminders_enabled' => true,
                'reminder_offsets' => [2880, 1440, 120],
                'channels' => [CommunicationChannel::WhatsApp->value, CommunicationChannel::Mail->value],
            ],
        );
        $message = $this->messages->make($appointment, $type);
        $scheduledFor = $offsetMinutes === null
            ? null
            : $this->messages->scheduledFor($appointment, $offsetMinutes);
        $queued = 0;

        foreach ($settings->normalizedChannels() as $channel) {
            $recipient = match ($channel) {
                CommunicationChannel::WhatsApp => $appointment->customer->whatsapp ?: $appointment->customer->phone,
                CommunicationChannel::Mail => $appointment->customer->email,
            };

            if ($this->queue(
                appointment: $appointment,
                type: $type,
                channel: $channel,
                recipient: $recipient,
                message: $message,
                identity: $this->identity($appointment, $type, $channel, $offsetMinutes),
                scheduledFor: $scheduledFor,
                metadata: ['offset_minutes' => $offsetMinutes, 'audience' => 'customer'],
            )) {
                $queued++;
            }
        }

        return $queued;
    }

    public function dispatchCompanyCancellation(Appointment $appointment): int
    {
        $appointment->loadMissing(['company.users', 'customer', 'professional', 'service']);
        $startsAt = $appointment->scheduled_at->setTimezone((string) $appointment->company->timezone);
        $message = new AppointmentMessage(
            subject: 'Cliente cancelou um atendimento',
            body: implode("\n", [
                "{$appointment->customer->name} cancelou um atendimento.",
                '',
                'Data: '.$startsAt->format('d/m/Y'),
                'Horário: '.$startsAt->format('H:i'),
                'Serviço: '.$appointment->service->name,
                'Profissional: '.$appointment->professional->name,
            ]),
            actionUrl: route('agenda.index'),
            actionLabel: 'Abrir agenda',
        );
        $queued = 0;

        $recipients = $appointment->company->users()
            ->wherePivot('is_active', true)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->get();

        foreach ($recipients as $user) {
            $identity = implode(':', [
                'company-cancellation',
                $appointment->uuid,
                (string) $user->getKey(),
            ]);

            if ($this->queue(
                appointment: $appointment,
                type: CommunicationType::Cancelled,
                channel: CommunicationChannel::Mail,
                recipient: $user->email,
                message: $message,
                identity: $identity,
                scheduledFor: null,
                metadata: ['audience' => 'company', 'user_id' => $user->getKey()],
            )) {
                $queued++;
            }
        }

        return $queued;
    }

    /** @param array<string, mixed> $metadata */
    private function queue(
        Appointment $appointment,
        CommunicationType $type,
        CommunicationChannel $channel,
        ?string $recipient,
        AppointmentMessage $message,
        string $identity,
        mixed $scheduledFor,
        array $metadata,
    ): bool {
        $log = NotificationLog::query()->firstOrCreate(
            ['idempotency_key' => hash('sha256', $identity)],
            [
                'company_id' => $appointment->company_id,
                'appointment_id' => $appointment->getKey(),
                'type' => $type,
                'channel' => $channel,
                'status' => NotificationLogStatus::Pending,
                'recipient' => $recipient,
                'message' => $message->body,
                'scheduled_for' => $scheduledFor,
                'metadata' => array_filter([
                    ...$metadata,
                    'subject' => $message->subject,
                    'action_url' => $message->actionUrl,
                    'action_label' => $message->actionLabel,
                    'secondary_action_url' => $message->secondaryActionUrl,
                    'secondary_action_label' => $message->secondaryActionLabel,
                ], fn (mixed $value): bool => $value !== null),
            ],
        );

        if (! $log->wasRecentlyCreated) {
            return false;
        }

        SendAppointmentMessageJob::dispatch((int) $log->getKey())->afterCommit();

        return true;
    }

    private function identity(
        Appointment $appointment,
        CommunicationType $type,
        CommunicationChannel $channel,
        ?int $offsetMinutes,
    ): string {
        return implode(':', [
            $appointment->uuid,
            $type->value,
            $channel->value,
            $appointment->scheduled_at->getTimestamp(),
            $offsetMinutes ?? 'immediate',
        ]);
    }
}
