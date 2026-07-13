<?php

/**
 * Form Request untuk Menyimpan Order Status Baru.
 *
 * File ini menangani validasi dan otorisasi pada saat pengguna
 * membuat data Order Status baru. Validasi mencakup pengecekan
 * nomor order unik per periode bulan, serta memastikan relasi
 * inputer dan account manager yang valid.
 *
 * @package App\Http\Requests\Operational
 */

namespace App\Http\Requests\Operational;

use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderStatusRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Hanya pengguna yang memiliki permission 'order_status.create'
     * yang diizinkan membuat Order Status baru.
     * Menggunakan null-safe operator (?->) untuk menghindari error jika user belum login.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('order_status.create') ?? false;
    }

    /**
     * Menyiapkan data sebelum proses validasi dijalankan.
     *
     * Metode ini melakukan dua hal:
     * 1. Mengatur inputer_id: Jika pengguna adalah Admin Inputer, maka ID-nya otomatis
     *    digunakan sebagai inputer_id (tidak bisa memilih inputer lain).
     *    Jika bukan Admin Inputer (misalnya Super Admin), maka inputer_id diambil dari input form.
     * 2. Mengatur source_system: Jika tidak diisi, default ke 'Dashboard NCX'.
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
            // Nomor order wajib diisi, unik per kombinasi order_number + period_month
            // Ini mencegah duplikasi nomor order dalam satu periode bulan yang sama
            'order_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('order_statuses', 'order_number')
                    ->where('period_month', $this->input('period_month')),
            ],
            // Nama pelanggan opsional, maksimum 150 karakter
            'customer_name' => ['nullable', 'string', 'max:150'],
            // Nama layanan/service opsional, maksimum 150 karakter
            'service_name' => ['nullable', 'string', 'max:150'],
            // Inputer wajib diisi dan harus merujuk ke user dengan role Admin Inputer yang aktif
            'inputer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN_INPUTER)->where('is_active', true)],
            // Account Manager wajib diisi dan harus merujuk ke user dengan role Account Manager yang aktif
            'account_manager_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ACCOUNT_MANAGER)->where('is_active', true)],
            // Status wajib diisi dan harus sesuai dengan daftar status yang valid dari model OrderStatus
            'status' => ['required', Rule::in(OrderStatus::statuses())],
            // Tahapan provisioning opsional, diisi ketika status sedang dalam proses provisioning
            'provisioning_stage' => ['nullable', 'string', 'max:150'],
            // Periode bulan wajib diisi dengan format YYYY-MM (contoh: 2026-07)
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            // Sumber sistem wajib diisi, menunjukkan dari mana data diinput
            'source_system' => ['required', 'string', 'max:100'],
            // Catatan tambahan opsional, maksimum 1000 karakter
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
