<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppEntryController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BlockedPeriodController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HoursController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\PublicAppointmentResponseController;
use App\Http\Controllers\PublicAvailabilityController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WhatsAppConnectionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['booking.token', 'throttle:30,1'])->group(function () {
    Route::get('booking/{token}/confirm', [PublicAppointmentResponseController::class, 'confirm'])
        ->name('booking.confirm.show');
    Route::post('booking/{token}/confirm', [PublicAppointmentResponseController::class, 'storeConfirmation'])
        ->name('booking.confirm.store');
    Route::get('booking/{token}/cancel', [PublicAppointmentResponseController::class, 'cancel'])
        ->name('booking.cancel.show');
    Route::post('booking/{token}/cancel', [PublicAppointmentResponseController::class, 'storeCancellation'])
        ->name('booking.cancel.store');
});

Route::middleware(['auth', 'verified', 'company'])->group(function () {
    Route::get('app', AppEntryController::class)->name('app.entry');
    Route::get('onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::patch('onboarding/company', [OnboardingController::class, 'update'])
        ->name('onboarding.company.update');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('agenda', AgendaController::class)->name('agenda.index');
    Route::get('agenda/disponibilidade', AvailabilityController::class)->name('agenda.availability');
    Route::post('agenda/agendamentos', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('agenda/agendamentos/{appointment}', [AppointmentController::class, 'update'])->name(
        'appointments.update',
    );
    Route::patch('agenda/agendamentos/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name(
        'appointments.status.update',
    );
    Route::post('agenda/bloqueios', [BlockedPeriodController::class, 'store'])->name('blocked-periods.store');
    Route::delete('agenda/bloqueios/{blockedPeriod}', [BlockedPeriodController::class, 'destroy'])->name(
        'blocked-periods.destroy',
    );

    Route::get('profissionais', [ProfessionalController::class, 'index'])->name('professionals.index');
    Route::post('profissionais', [ProfessionalController::class, 'store'])->name('professionals.store');
    Route::patch('profissionais/{professional}', [ProfessionalController::class, 'update'])->name(
        'professionals.update',
    );
    Route::delete('profissionais/{professional}', [ProfessionalController::class, 'destroy'])->name(
        'professionals.destroy',
    );

    Route::get('servicos', [ServiceController::class, 'index'])->name('services.index');
    Route::post('servicos', [ServiceController::class, 'store'])->name('services.store');
    Route::patch('servicos/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('servicos/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    Route::get('clientes', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('clientes', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('clientes/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::patch('clientes/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('clientes/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::get('horarios', [HoursController::class, 'index'])->name('hours.index');
    Route::put('horarios/empresa', [HoursController::class, 'updateCompany'])->name('hours.company.update');
    Route::put('horarios/profissionais/{professional}', [HoursController::class, 'updateProfessional'])->name(
        'hours.professionals.update',
    );

    Route::get('comunicacao', [CommunicationController::class, 'index'])->name('communication.index');
    Route::put('comunicacao', [CommunicationController::class, 'update'])->name('communication.update');
    Route::post('comunicacao/whatsapp/conectar', [WhatsAppConnectionController::class, 'store'])
        ->name('communication.whatsapp.connect');
    Route::get('comunicacao/whatsapp/status', [WhatsAppConnectionController::class, 'status'])
        ->name('communication.whatsapp.status');
    Route::delete('comunicacao/whatsapp', [WhatsAppConnectionController::class, 'destroy'])
        ->name('communication.whatsapp.disconnect');

    Route::get('relatorios', ReportController::class)->name('reports.index');

    Route::get('personalizacao', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::post('personalizacao', [BrandingController::class, 'update'])->name('branding.update');
});

Route::middleware('public.company')->group(function () {
    Route::get('agenda/{company}', [PublicBookingController::class, 'index'])
        ->name('public-booking.index');
    Route::get('agenda/{company}/disponibilidade', PublicAvailabilityController::class)
        ->middleware('throttle:60,1')
        ->name('public-booking.availability');
    Route::post('agenda/{company}', [PublicBookingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('public-booking.store');
    Route::get('agenda/{company}/sucesso/{appointment}', [PublicBookingController::class, 'success'])
        ->name('public-booking.success');
    Route::get('agenda/{company}/calendario/{appointment}.ics', [PublicBookingController::class, 'calendar'])
        ->name('public-booking.calendar');
});

require __DIR__.'/settings.php';
