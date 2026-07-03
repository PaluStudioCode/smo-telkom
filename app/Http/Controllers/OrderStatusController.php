<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AssertsFreshModel;
use App\Http\Requests\Operational\StoreOrderStatusRequest;
use App\Http\Requests\Operational\UpdateOrderStatusRequest;
use App\Models\OrderStatus;
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

class OrderStatusController extends Controller
{
    use AssertsFreshModel;

    public function index(Request $request): Response
    {
        Gate::authorize('order_status.view');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'inputer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(OrderStatus::statuses())],
            'period_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'sort' => ['nullable', Rule::in(['order_number', 'customer_name', 'service_name', 'status', 'period_month', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $user = $request->user();
        $sort = $filters['sort'] ?? 'updated_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);

        $query = $this->applyFilters(
            OrderStatus::query()
                ->with(['inputer:id,name', 'accountManager:id,name'])
                ->visibleTo($user),
            $filters,
            $user,
        );

        $summaryQuery = $this->applyFilters(
            OrderStatus::query()->visibleTo($user),
            Arr::except($filters, ['status']),
            $user,
        );

        $records = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (OrderStatus $orderStatus) => $this->serializeOrderStatus($orderStatus));

        return Inertia::render('OrderStatuses/Index', [
            'orderStatuses' => $records,
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
        StoreOrderStatusRequest $request,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('order_status.create');

        $validated = $request->validated();
        $transitions->assertOrderStatusTransition(null, $validated['status'], $request->user());

        DB::transaction(function () use ($request, $validated, $activityLogger): void {
            $record = OrderStatus::create([
                ...$validated,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'order_status', 'create', $record, null, $record->getAttributes());
        });

        return back()->with('success', 'Order Status berhasil ditambahkan.');
    }

    public function update(
        UpdateOrderStatusRequest $request,
        OrderStatus $orderStatus,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('order_status.update');
        $this->authorizeOwnership($request, $orderStatus);

        $validated = $request->validated();
        $this->assertFresh($orderStatus, $validated['updated_at']);
        $transitions->assertOrderStatusTransition($orderStatus, $validated['status'], $request->user());

        unset($validated['updated_at']);

        DB::transaction(function () use ($request, $orderStatus, $validated, $activityLogger): void {
            $oldValues = $orderStatus->getOriginal();
            $orderStatus->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'order_status', 'update', $orderStatus, $oldValues, $orderStatus->fresh()->getAttributes());
        });

        return back()->with('success', 'Order Status berhasil diperbarui.');
    }

    public function destroy(Request $request, OrderStatus $orderStatus, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('order_status.delete');
        $this->authorizeOwnership($request, $orderStatus);

        $validated = $request->validate([
            'updated_at' => ['nullable', 'date'],
        ]);

        $this->assertFresh($orderStatus, $validated['updated_at'] ?? null);

        DB::transaction(function () use ($request, $orderStatus, $activityLogger): void {
            $oldValues = $orderStatus->getOriginal();
            $orderStatus->update(['updated_by' => $request->user()->id]);
            $orderStatus->delete();

            $activityLogger->log($request, 'order_status', 'delete', $orderStatus, $oldValues, null);
        });

        return back()->with('success', 'Order Status berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            ->when(! $user->isAccountManager() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth));
    }

    /**
     * @return array<int, array{key: string, label: string, value: int, tone: string}>
     */
    private function statusStats(Builder $query): array
    {
        $counts = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(OrderStatus::LABELS)
            ->map(fn (string $label, string $status) => [
                'key' => $status,
                'label' => $label,
                'value' => (int) ($counts[$status] ?? 0),
                'tone' => $this->statusTone($status),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    private function statusOptions(): array
    {
        return collect(OrderStatus::LABELS)
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
    private function serializeOrderStatus(OrderStatus $orderStatus): array
    {
        return [
            'id' => $orderStatus->id,
            'order_number' => $orderStatus->order_number,
            'customer_name' => $orderStatus->customer_name,
            'service_name' => $orderStatus->service_name,
            'inputer_id' => $orderStatus->inputer_id,
            'inputer_name' => $orderStatus->inputer?->name,
            'account_manager_id' => $orderStatus->account_manager_id,
            'account_manager_name' => $orderStatus->accountManager?->name,
            'status' => $orderStatus->status,
            'status_label' => OrderStatus::LABELS[$orderStatus->status] ?? $orderStatus->status,
            'status_tone' => $this->statusTone($orderStatus->status),
            'provisioning_stage' => $orderStatus->provisioning_stage,
            'period_month' => $orderStatus->period_month,
            'source_system' => $orderStatus->source_system,
            'notes' => $orderStatus->notes,
            'updated_at' => $orderStatus->updated_at?->format('Y-m-d H:i'),
            'updated_at_token' => $this->updatedAtToken($orderStatus),
        ];
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            OrderStatus::STATUS_PROVISIONING => 'info',
            OrderStatus::STATUS_PENDING_BASO, OrderStatus::STATUS_PENDING_BILLING_APPROVAL => 'warning',
            OrderStatus::STATUS_COMPLETE => 'success',
            OrderStatus::STATUS_FAILED => 'danger',
            default => 'neutral',
        };
    }

    private function authorizeOwnership(Request $request, OrderStatus $orderStatus): void
    {
        abort_unless($request->user()->isSuperAdmin() || $orderStatus->inputer_id === $request->user()->id, 403);
    }
}
