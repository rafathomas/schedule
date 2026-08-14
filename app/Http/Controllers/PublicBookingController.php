<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Appointments\CreatePublicAppointmentAction;
use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\PublicBooking\StorePublicBookingRequest;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Professional;
use App\Models\Service;
use App\Support\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingController extends Controller
{
    public function index(Request $request, string $company, CurrentCompany $currentCompany): Response
    {
        $tenant = $currentCompany->getOrFail();
        $timezone = (string) $tenant->timezone;
        $today = CarbonImmutable::today($timezone);

        return Inertia::render('public/booking', [
            'company' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'segment' => $tenant->segment,
                'whatsapp' => $tenant->whatsapp,
                'phone' => $tenant->phone,
                'city' => $tenant->city,
                'state' => $tenant->state,
                'primary_color' => $tenant->primary_color,
                'logo_url' => $tenant->logoUrl(),
            ],
            'services' => Service::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['uuid', 'name', 'description', 'category', 'duration_minutes', 'price']),
            'professionals' => Professional::query()
                ->where('status', 'active')
                ->with('services:id,uuid')
                ->orderBy('name')
                ->get()
                ->map(fn (Professional $professional): array => [
                    'uuid' => $professional->uuid,
                    'name' => $professional->name,
                    'description' => $professional->description,
                    'service_uuids' => $professional->services->pluck('uuid')->values(),
                ]),
            'today' => $today->format('Y-m-d'),
            'maximumDate' => $today->addDays((int) $tenant->maximum_notice_days)->format('Y-m-d'),
            'timezone' => $timezone,
        ]);
    }

    public function store(
        StorePublicBookingRequest $request,
        string $company,
        CreatePublicAppointmentAction $action,
    ): RedirectResponse {
        try {
            $appointment = $action->execute($request->validated());
        } catch (SlotUnavailableException $exception) {
            return back()->withErrors(['time' => $exception->getMessage()])->withInput();
        }

        return to_route('public-booking.success', [
            'company' => $company,
            'appointment' => $appointment->uuid,
        ]);
    }

    public function success(
        Request $request,
        string $company,
        string $appointment,
        CurrentCompany $currentCompany,
    ): Response {
        $tenant = $currentCompany->getOrFail();
        $model = Appointment::query()
            ->with(['service:id,uuid,name', 'professional:id,uuid,name'])
            ->where('uuid', $appointment)
            ->firstOrFail();
        $startsAt = $model->scheduled_at->setTimezone((string) $tenant->timezone);
        CarbonImmutable::setLocale('pt_BR');

        return Inertia::render('public/success', [
            'company' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'whatsapp' => $tenant->whatsapp,
                'phone' => $tenant->phone,
                'address' => $this->address($tenant),
                'primary_color' => $tenant->primary_color,
                'logo_url' => $tenant->logoUrl(),
            ],
            'appointment' => [
                'uuid' => $model->uuid,
                'service' => $model->service->name,
                'professional' => $model->professional->name,
                'date' => $startsAt->translatedFormat('l, d \d\e F \d\e Y'),
                'time' => $startsAt->format('H:i'),
                'calendar_url' => route('public-booking.calendar', [
                    'company' => $company,
                    'appointment' => $model->uuid,
                ]),
            ],
        ]);
    }

    public function calendar(
        string $company,
        string $appointment,
        CurrentCompany $currentCompany,
    ): HttpResponse {
        $tenant = $currentCompany->getOrFail();
        $model = Appointment::query()
            ->with(['service:id,name', 'professional:id,name'])
            ->where('uuid', $appointment)
            ->firstOrFail();
        $content = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AgendaFlow//Booking//PT-BR',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:'.$model->uuid.'@agendaflow.local',
            'DTSTAMP:'.CarbonImmutable::now('UTC')->format('Ymd\THis\Z'),
            'DTSTART:'.$model->scheduled_at->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$model->ends_at->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->ics($model->service->name.' — '.$tenant->name),
            'DESCRIPTION:'.$this->ics('Atendimento com '.$model->professional->name),
            'LOCATION:'.$this->ics($this->address($tenant)),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="agendamento.ics"',
        ]);
    }

    private function address(Company $company): string
    {
        return collect([
            trim(implode(', ', array_filter([(string) $company->address, (string) $company->address_number]))),
            (string) $company->address_complement,
            trim(implode(' - ', array_filter([(string) $company->city, (string) $company->state]))),
        ])->filter()->implode(', ');
    }

    private function ics(string $value): string
    {
        return str_replace(['\\', ',', ';', "\r\n", "\n"], ['\\\\', '\\,', '\\;', '\\n', '\\n'], $value);
    }
}
