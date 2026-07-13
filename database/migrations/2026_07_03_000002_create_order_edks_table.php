<?php

/**
 * Migration: Membuat Tabel order_edks.
 *
 * Tabel ini menyimpan data Order EDK (Evaluasi Daftar Kerja) yang merupakan
 * komponen penting dalam proses monitoring order SMO Telkom. EDK merepresentasikan
 * daftar kerja evaluasi yang digunakan untuk melacak dan mengevaluasi order.
 *
 * Strukturnya mirip dengan tabel order_statuses, namun dengan
 * status dan referensi yang berbeda sesuai proses bisnis EDK.
 *
 * Relasi:
 * - Setiap EDK dimiliki oleh satu Inputer
 * - Setiap EDK ditangani oleh satu Account Manager
 * - Dapat ditautkan ke Completion Record untuk catatan penyelesaian
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration untuk membuat tabel order_edks.
     */
    public function up(): void
    {
        Schema::create('order_edks', function (Blueprint $table) {
            // Primary key auto-increment
            $table->id();

            // Nomor referensi EDK, identitas unik dari evaluasi daftar kerja
            $table->string('edk_reference', 100);

            // Nama pelanggan terkait EDK (opsional)
            $table->string('customer_name', 150)->nullable();

            // FK ke tabel users: Admin Inputer yang bertanggung jawab menginput data ini
            // restrictOnDelete: Tidak bisa menghapus user jika masih ada EDK yang terkait
            $table->foreignId('inputer_id')->constrained('users')->restrictOnDelete();

            // FK ke tabel users: Account Manager yang menangani EDK ini
            $table->foreignId('account_manager_id')->constrained('users')->restrictOnDelete();

            // Status EDK saat ini, dengan index untuk mempercepat filter
            // Nilai yang tersedia:
            // - lanjut: EDK dilanjutkan untuk diproses
            // - tidak_lanjut: EDK tidak dilanjutkan (dibatalkan)
            // - belum_input: EDK belum diinput ke sistem terkait
            // - ogp: Order Gangguan Pelanggan (masalah pada layanan pelanggan)
            // - complete: EDK telah selesai diproses
            $table->enum('status', [
                'lanjut',
                'tidak_lanjut',
                'belum_input',
                'ogp',
                'complete',
            ])->index();

            // Periode bulan data dalam format YYYY-MM
            // Index untuk mempercepat filter dan query per periode
            $table->char('period_month', 7)->index();

            // Sumber sistem tempat data diinput (default: 'Dashboard NCX')
            $table->string('source_system', 100)->default('Dashboard NCX');

            // Catatan tambahan (opsional)
            $table->text('notes')->nullable();

            // Audit trail: User yang pertama kali membuat record ini
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Audit trail: User yang terakhir memperbarui record ini
            // nullOnDelete: Jika user dihapus, field ini diset null
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamp created_at dan updated_at
            $table->timestamps();

            // Soft delete: Data ditandai dengan deleted_at, bukan dihapus permanen
            $table->softDeletes();

            // Constraint unik: Satu referensi EDK hanya boleh muncul sekali per periode bulan
            $table->unique(['edk_reference', 'period_month']);

            // Index komposit untuk mempercepat query filter inputer per periode
            $table->index(['inputer_id', 'period_month']);

            // Index komposit untuk mempercepat query filter account manager per periode
            $table->index(['account_manager_id', 'period_month']);
        });
    }

    /**
     * Batalkan migration: Hapus tabel order_edks.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_edks');
    }
};
