<?php

/**
 * Form Request untuk Menyimpan Completion Record (Catatan Penyelesaian) Baru.
 *
 * File ini menangani validasi dan otorisasi pada saat pengguna
 * membuat data Completion Record baru. Completion Record adalah catatan
 * penyelesaian order yang menghubungkan Order Status dan/atau Order EDK.
 *
 * Fitur validasi khusus:
 * - Minimal salah satu Order Status atau Order EDK harus dipilih
 * - Order yang ditautkan harus memiliki inputer dan account manager yang sama
 * - Order harus dapat diakses oleh pengguna yang membuat record
 * - Status persetujuan otomatis diatur berdasarkan role pengguna
 *
 * @package App\Http\Requests\Operational
 */

namespace App\Http\Requests\Operational;

use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompletionRecordRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Hanya pengguna yang memiliki permission 'complete.create'
     * yang diizinkan membuat Completion Record baru.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('complete.create') ?? false;
    }

    /**
     * Menyiapkan data sebelum proses validasi dijalankan.
     *
     * Logika bisnis:
     * 1. Admin Inputer otomatis menjadi inputer dan status persetujuan
     *    selalu diatur ke 'menunggu_persetujuan' (karena butuh approval dari atasan).
     * 2. Untuk role lain (Super Admin), inputer_id dan approval_status bisa diatur manual,
     *    tapi jika approval_status kosong, default ke 'menunggu_persetujuan'.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Admin Inputer otomatis menjadi inputer
            'inputer_id' => $this->user()->isAdminInputer() ? $this->user()->id : $this->input('inputer_id'),
            // Admin Inputer selalu memulai dengan status 'menunggu_persetujuan'
            // Super Admin bisa memilih status, default 'menunggu_persetujuan' jika tidak diisi
            'approval_status' => $this->user()->isAdminInputer()
                ? CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN
                : ($this->input('approval_status') ?: CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN),
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
            // Nomor completion wajib diisi, unik per kombinasi completion_number + period_month
            'completion_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('completion_records', 'completion_number')
                    ->where('period_month', $this->input('period_month')),
            ],
            // Referensi ke Order Status (opsional), harus ada di tabel order_statuses
            'order_status_id' => ['nullable', 'integer', Rule::exists('order_statuses', 'id')],
            // Referensi ke Order EDK (opsional), harus ada di tabel order_edks
            // Catatan: Minimal salah satu dari order_status_id atau order_edk_id harus diisi (divalidasi di withValidator)
            'order_edk_id' => ['nullable', 'integer', Rule::exists('order_edks', 'id')],
            // Inputer wajib diisi dan harus merujuk ke user dengan role Admin Inputer yang aktif
            'inputer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN_INPUTER)->where('is_active', true)],
            // Account Manager wajib diisi dan harus merujuk ke user dengan role Account Manager yang aktif
            'account_manager_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ACCOUNT_MANAGER)->where('is_active', true)],
            // Status persetujuan wajib diisi dan harus sesuai daftar status yang valid
            'approval_status' => ['required', Rule::in(CompletionRecord::statuses())],
            // Tanggal penyelesaian opsional, diisi saat order sudah selesai dikerjakan
            'completed_at' => ['nullable', 'date'],
            // Catatan revisi: opsional secara umum, WAJIB diisi jika status adalah 'revisi'
            // Ini memastikan reviewer memberikan alasan ketika meminta revisi
            'revision_note' => ['nullable', 'required_if:approval_status,'.CompletionRecord::STATUS_REVISI, 'string', 'max:1000'],
            // Periode bulan wajib diisi dengan format YYYY-MM
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            // Catatan tambahan opsional
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Menambahkan validasi tambahan setelah validasi dasar berhasil.
     *
     * Validasi ini dijalankan SETELAH semua rule dasar lolos validasi,
     * sehingga kita bisa memastikan data yang divalidasi sudah bersih.
     * Jika ada error dari validasi dasar, validasi tambahan dilewati.
     *
     * @param mixed $validator Instance validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            // Lewati validasi tambahan jika sudah ada error dari validasi dasar
            if ($validator->errors()->any()) {
                return;
            }

            // Jalankan validasi relasi order yang ditautkan
            $this->validateLinkedOrders($validator);
        });
    }

    /**
     * Memvalidasi bahwa minimal satu order (Status atau EDK) harus ditautkan.
     *
     * Aturan bisnis: Setiap Completion Record harus terhubung ke minimal
     * satu Order Status atau satu Order EDK. Tidak boleh keduanya kosong.
     *
     * @param mixed $validator Instance validator
     * @return void
     */
    private function validateLinkedOrders($validator): void
    {
        // Cek apakah minimal salah satu order diisi
        if (! $this->filled('order_status_id') && ! $this->filled('order_edk_id')) {
            $validator->errors()->add('order_status_id', 'Wajib memilih minimal salah satu Order Status atau Order EDK.');

            return;
        }

        // Validasi masing-masing tautan order jika diisi
        $this->validateOrderStatusLink($validator);
        $this->validateOrderEdkLink($validator);
    }

    /**
     * Memvalidasi tautan ke Order Status.
     *
     * Pengecekan:
     * 1. Order Status harus dapat diakses oleh pengguna saat ini (scope visibleTo)
     * 2. Inputer dan Account Manager pada Order Status harus sama dengan yang dipilih di form
     *
     * @param mixed $validator Instance validator
     * @return void
     */
    private function validateOrderStatusLink($validator): void
    {
        // Lewati jika order_status_id tidak diisi
        if (! $this->filled('order_status_id')) {
            return;
        }

        // Cari Order Status yang dapat diakses oleh pengguna saat ini
        $orderStatus = OrderStatus::query()
            ->visibleTo($this->user())
            ->find($this->integer('order_status_id'));

        // Jika tidak ditemukan, berarti pengguna tidak memiliki akses ke Order Status tersebut
        if (! $orderStatus) {
            $validator->errors()->add('order_status_id', 'Order Status tidak tersedia untuk pengguna ini.');

            return;
        }

        // Validasi konsistensi inputer dan account manager
        $this->validateRelationOwner($validator, 'order_status_id', $orderStatus->inputer_id, $orderStatus->account_manager_id);
    }

    /**
     * Memvalidasi tautan ke Order EDK.
     *
     * Pengecekan sama seperti validateOrderStatusLink:
     * 1. Order EDK harus dapat diakses oleh pengguna saat ini
     * 2. Inputer dan Account Manager harus konsisten
     *
     * @param mixed $validator Instance validator
     * @return void
     */
    private function validateOrderEdkLink($validator): void
    {
        // Lewati jika order_edk_id tidak diisi
        if (! $this->filled('order_edk_id')) {
            return;
        }

        // Cari Order EDK yang dapat diakses oleh pengguna saat ini
        $orderEdk = OrderEdk::query()
            ->visibleTo($this->user())
            ->find($this->integer('order_edk_id'));

        // Jika tidak ditemukan, berarti pengguna tidak memiliki akses ke Order EDK tersebut
        if (! $orderEdk) {
            $validator->errors()->add('order_edk_id', 'Order EDK tidak tersedia untuk pengguna ini.');

            return;
        }

        // Validasi konsistensi inputer dan account manager
        $this->validateRelationOwner($validator, 'order_edk_id', $orderEdk->inputer_id, $orderEdk->account_manager_id);
    }

    /**
     * Memvalidasi bahwa inputer dan account manager pada order yang ditautkan
     * harus sama dengan yang dipilih di Completion Record.
     *
     * Aturan bisnis: Konsistensi data antar relasi harus terjaga.
     * Completion Record, Order Status, dan Order EDK yang saling terhubung
     * harus dikelola oleh inputer dan account manager yang sama.
     *
     * @param mixed  $validator        Instance validator
     * @param string $field            Nama field yang sedang divalidasi
     * @param int    $inputerId        ID inputer dari order yang ditautkan
     * @param int    $accountManagerId ID account manager dari order yang ditautkan
     * @return void
     */
    private function validateRelationOwner($validator, string $field, int $inputerId, int $accountManagerId): void
    {
        if ($inputerId !== $this->integer('inputer_id') || $accountManagerId !== $this->integer('account_manager_id')) {
            $validator->errors()->add($field, 'Relasi order harus memiliki Inputer dan Account Manager yang sama.');
        }
    }
}
