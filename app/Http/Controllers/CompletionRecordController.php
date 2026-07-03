<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AssertsFreshModel;
use App\Http\Requests\Operational\ApprovalCompletionRecordRequest;
use App\Http\Requests\Operational\StoreCompletionRecordRequest;
use App\Http\Requests\Operational\UpdateCompletionRecordRequest;
use App\Models\CompletionRecord;
use App\Models\OrderEdk;
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

class CompletionRecordController extends Controller
{
    use AssertsFreshModel;

    public function index(Request $request): Response
    {
        Gate::authorize('complete.view');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'inputer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'approval_status' => ['nullable', Rule::in(CompletionRecord::statuses())],
            'period_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'sort' => ['nullable', Rule::in(['completion_number', 'approval_status', 'completed_at', 'period_month', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $user = $request->user();
        $sort = $filters['sort'] ?? 'updated_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);

        $query = $this->applyFilters(
            CompletionRecord::query()
                ->with([
                    'inputer:id,name',
                    'accountManager:id,name',
                    'orderStatus:id,order_number,customer_name,period_month',
                    'orderEdk:id,edk_reference,customer_name,period_month',
                ])
                ->visibleTo($user),
            $filters,
            $user,
        );

        $summaryQuery = $this->applyFilters(
            CompletionRecord::query()->visibleTo($user),
            Arr::except($filters, ['approval_status']),
            $user,
        );

        $records = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CompletionRecord $completionRecord) => $this->serializeCompletionRecord($completionRecord));

        return Inertia::render('CompletionRecords/Index', [
            'completionRecords' => $records,
            'stats' => $this->approvalStats($summaryQuery),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'inputer_id' => $filters['inputer_id'] ?? '',
                'account_manager_id' => $filters['account_manager_id'] ?? '',
                'approval_status' => $filters['approval_status'] ?? '',
                'period_month' => $filters['period_month'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'approvalStatusOptions' => $this->approvalStatusOptions(),
            'inputerOptions' => $this->userOptions(User::ROLE_ADMIN_INPUTER),
            'accountManagerOptions' => $this->userOptions(User::ROLE_ACCOUNT_MANAGER),
            'orderStatusOptions' => $this->orderStatusOptions($user),
            'orderEdkOptions' => $this->orderEdkOptions($user),
        ]);
    }

    public function store(StoreCompletionRecordRequest $request, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('complete.create');

        $validated = $this->approvalFieldsForSave($request->validated(), $request->user());

        DB::transaction(function () use ($request, $validated, $activityLogger): void {
            $record = CompletionRecord::create([
                ...$validated,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'complete', 'create', $record, null, $record->getAttributes());
        });

        return back()->with('success', 'Data Modul Complete berhasil ditambahkan.');
    }

    public function update(
        UpdateCompletionRecordRequest $request,
        CompletionRecord $completionRecord,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('complete.update');
        $this->authorizeOwnership($request, $completionRecord);

        $validated = $request->validated();
        $this->assertFresh($completionRecord, $validated['updated_at']);
        $transitions->assertCompletionTransition($completionRecord, $validated['approval_status']);

        unset($validated['updated_at']);
        $validated = $this->approvalFieldsForSave($validated, $request->user(), $completionRecord->approval_status);

        DB::transaction(function () use ($request, $completionRecord, $validated, $activityLogger): void {
            $oldValues = $completionRecord->getOriginal();
            $completionRecord->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'complete', 'update', $completionRecord, $oldValues, $completionRecord->fresh()->getAttributes());
        });

        return back()->with('success', 'Data Modul Complete berhasil diperbarui.');
    }

    public function approve(
        ApprovalCompletionRecordRequest $request,
        CompletionRecord $completionRecord,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $validated = $request->validated();
        Gate::authorize($this->approvalAbility($validated['approval_status']));

        $this->assertFresh($completionRecord, $validated['updated_at']);
        $transitions->assertCompletionTransition($completionRecord, $validated['approval_status']);

        unset($validated['updated_at']);
        $validated = $this->approvalFieldsForSave($validated, $request->user(), $completionRecord->approval_status);

        DB::transaction(function () use ($request, $completionRecord, $validated, $activityLogger): void {
            $oldValues = $completionRecord->getOriginal();
            $completionRecord->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log(
                $request,
                'complete',
                $this->approvalAction($validated['approval_status']),
                $completionRecord,
                $oldValues,
                $completionRecord->fresh()->getAttributes(),
            );
        });

        return back()->with('success', 'Status persetujuan berhasil diperbarui.');
    }

    public function destroy(Request $request, CompletionRecord $completionRecord, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('complete.delete');
        $this->authorizeOwnership($request, $completionRecord);

        $validated = $request->validate([
            'updated_at' => ['nullable', 'date'],
        ]);

        $this->assertFresh($completionRecord, $validated['updated_at'] ?? null);

        DB::transaction(function () use ($request, $completionRecord, $activityLogger): void {
            $oldValues = $completionRecord->getOriginal();
            $completionRecord->update(['updated_by' => $request->user()->id]);
            $completionRecord->delete();

            $activityLogger->log($request, 'complete', 'delete', $completionRecord, $oldValues, null);
        });

        return back()->with('success', 'Data Modul Complete berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('completion_number', 'like', "%{$search}%")
                        ->orWhereHas('orderStatus', fn (Builder $query) => $query->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('orderEdk', fn (Builder $query) => $query->where('edk_reference', 'like', "%{$search}%"));
                });
            })
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            ->when(! $user->isAccountManager() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId))
            ->when($filters['approval_status'] ?? null, fn (Builder $query, string $approvalStatus) => $query->where('approval_status', $approvalStatus))
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth));
    }

    /**
     * @return array<int, array{key: string, label: string, value: int, tone: string}>
     */
    private function approvalStats(Builder $query): array
    {
        $counts = (clone $query)
            ->select('approval_status', DB::raw('count(*) as total'))
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        return [
            [
                'key' => 'total',
                'label' => 'Total Complete',
                'value' => (int) $counts->sum(),
                'tone' => 'primary',
            ],
            [
                'key' => CompletionRecord::STATUS_DISETUJUI,
                'label' => CompletionRecord::LABELS[CompletionRecord::STATUS_DISETUJUI],
                'value' => (int) ($counts[CompletionRecord::STATUS_DISETUJUI] ?? 0),
                'tone' => 'success',
            ],
            [
                'key' => CompletionRecord::STATUS_TIDAK_DISETUJUI,
                'label' => CompletionRecord::LABELS[CompletionRecord::STATUS_TIDAK_DISETUJUI],
                'value' => (int) ($counts[CompletionRecord::STATUS_TIDAK_DISETUJUI] ?? 0),
                'tone' => 'danger',
            ],
            [
                'key' => CompletionRecord::STATUS_REVISI,
                'label' => CompletionRecord::LABELS[CompletionRecord::STATUS_REVISI],
                'value' => (int) ($counts[CompletionRecord::STATUS_REVISI] ?? 0),
                'tone' => 'warning',
            ],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    private function approvalStatusOptions(): array
    {
        return collect(CompletionRecord::LABELS)
            ->map(fn (string $label, string $status) => [
                'value' => $status,
                'label' => $label,
                'tone' => $this->approvalTone($status),
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
     * @return array<int, array{id: int, label: string, inputer_id: int, account_manager_id: int}>
     */
    private function orderStatusOptions(User $user): array
    {
        return OrderStatus::query()
            ->visibleTo($user)
            ->latest('updated_at')
            ->limit(250)
            ->get(['id', 'order_number', 'customer_name', 'period_month', 'inputer_id', 'account_manager_id'])
            ->map(fn (OrderStatus $orderStatus) => [
                'id' => $orderStatus->id,
                'label' => trim($orderStatus->order_number.' - '.($orderStatus->customer_name ?: 'Tanpa pelanggan').' - '.$orderStatus->period_month),
                'inputer_id' => $orderStatus->inputer_id,
                'account_manager_id' => $orderStatus->account_manager_id,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, label: string, inputer_id: int, account_manager_id: int}>
     */
    private function orderEdkOptions(User $user): array
    {
        return OrderEdk::query()
            ->visibleTo($user)
            ->latest('updated_at')
            ->limit(250)
            ->get(['id', 'edk_reference', 'customer_name', 'period_month', 'inputer_id', 'account_manager_id'])
            ->map(fn (OrderEdk $orderEdk) => [
                'id' => $orderEdk->id,
                'label' => trim($orderEdk->edk_reference.' - '.($orderEdk->customer_name ?: 'Tanpa pelanggan').' - '.$orderEdk->period_month),
                'inputer_id' => $orderEdk->inputer_id,
                'account_manager_id' => $orderEdk->account_manager_id,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCompletionRecord(CompletionRecord $completionRecord): array
    {
        return [
            'id' => $completionRecord->id,
            'completion_number' => $completionRecord->completion_number,
            'order_status_id' => $completionRecord->order_status_id,
            'order_status_label' => $completionRecord->orderStatus
                ? $completionRecord->orderStatus->order_number.' - '.$completionRecord->orderStatus->period_month
                : null,
            'order_edk_id' => $completionRecord->order_edk_id,
            'order_edk_label' => $completionRecord->orderEdk
                ? $completionRecord->orderEdk->edk_reference.' - '.$completionRecord->orderEdk->period_month
                : null,
            'inputer_id' => $completionRecord->inputer_id,
            'inputer_name' => $completionRecord->inputer?->name,
            'account_manager_id' => $completionRecord->account_manager_id,
            'account_manager_name' => $completionRecord->accountManager?->name,
            'approval_status' => $completionRecord->approval_status,
            'approval_status_label' => CompletionRecord::LABELS[$completionRecord->approval_status] ?? $completionRecord->approval_status,
            'approval_status_tone' => $this->approvalTone($completionRecord->approval_status),
            'completed_at' => $completionRecord->completed_at?->format('Y-m-d'),
            'approved_at' => $completionRecord->approved_at?->format('Y-m-d H:i'),
            'revision_note' => $completionRecord->revision_note,
            'period_month' => $completionRecord->period_month,
            'notes' => $completionRecord->notes,
            'updated_at' => $completionRecord->updated_at?->format('Y-m-d H:i'),
            'updated_at_token' => $this->updatedAtToken($completionRecord),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function approvalFieldsForSave(array $data, User $user, ?string $previousStatus = null): array
    {
        $status = $data['approval_status'];
        $changed = $previousStatus === null || $previousStatus !== $status;

        if ($status === CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN) {
            $data['approved_by'] = null;
            $data['approved_at'] = null;

            return $data;
        }

        if ($changed) {
            $data['approved_by'] = $user->id;
            $data['approved_at'] = now();
        }

        if ($status !== CompletionRecord::STATUS_REVISI && blank($data['revision_note'] ?? null)) {
            $data['revision_note'] = null;
        }

        return $data;
    }

    private function approvalAbility(string $status): string
    {
        return match ($status) {
            CompletionRecord::STATUS_DISETUJUI => 'complete.approve',
            CompletionRecord::STATUS_TIDAK_DISETUJUI => 'complete.reject',
            CompletionRecord::STATUS_REVISI => 'complete.request_revision',
            default => 'complete.approve',
        };
    }

    private function approvalAction(string $status): string
    {
        return match ($status) {
            CompletionRecord::STATUS_DISETUJUI => 'approve',
            CompletionRecord::STATUS_TIDAK_DISETUJUI => 'reject',
            CompletionRecord::STATUS_REVISI => 'request_revision',
            default => 'approval_update',
        };
    }

    private function approvalTone(string $status): string
    {
        return match ($status) {
            CompletionRecord::STATUS_DISETUJUI => 'success',
            CompletionRecord::STATUS_TIDAK_DISETUJUI => 'danger',
            default => 'warning',
        };
    }

    private function authorizeOwnership(Request $request, CompletionRecord $completionRecord): void
    {
        abort_unless($request->user()->isSuperAdmin() || $completionRecord->inputer_id === $request->user()->id, 403);
    }
}
