<?php

/**
 * Form Request untuk Memperbarui Order EDK (Evaluasi Daftar Kerja).
 *
 * File ini menangani validasi dan otorisasi pada saat pengguna
 * memperbarui data Order EDK yang sudah ada. Memeriksa kepemilikan
 * data (hanya pemilik atau Super Admin yang bisa mengedit) dan
 * mengabaikan record saat ini pada validasi unique.
 *
 * @package App\Http\Requests\Operational
 */

namespace App\Http\Requests\Operational;

use App\Models\OrderEdk;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderEdkRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Otorisasi berlapis:
     * 1. Pengguna harus login dan data order_edk harus ada
     * 2. Pengguna harus memiliki permission 'order_edk.update'
     * 3. Pengguna harus Super Admin ATAU merupakan inputer pemilik data tersebut
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var OrderEdk|null $orderEdk */
        $orderEdk = $this->route('order_edk');

        // Tolak jika user tidak login, data tidak ditemukan, atau tidak punya permission
        if (! $user || ! $orderEdk || ! $user->can('order_edk.update')) {
            return false;
        }

        // Super Admin bisa edit semua data, Admin Inputer hanya bisa edit data miliknya sendiri
        return $user->isSuperAdmin() || $orderEdk->inputer_id === $user->id;
    }

    /**
     * Menyiapkan data sebelum proses validasi dijalankan.
     *
     * Logika:
     * - Admin Inputer otomatis menjadi inputer
     * - Default source_system ke 'Dashboard NCX' jika kosong
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
        /** @var OrderEdk $orderEdk */
        $orderEdk = $this->route('order_edk');

        return [
            // Referensi EDK unik per periode bulan, tapi mengabaikan record saat ini (ignore)
            // agar tidak gagal validasi saat referensi EDK tidak diubah
            'edk_reference' => [
                'required',
                'string',
                'max:100',
                Rule::unique('order_edks', 'edk_reference')
                    ->where('period_month', $this->input('period_month'))
                    ->ignore($orderEdk),
            ],
            // Nama pelanggan opsional, maksimum 150 karakter
            'customer_name' => ['nullable', 'string', 'max:150'],
            // Inputer wajib diisi dan harus merujuk ke user dengan role Admin Inputer yang aktif
            'inputer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN_INPUTER)->where('is_active', true)],
            // Account Manager wajib diisi dan harus merujuk ke user dengan role Account Manager yang aktif
            'account_manager_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ACCOUNT_MANAGER)->where('is_active', true)],
            // Status wajib sesuai daftar status yang valid dari model OrderEdk
            'status' => ['required', Rule::in(OrderEdk::statuses())],
            // Periode bulan wajib format YYYY-MM (contoh: 2026-07)
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            // Sumber sistem wajib diisi
            'source_system' => ['required', 'string', 'max:100'],
            // Catatan tambahan opsional, maksimum 1000 karakter
            'notes' => ['nullable', 'string', 'max:1000'],
            // Timestamp update terakhir wajib diisi, digunakan untuk deteksi konflik optimistic locking
            'updated_at' => ['required', 'date'],
        ];
    }
}
