<?php

/**
 * Form Request untuk Persetujuan/Penolakan Completion Record.
 *
 * File ini menangani validasi dan otorisasi pada saat pengguna
 * melakukan aksi persetujuan (approve), penolakan (reject), atau
 * permintaan revisi terhadap sebuah Completion Record.
 *
 * Request ini digunakan khusus untuk proses approval workflow,
 * bukan untuk mengubah data isi record. Yang divalidasi hanya:
 * - Status persetujuan baru
 * - Catatan revisi (jika status revisi)
 * - Timestamp update terakhir (untuk optimistic locking)
 *
 * @package App\Http\Requests\Operational
 */

namespace App\Http\Requests\Operational;

use App\Models\CompletionRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalCompletionRecordRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna diizinkan melakukan request ini.
     *
     * Pengguna diizinkan jika memiliki SALAH SATU dari permission berikut:
     * - 'complete.approve': Dapat menyetujui Completion Record
     * - 'complete.reject': Dapat menolak Completion Record
     * - 'complete.request_revision': Dapat meminta revisi Completion Record
     *
     * Cek menggunakan operator OR karena satu pengguna mungkin hanya memiliki
     * sebagian dari permission tersebut tergantung role-nya.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && (
                $user->can('complete.approve')
                || $user->can('complete.reject')
                || $user->can('complete.request_revision')
            );
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * Hanya memvalidasi field yang terkait proses approval,
     * tidak memvalidasi seluruh data Completion Record.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Status persetujuan wajib diisi dan harus sesuai daftar status yang valid
            // (menunggu_persetujuan, disetujui, tidak_disetujui, revisi)
            'approval_status' => ['required', Rule::in(CompletionRecord::statuses())],
            // Catatan revisi: opsional secara umum, WAJIB diisi jika status adalah 'revisi'
            // Reviewer harus memberikan alasan/instruksi ketika meminta revisi
            'revision_note' => ['nullable', 'required_if:approval_status,'.CompletionRecord::STATUS_REVISI, 'string', 'max:1000'],
            // Timestamp update terakhir wajib diisi, untuk deteksi konflik optimistic locking
            // Mencegah persetujuan yang tumpang tindih jika data sudah diubah pengguna lain
            'updated_at' => ['required', 'date'],
        ];
    }
}
