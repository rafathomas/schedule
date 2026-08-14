<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Appointments\TransitionAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicAppointmentResponseController extends Controller
{
    public function confirm(Request $request, string $token): Response
    {
        return $this->page($request, $token, 'confirm');
    }

    public function storeConfirmation(
        Request $request,
        string $token,
        TransitionAppointmentStatusAction $action,
    ): RedirectResponse {
        $appointment = $this->appointment($request);

        if ($appointment->status === AppointmentStatus::Confirmed) {
            return to_route('booking.confirm.show', ['token' => $token, 'result' => 'confirmed']);
        }

        try {
            $action->execute($appointment, AppointmentStatus::Confirmed, null);
        } catch (DomainException $exception) {
            return back()->withErrors(['appointment' => $exception->getMessage()]);
        }

        return to_route('booking.confirm.show', ['token' => $token, 'result' => 'confirmed']);
    }

    public function cancel(Request $request, string $token): Response
    {
        return $this->page($request, $token, 'cancel');
    }

    public function storeCancellation(
        Request $request,
        string $token,
        TransitionAppointmentStatusAction $action,
    ): RedirectResponse {
        $appointment = $this->appointment($request);

        if ($appointment->status === AppointmentStatus::Cancelled) {
            return to_route('booking.cancel.show', ['token' => $token, 'result' => 'cancelled']);
        }

        try {
            $action->execute(
                $appointment,
                AppointmentStatus::Cancelled,
                null,
                'Cancelado pelo cliente.',
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['appointment' => $exception->getMessage()]);
        }

        return to_route('booking.cancel.show', ['token' => $token, 'result' => 'cancelled']);
    }

    private function page(Request $request, string $token, string $mode): Response
    {
        $appointment = $this->appointment($request);
        $timezone = (string) $appointment->company->timezone;
        $startsAt = $appointment->scheduled_at->setTimezone($timezone);
        CarbonImmutable::setLocale('pt_BR');

        return Inertia::render('public/appointment-response', [
            'mode' => $mode,
            'result' => $request->string('result')->toString() ?: null,
            'company' => [
                'name' => $appointment->company->name,
                'slug' => $appointment->company->slug,
                'primary_color' => $appointment->company->primary_color,
                'logo_url' => $appointment->company->logoUrl(),
            ],
            'appointment' => [
                'service' => $appointment->service->name,
                'professional' => $appointment->professional->name,
                'date' => $startsAt->translatedFormat('l, d \d\e F \d\e Y'),
                'time' => $startsAt->format('H:i'),
                'status' => $appointment->status->value,
                'status_label' => $appointment->status->label(),
                'confirmed_at' => $appointment->confirmed_at?->setTimezone($timezone)->format('d/m/Y H:i'),
                'cancelled_at' => $appointment->cancelled_at?->setTimezone($timezone)->format('d/m/Y H:i'),
            ],
            'actionUrl' => $mode === 'confirm'
                ? route('booking.confirm.store', ['token' => $token])
                : route('booking.cancel.store', ['token' => $token]),
            'canConfirm' => in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::AwaitingConfirmation], true),
            'canCancel' => in_array($appointment->status, [AppointmentStatus::Pending, AppointmentStatus::AwaitingConfirmation, AppointmentStatus::Confirmed], true),
        ]);
    }

    private function appointment(Request $request): Appointment
    {
        $appointment = $request->attributes->get('tokenAppointment');
        abort_unless($appointment instanceof Appointment, 404);

        return $appointment;
    }
}
