<?php

namespace Database\Seeders;

use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CompletionRecordSeeder extends Seeder
{
    /**
     * Seed dummy completion records linked to completed orders and EDKs.
     */
    public function run(): void
    {
        $superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
        $completionCounter = 1;

        // Create completion records for completed order statuses
        $completedOrders = OrderStatus::where('status', OrderStatus::STATUS_COMPLETE)->get();

        foreach ($completedOrders as $order) {
            $approvalStatus = $this->weightedApprovalStatus();
            $baseDate = Carbon::parse($order->created_at);
            $completedAt = $baseDate->copy()->addDays(rand(1, 10));

            $approvedBy = null;
            $approvedAt = null;
            $revisionNote = null;

            if ($approvalStatus === CompletionRecord::STATUS_DISETUJUI) {
                $approvedBy = $superAdmin->id;
                $approvedAt = $completedAt->copy()->addDays(rand(1, 5));
            } elseif ($approvalStatus === CompletionRecord::STATUS_TIDAK_DISETUJUI) {
                $approvedBy = $superAdmin->id;
                $approvedAt = $completedAt->copy()->addDays(rand(1, 5));
                $revisionNote = $this->getRandomRejectionNote();
            } elseif ($approvalStatus === CompletionRecord::STATUS_REVISI) {
                $revisionNote = $this->getRandomRevisionNote();
            }

            $completionNumber = sprintf('CMP-%s-%05d', str_replace('-', '', $order->period_month), $completionCounter);
            $completionCounter++;

            $notes = null;
            if (rand(1, 3) === 1) {
                $noteOptions = [
                    'BASO sudah ditandatangani pelanggan.',
                    'Layanan sudah aktif dan berjalan normal.',
                    'Dokumen serah terima sudah lengkap.',
                    'Pelanggan sudah melakukan pembayaran pertama.',
                    'Aktivasi berhasil, speed test sesuai SLA.',
                ];
                $notes = $noteOptions[array_rand($noteOptions)];
            }

            CompletionRecord::create([
                'completion_number' => $completionNumber,
                'order_status_id' => $order->id,
                'order_edk_id' => null,
                'inputer_id' => $order->inputer_id,
                'account_manager_id' => $order->account_manager_id,
                'approval_status' => $approvalStatus,
                'completed_at' => $completedAt,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'revision_note' => $revisionNote,
                'period_month' => $order->period_month,
                'notes' => $notes,
                'created_by' => $order->inputer_id,
                'updated_by' => $approvedBy,
                'created_at' => $completedAt,
                'updated_at' => $approvedAt ?? $completedAt,
            ]);
        }

        // Create completion records for completed EDKs
        $completedEdks = OrderEdk::where('status', OrderEdk::STATUS_COMPLETE)->get();

        foreach ($completedEdks as $edk) {
            $approvalStatus = $this->weightedApprovalStatus();
            $baseDate = Carbon::parse($edk->created_at);
            $completedAt = $baseDate->copy()->addDays(rand(1, 10));

            $approvedBy = null;
            $approvedAt = null;
            $revisionNote = null;

            if ($approvalStatus === CompletionRecord::STATUS_DISETUJUI) {
                $approvedBy = $superAdmin->id;
                $approvedAt = $completedAt->copy()->addDays(rand(1, 5));
            } elseif ($approvalStatus === CompletionRecord::STATUS_TIDAK_DISETUJUI) {
                $approvedBy = $superAdmin->id;
                $approvedAt = $completedAt->copy()->addDays(rand(1, 5));
                $revisionNote = $this->getRandomRejectionNote();
            } elseif ($approvalStatus === CompletionRecord::STATUS_REVISI) {
                $revisionNote = $this->getRandomRevisionNote();
            }

            $completionNumber = sprintf('CMP-%s-%05d', str_replace('-', '', $edk->period_month), $completionCounter);
            $completionCounter++;

            CompletionRecord::create([
                'completion_number' => $completionNumber,
                'order_status_id' => null,
                'order_edk_id' => $edk->id,
                'inputer_id' => $edk->inputer_id,
                'account_manager_id' => $edk->account_manager_id,
                'approval_status' => $approvalStatus,
                'completed_at' => $completedAt,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'revision_note' => $revisionNote,
                'period_month' => $edk->period_month,
                'notes' => null,
                'created_by' => $edk->inputer_id,
                'updated_by' => $approvedBy,
                'created_at' => $completedAt,
                'updated_at' => $approvedAt ?? $completedAt,
            ]);
        }
    }

    /**
     * Return a weighted random approval status (more likely to be approved).
     */
    private function weightedApprovalStatus(): string
    {
        $pool = [
            CompletionRecord::STATUS_DISETUJUI,
            CompletionRecord::STATUS_DISETUJUI,
            CompletionRecord::STATUS_DISETUJUI,
            CompletionRecord::STATUS_DISETUJUI,
            CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN,
            CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN,
            CompletionRecord::STATUS_REVISI,
            CompletionRecord::STATUS_TIDAK_DISETUJUI,
        ];

        return $pool[array_rand($pool)];
    }

    private function getRandomRejectionNote(): string
    {
        $notes = [
            'Dokumen BASO belum lengkap, harap dilengkapi terlebih dahulu.',
            'Nomor kontrak tidak sesuai dengan data di sistem.',
            'Bukti aktivasi layanan belum dilampirkan.',
            'Data pelanggan tidak cocok dengan dokumen pendukung.',
        ];

        return $notes[array_rand($notes)];
    }

    private function getRandomRevisionNote(): string
    {
        $notes = [
            'Harap perbaiki tanggal penyelesaian, tidak sesuai dengan laporan lapangan.',
            'Nama pelanggan perlu disesuaikan dengan dokumen kontrak resmi.',
            'Nomor order tidak sesuai, silakan dicek kembali.',
            'Lampiran foto instalasi belum sesuai standar dokumentasi.',
        ];

        return $notes[array_rand($notes)];
    }
}
