<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\Reports\ReportAnalyticsService;
use App\Support\Tenancy\CurrentCompany;
use App\ValueObjects\ReportPeriod;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(
        ReportFilterRequest $request,
        CurrentCompany $currentCompany,
        ReportAnalyticsService $analytics,
    ): Response {
        $company = $currentCompany->getOrFail();
        $period = ReportPeriod::fromInput(
            preset: (string) $request->validated('period'),
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            timezone: (string) $company->timezone,
        );

        return Inertia::render('reports/index', $analytics->build($period));
    }
}
