<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request, CurrentCompany $currentCompany): Response
    {
        Gate::authorize('viewAny', Customer::class);
        $search = trim((string) $request->string('search'));

        $customers = Customer::query()
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Customer $customer): array => $this->serialize($customer));

        return Inertia::render('customers/index', [
            'customers' => $customers,
            'filters' => ['search' => $search],
            'canManage' => $this->canManage($request->user(), $currentCompany),
        ]);
    }

    public function show(Request $request, string $customer, CurrentCompany $currentCompany): Response
    {
        $model = Customer::query()->where('uuid', $customer)->firstOrFail();
        Gate::authorize('view', $model);

        return Inertia::render('customers/show', [
            'customer' => $this->serialize($model),
            'canManage' => $this->canManage($request->user(), $currentCompany),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::query()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente cadastrado.']);

        return to_route('customers.index');
    }

    public function update(UpdateCustomerRequest $request, string $customer): RedirectResponse
    {
        $model = Customer::query()->where('uuid', $customer)->firstOrFail();
        Gate::authorize('update', $model);
        $model->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente atualizado.']);

        return to_route('customers.index');
    }

    public function destroy(string $customer): RedirectResponse
    {
        $model = Customer::query()->where('uuid', $customer)->firstOrFail();
        Gate::authorize('delete', $model);
        $model->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente removido.']);

        return to_route('customers.index');
    }

    /** @return array<string, mixed> */
    private function serialize(Customer $customer): array
    {
        return [
            'uuid' => $customer->uuid,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'whatsapp' => $customer->whatsapp,
            'email' => $customer->email,
            'birth_date' => $this->formatDate($customer->getAttribute('birth_date'), 'Y-m-d'),
            'notes' => $customer->notes,
            'first_appointment_at' => $this->formatDate($customer->getAttribute('first_appointment_at'), DateTimeInterface::ATOM),
            'last_appointment_at' => $this->formatDate($customer->getAttribute('last_appointment_at'), DateTimeInterface::ATOM),
        ];
    }

    private function formatDate(mixed $value, string $format): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format($format) : null;
    }

    private function canManage(?User $user, CurrentCompany $currentCompany): bool
    {
        return $user?->hasCompanyRole($currentCompany->id(), ['owner', 'admin']) === true;
    }
}
