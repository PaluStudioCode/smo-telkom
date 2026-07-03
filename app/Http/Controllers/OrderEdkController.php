<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AssertsFreshModel;
use App\Http\Requests\Operational\StoreOrderEdkRequest;
use App\Http\Requests\Operational\UpdateOrderEdkRequest;
use App\Models\OrderEdk;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\StatusTransitionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderEdkController extends Controller
{
    use AssertsFreshModel;

    public function index(Request $request): Response
    {
        Gate::authorize('order_edk.view');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'inputer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(OrderEdk::statuses())],
            'period_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'sort' => ['nullable', Rule::in(['edk_reference', 'customer_name', 'status', 'period_month', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $user = $request->user();
        $sort = $filters['sort'] ?? 'updated_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);

        $query = $this->applyFilters(
            OrderEdk::query()
                ->with(['inputer:id,name', 'accountManager:id,name'])
                ->visibleTo($user),
            $filters,
            $user,
        );

        $summaryQuery = $this->applyFilters(
            OrderEdk::query()->visibleTo($user),
            Arr::except($filters, ['status']),
            $user,
        );

        $records = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (OrderEdk $orderEdk) => $this->serializeOrderEdk($orderEdk));

        return Inertia::render('OrderEdks/Index', [
            'orderEdks' => $records,
            'stats' => $this->statusStats($summaryQuery),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'inputer_id' => $filters['inputer_id'] ?? '',
                'account_manager_id' => $filters['account_manager_id'] ?? '',
                'status' => $filters['status'] ?? '',
                'period_month' => $filters['period_month'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'statusOptions' => $this->statusOptions(),
            'inputerOptions' => $this->userOptions(User::ROLE_ADMIN_INPUTER),
            'accountManagerOptions' => $this->userOptions(User::ROLE_ACCOUNT_MANAGER),
        ]);
    }

    public function store(
        StoreOrderEdkRequest $request,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('order_edk.create');

        $validated = $request->validated();
        $transitions->assertOrderEdkTransition(null, $validated['status'], $request->user());

        DB::transaction(function () use ($request, $validated, $activityLogger): void {
            $record = OrderEdk::create([
                ...$validated,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'order_edk', 'create', $record, null, $record->getAttributes());
        });

        return back()->with('success', 'Order EDK berhasil ditambahkan.');
    }

    public function update(
        UpdateOrderEdkRequest $request,
        OrderEdk $orderEdk,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('order_edk.update');
        $this->authorizeOwnership($request, $orderEdk);

        $validated = $request->validated();
        $this->assertFresh($orderEdk, $validated['updated_at']);
        $transitions->assertOrderEdkTransition($orderEdk, $validated['status'], $request->user());

        unset($validated['updated_at']);

        DB::transaction(function () use ($request, $orderEdk, $validated, $activityLogger): void {
            $oldValues = $orderEdk->getOriginal();
            $orderEdk->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'order_edk', 'update', $orderEdk, $oldValues, $orderEdk->fresh()->getAttributes());
        });

        return back()->with('success', 'Order EDK berhasil diperbarui.');
    }

    public function destroy(Request $request, OrderEdk $orderEdk, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('order_edk.delete');
        $this->authorizeOwnership($request, $orderEdk);

        $validated = $request->validate([
            'updated_at' => ['nullable', 'date'],
        ]);

        $this->assertFresh($orderEdk, $validated['updated_at'] ?? null);

        DB::transaction(function () use ($request, $orderEdk, $activityLogger): void {
            $oldValues = $orderEdk->getOriginal();
            $orderEdk->update(['updated_by' => $request->user()->id]);
            $orderEdk->delete();

            $activityLogger->log($request, 'order_edk', 'delete', $orderEdk, $oldValues, null);
        });

        return back()->with('success', 'Order EDK berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('edk_reference', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            ->when(! $user->isAccountManager() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth));
    }

    /**
     * @return array<int, array{key: string, label: string, value: int|string, tone: string}>
     */
    private function statusStats(Builder $query): array
    {
        $counts = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();
        $complete = (int) ($counts[OrderEdk::STATUS_COMPLETE] ?? 0);
        $tidakLanjut = (int) ($counts[OrderEdk::STATUS_TIDAK_LANJUT] ?? 0);
        $achievement = $total > 0 ? round(($complete / $total) * 100, 1) : 0;
        $remaining = max($total - $complete - $tidakLanjut, 0);

        return collect(OrderEdk::LABELS)
            ->map(fn (string $label, string $status) => [
                'key' => $status,
                'label' => $label,
                'value' => (int) ($counts[$status] ?? 0),
                'tone' => $this->statusTone($status),
            ])
            ->values()
            ->push([
                'key' => 'achievement',
                'label' => 'Achievement',
                'value' => number_format($achievement, 1).'%',
                'tone' => 'success',
            ])
            ->push([
                'key' => 'sisa_populasi',
                'label' => 'Sisa Populasi',
                'value' => $remaining,
                'tone' => 'primary',
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    private function statusOptions(): array
    {
        return collect(OrderEdk::LABELS)
            ->map(fn (string $label, string $status) => [
                'value' => $status,
                'label' => $label,
                'tone' => $this->statusTone($status),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function userOptions(string $role): array
    {
        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrderEdk(OrderEdk $orderEdk): array
    {
        return [
            'id' => $orderEdk->id,
            'edk_reference' => $orderEdk->edk_reference,
            'customer_name' => $orderEdk->customer_name,
            'inputer_id' => $orderEdk->inputer_id,
            'inputer_name' => $orderEdk->inputer?->name,
            'account_manager_id' => $orderEdk->account_manager_id,
            'account_manager_name' => $orderEdk->accountManager?->name,
            'status' => $orderEdk->status,
            'status_label' => OrderEdk::LABELS[$orderEdk->status] ?? $orderEdk->status,
            'status_tone' => $this->statusTone($orderEdk->status),
            'period_month' => $orderEdk->period_month,
            'source_system' => $orderEdk->source_system,
            'notes' => $orderEdk->notes,
            'updated_at' => $orderEdk->updated_at?->format('Y-m-d H:i'),
            'updated_at_token' => $this->updatedAtToken($orderEdk),
        ];
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            OrderEdk::STATUS_LANJUT, OrderEdk::STATUS_OGP => 'info',
            OrderEdk::STATUS_BELUM_INPUT => 'warning',
            OrderEdk::STATUS_COMPLETE => 'success',
            default => 'neutral',
        };
    }

    private function authorizeOwnership(Request $request, OrderEdk $orderEdk): void
    {
        abort_unless($request->user()->isSuperAdmin() || $orderEdk->inputer_id === $request->user()->id, 403);
    }
}
