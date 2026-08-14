<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Appointments\CreateAppointmentAction;
use App\Actions\Appointments\TransitionAppointmentStatusAction;
use App\Actions\Appointments\UpdateAppointmentAction;
use App\Enums\AppointmentStatus;
use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\Appointments\StoreAppointmentRequest;
use App\Http\Requests\Appointments\UpdateAppointmentRequest;
use App\Http\Requests\Appointments\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function store(StoreAppointmentRequest $request, CreateAppointmentAction $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $action->execute($request->validated(), $user);
        } catch (SlotUnavailableException $exception) {
            return back()->withErrors(['time' => $exception->getMessage()])->withInput();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agendamento criado.']);

        return back();
    }

    public function update(
        UpdateAppointmentRequest $request,
        string $appointment,
        UpdateAppointmentAction $action,
    ): RedirectResponse {
        $model = Appointment::query()->where('uuid', $appointment)->firstOrFail();
        Gate::authorize('update', $model);
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $action->execute($model, $request->validated(), $user);
        } catch (SlotUnavailableException $exception) {
            return back()->withErrors(['time' => $exception->getMessage()])->withInput();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agendamento atualizado.']);

        return back();
    }

    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        string $appointment,
        TransitionAppointmentStatusAction $action,
    ): RedirectResponse {
        $model = Appointment::query()->where('uuid', $appointment)->firstOrFail();
        Gate::authorize('updateStatus', $model);
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        try {
            $action->execute(
                $model,
                AppointmentStatus::from($request->validated('status')),
                $user,
                $request->validated('reason'),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status do agendamento atualizado.']);

        return back();
    }
}
