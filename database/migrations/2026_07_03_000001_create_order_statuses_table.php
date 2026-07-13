<?php

/**
 * Migration: Membuat Tabel order_statuses.
 *
 * Tabel ini menyimpan data status order (Order Status) yang merupakan
 * inti dari sistem monitoring order SMO Telkom. Setiap record merepresentasikan
 * satu order dengan status provisioning tertentu dalam periode bulan tertentu.
 *
 * Relasi:
 * - Setiap order dimiliki oleh satu Inputer (admin yang menginput data)
 * - Setiap order ditangani oleh satu Account Manager
 * - Audit trail: created_by dan updated_by mencatat siapa yang membuat/mengubah data
 *
 * Constraint unik: Kombinasi order_number + period_month harus unik,
 * sehingga nomor order yang sama bisa digunakan di periode bulan berbeda.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration untuk membuat tabel order_statuses.
     */
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            // Primary key auto-increment
            $table->id();

            // Nomor order dari sistem sumber (contoh: nomor order NCX)
            $table->string('order_number', 100);

            // Nama pelanggan terkait order (opsional, karena mungkin belum diketahui saat input awal)
            $table->string('customer_name', 150)->nullable();

            // Nama layanan/produk yang dipesan (opsional)
            $table->string('service_name', 150)->nullable();

            // FK ke tabel users: Admin Inputer yang bertanggung jawab menginput data ini
            // restrictOnDelete: Tidak bisa menghapus user jika masih ada order yang terkait
            $table->foreignId('inputer_id')->constrained('users')->restrictOnDelete();

            // FK ke tabel users: Account Manager yang menangani order ini
            // restrictOnDelete: Melindungi data relasi agar tidak hilang
            $table->foreignId('account_manager_id')->constrained('users')->restrictOnDelete();

            // Status order saat ini, dengan index untuk mempercepat filter berdasarkan status
            // Nilai yang tersedia:
            // - provisioning: Order sedang dalam proses provisioning
            // - pending_baso: Menunggu Berita Acara Serah Operasi (BASO)
            // - pending_billing_approval: Menunggu persetujuan billing
            // - complete: Order telah selesai
            // - failed: Order gagal diproses
            // - cancel_abandoned: Order dibatalkan atau ditinggalkan
            $table->enum('status', [
                'provisioning',
                'pending_baso',
                'pending_billing_approval',
                'complete',
                'failed',
                'cancel_abandoned',
            ])->index();

            // Tahapan detail dalam proses provisioning (opsional, untuk tracking progress lebih rinci)
            $table->string('provisioning_stage', 150)->nullable();

            // Periode bulan data dalam format YYYY-MM (contoh: '2026-07')
            // Digunakan untuk mengelompokkan data per periode pelaporan
            // Index untuk mempercepat filter dan query per periode
            $table->char('period_month', 7)->index();

            // Sumber sistem tempat data diinput (default: 'Dashboard NCX')
            $table->string('source_system', 100)->default('Dashboard NCX');

            // Catatan tambahan dari inputer (opsional)
            $table->text('notes')->nullable();

            // Audit trail: User yang pertama kali membuat record ini
            // restrictOnDelete: Menjaga integritas audit trail
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Audit trail: User yang terakhir memperbarui record ini (opsional)
            // nullOnDelete: Jika user dihapus, field ini diset null (data audit parsial tetap ada)
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamp created_at dan updated_at otomatis dari Laravel
            $table->timestamps();

            // Soft delete: Data tidak benar-benar dihapus, hanya ditandai dengan deleted_at
            $table->softDeletes();

            // Constraint unik: Satu nomor order hanya boleh muncul sekali dalam satu periode bulan
            $table->unique(['order_number', 'period_month']);

            // Index komposit untuk mempercepat query filter berdasarkan inputer + periode
            // Digunakan saat Admin Inputer melihat daftar order miliknya per periode
            $table->index(['inputer_id', 'period_month']);

            // Index komposit untuk mempercepat query filter berdasarkan account manager + periode
            // Digunakan saat Account Manager melihat daftar order yang ditanganinya per periode
            $table->index(['account_manager_id', 'period_month']);
        });
    }

    /**
     * Batalkan migration: Hapus tabel order_statuses.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
