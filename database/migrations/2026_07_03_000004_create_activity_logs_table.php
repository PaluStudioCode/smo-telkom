<?php

/**
 * Migration: Membuat Tabel activity_logs.
 *
 * Tabel ini menyimpan log aktivitas (audit trail) seluruh operasi penting
 * yang dilakukan pengguna dalam sistem SMO Telkom. Setiap perubahan data
 * (create, update, delete) dicatat dengan detail nilai sebelum dan sesudah.
 *
 * Tujuan:
 * - Audit trail: Melacak siapa melakukan apa dan kapan
 * - Debugging: Membantu identifikasi masalah dengan melihat riwayat perubahan
 * - Keamanan: Mendeteksi aktivitas mencurigakan
 * - Compliance: Memenuhi kebutuhan pelaporan dan kepatuhan
 *
 * Catatan: Tabel ini menggunakan relasi polimorfik (record_type + record_id)
 * untuk merujuk ke berbagai jenis record dari tabel yang berbeda.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration untuk membuat tabel activity_logs.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            // Primary key auto-increment
            $table->id();

            // FK ke tabel users: Pengguna yang melakukan aktivitas (opsional)
            // nullOnDelete: Jika user dihapus, log tetap ada tapi user_id diset null
            // Nullable karena bisa ada aktivitas sistem yang tidak dilakukan oleh user tertentu
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Modul/fitur tempat aktivitas terjadi (contoh: 'order_status', 'order_edk', 'completion_record', 'user')
            // Index untuk mempercepat filter log berdasarkan modul
            $table->string('module', 100)->index();

            // Jenis aksi yang dilakukan (contoh: 'created', 'updated', 'deleted', 'approved', 'login')
            // Index untuk mempercepat filter log berdasarkan jenis aksi
            $table->string('action', 100)->index();

            // Tipe record yang terpengaruh (nama class model, contoh: 'App\Models\OrderStatus')
            // Bagian dari relasi polimorfik untuk merujuk ke berbagai tabel
            $table->string('record_type', 150)->nullable();

            // ID record yang terpengaruh (opsional)
            // Bagian dari relasi polimorfik bersama record_type
            $table->unsignedBigInteger('record_id')->nullable();

            // Nilai data SEBELUM perubahan (dalam format JSON)
            // Null pada aksi 'create' karena tidak ada data sebelumnya
            $table->json('old_values')->nullable();

            // Nilai data SESUDAH perubahan (dalam format JSON)
            // Null pada aksi 'delete' karena data sudah dihapus
            $table->json('new_values')->nullable();

            // Alamat IP pengguna saat melakukan aktivitas
            // Mendukung IPv4 (max 15 karakter) dan IPv6 (max 45 karakter)
            $table->string('ip_address', 45)->nullable();

            // User agent browser/klien yang digunakan pengguna
            // Berguna untuk identifikasi perangkat dan browser
            $table->text('user_agent')->nullable();

            // Timestamp kapan aktivitas terjadi (hanya created_at, tanpa updated_at)
            // Log bersifat immutable (tidak pernah diubah setelah dibuat)
            $table->timestamp('created_at')->nullable();

            // Index komposit polimorfik untuk mempercepat pencarian log berdasarkan record tertentu
            // Contoh: "Tampilkan semua log untuk OrderStatus dengan ID 123"
            $table->index(['record_type', 'record_id']);
        });
    }

    /**
     * Batalkan migration: Hapus tabel activity_logs.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
