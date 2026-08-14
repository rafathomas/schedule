<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Appointment;
use App\Models\Company;
use App\Support\Tenancy\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAppointmentToken
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');
        abort_unless(is_string($token) && strlen($token) >= 32, 404);

        $unscoped = Appointment::withoutGlobalScopes()
            ->where('confirmation_token', hash('sha256', $token))
            ->firstOrFail();
        $company = Company::query()
            ->whereKey($unscoped->company_id)
            ->where('status', 'active')
            ->firstOrFail();

        $this->currentCompany->set($company);
        $appointment = Appointment::query()
            ->with(['company', 'customer', 'professional', 'service'])
            ->whereKey($unscoped->getKey())
            ->firstOrFail();
        $request->attributes->set('tokenAppointment', $appointment);

        try {
            return $next($request);
        } finally {
            $this->currentCompany->forget();
        }
    }
}
