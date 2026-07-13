<?php

/**
 * Form Request untuk Menyimpan Pengguna (User) Baru.
 *
 * File ini menangani validasi dan otorisasi pada saat Super Admin
 * membuat akun pengguna baru. Validasi mencakup pengecekan email unik,
 * password minimum, dan role yang valid sesuai sistem.
 *
 * @package App\Http\Requests\Users
 */

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Hanya pengguna yang memiliki permission 'user.create'
     * yang diizinkan membuat pengguna baru (biasanya Super Admin).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('user.create') ?? false;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Nama lengkap pengguna wajib diisi, maksimum 150 karakter
            'name' => ['required', 'string', 'max:150'],
            // Email wajib diisi, harus format email valid, unik di tabel users
            // Email digunakan sebagai identitas login pengguna
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')],
            // Password wajib diisi saat membuat user baru, minimum 8 karakter
            'password' => ['required', 'string', 'min:8'],
            // Role wajib dipilih dari daftar role yang tersedia dalam sistem:
            // - Super Admin: Akses penuh ke seluruh fitur
            // - Admin Inputer: Dapat menginput dan mengelola data operasional
            // - Account Manager: Dapat melihat data terkait order yang dikelolanya
            'role' => ['required', Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN_INPUTER,
                User::ROLE_ACCOUNT_MANAGER,
            ])],
            // Nomor telepon opsional, maksimum 30 karakter
            'phone' => ['nullable', 'string', 'max:30'],
            // Bio/deskripsi profil opsional, maksimum 1000 karakter
            'bio' => ['nullable', 'string', 'max:1000'],
            // Status aktif/nonaktif wajib diisi (boolean: true/false)
            // User nonaktif tidak bisa login ke sistem
            'is_active' => ['required', 'boolean'],
        ];
    }
}
