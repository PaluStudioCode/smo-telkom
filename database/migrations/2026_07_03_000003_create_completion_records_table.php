<?php

/**
 * Migration: Membuat Tabel completion_records.
 *
 * Tabel ini menyimpan data Catatan Penyelesaian (Completion Record) yang
 * menghubungkan Order Status dan/atau Order EDK dengan proses penyelesaian.
 * Tabel ini juga mengelola alur persetujuan (approval workflow) dimana
 * setiap catatan penyelesaian harus disetujui oleh pihak berwenang.
 *
 * Alur Approval:
 * 1. Admin Inputer membuat record → status 'menunggu_persetujuan'
 * 2. Reviewer mereview → bisa 'disetujui', 'tidak_disetujui', atau 'revisi'
 * 3. Jika 'revisi' → Admin Inputer memperbaiki dan re-submit → kembali ke 'menunggu_persetujuan'
 *
 * Relasi:
 * - Bisa terhubung ke Order Status (opsional, nullable)
 * - Bisa terhubung ke Order EDK (opsional, nullable)
 * - Minimal salah satu harus terisi (divalidasi di level aplikasi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration untuk membuat tabel completion_records.
     */
    public function up(): void
    {
        Schema::create('completion_records', function (Blueprint $table) {
            // Primary key auto-increment
            $table->id();

            // Nomor catatan penyelesaian, identitas unik per periode bulan
            $table->string('completion_number', 100);

            // FK ke tabel order_statuses: Referensi ke Order Status yang terkait (opsional)
            // nullOnDelete: Jika Order Status dihapus, referensi diset null (record tetap ada)
            $table->foreignId('order_status_id')->nullable()->constrained('order_statuses')->nullOnDelete();

            // FK ke tabel order_edks: Referensi ke Order EDK yang terkait (opsional)
            // nullOnDelete: Jika Order EDK dihapus, referensi diset null
            // Catatan: Minimal salah satu dari order_status_id atau order_edk_id harus diisi
            $table->foreignId('order_edk_id')->nullable()->constrained('order_edks')->nullOnDelete();

            // FK ke tabel users: Admin Inputer yang bertanggung jawab menginput data ini
            $table->foreignId('inputer_id')->constrained('users')->restrictOnDelete();

            // FK ke tabel users: Account Manager yang menangani record ini
            $table->foreignId('account_manager_id')->constrained('users')->restrictOnDelete();

            // Status persetujuan dalam alur approval, dengan index untuk filter cepat
            // Nilai yang tersedia:
            // - menunggu_persetujuan: Menunggu review dari atasan/Super Admin
            // - disetujui: Telah disetujui, penyelesaian order dikonfirmasi
            // - tidak_disetujui: Ditolak, penyelesaian order tidak valid
            // - revisi: Perlu diperbaiki oleh inputer sebelum di-review ulang
            $table->enum('approval_status', [
                'menunggu_persetujuan',
                'disetujui',
                'tidak_disetujui',
                'revisi',
            ])->index();

            // Tanggal penyelesaian aktual order (opsional, diisi saat order benar-benar selesai)
            $table->date('completed_at')->nullable();

            // FK ke tabel users: User yang menyetujui/menolak record ini (opsional)
            // nullOnDelete: Jika reviewer dihapus, field ini diset null
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamp kapan persetujuan/penolakan dilakukan (opsional)
            $table->timestamp('approved_at')->nullable();

            // Catatan revisi dari reviewer (opsional, wajib diisi jika status 'revisi')
            // Berisi instruksi/alasan mengapa record perlu diperbaiki
            $table->text('revision_note')->nullable();

            // Periode bulan data dalam format YYYY-MM
            // Index untuk mempercepat filter per periode
            $table->char('period_month', 7)->index();

            // Catatan tambahan (opsional)
            $table->text('notes')->nullable();

            // Audit trail: User yang membuat record
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Audit trail: User yang terakhir memperbarui record
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamp created_at dan updated_at
            $table->timestamps();

            // Soft delete: Data ditandai dengan deleted_at, bukan dihapus permanen
            $table->softDeletes();

            // Constraint unik: Satu nomor completion hanya boleh muncul sekali per periode bulan
            $table->unique(['completion_number', 'period_month']);

            // Index komposit untuk mempercepat query filter inputer per periode
            $table->index(['inputer_id', 'period_month']);

            // Index komposit untuk mempercepat query filter account manager per periode
            $table->index(['account_manager_id', 'period_month']);
        });
    }

    /**
     * Batalkan migration: Hapus tabel completion_records.
     */
    public function down(): void
    {
        Schema::dropIfExists('completion_records');
    }
};
