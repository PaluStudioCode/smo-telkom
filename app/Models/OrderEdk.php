<?php

/**
 * Model OrderEdk - Sistem Manajemen Order (SMO) Telkom
 *
 * Model ini merepresentasikan data order EDK (Evaluasi Data Kontrak)
 * dalam sistem SMO Telkom. EDK merupakan proses evaluasi dan validasi
 * data kontrak pelanggan sebelum layanan diaktifkan.
 *
 * Alur status order EDK:
 * 1. Belum Input     → Data EDK belum diinput ke sistem
 * 2. Lanjut          → EDK dievaluasi dan dilanjutkan ke proses berikutnya
 * 3. OGP             → Order dalam tahap OGP (Order Garap Proses)
 * 4. Complete         → Proses EDK telah selesai (status akhir)
 * 5. Tidak Lanjut     → EDK tidak dilanjutkan/dibatalkan (status akhir)
 *
 * Model ini menggunakan SoftDeletes untuk menjaga integritas data historis.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderEdk
 *
 * Merepresentasikan data Evaluasi Data Kontrak (EDK) di sistem SMO Telkom.
 * Setiap record menyimpan informasi referensi EDK, status terkini,
 * serta relasi ke pengguna yang menginput dan mengelolanya.
 */
class OrderEdk extends Model
{
    use SoftDeletes;

    // ============================================================
    // Konstanta Status EDK
    // Mendefinisikan semua kemungkinan status dalam siklus hidup EDK.
    // ============================================================

    /** Status Lanjut - EDK telah dievaluasi dan diputuskan untuk dilanjutkan */
    public const STATUS_LANJUT = 'lanjut';

    /** Status Tidak Lanjut - EDK tidak dilanjutkan/dibatalkan (status akhir) */
    public const STATUS_TIDAK_LANJUT = 'tidak_lanjut';

    /** Status Belum Input - data EDK belum diinput ke dalam sistem */
    public const STATUS_BELUM_INPUT = 'belum_input';

    /** Status OGP (Order Garap Proses) - EDK sedang dalam proses penggarapan */
    public const STATUS_OGP = 'ogp';

    /** Status Complete - proses EDK telah selesai dan kontrak tervalidasi (status akhir) */
    public const STATUS_COMPLETE = 'complete';

    /**
     * Daftar status akhir (final) yang menandakan EDK sudah tidak dapat diproses lebih lanjut.
     * EDK dengan status ini tidak boleh diubah statusnya lagi.
     */
    public const FINAL_STATUSES = [
        self::STATUS_TIDAK_LANJUT, // EDK dibatalkan
        self::STATUS_COMPLETE,     // EDK selesai
    ];

    /**
     * Pemetaan status ke label yang ramah pengguna (human-readable).
     * Digunakan untuk menampilkan status di antarmuka pengguna (UI).
     */
    public const LABELS = [
        self::STATUS_LANJUT => 'Lanjut',
        self::STATUS_TIDAK_LANJUT => 'Tidak Lanjut',
        self::STATUS_BELUM_INPUT => 'Belum Input',
        self::STATUS_OGP => 'OGP',
        self::STATUS_COMPLETE => 'Complete',
    ];

    /**
     * Atribut yang boleh diisi secara massal (mass assignment).
     * Melindungi dari kerentanan mass assignment dengan hanya mengizinkan kolom tertentu.
     */
    protected $fillable = [
        'edk_reference',      // Nomor referensi EDK yang unik
        'customer_name',      // Nama pelanggan terkait EDK
        'inputer_id',         // ID pengguna yang menginput data EDK (Admin Inputer)
        'account_manager_id', // ID pengguna yang mengelola EDK (Account Manager)
        'status',             // Status terkini EDK (mengacu pada konstanta STATUS_*)
        'period_month',       // Periode bulan pelaporan (format: YYYY-MM)
        'source_system',      // Sistem asal data EDK (misal: MyTens, BGES)
        'notes',              // Catatan tambahan terkait EDK
        'created_by',         // ID pengguna yang membuat record ini
        'updated_by',         // ID pengguna yang terakhir mengubah record ini
    ];

    // ============================================================
    // Relasi (Relationships)
    // Setiap order EDK memiliki relasi ke beberapa pengguna dengan peran berbeda.
    // ============================================================

    /**
     * Relasi ke pengguna yang berperan sebagai Inputer EDK ini.
     * Inputer adalah admin yang bertanggung jawab menginput data EDK.
     *
     * @return BelongsTo Relasi ke model User melalui kolom inputer_id
     */
    public function inputer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inputer_id');
    }

    /**
     * Relasi ke pengguna yang berperan sebagai Account Manager EDK ini.
     * Account Manager bertanggung jawab mengelola hubungan pelanggan terkait EDK.
     *
     * @return BelongsTo Relasi ke model User melalui kolom account_manager_id
     */
    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    /**
     * Relasi ke pengguna yang pertama kali membuat record EDK ini.
     * Digunakan untuk keperluan audit trail (jejak audit).
     *
     * @return BelongsTo Relasi ke model User melalui kolom created_by
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke pengguna yang terakhir mengubah record EDK ini.
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
     * @return array<int, string> Daftar nilai status (misal: ['lanjut', 'tidak_lanjut', ...])
     */
    public static function statuses(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * Mengecek apakah EDK ini sudah berada di status akhir (final).
     *
     * EDK dengan status akhir tidak dapat diproses lebih lanjut.
     * Status akhir meliputi: Complete dan Tidak Lanjut.
     *
     * @return bool True jika status EDK sudah final
     */
    public function isFinalStatus(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    // ============================================================
    // Query Scopes
    // ============================================================

    /**
     * Scope untuk memfilter order EDK berdasarkan hak akses pengguna.
     *
     * Menerapkan pola visibilitas berbasis peran (role-based visibility):
     * - Admin Inputer: hanya melihat EDK yang diinput olehnya (inputer_id = user.id)
     * - Account Manager: hanya melihat EDK yang dikelolanya (account_manager_id = user.id)
     * - Super Admin (default): melihat semua EDK tanpa filter
     *
     * Penggunaan: OrderEdk::query()->visibleTo($user)->get()
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
