<?php

/**
 * Model CompletionRecord - Sistem Manajemen Order (SMO) Telkom
 *
 * Model ini merepresentasikan catatan penyelesaian (completion record) dalam
 * sistem SMO Telkom. CompletionRecord adalah dokumen yang mencatat bahwa
 * suatu order (baik OrderStatus maupun OrderEdk) telah selesai dikerjakan
 * dan memerlukan proses persetujuan (approval).
 *
 * Alur persetujuan catatan penyelesaian:
 * 1. Menunggu Persetujuan → Record baru dibuat, menunggu review
 * 2. Disetujui           → Record telah disetujui oleh approver (status akhir)
 * 3. Tidak Disetujui     → Record ditolak oleh approver
 * 4. Revisi              → Record perlu direvisi sebelum diajukan kembali
 *
 * Catatan: Hanya status "Disetujui" yang dianggap sebagai status akhir,
 * karena record yang ditolak atau direvisi masih bisa diproses ulang.
 *
 * Model ini menggunakan SoftDeletes untuk menjaga integritas data historis.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CompletionRecord
 *
 * Merepresentasikan catatan penyelesaian order di sistem SMO Telkom.
 * Setiap record terhubung ke OrderStatus atau OrderEdk yang diselesaikan,
 * dan melalui proses persetujuan bertingkat sebelum dianggap final.
 */
class CompletionRecord extends Model
{
    use SoftDeletes;

    // ============================================================
    // Konstanta Status Persetujuan (Approval Status)
    // Mendefinisikan semua kemungkinan status dalam alur approval.
    // ============================================================

    /** Status Menunggu Persetujuan - record baru diajukan dan belum direview */
    public const STATUS_MENUNGGU_PERSETUJUAN = 'menunggu_persetujuan';

    /** Status Disetujui - record telah disetujui oleh pihak berwenang (status akhir) */
    public const STATUS_DISETUJUI = 'disetujui';

    /** Status Tidak Disetujui - record ditolak, perlu perbaikan atau pembatalan */
    public const STATUS_TIDAK_DISETUJUI = 'tidak_disetujui';

    /** Status Revisi - record dikembalikan untuk direvisi sebelum diajukan kembali */
    public const STATUS_REVISI = 'revisi';

    /**
     * Daftar status akhir (final).
     * Hanya "Disetujui" yang menjadi status akhir karena record yang ditolak
     * atau direvisi masih memungkinkan untuk diproses ulang.
     */
    public const FINAL_STATUSES = [
        self::STATUS_DISETUJUI,
    ];

    /**
     * Pemetaan status ke label yang ramah pengguna (human-readable).
     * Digunakan untuk menampilkan status persetujuan di antarmuka pengguna (UI).
     */
    public const LABELS = [
        self::STATUS_MENUNGGU_PERSETUJUAN => 'Menunggu Persetujuan',
        self::STATUS_DISETUJUI => 'Disetujui',
        self::STATUS_TIDAK_DISETUJUI => 'Tidak Disetujui',
        self::STATUS_REVISI => 'Revisi',
    ];

    /**
     * Atribut yang boleh diisi secara massal (mass assignment).
     * Melindungi dari kerentanan mass assignment dengan hanya mengizinkan kolom tertentu.
     */
    protected $fillable = [
        'completion_number',  // Nomor unik catatan penyelesaian
        'order_status_id',    // ID relasi ke OrderStatus yang diselesaikan (opsional)
        'order_edk_id',       // ID relasi ke OrderEdk yang diselesaikan (opsional)
        'inputer_id',         // ID pengguna yang menginput catatan ini (Admin Inputer)
        'account_manager_id', // ID Account Manager yang terkait dengan order
        'approval_status',    // Status persetujuan saat ini (mengacu pada konstanta STATUS_*)
        'completed_at',       // Tanggal penyelesaian pekerjaan di lapangan
        'approved_by',        // ID pengguna yang menyetujui/menolak record
        'approved_at',        // Timestamp saat persetujuan diberikan
        'revision_note',      // Catatan revisi jika status dikembalikan untuk perbaikan
        'period_month',       // Periode bulan pelaporan (format: YYYY-MM)
        'notes',              // Catatan tambahan terkait penyelesaian
        'created_by',         // ID pengguna yang membuat record ini
        'updated_by',         // ID pengguna yang terakhir mengubah record ini
    ];

    /**
     * Mendefinisikan casting tipe data untuk atribut tertentu.
     *
     * - completed_at: dikonversi ke objek Carbon (hanya tanggal, tanpa waktu)
     * - approved_at: dikonversi ke objek Carbon (tanggal dan waktu lengkap)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'date',     // Tanggal penyelesaian (format: Y-m-d)
            'approved_at' => 'datetime',  // Waktu persetujuan (format: Y-m-d H:i:s)
        ];
    }

    // ============================================================
    // Relasi (Relationships)
    // CompletionRecord terhubung ke order yang diselesaikan dan
    // beberapa pengguna yang terlibat dalam proses penyelesaian.
    // ============================================================

    /**
     * Relasi ke OrderStatus yang diselesaikan oleh catatan ini.
     * Satu CompletionRecord dapat merujuk ke satu OrderStatus.
     * Kolom order_status_id bersifat opsional (nullable).
     *
     * @return BelongsTo Relasi ke model OrderStatus
     */
    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    /**
     * Relasi ke OrderEdk yang diselesaikan oleh catatan ini.
     * Satu CompletionRecord dapat merujuk ke satu OrderEdk.
     * Kolom order_edk_id bersifat opsional (nullable).
     *
     * @return BelongsTo Relasi ke model OrderEdk
     */
    public function orderEdk(): BelongsTo
    {
        return $this->belongsTo(OrderEdk::class);
    }

    /**
     * Relasi ke pengguna yang berperan sebagai Inputer catatan penyelesaian ini.
     * Inputer adalah admin yang menginput data penyelesaian order.
     *
     * @return BelongsTo Relasi ke model User melalui kolom inputer_id
     */
    public function inputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inputer_id');
    }

    /**
     * Relasi ke pengguna yang berperan sebagai Account Manager.
     * Account Manager terkait dengan order yang diselesaikan.
     *
     * @return BelongsTo Relasi ke model User melalui kolom account_manager_id
     */
    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    /**
     * Relasi ke pengguna yang menyetujui atau menolak catatan penyelesaian ini.
     * Approver biasanya adalah Super Admin atau pejabat berwenang.
     *
     * @return BelongsTo Relasi ke model User melalui kolom approved_by
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relasi ke pengguna yang pertama kali membuat record ini.
     * Digunakan untuk keperluan audit trail (jejak audit).
     *
     * @return BelongsTo Relasi ke model User melalui kolom created_by
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke pengguna yang terakhir mengubah record ini.
     * Digunakan untuk keperluan audit trail (jejak audit).
     *
     * @return BelongsTo Relasi ke model User melalui kolom updated_by
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================================
    // Metode Statis & Utilitas
    // ============================================================

    /**
     * Mendapatkan daftar semua kunci status persetujuan yang tersedia.
     *
     * Mengembalikan array berisi nilai-nilai status (bukan label).
     * Berguna untuk validasi input dan pembuatan dropdown di form.
     *
     * @return array<int, string> Daftar nilai status (misal: ['menunggu_persetujuan', 'disetujui', ...])
     */
    public static function statuses(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * Mengecek apakah catatan penyelesaian ini sudah disetujui.
     *
     * Metode ini secara spesifik mengecek apakah approval_status bernilai "disetujui".
     * Digunakan untuk menentukan apakah order terkait dapat dianggap selesai secara resmi.
     *
     * @return bool True jika catatan ini telah disetujui
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_DISETUJUI;
    }

    // ============================================================
    // Query Scopes
    // ============================================================

    /**
     * Scope untuk memfilter catatan penyelesaian berdasarkan hak akses pengguna.
     *
     * Menerapkan pola visibilitas berbasis peran (role-based visibility):
     * - Admin Inputer: hanya melihat record yang diinput olehnya (inputer_id = user.id)
     * - Account Manager: hanya melihat record yang terkait dengannya (account_manager_id = user.id)
     * - Super Admin (default): melihat semua record tanpa filter
     *
     * Penggunaan: CompletionRecord::query()->visibleTo($user)->get()
     *
     * @param Builder $query Query builder yang sedang aktif
     * @param User $user Pengguna yang sedang login
     * @return Builder Query yang sudah difilter sesuai peran pengguna
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            User::ROLE_ADMIN_INPUTER => $query->where('inputer_id', $user->id),
            User::ROLE_ACCOUNT_MANAGER => $query->where('account_manager_id', $user->id),
            default => $query, // Super Admin dapat melihat semua data
        };
    }
}
