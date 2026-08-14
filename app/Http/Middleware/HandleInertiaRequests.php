<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $resolveCompany = function () use ($request): ?Company {
            $resolved = app(CurrentCompany::class)->get();

            if ($resolved !== null) {
                return $resolved;
            }

            $user = $request->user();
            $companyId = $user?->getAttribute('current_company_id');

            if (! $user instanceof User || ! is_numeric($companyId)) {
                return null;
            }

            return $user->companies()
                ->wherePivot('is_active', true)
                ->where('companies.status', 'active')
                ->find((int) $companyId);
        };

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'company' => function () use ($resolveCompany): ?array {
                $company = $resolveCompany();

                if ($company === null) {
                    return null;
                }

                return [
                    ...$company->only([
                        'id',
                        'uuid',
                        'name',
                        'slug',
                        'status',
                        'segment',
                        'phone',
                        'whatsapp',
                        'postal_code',
                        'address',
                        'address_number',
                        'address_complement',
                        'city',
                        'state',
                        'timezone',
                        'appointment_interval_minutes',
                        'minimum_notice_minutes',
                        'maximum_notice_days',
                        'onboarding_steps',
                        'onboarding_completed_at',
                        'primary_color',
                    ]),
                    'logo_url' => $company->logoUrl(),
                ];
            },
            'permissions' => function () use ($request, $resolveCompany): array {
                $user = $request->user();
                $company = $resolveCompany();
                $companyId = $company?->getKey() ?? $user?->getAttribute('current_company_id');

                return [
                    'branding' => $user instanceof User
                        && is_numeric($companyId)
                        && $user->hasCompanyRole((int) $companyId, ['owner', 'admin']),
                    'reports' => $user instanceof User
                        && is_numeric($companyId)
                        && $user->hasCompanyRole((int) $companyId, ['owner', 'admin']),
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
