<?php

namespace App\Services;

use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StatusTransitionService
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $orderStatusTransitions = [
        OrderStatus::STATUS_PROVISIONING => [
            OrderStatus::STATUS_PENDING_BASO,
            OrderStatus::STATUS_PENDING_BILLING_APPROVAL,
            OrderStatus::STATUS_COMPLETE,
            OrderStatus::STATUS_FAILED,
            OrderStatus::STATUS_CANCEL_ABANDONED,
        ],
        OrderStatus::STATUS_PENDING_BASO => [
            OrderStatus::STATUS_PENDING_BILLING_APPROVAL,
            OrderStatus::STATUS_COMPLETE,
            OrderStatus::STATUS_FAILED,
            OrderStatus::STATUS_CANCEL_ABANDONED,
        ],
        OrderStatus::STATUS_PENDING_BILLING_APPROVAL => [
            OrderStatus::STATUS_COMPLETE,
            OrderStatus::STATUS_FAILED,
            OrderStatus::STATUS_CANCEL_ABANDONED,
        ],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private array $orderEdkTransitions = [
        OrderEdk::STATUS_BELUM_INPUT => [
            OrderEdk::STATUS_LANJUT,
            OrderEdk::STATUS_TIDAK_LANJUT,
            OrderEdk::STATUS_OGP,
            OrderEdk::STATUS_COMPLETE,
        ],
        OrderEdk::STATUS_LANJUT => [
            OrderEdk::STATUS_OGP,
            OrderEdk::STATUS_COMPLETE,
            OrderEdk::STATUS_TIDAK_LANJUT,
        ],
        OrderEdk::STATUS_OGP => [
            OrderEdk::STATUS_COMPLETE,
            OrderEdk::STATUS_TIDAK_LANJUT,
        ],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private array $completionTransitions = [
        CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN => [
            CompletionRecord::STATUS_DISETUJUI,
            CompletionRecord::STATUS_TIDAK_DISETUJUI,
            CompletionRecord::STATUS_REVISI,
        ],
        CompletionRecord::STATUS_REVISI => [
            CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN,
        ],
        CompletionRecord::STATUS_TIDAK_DISETUJUI => [
            CompletionRecord::STATUS_REVISI,
        ],
        CompletionRecord::STATUS_DISETUJUI => [
            CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN,
            CompletionRecord::STATUS_TIDAK_DISETUJUI,
            CompletionRecord::STATUS_REVISI,
        ],
    ];

    public function assertOrderStatusTransition(?OrderStatus $record, string $nextStatus, User $user): void
    {
        if (! $record) {
            if (! $user->isSuperAdmin() && in_array($nextStatus, OrderStatus::FINAL_STATUSES, true)) {
                $this->fail('Hanya Super Admin yang dapat membuat data dengan status akhir.');
            }

            return;
        }

        $currentStatus = $record->status;

        if ($currentStatus === $nextStatus) {
            return;
        }

        if (! $user->isSuperAdmin() && ($record->isFinalStatus() || in_array($nextStatus, OrderStatus::FINAL_STATUSES, true))) {
            $this->fail('Hanya Super Admin yang dapat mengubah status akhir.');
        }

        if ($user->isSuperAdmin() && $record->isFinalStatus()) {
            return;
        }

        if (! in_array($nextStatus, $this->orderStatusTransitions[$currentStatus] ?? [], true)) {
            $this->fail('Perubahan status Order Status tidak mengikuti alur yang diizinkan.');
        }
    }

    public function assertOrderEdkTransition(?OrderEdk $record, string $nextStatus, User $user): void
    {
        if (! $record) {
            if (! $user->isSuperAdmin() && in_array($nextStatus, OrderEdk::FINAL_STATUSES, true)) {
                $this->fail('Hanya Super Admin yang dapat membuat data dengan status akhir.');
            }

            return;
        }

        $currentStatus = $record->status;

        if ($currentStatus === $nextStatus) {
            return;
        }

        if (! $user->isSuperAdmin() && ($record->isFinalStatus() || in_array($nextStatus, OrderEdk::FINAL_STATUSES, true))) {
            $this->fail('Hanya Super Admin yang dapat mengubah status akhir.');
        }

        if ($user->isSuperAdmin() && $record->isFinalStatus()) {
            return;
        }

        if (! in_array($nextStatus, $this->orderEdkTransitions[$currentStatus] ?? [], true)) {
            $this->fail('Perubahan status Order EDK tidak mengikuti alur yang diizinkan.');
        }
    }

    public function assertCompletionTransition(CompletionRecord $record, string $nextStatus): void
    {
        if ($record->approval_status === $nextStatus) {
            return;
        }

        if (! in_array($nextStatus, $this->completionTransitions[$record->approval_status] ?? [], true)) {
            $this->fail('Perubahan status persetujuan tidak mengikuti alur yang diizinkan.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            'status' => $message,
            'approval_status' => $message,
        ]);
    }
}
