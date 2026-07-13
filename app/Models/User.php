<?php

/**
 * Model User - Sistem Manajemen Order (SMO) Telkom
 *
 * Model ini merepresentasikan pengguna dalam sistem SMO Telkom.
 * Setiap pengguna memiliki peran (role) yang menentukan hak akses dan
 * tanggung jawab mereka dalam proses manajemen order.
 *
 * Terdapat 3 peran utama dalam sistem:
 * - Super Admin: memiliki akses penuh ke seluruh sistem
 * - Admin Inputer: bertanggung jawab menginput dan mengelola data order
 * - Account Manager: bertanggung jawab mengelola hubungan dengan pelanggan
 *
 * Model ini menggunakan SoftDeletes sehingga data pengguna yang dihapus
 * tidak benar-benar dihapus dari database, melainkan ditandai dengan timestamp deleted_at.
 */

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 *
 * Model autentikasi utama untuk sistem SMO Telkom.
 * Meng-extend Authenticatable dari Laravel untuk mendukung fitur login,
 * session, dan manajemen password secara otomatis.
 *
 * Traits yang digunakan:
 * - HasFactory: mendukung pembuatan data dummy melalui factory (untuk testing)
 * - Notifiable: mendukung pengiriman notifikasi ke pengguna
 * - SoftDeletes: mendukung penghapusan lunak (soft delete)
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    // ============================================================
    // Konstanta Peran (Role) Pengguna
    // Digunakan untuk mengontrol hak akses dan visibilitas data
    // di seluruh sistem SMO Telkom.
    // ============================================================

    /** Peran Super Admin - memiliki akses penuh ke semua fitur dan data */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /** Peran Admin Inputer - bertugas menginput dan memproses data order */
    public const ROLE_ADMIN_INPUTER = 'admin_inputer';

    /** Peran Account Manager - mengelola relasi pelanggan dan order terkait */
    public const ROLE_ACCOUNT_MANAGER = 'account_manager';

    /**
     * Atribut yang boleh diisi secara massal (mass assignment).
     *
     * Daftar kolom yang diizinkan untuk diisi melalui metode create() atau update()
     * guna mencegah kerentanan mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',               // Nama lengkap pengguna
        'email',              // Alamat email (digunakan untuk login)
        'password',           // Password terenkripsi
        'role',               // Peran pengguna (super_admin, admin_inputer, account_manager)
        'phone',              // Nomor telepon pengguna
        'profile_photo_path', // Path ke foto profil pengguna
        'bio',                // Biografi singkat pengguna
        'is_active',          // Status aktif/nonaktif akun pengguna
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi (misalnya saat response JSON).
     * Password dan token tidak boleh terekspos ke client.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',       // Password tidak boleh ditampilkan di response API
        'remember_token', // Token "ingat saya" bersifat rahasia
    ];

    /**
     * Mendefinisikan casting tipe data untuk atribut tertentu.
     *
     * Casting memastikan atribut selalu dikembalikan dalam tipe data yang benar:
     * - email_verified_at: dikonversi ke objek Carbon (datetime)
     * - is_active: dikonversi ke boolean (true/false)
     * - password: secara otomatis di-hash saat disimpan
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // ============================================================
    // Metode Pengecekan Peran (Role Checker)
    // Digunakan untuk memverifikasi peran pengguna saat ini,
    // misalnya untuk otorisasi di controller atau middleware.
    // ============================================================

    /**
     * Mengecek apakah pengguna memiliki peran Super Admin.
     *
     * Super Admin memiliki akses penuh ke seluruh data dan fitur sistem,
     * termasuk manajemen pengguna dan konfigurasi sistem.
     *
     * @return bool True jika pengguna adalah Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Mengecek apakah pengguna memiliki peran Admin Inputer.
     *
     * Admin Inputer hanya dapat melihat dan mengelola order
     * yang ditugaskan kepadanya (berdasarkan inputer_id).
     *
     * @return bool True jika pengguna adalah Admin Inputer
     */
    public function isAdminInputer(): bool
    {
        return $this->role === self::ROLE_ADMIN_INPUTER;
    }

    /**
     * Mengecek apakah pengguna memiliki peran Account Manager.
     *
     * Account Manager hanya dapat melihat dan mengelola order
     * yang ditugaskan kepadanya (berdasarkan account_manager_id).
     *
     * @return bool True jika pengguna adalah Account Manager
     */
    public function isAccountManager(): bool
    {
        return $this->role === self::ROLE_ACCOUNT_MANAGER;
    }

    // ============================================================
    // Relasi (Relationships)
    // Mendefinisikan hubungan antara User dengan model lain.
    // Setiap pengguna dapat berperan sebagai Inputer ATAU
    // Account Manager di berbagai entitas order.
    // ============================================================

    /**
     * Relasi ke OrderStatus di mana pengguna ini berperan sebagai Inputer.
     *
     * Mengembalikan semua order status yang diinput oleh pengguna ini.
     * Foreign key: inputer_id pada tabel order_statuses.
     *
     * @return HasMany Koleksi OrderStatus yang diinput oleh user ini
     */
    public function orderStatusesAsInputer(): HasMany
    {
        return $this->hasMany(OrderStatus::class, 'inputer_id');
    }

    /**
     * Relasi ke OrderStatus di mana pengguna ini berperan sebagai Account Manager.
     *
     * Mengembalikan semua order status yang dikelola oleh pengguna ini sebagai AM.
     * Foreign key: account_manager_id pada tabel order_statuses.
     *
     * @return HasMany Koleksi OrderStatus yang dikelola oleh user ini sebagai AM
     */
    public function orderStatusesAsAccountManager(): HasMany
    {
        return $this->hasMany(OrderStatus::class, 'account_manager_id');
    }

    /**
     * Relasi ke OrderEdk di mana pengguna ini berperan sebagai Inputer.
     *
     * Mengembalikan semua order EDK yang diinput oleh pengguna ini.
     * Foreign key: inputer_id pada tabel order_edks.
     *
     * @return HasMany Koleksi OrderEdk yang diinput oleh user ini
     */
    public function orderEdksAsInputer(): HasMany
    {
        return $this->hasMany(OrderEdk::class, 'inputer_id');
    }

    /**
     * Relasi ke OrderEdk di mana pengguna ini berperan sebagai Account Manager.
     *
     * Mengembalikan semua order EDK yang dikelola oleh pengguna ini sebagai AM.
     * Foreign key: account_manager_id pada tabel order_edks.
     *
     * @return HasMany Koleksi OrderEdk yang dikelola oleh user ini sebagai AM
     */
    public function orderEdksAsAccountManager(): HasMany
    {
        return $this->hasMany(OrderEdk::class, 'account_manager_id');
    }

    /**
     * Relasi ke CompletionRecord di mana pengguna ini berperan sebagai Inputer.
     *
     * Mengembalikan semua catatan penyelesaian yang diinput oleh pengguna ini.
     * Foreign key: inputer_id pada tabel completion_records.
     *
     * @return HasMany Koleksi CompletionRecord yang diinput oleh user ini
     */
    public function completionRecordsAsInputer(): HasMany
    {
        return $this->hasMany(CompletionRecord::class, 'inputer_id');
    }

    /**
     * Relasi ke CompletionRecord di mana pengguna ini berperan sebagai Account Manager.
     *
     * Mengembalikan semua catatan penyelesaian yang dikelola oleh pengguna ini sebagai AM.
     * Foreign key: account_manager_id pada tabel completion_records.
     *
     * @return HasMany Koleksi CompletionRecord yang dikelola oleh user ini sebagai AM
     */
    public function completionRecordsAsAccountManager(): HasMany
    {
        return $this->hasMany(CompletionRecord::class, 'account_manager_id');
    }

    // ============================================================
    // Metode Utilitas (Utility Methods)
    // ============================================================

    /**
     * Mengecek apakah pengguna memiliki catatan operasional di sistem.
     *
     * Metode ini digunakan untuk menentukan apakah pengguna aman untuk dihapus.
     * Jika pengguna masih terkait dengan order status, order EDK, atau
     * catatan penyelesaian (baik sebagai inputer maupun account manager),
     * maka pengguna TIDAK boleh dihapus permanen karena akan merusak
     * integritas data historis.
     *
     * @return bool True jika pengguna memiliki minimal satu catatan operasional
     */
    public function hasOperationalRecords(): bool
    {
        // Cek keberadaan relasi di semua entitas order secara berurutan
        // Menggunakan operator OR (||) sehingga berhenti di pengecekan pertama yang bernilai true
        return $this->orderStatusesAsInputer()->exists()
            || $this->orderStatusesAsAccountManager()->exists()
            || $this->orderEdksAsInputer()->exists()
            || $this->orderEdksAsAccountManager()->exists()
            || $this->completionRecordsAsInputer()->exists()
            || $this->completionRecordsAsAccountManager()->exists();
    }
}
