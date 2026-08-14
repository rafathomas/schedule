<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Hours\UpdateWeeklyHoursAction;
use App\Http\Requests\Hours\UpdateWeeklyHoursRequest;
use App\Models\BusinessHour;
use App\Models\Professional;
use App\Models\ProfessionalHour;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HoursController extends Controller
{
    public function index(Request $request, CurrentCompany $currentCompany): Response
    {
        Gate::authorize('viewAny', BusinessHour::class);
        $selected = null;
        $professionalHours = null;
        $usesCompanyHours = false;
        $professionalUuid = $request->string('professional')->toString();
        $companyHours = $this->week(BusinessHour::query()->get(), true);

        if ($professionalUuid !== '') {
            $selected = Professional::query()->where('uuid', $professionalUuid)->firstOrFail();
            Gate::authorize('view', $selected);
            $hourRecords = $selected->hours()->get();
            $usesCompanyHours = $hourRecords->isEmpty();
            $professionalHours = $usesCompanyHours ? $companyHours : $this->week($hourRecords, false);
        }

        return Inertia::render('hours/index', [
            'companyHours' => $companyHours,
            'professionals' => Professional::query()->orderBy('name')->get(['uuid', 'name', 'status']),
            'selectedProfessional' => $selected?->only(['uuid', 'name']),
            'professionalHours' => $professionalHours,
            'usesCompanyHours' => $usesCompanyHours,
            'canManage' => $this->canManage($request->user(), $currentCompany),
        ]);
    }

    public function updateCompany(
        UpdateWeeklyHoursRequest $request,
        UpdateWeeklyHoursAction $action,
        CurrentCompany $currentCompany,
    ): RedirectResponse {
        /** @var array<int, array<string, mixed>> $hours */
        $hours = $request->validated('hours');
        $action->forCompany($hours);
        $currentCompany->getOrFail()->completeOnboardingStep('business_hours');
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horários da empresa atualizados.']);

        return to_route('hours.index');
    }

    public function updateProfessional(
        UpdateWeeklyHoursRequest $request,
        string $professional,
        UpdateWeeklyHoursAction $action,
    ): RedirectResponse {
        $model = Professional::query()->where('uuid', $professional)->firstOrFail();
        Gate::authorize('updateAny', ProfessionalHour::class);
        /** @var array<int, array<string, mixed>> $hours */
        $hours = $request->validated('hours');
        $action->forProfessional($model, $hours);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horários do profissional atualizados.']);

        return to_route('hours.index', ['professional' => $model->uuid]);
    }

    /**
     * @param  iterable<int, BusinessHour|ProfessionalHour>  $records
     * @return array<int, array<string, mixed>>
     */
    private function week(iterable $records, bool $useDefaults): array
    {
        $byDay = collect($records)->keyBy('day_of_week');

        return collect(range(0, 6))->map(function (int $day) use ($byDay, $useDefaults): array {
            $candidate = $byDay->get($day);
            $record = $candidate instanceof BusinessHour || $candidate instanceof ProfessionalHour
                ? $candidate
                : null;
            $closedByDefault = $day === 0;

            return [
                'day_of_week' => $day,
                'is_closed' => $record === null ? ($useDefaults ? $closedByDefault : false) : $record->is_closed,
                'starts_at' => $this->time($record?->starts_at) ?? ($closedByDefault ? null : '09:00'),
                'ends_at' => $this->time($record?->ends_at) ?? ($closedByDefault ? null : ($day === 6 ? '13:00' : '18:00')),
                'break_starts_at' => $this->time($record?->break_starts_at) ?? ($day > 0 && $day < 6 ? '12:00' : null),
                'break_ends_at' => $this->time($record?->break_ends_at) ?? ($day > 0 && $day < 6 ? '13:00' : null),
            ];
        })->all();
    }

    private function time(mixed $value): ?string
    {
        return is_string($value) ? substr($value, 0, 5) : null;
    }

    private function canManage(?User $user, CurrentCompany $currentCompany): bool
    {
        return $user?->hasCompanyRole($currentCompany->id(), ['owner', 'admin']) === true;
    }
}
