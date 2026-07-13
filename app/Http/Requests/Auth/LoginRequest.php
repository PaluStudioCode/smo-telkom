<?php

/**
 * Form Request untuk Proses Login (Autentikasi).
 *
 * File ini menangani validasi kredensial login dan proses autentikasi pengguna.
 * Fitur keamanan yang diterapkan:
 * 1. Rate Limiting: Membatasi percobaan login maksimal 5 kali untuk mencegah brute force
 * 2. Throttle Key: Menggunakan kombinasi email + IP address sebagai kunci pembatas
 * 3. Pengecekan Status Aktif: Akun yang dinonaktifkan tidak bisa login meskipun kredensial benar
 * 4. Lockout Event: Memicu event ketika rate limit tercapai untuk keperluan logging/monitoring
 *
 * @package App\Http\Requests\Auth
 */

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Login request selalu diizinkan karena pengguna belum terautentikasi.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * Validasi dasar untuk memastikan email dan password tidak kosong.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Email wajib diisi dengan format email yang valid
            'email' => ['required', 'string', 'email'],
            // Password wajib diisi (validasi kebenaran dilakukan di method authenticate)
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Mencoba melakukan autentikasi dengan kredensial yang diberikan.
     *
     * Alur proses autentikasi:
     * 1. Cek apakah request sudah melebihi batas rate limit
     * 2. Coba autentikasi dengan email dan password
     * 3. Jika gagal: tambahkan hitungan rate limiter dan lempar error
     * 4. Jika berhasil: cek apakah akun aktif
     *    - Jika akun nonaktif: logout paksa, tambah hitungan rate limiter, dan lempar error
     *    - Jika akun aktif: bersihkan hitungan rate limiter
     *
     * @throws ValidationException Jika kredensial salah atau akun nonaktif
     * @return void
     */
    public function authenticate(): void
    {
        // Langkah 1: Pastikan request belum melebihi batas percobaan login
        $this->ensureIsNotRateLimited();

        // Langkah 2: Coba autentikasi dengan email, password, dan opsi 'remember me'
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // Login gagal: tambahkan hitungan percobaan gagal ke rate limiter
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        // Langkah 3: Login berhasil, tapi cek apakah akun dalam status aktif
        if (! Auth::user()->is_active) {
            // Akun nonaktif: paksa logout dan tolak akses
            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Akun Anda tidak aktif. Hubungi Super Admin.',
            ]);
        }

        // Langkah 4: Login berhasil dan akun aktif, bersihkan hitungan rate limiter
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Memastikan request login tidak melebihi batas rate limit.
     *
     * Batas: Maksimal 5 percobaan login gagal berturut-turut.
     * Jika melebihi batas, pengguna harus menunggu beberapa detik/menit
     * sebelum bisa mencoba login kembali.
     *
     * @throws ValidationException Jika terlalu banyak percobaan login
     * @return void
     */
    public function ensureIsNotRateLimited(): void
    {
        // Cek apakah jumlah percobaan sudah melebihi 5 kali
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        // Trigger event Lockout untuk keperluan logging/monitoring
        event(new Lockout($this));

        // Hitung sisa waktu tunggu sebelum bisa mencoba login lagi
        $seconds = RateLimiter::availableIn($this->throttleKey());

        // Lempar error dengan pesan berisi waktu tunggu dalam detik dan menit
        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Mendapatkan kunci throttle (pembatas) untuk request ini.
     *
     * Kunci dibuat dari kombinasi email (lowercase) dan IP address pengguna.
     * Ini memungkinkan:
     * - Pengguna yang sama dari IP berbeda memiliki batas terpisah
     * - Pengguna berbeda dari IP yang sama memiliki batas terpisah
     *
     * Str::transliterate() digunakan untuk menangani karakter unicode/khusus
     * agar kunci selalu dalam format ASCII yang konsisten.
     *
     * @return string Kunci throttle unik (contoh: "user@email.com|192.168.1.1")
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
