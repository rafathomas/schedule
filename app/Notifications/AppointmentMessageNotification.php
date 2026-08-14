<?php

declare(strict_types=1);

namespace App\Notifications;

use App\ValueObjects\AppointmentMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentMessageNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly AppointmentMessage $message) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionUrls = array_filter([
            $this->message->actionUrl,
            $this->message->secondaryActionUrl,
        ]);
        $actionLabels = array_filter([
            $this->message->actionLabel,
            $this->message->secondaryActionLabel,
        ]);
        $bodyLines = array_values(array_filter(
            explode("\n", $this->message->body),
            fn (string $line): bool => $line !== ''
                && ! $this->containsActionUrl($line, $actionUrls)
                && ! $this->isActionLabel($line, $actionLabels),
        ));
        $greeting = array_shift($bodyLines) ?? 'Olá!';

        return (new MailMessage)
            ->subject($this->message->subject)
            ->markdown('mail.appointment-message', [
                'greeting' => $greeting,
                'bodyLines' => $bodyLines,
                'actionUrl' => $this->message->actionUrl,
                'actionLabel' => $this->message->actionLabel,
                'secondaryActionUrl' => $this->message->secondaryActionUrl,
                'secondaryActionLabel' => $this->message->secondaryActionLabel,
            ]);
    }

    /** @param array<int, string> $actionUrls */
    private function containsActionUrl(string $line, array $actionUrls): bool
    {
        foreach ($actionUrls as $url) {
            if (str_contains($line, $url)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int, string> $actionLabels */
    private function isActionLabel(string $line, array $actionLabels): bool
    {
        $normalized = trim($line, "*:\t\n\r\0\x0B ");

        return in_array($normalized, $actionLabels, true);
    }
}
