<?php

/**
 * Form Request untuk Memperbarui Profil Pengguna Sendiri.
 *
 * File ini menangani validasi saat pengguna memperbarui profil
 * pribadinya sendiri. Berbeda dengan UpdateUserRequest yang digunakan
 * oleh Super Admin untuk mengelola user lain, request ini digunakan
 * oleh setiap pengguna untuk mengedit profil mereka sendiri.
 *
 * Field yang bisa diedit: nama, email, telepon, bio, dan foto profil.
 * Role dan status aktif TIDAK bisa diubah oleh pengguna sendiri.
 *
 * @package App\Http\Requests
 */

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * Tidak ada method authorize() karena setiap pengguna yang login
     * diizinkan memperbarui profilnya sendiri (default: true).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Nama lengkap pengguna wajib diisi, maksimum 150 karakter
            'name' => ['required', 'string', 'max:150'],
            // Email wajib diisi, harus lowercase, format valid, unik tapi mengabaikan user saat ini
            // Aturan 'lowercase' memastikan email selalu disimpan dalam huruf kecil
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Nomor telepon opsional, maksimum 30 karakter
            'phone' => ['nullable', 'string', 'max:30'],
            // Bio/deskripsi profil opsional, maksimum 1000 karakter
            'bio' => ['nullable', 'string', 'max:1000'],
            // Foto profil opsional: harus file gambar, format JPG/JPEG/PNG/WebP, maksimum 2MB
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
