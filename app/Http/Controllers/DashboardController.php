<?php

namespace App\Http\Controllers;

use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('dashboard.view_related');

        $filters = $request->validate([
            'period_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'inputer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $user = $request->user();
        $orderStatuses = $this->orderStatusQuery($filters, $user);
        $orderEdks = $this->orderEdkQuery($filters, $user);
        $completionRecords = $this->completionRecordQuery($filters, $user);

        $metrics = $this->metrics($orderStatuses, $orderEdks);
        $recaps = $user->isSuperAdmin()
            ? [
                'inputers' => $this->userRecap(User::ROLE_ADMIN_INPUTER, 'inputer_id', $filters, $user),
                'accountManagers' => $this->userRecap(User::ROLE_ACCOUNT_MANAGER, 'account_manager_id', $filters, $user),
            ]
            : [
                'inputers' => [],
                'accountManagers' => [],
            ];

        return Inertia::render('Dashboard', [
            'cards' => $this->cards($metrics, $filters['period_month'] ?? null),
            'charts' => [
                'statusComposition' => $this->statusComposition($orderStatuses, $orderEdks),
                'barCharts' => $this->barCharts($user, $recaps, $metrics, (clone $completionRecords)->count()),
            ],
            'recaps' => $recaps,
            'filters' => [
                'period_month' => $filters['period_month'] ?? '',
                'inputer_id' => $user->isSuperAdmin() ? ($filters['inputer_id'] ?? '') : '',
                'account_manager_id' => $user->isSuperAdmin() ? ($filters['account_manager_id'] ?? '') : '',
            ],
            'inputerOptions' => $this->userOptions(User::ROLE_ADMIN_INPUTER),
            'accountManagerOptions' => $this->userOptions(User::ROLE_ACCOUNT_MANAGER),
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function orderStatusQuery(array $filters, User $user): Builder
    {
        return OrderStatus::query()
            ->visibleTo($user)
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth))
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            ->when($user->isSuperAdmin() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function orderEdkQuery(array $filters, User $user): Builder
    {
        return OrderEdk::query()
            ->visibleTo($user)
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth))
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            ->when($user->isSuperAdmin() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function completionRecordQuery(array $filters, User $user): Builder
    {
        return CompletionRecord::query()
            ->visibleTo($user)
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth))
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            ->when($user->isSuperAdmin() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId));
    }

    /**
     * @return array<string, int>
     */
    private function metrics(Builder $orderStatuses, Builder $orderEdks): array
    {
        $totalEdk = (clone $orderEdks)->count();
        $edkComplete = (clone $orderEdks)->where('status', OrderEdk::STATUS_COMPLETE)->count();
        $edkTidakLanjut = (clone $orderEdks)->where('status', OrderEdk::STATUS_TIDAK_LANJUT)->count();

        return [
            'total_order' => (clone $orderStatuses)->count(),
            'pending_baso' => (clone $orderStatuses)->where('status', OrderStatus::STATUS_PENDING_BASO)->count(),
            'complete' => (clone $orderStatuses)->where('status', OrderStatus::STATUS_COMPLETE)->count() + $edkComplete,
            'failed' => (clone $orderStatuses)->where('status', OrderStatus::STATUS_FAILED)->count(),
            'sisa_populasi' => max($totalEdk - $edkComplete - $edkTidakLanjut, 0),
            'total_edk' => $totalEdk,
        ];
    }

    /**
     * @param  array<string, int>  $metrics
     * @return array<int, array{key: string, label: string, value: int, context: string, tone: string}>
     */
    private function cards(array $metrics, ?string $periodMonth): array
    {
        $context = $periodMonth ? 'Periode '.$periodMonth : 'Semua periode';

        return [
            ['key' => 'total_order', 'label' => 'Total Order', 'value' => $metrics['total_order'], 'context' => $context, 'tone' => 'primary'],
            ['key' => 'pending_baso', 'label' => 'Pending BASO', 'value' => $metrics['pending_baso'], 'context' => $context, 'tone' => 'warning'],
            ['key' => 'complete', 'label' => 'Complete', 'value' => $metrics['complete'], 'context' => 'Order Status + EDK', 'tone' => 'success'],
            ['key' => 'failed', 'label' => 'Failed', 'value' => $metrics['failed'], 'context' => $context, 'tone' => 'danger'],
            ['key' => 'sisa_populasi', 'label' => 'Sisa Populasi', 'value' => $metrics['sisa_populasi'], 'context' => 'Total EDK - Complete - Tidak Lanjut', 'tone' => 'info'],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, value: int, tone: string, color: string}>
     */
    private function statusComposition(Builder $orderStatuses, Builder $orderEdks): array
    {
        $orderStatusCounts = (clone $orderStatuses)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $orderEdkCounts = (clone $orderEdks)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $orderStatusItems = collect(OrderStatus::LABELS)
            ->map(fn (string $label, string $status) => $this->chartItem(
                'order_status_'.$status,
                'OS '.$label,
                (int) ($orderStatusCounts[$status] ?? 0),
                $this->orderStatusTone($status),
            ));

        $orderEdkItems = collect(OrderEdk::LABELS)
            ->map(fn (string $label, string $status) => $this->chartItem(
                'order_edk_'.$status,
                'EDK '.$label,
                (int) ($orderEdkCounts[$status] ?? 0),
                $this->orderEdkTone($status),
            ));

        return $orderStatusItems
            ->merge($orderEdkItems)
            ->values()
            ->all();
    }

    /**
     * @param  array{inputers: array<int, array<string, mixed>>, accountManagers: array<int, array<string, mixed>>}  $recaps
     * @param  array<string, int>  $metrics
     * @return array<int, array{key: string, title: string, description: string, items: array<int, array{label: string, value: int, color: string}>}>
     */
    private function barCharts(User $user, array $recaps, array $metrics, int $completionTotal): array
    {
        if ($user->isSuperAdmin()) {
            return [
                [
                    'key' => 'inputers',
                    'title' => 'Rekap Berdasarkan Inputer',
                    'description' => 'Total data operasional yang dikelola tiap Inputer.',
                    'items' => $this->barItems($recaps['inputers']),
                ],
                [
                    'key' => 'account_managers',
                    'title' => 'Rekap Berdasarkan Account Manager',
                    'description' => 'Total data operasional yang terkait tiap Account Manager.',
                    'items' => $this->barItems($recaps['accountManagers']),
                ],
            ];
        }

        return [
            [
                'key' => 'related_modules',
                'title' => 'Rekap Modul Terkait',
                'description' => 'Jumlah data yang sesuai cakupan akses pengguna.',
                'items' => [
                    ['label' => 'Order Status', 'value' => $metrics['total_order'], 'color' => '#E42313'],
                    ['label' => 'Order EDK', 'value' => $metrics['total_edk'], 'color' => '#2563EB'],
                    ['label' => 'Modul Complete', 'value' => $completionTotal, 'color' => '#16A34A'],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $recaps
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function barItems(array $recaps): array
    {
        return collect($recaps)
            ->take(8)
            ->map(fn (array $recap, int $index) => [
                'label' => $recap['name'],
                'value' => $recap['total'],
                'color' => $index % 2 === 0 ? '#E42313' : '#2563EB',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function userRecap(string $role, string $groupField, array $filters, User $user): array
    {
        $orderStatuses = $this->orderStatusQuery($filters, $user);
        $orderEdks = $this->orderEdkQuery($filters, $user);
        $completionRecords = $this->completionRecordQuery($filters, $user);

        $orderStatusCounts = $this->countBy($orderStatuses, $groupField);
        $orderEdkCounts = $this->countBy($orderEdks, $groupField);
        $completionCounts = $this->countBy($completionRecords, $groupField);
        $pendingBasoCounts = $this->countBy((clone $orderStatuses)->where('status', OrderStatus::STATUS_PENDING_BASO), $groupField);
        $failedCounts = $this->countBy((clone $orderStatuses)->where('status', OrderStatus::STATUS_FAILED), $groupField);
        $orderStatusCompleteCounts = $this->countBy((clone $orderStatuses)->where('status', OrderStatus::STATUS_COMPLETE), $groupField);
        $orderEdkCompleteCounts = $this->countBy((clone $orderEdks)->where('status', OrderEdk::STATUS_COMPLETE), $groupField);
        $orderEdkTidakLanjutCounts = $this->countBy((clone $orderEdks)->where('status', OrderEdk::STATUS_TIDAK_LANJUT), $groupField);

        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->when($role === User::ROLE_ADMIN_INPUTER ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->whereKey($inputerId))
            ->when($role === User::ROLE_ACCOUNT_MANAGER ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->whereKey($accountManagerId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $recapUser) use (
                $orderStatusCounts,
                $orderEdkCounts,
                $completionCounts,
                $pendingBasoCounts,
                $failedCounts,
                $orderStatusCompleteCounts,
                $orderEdkCompleteCounts,
                $orderEdkTidakLanjutCounts,
            ): array {
                $orderStatus = (int) ($orderStatusCounts[$recapUser->id] ?? 0);
                $orderEdk = (int) ($orderEdkCounts[$recapUser->id] ?? 0);
                $complete = (int) ($orderStatusCompleteCounts[$recapUser->id] ?? 0) + (int) ($orderEdkCompleteCounts[$recapUser->id] ?? 0);
                $sisaPopulasi = max($orderEdk - (int) ($orderEdkCompleteCounts[$recapUser->id] ?? 0) - (int) ($orderEdkTidakLanjutCounts[$recapUser->id] ?? 0), 0);
                $modulComplete = (int) ($completionCounts[$recapUser->id] ?? 0);

                return [
                    'id' => $recapUser->id,
                    'name' => $recapUser->name,
                    'order_status' => $orderStatus,
                    'order_edk' => $orderEdk,
                    'modul_complete' => $modulComplete,
                    'pending_baso' => (int) ($pendingBasoCounts[$recapUser->id] ?? 0),
                    'complete' => $complete,
                    'failed' => (int) ($failedCounts[$recapUser->id] ?? 0),
                    'sisa_populasi' => $sisaPopulasi,
                    'total' => $orderStatus + $orderEdk + $modulComplete,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int|string, int>
     */
    private function countBy(Builder $query, string $groupField): Collection
    {
        return (clone $query)
            ->select($groupField, DB::raw('count(*) as total'))
            ->groupBy($groupField)
            ->pluck('total', $groupField);
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
     * @return array{key: string, label: string, value: int, tone: string, color: string}
     */
    private function chartItem(string $key, string $label, int $value, string $tone): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'tone' => $tone,
            'color' => $this->toneColor($tone),
        ];
    }

    private function orderStatusTone(string $status): string
    {
        return match ($status) {
            OrderStatus::STATUS_PROVISIONING => 'info',
            OrderStatus::STATUS_PENDING_BASO, OrderStatus::STATUS_PENDING_BILLING_APPROVAL => 'warning',
            OrderStatus::STATUS_COMPLETE => 'success',
            OrderStatus::STATUS_FAILED => 'danger',
            default => 'neutral',
        };
    }

    private function orderEdkTone(string $status): string
    {
        return match ($status) {
            OrderEdk::STATUS_LANJUT, OrderEdk::STATUS_OGP => 'info',
            OrderEdk::STATUS_BELUM_INPUT => 'warning',
            OrderEdk::STATUS_COMPLETE => 'success',
            default => 'neutral',
        };
    }

    private function toneColor(string $tone): string
    {
        return match ($tone) {
            'primary' => '#E42313',
            'success' => '#16A34A',
            'warning' => '#D97706',
            'danger' => '#DC2626',
            'info' => '#2563EB',
            default => '#64748B',
        };
    }
}
