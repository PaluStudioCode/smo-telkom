<?php

/**
 * Model ActivityLog - Sistem Manajemen Order (SMO) Telkom
 *
 * Model ini merepresentasikan log aktivitas (audit trail) dalam sistem SMO Telkom.
 * Setiap perubahan data penting yang dilakukan oleh pengguna akan dicatat di sini,
 * termasuk informasi tentang siapa yang melakukan, apa yang diubah, nilai lama,
 * nilai baru, serta informasi teknis seperti IP address dan user agent.
 *
 * ActivityLog bersifat append-only (hanya ditambahkan, tidak diubah atau dihapus)
 * untuk menjaga integritas jejak audit. Model ini tidak menggunakan
 * timestamp otomatis dari Laravel karena hanya membutuhkan created_at
 * yang dikelola secara manual.
 *
 * Kegunaan utama:
 * - Melacak siapa yang mengubah data dan kapan
 * - Menyimpan snapshot nilai sebelum dan sesudah perubahan
 * - Mendukung kebutuhan audit dan kepatuhan (compliance)
 * - Membantu proses debugging dan investigasi masalah
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ActivityLog
 *
 * Model untuk mencatat seluruh aktivitas pengguna di sistem SMO Telkom.
 * Berfungsi sebagai audit trail yang mencatat setiap operasi CRUD
 * pada modul-modul utama (Order Status, Order EDK, Completion Record, dll).
 *
 * Catatan: timestamps dinonaktifkan karena hanya created_at yang
 * dibutuhkan dan dikelola secara manual melalui fillable.
 */
class ActivityLog extends Model
{
    /**
     * Menonaktifkan pengelolaan timestamp otomatis (created_at & updated_at).
     *
     * ActivityLog hanya membutuhkan created_at yang diisi secara manual,
     * dan tidak memerlukan updated_at karena log tidak boleh diubah
     * setelah dibuat (prinsip append-only untuk integritas audit).
     */
    public $timestamps = false;

    /**
     * Atribut yang boleh diisi secara massal (mass assignment).
     * Melindungi dari kerentanan mass assignment dengan hanya mengizinkan kolom tertentu.
     */
    protected $fillable = [
        'user_id',     // ID pengguna yang melakukan aksi (relasi ke tabel users)
        'module',      // Nama modul tempat aksi terjadi (misal: 'order_status', 'order_edk', 'completion_record')
        'action',      // Jenis aksi yang dilakukan (misal: 'create', 'update', 'delete')
        'record_type', // Tipe model yang terpengaruh (nama class, misal: 'App\Models\OrderStatus')
        'record_id',   // ID record yang terpengaruh oleh aksi
        'old_values',  // Snapshot nilai atribut SEBELUM perubahan (format JSON, null untuk aksi create)
        'new_values',  // Snapshot nilai atribut SESUDAH perubahan (format JSON, null untuk aksi delete)
        'ip_address',  // Alamat IP pengguna saat melakukan aksi (untuk keamanan dan audit)
        'user_agent',  // User agent browser pengguna (untuk identifikasi perangkat)
        'created_at',  // Timestamp saat log dibuat (dikelola secara manual)
    ];

    /**
     * Mendefinisikan casting tipe data untuk atribut tertentu.
     *
     * - old_values: dikonversi dari JSON string ke array PHP dan sebaliknya
     * - new_values: dikonversi dari JSON string ke array PHP dan sebaliknya
     * - created_at: dikonversi ke objek Carbon untuk kemudahan manipulasi tanggal
     *
     * Casting old_values dan new_values memudahkan perbandingan nilai
     * sebelum dan sesudah perubahan tanpa perlu decode JSON secara manual.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',    // Nilai lama, otomatis di-encode/decode JSON
            'new_values' => 'array',    // Nilai baru, otomatis di-encode/decode JSON
            'created_at' => 'datetime', // Timestamp pembuatan log
        ];
    }

    // ============================================================
    // Relasi (Relationships)
    // ============================================================

    /**
     * Relasi ke pengguna yang melakukan aksi yang dicatat dalam log ini.
     *
     * Setiap log aktivitas selalu terkait dengan satu pengguna.
     * Relasi ini digunakan untuk menampilkan nama pengguna yang
     * melakukan perubahan di halaman audit trail.
     *
     * @return BelongsTo Relasi ke model User melalui kolom user_id
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
