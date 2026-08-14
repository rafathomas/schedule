<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Services\Reports\DashboardSummaryService;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentCompany $currentCompany,
        DashboardSummaryService $summary,
    ): Response {
        $company = $currentCompany->getOrFail();
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $canManage = $user->hasCompanyRole($company->getKey(), ['owner', 'admin']);
        $professionalId = $canManage
            ? null
            : Professional::query()->where('user_id', $user->getKey())->value('id');

        return Inertia::render('dashboard', [
            'catalogSummary' => [
                'customers' => $canManage
                    ? Customer::query()->count()
                    : Customer::query()->whereHas('appointments', fn ($query) => $query->where('professional_id', $professionalId))->count(),
                'professionals' => $canManage
                    ? Professional::query()->where('status', 'active')->count()
                    : ($professionalId === null ? 0 : 1),
                'services' => $canManage
                    ? Service::query()->where('is_active', true)->count()
                    : Service::query()->whereHas('professionals', fn ($query) => $query->whereKey($professionalId))->count(),
            ],
            'operationalSummary' => $summary->build($company, $professionalId, ! $canManage),
            'canManage' => $canManage,
        ]);
    }
}
