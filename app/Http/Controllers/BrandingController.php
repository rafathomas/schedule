<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyBrandingRequest;
use App\Services\CompanyLogoService;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(CurrentCompany $currentCompany): Response
    {
        $company = $currentCompany->getOrFail();
        Gate::authorize('update', $company);

        return Inertia::render('branding/edit', [
            'branding' => [
                'company_name' => $company->name,
                'primary_color' => $company->primary_color,
                'logo_url' => $company->logoUrl(),
            ],
        ]);
    }

    public function update(
        UpdateCompanyBrandingRequest $request,
        CurrentCompany $currentCompany,
        CompanyLogoService $logos,
    ): RedirectResponse {
        $company = $currentCompany->getOrFail();
        $company->update([
            'primary_color' => $request->validated('primary_color'),
            'logo_path' => $logos->replace(
                $company->uuid,
                $company->logo_path,
                $request->file('logo'),
                $request->boolean('remove_logo'),
            ),
        ]);

        return back()->with('success', 'Personalização atualizada com sucesso.');
    }
}
