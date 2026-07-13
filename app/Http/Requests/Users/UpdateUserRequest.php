<?php

/**
 * Form Request untuk Memperbarui Data Pengguna (User).
 *
 * File ini menangani validasi dan otorisasi pada saat Super Admin
 * memperbarui data pengguna yang sudah ada. Perbedaan utama dengan
 * StoreUserRequest:
 * - Password bersifat opsional (nullable) - hanya diubah jika diisi
 * - Email unique mengabaikan record saat ini (ignore) agar tidak error
 *   ketika email tidak diubah
 *
 * @package App\Http\Requests\Users
 */

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Hanya pengguna yang memiliki permission 'user.update'
     * yang diizinkan memperbarui data pengguna (biasanya Super Admin).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('user.update') ?? false;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $managedUser - User yang sedang diedit, diambil dari route parameter */
        $managedUser = $this->route('user');

        return [
            // Nama lengkap pengguna wajib diisi, maksimum 150 karakter
            'name' => ['required', 'string', 'max:150'],
            // Email wajib diisi, unik tapi mengabaikan record user yang sedang diedit
            // Ini memungkinkan user mempertahankan email yang sama tanpa error unique
            'email' => ['required', 'email', 'max:150', Rule::unique(User::class, 'email')->ignore($managedUser)],
            // Password opsional saat update - jika diisi maka password diubah, jika kosong password tetap
            // Minimum 8 karakter jika diisi
            'password' => ['nullable', 'string', 'min:8'],
            // Role wajib dipilih dari daftar role yang tersedia
            'role' => ['required', Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN_INPUTER,
                User::ROLE_ACCOUNT_MANAGER,
            ])],
            // Nomor telepon opsional, maksimum 30 karakter
            'phone' => ['nullable', 'string', 'max:30'],
            // Bio/deskripsi profil opsional, maksimum 1000 karakter
            'bio' => ['nullable', 'string', 'max:1000'],
            // Status aktif/nonaktif wajib diisi
            // Menonaktifkan user akan mencegah login ke sistem
            'is_active' => ['required', 'boolean'],
        ];
    }
}
