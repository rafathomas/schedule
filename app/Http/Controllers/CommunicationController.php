<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CommunicationChannel;
use App\Http\Requests\UpdateCommunicationSettingsRequest;
use App\Models\NotificationLog;
use App\Models\ReminderSetting;
use App\Models\User;
use App\Models\WhatsAppConnection;
use App\Services\Messaging\EvolutionApiClient;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationController extends Controller
{
    public function index(
        Request $request,
        CurrentCompany $currentCompany,
        EvolutionApiClient $evolution,
    ): Response {
        $this->authorizeAccess($request->user(), $currentCompany);
        $settings = ReminderSetting::query()->firstOrCreate(['company_id' => $currentCompany->id()]);

        return Inertia::render('communication/index', [
            'settings' => [
                'confirmation_enabled' => $settings->confirmation_enabled,
                'confirmation_minutes_before' => $settings->confirmation_minutes_before,
                'reminders_enabled' => $settings->reminders_enabled,
                'reminder_offsets' => $settings->normalizedOffsets(),
                'channels' => array_map(
                    fn (CommunicationChannel $channel): string => $channel->value,
                    $settings->normalizedChannels(),
                ),
            ],
            'whatsapp' => $this->whatsAppState($request, $evolution),
            'logs' => NotificationLog::query()
                ->with(['appointment.customer:id,name', 'appointment.service:id,name'])
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (NotificationLog $log): array => [
                    'id' => $log->getKey(),
                    'type' => $log->type->label(),
                    'channel' => $log->channel->label(),
                    'status' => $log->status->value,
                    'recipient' => $log->recipient,
                    'customer' => $log->appointment->customer->name,
                    'service' => $log->appointment->service->name,
                    'scheduled_for' => $log->scheduled_for?->setTimezone((string) $currentCompany->getOrFail()->timezone)->format('d/m/Y H:i'),
                    'sent_at' => $log->sent_at?->setTimezone((string) $currentCompany->getOrFail()->timezone)->format('d/m/Y H:i'),
                    'attempt' => $log->attempt,
                    'error' => $log->error,
                ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function whatsAppState(Request $request, EvolutionApiClient $evolution): array
    {
        $connection = WhatsAppConnection::query()->first();

        return [
            'available' => $evolution->isConfigured(),
            'status' => $connection->status ?? 'disconnected',
            'instance_name' => $connection?->instance_name,
            'last_checked_at' => $connection?->last_checked_at?->toIso8601String(),
            'last_error' => $connection?->last_error,
            'qr_code' => $request->session()->pull('whatsapp_qr_code'),
        ];
    }

    public function update(
        UpdateCommunicationSettingsRequest $request,
        CurrentCompany $currentCompany,
    ): RedirectResponse {
        $settings = ReminderSetting::query()->firstOrCreate(['company_id' => $currentCompany->id()]);
        $settings->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comunicações atualizadas.']);

        return to_route('communication.index');
    }

    private function authorizeAccess(?User $user, CurrentCompany $currentCompany): void
    {
        abort_unless($user?->hasCompanyRole($currentCompany->id(), ['owner', 'admin']) === true, 403);
    }
}
