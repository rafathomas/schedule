<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentCompany
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null, Response::HTTP_UNAUTHORIZED);

        $availableCompanies = $user->companies()
            ->wherePivot('is_active', true)
            ->where('companies.status', 'active');

        $company = $user->current_company_id !== null
            ? (clone $availableCompanies)->find($user->current_company_id)
            : null;

        $company ??= $availableCompanies->first();

        abort_if($company === null, Response::HTTP_FORBIDDEN, 'Nenhuma empresa ativa está vinculada a este usuário.');

        if ($user->current_company_id !== $company->getKey()) {
            $user->forceFill(['current_company_id' => $company->getKey()])->saveQuietly();
        }

        $this->currentCompany->set($company);

        try {
            return $next($request);
        } finally {
            $this->currentCompany->forget();
        }
    }
}
