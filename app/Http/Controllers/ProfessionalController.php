<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Professionals\StoreProfessionalRequest;
use App\Http\Requests\Professionals\UpdateProfessionalRequest;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use App\Services\ProfessionalAvatarService;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalController extends Controller
{
    public function index(Request $request, CurrentCompany $currentCompany): Response
    {
        Gate::authorize('viewAny', Professional::class);
        $search = trim((string) $request->string('search'));

        $professionals = Professional::query()
            ->with('services:id,uuid,name')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Professional $professional): array => [
                'uuid' => $professional->uuid,
                'name' => $professional->name,
                'email' => $professional->email,
                'phone' => $professional->phone,
                'description' => $professional->description,
                'avatar_url' => $professional->avatar_path === null ? null : Storage::disk('public')->url($professional->avatar_path),
                'status' => $professional->status,
                'service_uuids' => $professional->services->pluck('uuid')->values(),
                'services' => $professional->services->map->only(['uuid', 'name'])->values(),
            ]);

        return Inertia::render('professionals/index', [
            'professionals' => $professionals,
            'services' => Service::query()->orderBy('name')->get(['uuid', 'name', 'is_active']),
            'filters' => ['search' => $search],
            'canManage' => $this->canManage($request->user(), $currentCompany),
        ]);
    }

    public function store(
        StoreProfessionalRequest $request,
        CurrentCompany $currentCompany,
        ProfessionalAvatarService $avatars,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $currentCompany, $avatars): void {
            $professional = Professional::query()->create([
                ...$this->attributes($validated),
                'avatar_path' => $avatars->replace(null, $request->file('avatar'), false),
            ]);
            $this->syncServices($professional, $validated['service_uuids'] ?? [], $currentCompany);
            $currentCompany->getOrFail()->completeOnboardingStep('first_professional');
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profissional cadastrado.']);

        return to_route('professionals.index');
    }

    public function update(
        UpdateProfessionalRequest $request,
        string $professional,
        CurrentCompany $currentCompany,
        ProfessionalAvatarService $avatars,
    ): RedirectResponse {
        $model = Professional::query()->where('uuid', $professional)->firstOrFail();
        Gate::authorize('update', $model);
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $model, $currentCompany, $avatars): void {
            $model->update([
                ...$this->attributes($validated),
                'avatar_path' => $avatars->replace(
                    $model->avatar_path,
                    $request->file('avatar'),
                    $request->boolean('remove_avatar'),
                ),
            ]);
            $this->syncServices($model, $validated['service_uuids'] ?? [], $currentCompany);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profissional atualizado.']);

        return to_route('professionals.index');
    }

    public function destroy(string $professional, ProfessionalAvatarService $avatars): RedirectResponse
    {
        $model = Professional::query()->where('uuid', $professional)->firstOrFail();
        Gate::authorize('delete', $model);

        DB::transaction(function () use ($model, $avatars): void {
            $model->services()->detach();
            $avatars->remove($model->avatar_path);
            $model->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profissional removido.']);

        return to_route('professionals.index');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ];
    }

    /** @param array<int, string> $serviceUuids */
    private function syncServices(Professional $professional, array $serviceUuids, CurrentCompany $currentCompany): void
    {
        $ids = Service::query()->whereIn('uuid', $serviceUuids)->pluck('id');
        $pivot = $ids->mapWithKeys(fn (int $id): array => [$id => ['company_id' => $currentCompany->id()]])->all();
        $professional->services()->sync($pivot);
    }

    private function canManage(?User $user, CurrentCompany $currentCompany): bool
    {
        return $user?->hasCompanyRole($currentCompany->id(), ['owner', 'admin']) === true;
    }
}
