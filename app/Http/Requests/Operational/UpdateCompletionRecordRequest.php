<?php

/**
 * Form Request untuk Memperbarui Completion Record (Catatan Penyelesaian).
 *
 * File ini menangani validasi dan otorisasi pada saat pengguna
 * memperbarui data Completion Record yang sudah ada. Selain validasi dasar,
 * file ini juga mengelola alur persetujuan (approval workflow):
 * - Admin Inputer yang mengedit record berstatus 'revisi' akan otomatis
 *   mengubah status ke 'menunggu_persetujuan' (re-submit setelah revisi)
 * - Admin Inputer tidak bisa mengubah status persetujuan atau catatan revisi secara manual
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

class UpdateCompletionRecordRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Otorisasi berlapis:
     * 1. Pengguna harus login dan data completion_record harus ada
     * 2. Pengguna harus memiliki permission 'complete.update'
     * 3. Pengguna harus Super Admin ATAU merupakan inputer pemilik data tersebut
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var CompletionRecord|null $completionRecord */
        $completionRecord = $this->route('completion_record');

        // Tolak jika user tidak login, data tidak ditemukan, atau tidak punya permission
        if (! $user || ! $completionRecord || ! $user->can('complete.update')) {
            return false;
        }

        // Super Admin bisa edit semua data, Admin Inputer hanya bisa edit data miliknya sendiri
        return $user->isSuperAdmin() || $completionRecord->inputer_id === $user->id;
    }

    /**
     * Menyiapkan data sebelum proses validasi dijalankan.
     *
     * Logika alur persetujuan (approval workflow):
     * 1. Admin Inputer:
     *    - Tidak bisa mengubah status persetujuan secara manual (tetap menggunakan status saat ini)
     *    - KECUALI jika status saat ini adalah 'revisi', maka otomatis berubah ke 'menunggu_persetujuan'
     *      (ini mensimulasikan proses re-submit setelah melakukan perbaikan)
     *    - Tidak bisa mengubah catatan revisi (tetap menggunakan catatan yang ada)
     * 2. Untuk role lain (Super Admin):
     *    - Bisa mengubah status persetujuan dan catatan revisi secara manual
     *    - Jika tidak diisi, default ke status saat ini
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        /** @var CompletionRecord $completionRecord */
        $completionRecord = $this->route('completion_record');

        // Tentukan status persetujuan berdasarkan role pengguna
        $approvalStatus = $this->user()->isAdminInputer()
            ? $completionRecord->approval_status  // Admin Inputer: pertahankan status saat ini
            : ($this->input('approval_status') ?: $completionRecord->approval_status);  // Super Admin: ambil dari input atau pertahankan

        // Jika Admin Inputer mengedit record yang berstatus 'revisi',
        // otomatis ubah status ke 'menunggu_persetujuan' (re-submit untuk review ulang)
        if ($this->user()->isAdminInputer() && $completionRecord->approval_status === CompletionRecord::STATUS_REVISI) {
            $approvalStatus = CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN;
        }

        $this->merge([
            // Admin Inputer otomatis menjadi inputer
            'inputer_id' => $this->user()->isAdminInputer() ? $this->user()->id : $this->input('inputer_id'),
            // Status persetujuan yang sudah dihitung berdasarkan logika di atas
            'approval_status' => $approvalStatus,
            // Admin Inputer tidak bisa mengubah catatan revisi (catatan dari reviewer harus tetap ada)
            'revision_note' => $this->user()->isAdminInputer() ? $completionRecord->revision_note : $this->input('revision_note'),
        ]);
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var CompletionRecord $completionRecord */
        $completionRecord = $this->route('completion_record');

        return [
            // Nomor completion unik per periode bulan, mengabaikan record saat ini (ignore)
            'completion_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('completion_records', 'completion_number')
                    ->where('period_month', $this->input('period_month'))
                    ->ignore($completionRecord),
            ],
            // Referensi ke Order Status (opsional), harus ada di tabel order_statuses
            'order_status_id' => ['nullable', 'integer', Rule::exists('order_statuses', 'id')],
            // Referensi ke Order EDK (opsional), harus ada di tabel order_edks
            'order_edk_id' => ['nullable', 'integer', Rule::exists('order_edks', 'id')],
            // Inputer wajib diisi dan harus merujuk ke user dengan role Admin Inputer yang aktif
            'inputer_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN_INPUTER)->where('is_active', true)],
            // Account Manager wajib diisi dan harus merujuk ke user dengan role Account Manager yang aktif
            'account_manager_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_ACCOUNT_MANAGER)->where('is_active', true)],
            // Status persetujuan wajib dan harus sesuai daftar status yang valid
            'approval_status' => ['required', Rule::in(CompletionRecord::statuses())],
            // Tanggal penyelesaian opsional
            'completed_at' => ['nullable', 'date'],
            // Catatan revisi: opsional secara umum, WAJIB diisi jika status adalah 'revisi'
            'revision_note' => ['nullable', 'required_if:approval_status,'.CompletionRecord::STATUS_REVISI, 'string', 'max:1000'],
            // Periode bulan wajib format YYYY-MM
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            // Catatan tambahan opsional
            'notes' => ['nullable', 'string', 'max:1000'],
            // Timestamp update terakhir wajib diisi, untuk deteksi konflik optimistic locking
            'updated_at' => ['required', 'date'],
        ];
    }

    /**
     * Menambahkan validasi tambahan setelah validasi dasar berhasil.
     *
     * Dijalankan setelah semua rule dasar lolos, untuk memvalidasi
     * konsistensi relasi order yang ditautkan.
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
     * Order Status harus dapat diakses pengguna saat ini (scope visibleTo)
     * dan memiliki inputer serta account manager yang konsisten.
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

        // Jika tidak ditemukan, pengguna tidak memiliki akses
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
     * Order EDK harus dapat diakses pengguna saat ini dan
     * memiliki inputer serta account manager yang konsisten.
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

        // Jika tidak ditemukan, pengguna tidak memiliki akses
        if (! $orderEdk) {
            $validator->errors()->add('order_edk_id', 'Order EDK tidak tersedia untuk pengguna ini.');

            return;
        }

        // Validasi konsistensi inputer dan account manager
        $this->validateRelationOwner($validator, 'order_edk_id', $orderEdk->inputer_id, $orderEdk->account_manager_id);
    }

    /**
     * Memvalidasi konsistensi inputer dan account manager antar relasi.
     *
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
