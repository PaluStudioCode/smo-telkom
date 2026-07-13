<?php

/**
 * ==========================================================================
 * Controller Completion Record (Modul Complete) - Sistem SMO Telkom
 * ==========================================================================
 *
 * Controller ini mengelola operasi CRUD dan alur persetujuan (approval workflow)
 * untuk modul Completion Record dalam sistem SMO Telkom. Modul ini mencatat
 * penyelesaian order yang telah melalui proses dari Order Status atau Order EDK.
 *
 * Fitur utama:
 * - Daftar completion record dengan filter, pencarian, sorting, dan paginasi
 * - Pembuatan dan pembaruan completion record dengan relasi ke Order Status/EDK
 * - Alur persetujuan (approval workflow) multi-level
 * - Penghapusan completion record oleh pemilik atau Super Admin
 * - Statistik per status persetujuan
 *
 * Alur persetujuan (Approval Workflow):
 * Menunggu Persetujuan → Disetujui
 *                      → Tidak Disetujui → Revisi → Menunggu Persetujuan (ulang)
 *                      → Revisi → Menunggu Persetujuan (ulang)
 * Disetujui → Menunggu Persetujuan (dibatalkan)
 *           → Tidak Disetujui
 *           → Revisi
 *
 * Peran dalam approval:
 * - Admin Inputer: membuat dan mengedit data, mengirimkan untuk persetujuan
 * - Super Admin: menyetujui, menolak, atau meminta revisi
 * - Account Manager: melihat data terkait cakupan aksesnya
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AssertsFreshModel;
use App\Http\Requests\Operational\ApprovalCompletionRecordRequest;
use App\Http\Requests\Operational\StoreCompletionRecordRequest;
use App\Http\Requests\Operational\UpdateCompletionRecordRequest;
use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\StatusTransitionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompletionRecordController extends Controller
{
    // Trait untuk mekanisme optimistic locking
    use AssertsFreshModel;

    /**
     * Menampilkan daftar Completion Record dengan filter, sorting, dan paginasi.
     *
     * Alur proses:
     * 1. Otorisasi akses
     * 2. Validasi parameter filter
     * 3. Membangun query utama dengan eager loading relasi (inputer, AM, order status, order EDK)
     * 4. Membangun query ringkasan terpisah untuk statistik approval
     * 5. Menyediakan opsi dropdown (status, inputer, AM, order status, order EDK)
     *
     * @param  Request  $request  HTTP request dari pengguna
     * @return Response  Response Inertia untuk halaman daftar Completion Record
     */
    public function index(Request $request): Response
    {
        Gate::authorize('complete.view');

        // Validasi parameter filter dan sorting
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'inputer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'approval_status' => ['nullable', Rule::in(CompletionRecord::statuses())],
            'period_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'sort' => ['nullable', Rule::in(['completion_number', 'approval_status', 'completed_at', 'period_month', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $user = $request->user();
        $sort = $filters['sort'] ?? 'updated_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);

        // Query utama: eager load semua relasi yang diperlukan untuk tampilan
        // Termasuk relasi ke Order Status dan Order EDK yang menjadi dasar completion
        $query = $this->applyFilters(
            CompletionRecord::query()
                ->with([
                    'inputer:id,name',
                    'accountManager:id,name',
                    // Relasi opsional ke sumber data yang di-complete-kan
                    'orderStatus:id,order_number,customer_name,period_month',
                    'orderEdk:id,edk_reference,customer_name,period_month',
                ])
                ->visibleTo($user),
            $filters,
            $user,
        );

        // Query ringkasan: tanpa filter approval_status, untuk menampilkan statistik semua status
        $summaryQuery = $this->applyFilters(
            CompletionRecord::query()->visibleTo($user),
            Arr::except($filters, ['approval_status']),
            $user,
        );

        $records = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (CompletionRecord $completionRecord) => $this->serializeCompletionRecord($completionRecord));

        return Inertia::render('CompletionRecords/Index', [
            'completionRecords' => $records,
            'stats' => $this->approvalStats($summaryQuery),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'inputer_id' => $filters['inputer_id'] ?? '',
                'account_manager_id' => $filters['account_manager_id'] ?? '',
                'approval_status' => $filters['approval_status'] ?? '',
                'period_month' => $filters['period_month'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'approvalStatusOptions' => $this->approvalStatusOptions(),
            'inputerOptions' => $this->userOptions(User::ROLE_ADMIN_INPUTER),
            'accountManagerOptions' => $this->userOptions(User::ROLE_ACCOUNT_MANAGER),
            // Opsi Order Status dan Order EDK untuk dropdown relasi saat membuat/mengedit
            'orderStatusOptions' => $this->orderStatusOptions($user),
            'orderEdkOptions' => $this->orderEdkOptions($user),
        ]);
    }

    /**
     * Menyimpan Completion Record baru ke database.
     *
     * Alur proses:
     * 1. Otorisasi: memastikan pengguna berhak membuat completion record
     * 2. Validasi input melalui StoreCompletionRecordRequest
     * 3. Mengisi field approval otomatis berdasarkan status yang dipilih
     * 4. Menyimpan data dalam transaksi database
     * 5. Mencatat log aktivitas
     *
     * @param  StoreCompletionRecordRequest  $request  Request dengan validasi bawaan
     * @param  ActivityLogger  $activityLogger  Service pencatatan log
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function store(StoreCompletionRecordRequest $request, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('complete.create');

        // Mengisi field-field approval otomatis berdasarkan status yang dipilih
        // Contoh: jika status = 'menunggu_persetujuan', approved_by dan approved_at dikosongkan
        $validated = $this->approvalFieldsForSave($request->validated(), $request->user());

        DB::transaction(function () use ($request, $validated, $activityLogger): void {
            $record = CompletionRecord::create([
                ...$validated,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'complete', 'create', $record, null, $record->getAttributes());
        });

        return back()->with('success', 'Data Modul Complete berhasil ditambahkan.');
    }

    /**
     * Memperbarui Completion Record yang sudah ada.
     *
     * Alur proses:
     * 1. Otorisasi dan verifikasi kepemilikan
     * 2. Validasi input dan optimistic locking
     * 3. Validasi transisi status persetujuan
     * 4. Mengisi field approval otomatis berdasarkan perubahan status
     * 5. Update dalam transaksi + log aktivitas
     *
     * @param  UpdateCompletionRecordRequest  $request  Request dengan validasi bawaan
     * @param  CompletionRecord  $completionRecord  Instance model dari route model binding
     * @param  StatusTransitionService  $transitions  Service validasi transisi status
     * @param  ActivityLogger  $activityLogger  Service pencatatan log
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function update(
        UpdateCompletionRecordRequest $request,
        CompletionRecord $completionRecord,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('complete.update');
        $this->authorizeOwnership($request, $completionRecord);

        $validated = $request->validated();
        // Optimistic locking: pastikan data belum diubah oleh pengguna lain
        $this->assertFresh($completionRecord, $validated['updated_at']);
        // Validasi transisi: pastikan perubahan status approval mengikuti alur yang valid
        $transitions->assertCompletionTransition($completionRecord, $validated['approval_status']);

        unset($validated['updated_at']);
        // Mengisi field approval berdasarkan perubahan status, dengan menyertakan status sebelumnya
        // untuk menentukan apakah status benar-benar berubah
        $validated = $this->approvalFieldsForSave($validated, $request->user(), $completionRecord->approval_status);

        DB::transaction(function () use ($request, $completionRecord, $validated, $activityLogger): void {
            $oldValues = $completionRecord->getOriginal();
            $completionRecord->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'complete', 'update', $completionRecord, $oldValues, $completionRecord->fresh()->getAttributes());
        });

        return back()->with('success', 'Data Modul Complete berhasil diperbarui.');
    }

    /**
     * Menangani aksi persetujuan (approve, reject, request revision) pada Completion Record.
     *
     * Method ini berbeda dari update() karena:
     * - Otorisasi berdasarkan aksi spesifik (approve, reject, request_revision)
     * - Tidak memeriksa kepemilikan (approver bukan pemilik data)
     * - Menggunakan ApprovalCompletionRecordRequest khusus
     * - Log aktivitas menggunakan nama aksi yang lebih spesifik
     *
     * Alur proses:
     * 1. Validasi input
     * 2. Otorisasi berdasarkan jenis aksi approval (complete.approve, complete.reject, dll)
     * 3. Optimistic locking dan validasi transisi
     * 4. Mengisi field approval (approved_by, approved_at)
     * 5. Update dalam transaksi + log aktivitas
     *
     * @param  ApprovalCompletionRecordRequest  $request  Request khusus untuk approval
     * @param  CompletionRecord  $completionRecord  Record yang akan di-approve/reject
     * @param  StatusTransitionService  $transitions  Service validasi transisi
     * @param  ActivityLogger  $activityLogger  Service pencatatan log
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function approve(
        ApprovalCompletionRecordRequest $request,
        CompletionRecord $completionRecord,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $validated = $request->validated();
        // Otorisasi berdasarkan aksi spesifik: approve, reject, atau request_revision
        // Masing-masing aksi memiliki gate/permission yang berbeda
        Gate::authorize($this->approvalAbility($validated['approval_status']));

        $this->assertFresh($completionRecord, $validated['updated_at']);
        $transitions->assertCompletionTransition($completionRecord, $validated['approval_status']);

        unset($validated['updated_at']);
        $validated = $this->approvalFieldsForSave($validated, $request->user(), $completionRecord->approval_status);

        DB::transaction(function () use ($request, $completionRecord, $validated, $activityLogger): void {
            $oldValues = $completionRecord->getOriginal();
            $completionRecord->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            // Log dengan nama aksi spesifik (approve, reject, request_revision)
            // bukan generik 'update', untuk memudahkan audit
            $activityLogger->log(
                $request,
                'complete',
                $this->approvalAction($validated['approval_status']),
                $completionRecord,
                $oldValues,
                $completionRecord->fresh()->getAttributes(),
            );
        });

        return back()->with('success', 'Status persetujuan berhasil diperbarui.');
    }

    /**
     * Menghapus Completion Record dari database.
     *
     * Alur: otorisasi → verifikasi kepemilikan → optimistic locking →
     * hapus dalam transaksi → catat log.
     *
     * @param  Request  $request  HTTP request
     * @param  CompletionRecord  $completionRecord  Record yang akan dihapus
     * @param  ActivityLogger  $activityLogger  Service pencatatan log
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function destroy(Request $request, CompletionRecord $completionRecord, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('complete.delete');
        $this->authorizeOwnership($request, $completionRecord);

        $validated = $request->validate([
            'updated_at' => ['nullable', 'date'],
        ]);

        $this->assertFresh($completionRecord, $validated['updated_at'] ?? null);

        DB::transaction(function () use ($request, $completionRecord, $activityLogger): void {
            $oldValues = $completionRecord->getOriginal();
            $completionRecord->update(['updated_by' => $request->user()->id]);
            $completionRecord->delete();

            $activityLogger->log($request, 'complete', 'delete', $completionRecord, $oldValues, null);
        });

        return back()->with('success', 'Data Modul Complete berhasil dihapus.');
    }

    /**
     * Menerapkan filter ke query builder Completion Record.
     *
     * Pencarian lebih kompleks dari modul lain karena mendukung pencarian
     * lintas relasi (order_number dari OrderStatus, edk_reference dari OrderEdk).
     *
     * @param  Builder  $query  Query builder yang akan difilter
     * @param  array<string, mixed>  $filters  Parameter filter
     * @param  User  $user  Pengguna yang sedang login
     * @return Builder  Query builder yang sudah difilter
     */
    private function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        return $query
            // Pencarian mencakup: completion_number, order_number (via relasi), edk_reference (via relasi)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('completion_number', 'like', "%{$search}%")
                        // Pencarian lintas relasi menggunakan whereHas
                        ->orWhereHas('orderStatus', fn (Builder $query) => $query->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('orderEdk', fn (Builder $query) => $query->where('edk_reference', 'like', "%{$search}%"));
                });
            })
            // Filter inputer hanya untuk Super Admin
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            // Filter AM tidak berlaku jika pengguna adalah AM
            ->when(! $user->isAccountManager() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId))
            ->when($filters['approval_status'] ?? null, fn (Builder $query, string $approvalStatus) => $query->where('approval_status', $approvalStatus))
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth));
    }

    /**
     * Menghitung statistik per status persetujuan untuk ringkasan di halaman daftar.
     *
     * Menampilkan 4 statistik:
     * - Total Complete: jumlah seluruh record completion
     * - Disetujui: record yang sudah disetujui
     * - Tidak Disetujui: record yang ditolak
     * - Revisi: record yang perlu direvisi
     *
     * @param  Builder  $query  Query builder tanpa filter status untuk menghitung semua status
     * @return array<int, array{key: string, label: string, value: int, tone: string}>
     */
    private function approvalStats(Builder $query): array
    {
        $counts = (clone $query)
            ->select('approval_status', DB::raw('count(*) as total'))
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        return [
            // Total keseluruhan completion record
            [
                'key' => 'total',
                'label' => 'Total Complete',
                'value' => (int) $counts->sum(),
                'tone' => 'primary',
            ],
            // Status: Disetujui
            [
                'key' => CompletionRecord::STATUS_DISETUJUI,
                'label' => CompletionRecord::LABELS[CompletionRecord::STATUS_DISETUJUI],
                'value' => (int) ($counts[CompletionRecord::STATUS_DISETUJUI] ?? 0),
                'tone' => 'success',
            ],
            // Status: Tidak Disetujui
            [
                'key' => CompletionRecord::STATUS_TIDAK_DISETUJUI,
                'label' => CompletionRecord::LABELS[CompletionRecord::STATUS_TIDAK_DISETUJUI],
                'value' => (int) ($counts[CompletionRecord::STATUS_TIDAK_DISETUJUI] ?? 0),
                'tone' => 'danger',
            ],
            // Status: Revisi (perlu diperbaiki dan diajukan ulang)
            [
                'key' => CompletionRecord::STATUS_REVISI,
                'label' => CompletionRecord::LABELS[CompletionRecord::STATUS_REVISI],
                'value' => (int) ($counts[CompletionRecord::STATUS_REVISI] ?? 0),
                'tone' => 'warning',
            ],
        ];
    }

    /**
     * Menghasilkan opsi status persetujuan untuk dropdown filter.
     *
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    private function approvalStatusOptions(): array
    {
        return collect(CompletionRecord::LABELS)
            ->map(fn (string $label, string $status) => [
                'value' => $status,
                'label' => $label,
                'tone' => $this->approvalTone($status),
            ])
            ->values()
            ->all();
    }

    /**
     * Mengambil daftar pengguna aktif berdasarkan peran untuk dropdown filter.
     *
     * @param  string  $role  Peran pengguna
     * @return array<int, array{id: int, name: string}>
     */
    private function userOptions(string $role): array
    {
        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->all();
    }

    /**
     * Mengambil daftar Order Status yang bisa dijadikan relasi Completion Record.
     *
     * Menyediakan opsi Order Status untuk dropdown saat membuat/mengedit completion record.
     * Setiap opsi menyertakan inputer_id dan account_manager_id untuk mengisi
     * field tersebut secara otomatis di form frontend ketika pengguna memilih order.
     *
     * Dibatasi 250 record terbaru untuk menjaga performa dropdown.
     *
     * @param  User  $user  Pengguna yang sedang login (untuk filter visibleTo)
     * @return array<int, array{id: int, label: string, inputer_id: int, account_manager_id: int}>
     */
    private function orderStatusOptions(User $user): array
    {
        return OrderStatus::query()
            ->visibleTo($user)
            ->latest('updated_at')
            ->limit(250) // Batasi jumlah opsi untuk performa
            ->get(['id', 'order_number', 'customer_name', 'period_month', 'inputer_id', 'account_manager_id'])
            ->map(fn (OrderStatus $orderStatus) => [
                'id' => $orderStatus->id,
                // Label format: "NO-ORDER - Nama Pelanggan - 2026-07"
                'label' => trim($orderStatus->order_number.' - '.($orderStatus->customer_name ?: 'Tanpa pelanggan').' - '.$orderStatus->period_month),
                // ID inputer dan AM untuk auto-fill form di frontend
                'inputer_id' => $orderStatus->inputer_id,
                'account_manager_id' => $orderStatus->account_manager_id,
            ])
            ->all();
    }

    /**
     * Mengambil daftar Order EDK yang bisa dijadikan relasi Completion Record.
     *
     * Fungsi dan logika sama dengan orderStatusOptions() tetapi untuk Order EDK.
     *
     * @param  User  $user  Pengguna yang sedang login
     * @return array<int, array{id: int, label: string, inputer_id: int, account_manager_id: int}>
     */
    private function orderEdkOptions(User $user): array
    {
        return OrderEdk::query()
            ->visibleTo($user)
            ->latest('updated_at')
            ->limit(250)
            ->get(['id', 'edk_reference', 'customer_name', 'period_month', 'inputer_id', 'account_manager_id'])
            ->map(fn (OrderEdk $orderEdk) => [
                'id' => $orderEdk->id,
                'label' => trim($orderEdk->edk_reference.' - '.($orderEdk->customer_name ?: 'Tanpa pelanggan').' - '.$orderEdk->period_month),
                'inputer_id' => $orderEdk->inputer_id,
                'account_manager_id' => $orderEdk->account_manager_id,
            ])
            ->all();
    }

    /**
     * Mengubah model CompletionRecord menjadi array untuk dikirim ke frontend.
     *
     * Menyertakan data relasi Order Status dan Order EDK dalam format label
     * ringkas (nomor + periode) untuk ditampilkan di tabel.
     *
     * @param  CompletionRecord  $completionRecord  Instance model
     * @return array<string, mixed>  Data yang siap dikirim ke komponen Vue
     */
    private function serializeCompletionRecord(CompletionRecord $completionRecord): array
    {
        return [
            'id' => $completionRecord->id,
            'completion_number' => $completionRecord->completion_number,
            'order_status_id' => $completionRecord->order_status_id,
            // Label ringkas dari Order Status yang terkait (jika ada)
            'order_status_label' => $completionRecord->orderStatus
                ? $completionRecord->orderStatus->order_number.' - '.$completionRecord->orderStatus->period_month
                : null,
            'order_edk_id' => $completionRecord->order_edk_id,
            // Label ringkas dari Order EDK yang terkait (jika ada)
            'order_edk_label' => $completionRecord->orderEdk
                ? $completionRecord->orderEdk->edk_reference.' - '.$completionRecord->orderEdk->period_month
                : null,
            'inputer_id' => $completionRecord->inputer_id,
            'inputer_name' => $completionRecord->inputer?->name,
            'account_manager_id' => $completionRecord->account_manager_id,
            'account_manager_name' => $completionRecord->accountManager?->name,
            'approval_status' => $completionRecord->approval_status,
            'approval_status_label' => CompletionRecord::LABELS[$completionRecord->approval_status] ?? $completionRecord->approval_status,
            'approval_status_tone' => $this->approvalTone($completionRecord->approval_status),
            'completed_at' => $completionRecord->completed_at?->format('Y-m-d'),
            'approved_at' => $completionRecord->approved_at?->format('Y-m-d H:i'),
            'revision_note' => $completionRecord->revision_note,
            'period_month' => $completionRecord->period_month,
            'notes' => $completionRecord->notes,
            'updated_at' => $completionRecord->updated_at?->format('Y-m-d H:i'),
            'updated_at_token' => $this->updatedAtToken($completionRecord),
        ];
    }

    /**
     * Mengisi field-field terkait approval secara otomatis berdasarkan perubahan status.
     *
     * Logika bisnis:
     * 1. Jika status = "Menunggu Persetujuan": reset approved_by dan approved_at ke null
     *    karena data belum/tidak lagi disetujui
     * 2. Jika status berubah (bukan tetap sama): catat siapa yang menyetujui/menolak
     *    dan kapan aksi tersebut dilakukan
     * 3. Jika status bukan "Revisi" dan revision_note kosong: hapus catatan revisi
     *    karena tidak relevan
     *
     * @param  array<string, mixed>  $data  Data yang akan disimpan
     * @param  User  $user  Pengguna yang melakukan aksi
     * @param  string|null  $previousStatus  Status sebelumnya (null untuk record baru)
     * @return array<string, mixed>  Data yang sudah dilengkapi field approval
     */
    private function approvalFieldsForSave(array $data, User $user, ?string $previousStatus = null): array
    {
        $status = $data['approval_status'];
        // Tentukan apakah status benar-benar berubah
        $changed = $previousStatus === null || $previousStatus !== $status;

        // Status "Menunggu Persetujuan": reset data approval
        if ($status === CompletionRecord::STATUS_MENUNGGU_PERSETUJUAN) {
            $data['approved_by'] = null;
            $data['approved_at'] = null;

            return $data;
        }

        // Jika status berubah, catat siapa yang melakukan aksi approval dan kapan
        if ($changed) {
            $data['approved_by'] = $user->id;
            $data['approved_at'] = now();
        }

        // Bersihkan catatan revisi jika status bukan Revisi dan tidak ada catatan yang diisi
        if ($status !== CompletionRecord::STATUS_REVISI && blank($data['revision_note'] ?? null)) {
            $data['revision_note'] = null;
        }

        return $data;
    }

    /**
     * Menentukan kemampuan (ability/permission) Gate berdasarkan status approval.
     *
     * Memetakan status approval ke permission yang sesuai untuk otorisasi.
     * Setiap aksi approval memiliki permission terpisah agar bisa dikontrol
     * secara granular per peran pengguna.
     *
     * @param  string  $status  Status approval yang dituju
     * @return string  Nama ability Gate
     */
    private function approvalAbility(string $status): string
    {
        return match ($status) {
            CompletionRecord::STATUS_DISETUJUI => 'complete.approve',
            CompletionRecord::STATUS_TIDAK_DISETUJUI => 'complete.reject',
            CompletionRecord::STATUS_REVISI => 'complete.request_revision',
            default => 'complete.approve',
        };
    }

    /**
     * Menentukan nama aksi untuk log aktivitas berdasarkan status approval.
     *
     * Menggunakan nama aksi yang lebih deskriptif daripada generik 'update'
     * agar log audit lebih mudah dibaca dan difilter.
     *
     * @param  string  $status  Status approval yang dituju
     * @return string  Nama aksi untuk log (approve, reject, request_revision)
     */
    private function approvalAction(string $status): string
    {
        return match ($status) {
            CompletionRecord::STATUS_DISETUJUI => 'approve',
            CompletionRecord::STATUS_TIDAK_DISETUJUI => 'reject',
            CompletionRecord::STATUS_REVISI => 'request_revision',
            default => 'approval_update',
        };
    }

    /**
     * Menentukan tone/tema warna berdasarkan status persetujuan.
     *
     * @param  string  $status  Nilai status persetujuan
     * @return string  Nama tone warna (success, danger, warning)
     */
    private function approvalTone(string $status): string
    {
        return match ($status) {
            CompletionRecord::STATUS_DISETUJUI => 'success',
            CompletionRecord::STATUS_TIDAK_DISETUJUI => 'danger',
            default => 'warning',
        };
    }

    /**
     * Memverifikasi kepemilikan data: hanya pemilik atau Super Admin.
     *
     * @param  Request  $request  HTTP request
     * @param  CompletionRecord  $completionRecord  Record yang akan divalidasi
     */
    private function authorizeOwnership(Request $request, CompletionRecord $completionRecord): void
    {
        abort_unless($request->user()->isSuperAdmin() || $completionRecord->inputer_id === $request->user()->id, 403);
    }
}
