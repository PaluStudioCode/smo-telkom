<?php

/**
 * Form Request untuk Menyimpan Order EDK (Evaluasi Daftar Kerja) Baru.
 *
 * File ini menangani validasi dan otorisasi pada saat pengguna
 * membuat data Order EDK baru. EDK merupakan referensi evaluasi
 * daftar kerja yang digunakan untuk melacak progres order.
 * Validasi mencakup pengecekan referensi EDK unik per periode bulan.
 *
 * @package App\Http\Requests\Operational
 */

namespace App\Http\Requests\Operational;

use App\Models\OrderEdk;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderEdkRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Hanya pengguna yang memiliki permission 'order_edk.create'
     * yang diizinkan membuat Order EDK baru.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('order_edk.create') ?? false;
    }

    /**
     * Menyiapkan data sebelum proses validasi dijalankan.
     *
     * Logika:
     * 1. Admin Inputer otomatis menjadi inputer (tidak bisa memilih inputer lain)
     * 2. Default source_system ke 'Dashboard NCX' jika tidak diisi
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Admin Inputer otomatis menjadi inputer, Super Admin bisa memilih inputer lain
            'inputer_id' => $this->user()->isAdminInputer() ? $this->user()->id : $this->input('inputer_id'),
            // Default sumber sistem adalah 'Dashboard NCX' jika tidak diisi
            'source_system' => $this->input('source_system') ?: 'Dashboard NCX',
        ]);
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Referensi EDK wajib diisi, unik per kombinasi edk_reference + period_month
            // Mencegah duplikasi referensi EDK dalam satu periode bulan yang sama
            'edk_reference' => [
                'required',
                'string',
                'max:100',
                Rule::unique('order_edks', 'edk_reference')
                    ->where('period_month', $this->input('period_month')),
            ],
            // Nama pelanggan opsional, maksimum 150 karakter
            'customer_name' => ['nullable', 'string', 'max:150'],
            // Inputer wajib diisi dan harus merujuk ke user dengan role Admin Inputer yang aktif
            'inputer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN_INPUTER)->where('is_active', true)],
            // Account Manager wajib diisi dan harus merujuk ke user dengan role Account Manager yang aktif
            'account_manager_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ACCOUNT_MANAGER)->where('is_active', true)],
            // Status wajib diisi dan harus sesuai dengan daftar status yang valid dari model OrderEdk
            'status' => ['required', Rule::in(OrderEdk::statuses())],
            // Periode bulan wajib diisi dengan format YYYY-MM (contoh: 2026-07)
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            // Sumber sistem wajib diisi, menunjukkan dari mana data diinput
            'source_system' => ['required', 'string', 'max:100'],
            // Catatan tambahan opsional, maksimum 1000 karakter
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
