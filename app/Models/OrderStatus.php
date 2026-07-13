<?php

/**
 * Model OrderStatus - Sistem Manajemen Order (SMO) Telkom
 *
 * Model ini merepresentasikan status order layanan Telkom.
 * Setiap order memiliki siklus hidup (lifecycle) dengan beberapa tahap status,
 * mulai dari provisioning hingga selesai (complete), gagal (failed),
 * atau dibatalkan (cancel/abandoned).
 *
 * Alur status order:
 * 1. Provisioning       → Order sedang dalam proses penyediaan layanan
 * 2. Pending BASO       → Menunggu Berita Acara Serah Terima Operasi
 * 3. Pending Billing    → Menunggu persetujuan billing/penagihan
 * 4. Complete           → Order telah selesai (status akhir)
 * 5. Failed             → Order gagal diproses (status akhir)
 * 6. Cancel/Abandoned   → Order dibatalkan atau ditinggalkan (status akhir)
 *
 * Model ini menggunakan SoftDeletes untuk menjaga integritas data historis.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderStatus
 *
 * Merepresentasikan data status order layanan di sistem SMO Telkom.
 * Setiap record menyimpan informasi order beserta status terkininya,
 * serta siapa yang menginput dan mengelolanya.
 */
class OrderStatus extends Model
{
    use SoftDeletes;

    // ============================================================
    // Konstanta Status Order
    // Mendefinisikan semua kemungkinan status dalam siklus hidup order.
    // Nilai konstanta disimpan sebagai string di database.
    // ============================================================

    /** Status Provisioning - order sedang dalam proses penyediaan/aktivasi layanan */
    public const STATUS_PROVISIONING = 'provisioning';

    /** Status Pending BASO - menunggu Berita Acara Serah Terima Operasi dari lapangan */
    public const STATUS_PENDING_BASO = 'pending_baso';

    /** Status Pending Billing Approval - menunggu persetujuan dari tim billing/keuangan */
    public const STATUS_PENDING_BILLING_APPROVAL = 'pending_billing_approval';

    /** Status Complete - order telah selesai diproses dan layanan aktif */
    public const STATUS_COMPLETE = 'complete';

    /** Status Failed - order gagal diproses karena kendala teknis atau lainnya */
    public const STATUS_FAILED = 'failed';

    /** Status Cancel/Abandoned - order dibatalkan oleh pelanggan atau ditinggalkan */
    public const STATUS_CANCEL_ABANDONED = 'cancel_abandoned';

    /**
     * Daftar status akhir (final) yang menandakan order sudah tidak dapat diproses lebih lanjut.
     * Order dengan status ini tidak boleh diubah statusnya lagi.
     */
    public const FINAL_STATUSES = [
        self::STATUS_COMPLETE,
        self::STATUS_FAILED,
        self::STATUS_CANCEL_ABANDONED,
    ];

    /**
     * Pemetaan status ke label yang ramah pengguna (human-readable).
     * Digunakan untuk menampilkan status di antarmuka pengguna (UI).
     */
    public const LABELS = [
        self::STATUS_PROVISIONING => 'Provisioning',
        self::STATUS_PENDING_BASO => 'Pending BASO',
        self::STATUS_PENDING_BILLING_APPROVAL => 'Pending Billing Approval',
        self::STATUS_COMPLETE => 'Complete',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_CANCEL_ABANDONED => 'Cancel / Abandoned',
    ];

    /**
     * Atribut yang boleh diisi secara massal (mass assignment).
     * Melindungi dari kerentanan mass assignment dengan hanya mengizinkan kolom tertentu.
     */
    protected $fillable = [
        'order_number',       // Nomor unik order dari sistem Telkom
        'customer_name',      // Nama pelanggan pemilik order
        'service_name',       // Nama layanan yang dipesan (misal: IndiHome, Astinet)
        'inputer_id',         // ID pengguna yang menginput order (Admin Inputer)
        'account_manager_id', // ID pengguna yang mengelola order (Account Manager)
        'status',             // Status terkini order (mengacu pada konstanta STATUS_*)
        'provisioning_stage', // Tahap provisioning saat ini (detail sub-tahap)
        'period_month',       // Periode bulan pelaporan (format: YYYY-MM)
        'source_system',      // Sistem asal data order (misal: MyTens, BGES)
        'notes',              // Catatan tambahan terkait order
        'created_by',         // ID pengguna yang membuat record ini
        'updated_by',         // ID pengguna yang terakhir mengubah record ini
    ];

    // ============================================================
    // Relasi (Relationships)
    // Setiap order memiliki relasi ke beberapa pengguna dengan peran berbeda.
    // ============================================================

    /**
     * Relasi ke pengguna yang berperan sebagai Inputer order ini.
     * Inputer adalah admin yang bertanggung jawab menginput data order.
     *
     * @return BelongsTo Relasi ke model User melalui kolom inputer_id
     */
    public function inputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inputer_id');
    }

    /**
     * Relasi ke pengguna yang berperan sebagai Account Manager order ini.
     * Account Manager bertanggung jawab mengelola hubungan dengan pelanggan.
     *
     * @return BelongsTo Relasi ke model User melalui kolom account_manager_id
     */
    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    /**
     * Relasi ke pengguna yang pertama kali membuat record order ini.
     * Digunakan untuk keperluan audit trail (jejak audit).
     *
     * @return BelongsTo Relasi ke model User melalui kolom created_by
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke pengguna yang terakhir mengubah record order ini.
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
     * Mendapatkan daftar semua kunci status yang tersedia.
     *
     * Mengembalikan array berisi nilai-nilai status (bukan label).
     * Berguna untuk validasi input dan pembuatan dropdown di form.
     *
     * @return array<int, string> Daftar nilai status (misal: ['provisioning', 'pending_baso', ...])
     */
    public static function statuses(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * Mengecek apakah order ini sudah berada di status akhir (final).
     *
     * Order dengan status akhir tidak dapat diproses lebih lanjut.
     * Status akhir meliputi: Complete, Failed, dan Cancel/Abandoned.
     *
     * @return bool True jika status order sudah final
     */
    public function isFinalStatus(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    // ============================================================
    // Query Scopes
    // ============================================================

    /**
     * Scope untuk memfilter order berdasarkan hak akses pengguna.
     *
     * Menerapkan pola visibilitas berbasis peran (role-based visibility):
     * - Admin Inputer: hanya melihat order yang diinput olehnya (inputer_id = user.id)
     * - Account Manager: hanya melihat order yang dikelolanya (account_manager_id = user.id)
     * - Super Admin (default): melihat semua order tanpa filter
     *
     * Penggunaan: OrderStatus::query()->visibleTo($user)->get()
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
