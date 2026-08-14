<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AppointmentStatus;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Enums\NotificationLogStatus;
use App\Models\Company;
use App\Models\NotificationLog;
use App\Notifications\AppointmentMessageNotification;
use App\Services\Appointments\AppointmentEventRecorder;
use App\Services\Messaging\WhatsAppService;
use App\Support\Tenancy\CurrentCompany;
use App\ValueObjects\AppointmentMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendAppointmentMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public readonly int $notificationLogId)
    {
        $this->onQueue('notifications');
    }

    public function handle(
        CurrentCompany $currentCompany,
        WhatsAppService $whatsApp,
        AppointmentEventRecorder $events,
    ): void {
        $rawCompanyId = NotificationLog::withoutGlobalScopes()->whereKey($this->notificationLogId)->value('company_id');

        if (! is_numeric($rawCompanyId)) {
            return;
        }

        $companyId = (int) $rawCompanyId;
        $company = Company::query()->findOrFail($companyId);
        $currentCompany->set($company);

        try {
            $log = NotificationLog::query()
                ->with(['appointment.customer', 'appointment.company'])
                ->whereKey($this->notificationLogId)
                ->firstOrFail();

            if (in_array($log->status, [NotificationLogStatus::Sent, NotificationLogStatus::Skipped], true)) {
                return;
            }

            $log->update([
                'status' => NotificationLogStatus::Processing,
                'attempt' => $log->attempt + 1,
                'error' => null,
            ]);

            if ($log->recipient === null || $log->recipient === '') {
                $log->update([
                    'status' => NotificationLogStatus::Skipped,
                    'error' => 'Destinatário não informado.',
                ]);

                return;
            }

            $providerMessageId = match ($log->channel) {
                CommunicationChannel::WhatsApp => $whatsApp->sendMessage($log->recipient, $log->message)->messageId,
                CommunicationChannel::Mail => $this->sendMail($log),
            };

            $log->update([
                'status' => NotificationLogStatus::Sent,
                'sent_at' => now(),
                'provider_message_id' => $providerMessageId,
            ]);

            if ($log->type === CommunicationType::ConfirmationRequest) {
                $appointment = $log->appointment;

                if ($appointment->status === AppointmentStatus::Pending) {
                    $appointment->update(['status' => AppointmentStatus::AwaitingConfirmation]);
                }

                $events->record($appointment, 'confirmation_sent', null, [
                    'channel' => $log->channel->value,
                    'notification_log_id' => $log->getKey(),
                ]);
            }
        } catch (Throwable $exception) {
            NotificationLog::withoutGlobalScopes()->whereKey($this->notificationLogId)->update([
                'status' => NotificationLogStatus::Failed->value,
                'error' => mb_substr($exception->getMessage(), 0, 4000),
            ]);

            throw $exception;
        } finally {
            $currentCompany->forget();
        }
    }

    private function sendMail(NotificationLog $log): string
    {
        $metadata = $log->metadata ?? [];
        $message = new AppointmentMessage(
            subject: (string) ($metadata['subject'] ?? 'Atualização do seu agendamento'),
            body: $log->message,
            actionUrl: isset($metadata['action_url']) ? (string) $metadata['action_url'] : null,
            actionLabel: isset($metadata['action_label']) ? (string) $metadata['action_label'] : null,
            secondaryActionUrl: isset($metadata['secondary_action_url']) ? (string) $metadata['secondary_action_url'] : null,
            secondaryActionLabel: isset($metadata['secondary_action_label']) ? (string) $metadata['secondary_action_label'] : null,
        );

        Notification::route('mail', $log->recipient)->notifyNow(new AppointmentMessageNotification($message));

        return 'mail';
    }
}
