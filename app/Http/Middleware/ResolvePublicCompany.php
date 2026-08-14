<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\Tenancy\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePublicCompany
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('company');
        abort_unless(is_string($slug), 404);

        $company = Company::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $this->currentCompany->set($company);
        $request->attributes->set('publicCompany', $company);

        try {
            return $next($request);
        } finally {
            $this->currentCompany->forget();
        }
    }
}
