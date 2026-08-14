<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Services\StoreServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Models\Service;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request, CurrentCompany $currentCompany): Response
    {
        Gate::authorize('viewAny', Service::class);
        $search = trim((string) $request->string('search'));

        $services = Service::query()
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Service $service): array => [
                'uuid' => $service->uuid,
                'name' => $service->name,
                'description' => $service->description,
                'category' => $service->category,
                'duration_minutes' => $service->duration_minutes,
                'buffer_minutes' => $service->buffer_minutes,
                'price' => $service->price,
                'is_active' => $service->is_active,
            ]);

        return Inertia::render('services/index', [
            'services' => $services,
            'filters' => ['search' => $search],
            'canManage' => $this->canManage($request->user(), $currentCompany),
        ]);
    }

    public function store(StoreServiceRequest $request, CurrentCompany $currentCompany): RedirectResponse
    {
        Service::query()->create($request->validated());
        $currentCompany->getOrFail()->completeOnboardingStep('first_service');
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Serviço cadastrado.']);

        return to_route('services.index');
    }

    public function update(UpdateServiceRequest $request, string $service): RedirectResponse
    {
        $model = Service::query()->where('uuid', $service)->firstOrFail();
        Gate::authorize('update', $model);
        $model->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Serviço atualizado.']);

        return to_route('services.index');
    }

    public function destroy(string $service): RedirectResponse
    {
        $model = Service::query()->where('uuid', $service)->firstOrFail();
        Gate::authorize('delete', $model);
        $model->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Serviço removido.']);

        return to_route('services.index');
    }

    private function canManage(?User $user, CurrentCompany $currentCompany): bool
    {
        return $user?->hasCompanyRole($currentCompany->id(), ['owner', 'admin']) === true;
    }
}
