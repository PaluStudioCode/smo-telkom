<?php

/**
 * ==========================================================================
 * Controller Order Status - Sistem SMO Telkom
 * ==========================================================================
 *
 * Controller ini mengelola operasi CRUD (Create, Read, Update, Delete) untuk
 * modul Order Status dalam sistem SMO Telkom. Order Status merepresentasikan
 * status pesanan layanan Telkom yang sedang diproses.
 *
 * Fitur utama:
 * - Daftar order status dengan filter, pencarian, sorting, dan paginasi
 * - Pembuatan order status baru dengan validasi transisi status
 * - Pembaruan order status dengan mekanisme optimistic locking (assertFresh)
 * - Penghapusan order status dengan otorisasi kepemilikan
 * - Statistik per status untuk ringkasan di halaman daftar
 *
 * Aturan bisnis penting:
 * - Hanya Admin Inputer yang bisa membuat, mengubah, dan menghapus data
 * - Super Admin bisa mengakses semua data; peran lain dibatasi oleh scope visibleTo()
 * - Perubahan status harus mengikuti alur transisi yang valid (StatusTransitionService)
 * - Setiap operasi dicatat dalam log aktivitas (ActivityLogger)
 * - Optimistic locking mencegah konflik pengeditan bersamaan
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AssertsFreshModel;
use App\Http\Requests\Operational\StoreOrderStatusRequest;
use App\Http\Requests\Operational\UpdateOrderStatusRequest;
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

class OrderStatusController extends Controller
{
    // Trait untuk mekanisme optimistic locking - mencegah konflik update bersamaan
    use AssertsFreshModel;

    /**
     * Menampilkan daftar Order Status dengan filter, sorting, dan paginasi.
     *
     * Alur proses:
     * 1. Otorisasi: memastikan pengguna berhak melihat data order status
     * 2. Validasi filter dari query string (search, inputer, AM, status, period, sort, pagination)
     * 3. Membangun query utama dengan filter dan scope visibleTo()
     * 4. Membangun query ringkasan terpisah (tanpa filter status) untuk statistik
     * 5. Menjalankan paginasi dan serialisasi data
     * 6. Mengembalikan response Inertia dengan data, statistik, filter aktif, dan opsi dropdown
     *
     * @param  Request  $request  HTTP request dari pengguna
     * @return Response  Response Inertia untuk halaman daftar Order Status
     */
    public function index(Request $request): Response
    {
        // Otorisasi: periksa apakah pengguna boleh melihat order status
        Gate::authorize('order_status.view');

        // Validasi semua parameter filter dari query string
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'inputer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(OrderStatus::statuses())],
            'period_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'sort' => ['nullable', Rule::in(['order_number', 'customer_name', 'service_name', 'status', 'period_month', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $user = $request->user();
        // Nilai default sorting: terbaru terlebih dahulu
        $sort = $filters['sort'] ?? 'updated_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);

        // Query utama: mengambil data dengan eager loading relasi inputer dan account manager
        // Scope visibleTo() membatasi data sesuai peran pengguna
        $query = $this->applyFilters(
            OrderStatus::query()
                ->with(['inputer:id,name', 'accountManager:id,name'])
                ->visibleTo($user),
            $filters,
            $user,
        );

        // Query ringkasan: query terpisah tanpa filter status untuk menghitung statistik per status
        // Ini memungkinkan statistik menampilkan jumlah total per status meskipun pengguna memfilter satu status
        $summaryQuery = $this->applyFilters(
            OrderStatus::query()->visibleTo($user),
            Arr::except($filters, ['status']),
            $user,
        );

        // Menjalankan paginasi dan mengubah setiap record menjadi format serialisasi standar
        $records = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString() // Mempertahankan query string pada link pagination
            ->through(fn (OrderStatus $orderStatus) => $this->serializeOrderStatus($orderStatus));

        // Mengembalikan response Inertia dengan semua data yang dibutuhkan frontend
        return Inertia::render('OrderStatuses/Index', [
            'orderStatuses' => $records,
            'stats' => $this->statusStats($summaryQuery),
            // Mengembalikan filter aktif ke frontend untuk menjaga state form
            'filters' => [
                'search' => $filters['search'] ?? '',
                'inputer_id' => $filters['inputer_id'] ?? '',
                'account_manager_id' => $filters['account_manager_id'] ?? '',
                'status' => $filters['status'] ?? '',
                'period_month' => $filters['period_month'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            // Opsi dropdown untuk filter
            'statusOptions' => $this->statusOptions(),
            'inputerOptions' => $this->userOptions(User::ROLE_ADMIN_INPUTER),
            'accountManagerOptions' => $this->userOptions(User::ROLE_ACCOUNT_MANAGER),
        ]);
    }

    /**
     * Menyimpan Order Status baru ke database.
     *
     * Alur proses:
     * 1. Otorisasi: memastikan pengguna berhak membuat order status
     * 2. Validasi input melalui StoreOrderStatusRequest (Form Request)
     * 3. Validasi transisi status: memastikan status awal yang dipilih valid
     * 4. Menyimpan data dalam transaksi database untuk konsistensi
     * 5. Mencatat aktivitas pembuatan di log
     *
     * @param  StoreOrderStatusRequest  $request  Request dengan validasi bawaan
     * @param  StatusTransitionService  $transitions  Service untuk validasi transisi status
     * @param  ActivityLogger  $activityLogger  Service untuk pencatatan log aktivitas
     * @return RedirectResponse  Redirect kembali ke halaman sebelumnya dengan pesan sukses
     */
    public function store(
        StoreOrderStatusRequest $request,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        // Otorisasi: hanya Admin Inputer dan Super Admin yang bisa membuat data
        Gate::authorize('order_status.create');

        $validated = $request->validated();
        // Validasi transisi status: null = record baru, memastikan status awal diperbolehkan
        // Contoh: non-Super Admin tidak bisa langsung membuat order dengan status akhir (Complete/Failed)
        $transitions->assertOrderStatusTransition(null, $validated['status'], $request->user());

        // Gunakan transaksi database untuk memastikan pembuatan record dan log aktivitas
        // berhasil secara atomik (keduanya berhasil atau keduanya gagal)
        DB::transaction(function () use ($request, $validated, $activityLogger): void {
            $record = OrderStatus::create([
                ...$validated,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            // Catat aktivitas: old_values = null karena ini record baru
            $activityLogger->log($request, 'order_status', 'create', $record, null, $record->getAttributes());
        });

        return back()->with('success', 'Order Status berhasil ditambahkan.');
    }

    /**
     * Memperbarui data Order Status yang sudah ada.
     *
     * Alur proses:
     * 1. Otorisasi: memastikan pengguna berhak mengubah data
     * 2. Otorisasi kepemilikan: memastikan pengguna adalah pemilik data atau Super Admin
     * 3. Validasi input melalui UpdateOrderStatusRequest
     * 4. Optimistic locking: memastikan data belum diubah oleh pengguna lain
     * 5. Validasi transisi status: memastikan perubahan status mengikuti alur yang valid
     * 6. Memperbarui data dalam transaksi database
     * 7. Mencatat perubahan di log aktivitas (old_values dan new_values)
     *
     * @param  UpdateOrderStatusRequest  $request  Request dengan validasi bawaan
     * @param  OrderStatus  $orderStatus  Instance model yang akan diperbarui (route model binding)
     * @param  StatusTransitionService  $transitions  Service untuk validasi transisi status
     * @param  ActivityLogger  $activityLogger  Service untuk pencatatan log aktivitas
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function update(
        UpdateOrderStatusRequest $request,
        OrderStatus $orderStatus,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('order_status.update');
        // Verifikasi bahwa pengguna yang login adalah pemilik data atau Super Admin
        $this->authorizeOwnership($request, $orderStatus);

        $validated = $request->validated();
        // Optimistic locking: bandingkan updated_at dari frontend dengan database
        // Jika berbeda, berarti data sudah dimodifikasi oleh pengguna lain
        $this->assertFresh($orderStatus, $validated['updated_at']);
        // Validasi bahwa perubahan status dari status saat ini ke status baru diperbolehkan
        $transitions->assertOrderStatusTransition($orderStatus, $validated['status'], $request->user());

        // Hapus updated_at dari data yang akan disimpan karena sudah digunakan untuk locking
        unset($validated['updated_at']);

        DB::transaction(function () use ($request, $orderStatus, $validated, $activityLogger): void {
            // Simpan nilai lama sebelum update untuk log aktivitas
            $oldValues = $orderStatus->getOriginal();
            $orderStatus->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            // Catat perubahan dengan menyertakan nilai lama dan baru
            $activityLogger->log($request, 'order_status', 'update', $orderStatus, $oldValues, $orderStatus->fresh()->getAttributes());
        });

        return back()->with('success', 'Order Status berhasil diperbarui.');
    }

    /**
     * Menghapus Order Status dari database.
     *
     * Alur proses:
     * 1. Otorisasi: memastikan pengguna berhak menghapus data
     * 2. Otorisasi kepemilikan: hanya pemilik data atau Super Admin
     * 3. Optimistic locking: memastikan data tidak berubah sebelum dihapus
     * 4. Menandai siapa yang terakhir mengubah (updated_by) sebelum menghapus
     * 5. Melakukan soft/hard delete dalam transaksi
     * 6. Mencatat penghapusan di log aktivitas
     *
     * @param  Request  $request  HTTP request
     * @param  OrderStatus  $orderStatus  Instance model yang akan dihapus
     * @param  ActivityLogger  $activityLogger  Service untuk pencatatan log aktivitas
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function destroy(Request $request, OrderStatus $orderStatus, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('order_status.delete');
        $this->authorizeOwnership($request, $orderStatus);

        // Validasi updated_at untuk optimistic locking sebelum penghapusan
        $validated = $request->validate([
            'updated_at' => ['nullable', 'date'],
        ]);

        $this->assertFresh($orderStatus, $validated['updated_at'] ?? null);

        DB::transaction(function () use ($request, $orderStatus, $activityLogger): void {
            $oldValues = $orderStatus->getOriginal();
            // Catat siapa yang terakhir mengubah sebelum menghapus
            $orderStatus->update(['updated_by' => $request->user()->id]);
            $orderStatus->delete();

            // Catat penghapusan: new_values = null karena data dihapus
            $activityLogger->log($request, 'order_status', 'delete', $orderStatus, $oldValues, null);
        });

        return back()->with('success', 'Order Status berhasil dihapus.');
    }

    /**
     * Menerapkan semua filter ke query builder Order Status.
     *
     * Filter yang didukung:
     * - search: Pencarian berdasarkan nomor order atau nama pelanggan (LIKE)
     * - inputer_id: Filter berdasarkan inputer (hanya berlaku untuk Super Admin)
     * - account_manager_id: Filter berdasarkan AM (tidak berlaku untuk AM sendiri, karena sudah difilter visibleTo)
     * - status: Filter berdasarkan status order
     * - period_month: Filter berdasarkan periode bulan
     *
     * @param  Builder  $query  Query builder yang akan difilter
     * @param  array<string, mixed>  $filters  Parameter filter dari request
     * @param  User  $user  Pengguna yang sedang login
     * @return Builder  Query builder yang sudah difilter
     */
    private function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        return $query
            // Pencarian teks: cocokkan nomor order ATAU nama pelanggan
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            // Filter inputer hanya berlaku jika pengguna adalah Super Admin
            // Non-Super Admin sudah dibatasi datanya oleh scope visibleTo()
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            // Filter AM tidak berlaku untuk Account Manager (data sudah difilter oleh visibleTo)
            ->when(! $user->isAccountManager() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId))
            // Filter status dan periode bulan berlaku untuk semua peran
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth));
    }

    /**
     * Menghitung statistik jumlah record per status untuk ringkasan di halaman daftar.
     *
     * Menghasilkan array yang berisi jumlah record untuk setiap status yang
     * didefinisikan di model OrderStatus::LABELS. Data ini ditampilkan sebagai
     * badge/chip di atas tabel untuk memberikan gambaran cepat distribusi status.
     *
     * @param  Builder  $query  Query builder yang sudah difilter (tanpa filter status)
     * @return array<int, array{key: string, label: string, value: int, tone: string}>
     */
    private function statusStats(Builder $query): array
    {
        // Menghitung jumlah per status menggunakan GROUP BY
        $counts = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Memetakan setiap status ke format statistik dengan tone warna
        return collect(OrderStatus::LABELS)
            ->map(fn (string $label, string $status) => [
                'key' => $status,
                'label' => $label,
                'value' => (int) ($counts[$status] ?? 0),
                'tone' => $this->statusTone($status),
            ])
            ->values()
            ->all();
    }

    /**
     * Menghasilkan daftar opsi status untuk dropdown filter di frontend.
     *
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    private function statusOptions(): array
    {
        return collect(OrderStatus::LABELS)
            ->map(fn (string $label, string $status) => [
                'value' => $status,
                'label' => $label,
                'tone' => $this->statusTone($status),
            ])
            ->values()
            ->all();
    }

    /**
     * Mengambil daftar pengguna aktif berdasarkan peran untuk opsi dropdown filter.
     *
     * @param  string  $role  Peran pengguna (admin_inputer atau account_manager)
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
     * Mengubah model OrderStatus menjadi array untuk dikirim ke frontend.
     *
     * Serialisasi ini memastikan hanya data yang diperlukan frontend yang dikirim,
     * termasuk data relasi (nama inputer dan account manager), label status yang
     * mudah dibaca, tone warna untuk badge status, dan token updated_at untuk
     * mekanisme optimistic locking.
     *
     * @param  OrderStatus  $orderStatus  Instance model yang akan diserialisasi
     * @return array<string, mixed>  Data yang siap dikirim ke komponen Vue
     */
    private function serializeOrderStatus(OrderStatus $orderStatus): array
    {
        return [
            'id' => $orderStatus->id,
            'order_number' => $orderStatus->order_number,
            'customer_name' => $orderStatus->customer_name,
            'service_name' => $orderStatus->service_name,
            'inputer_id' => $orderStatus->inputer_id,
            // Menggunakan null-safe operator (?->) untuk menghindari error jika relasi null
            'inputer_name' => $orderStatus->inputer?->name,
            'account_manager_id' => $orderStatus->account_manager_id,
            'account_manager_name' => $orderStatus->accountManager?->name,
            'status' => $orderStatus->status,
            // Label status yang mudah dibaca (contoh: 'provisioning' -> 'Provisioning')
            'status_label' => OrderStatus::LABELS[$orderStatus->status] ?? $orderStatus->status,
            // Tone warna untuk styling badge status di frontend
            'status_tone' => $this->statusTone($orderStatus->status),
            'provisioning_stage' => $orderStatus->provisioning_stage,
            'period_month' => $orderStatus->period_month,
            'source_system' => $orderStatus->source_system,
            'notes' => $orderStatus->notes,
            'updated_at' => $orderStatus->updated_at?->format('Y-m-d H:i'),
            // Token untuk optimistic locking - dikirim kembali saat update/delete
            'updated_at_token' => $this->updatedAtToken($orderStatus),
        ];
    }

    /**
     * Menentukan tone/tema warna berdasarkan status Order Status.
     *
     * @param  string  $status  Nilai status order
     * @return string  Nama tone warna (info, warning, success, danger, neutral)
     */
    private function statusTone(string $status): string
    {
        return match ($status) {
            OrderStatus::STATUS_PROVISIONING => 'info',
            OrderStatus::STATUS_PENDING_BASO, OrderStatus::STATUS_PENDING_BILLING_APPROVAL => 'warning',
            OrderStatus::STATUS_COMPLETE => 'success',
            OrderStatus::STATUS_FAILED => 'danger',
            default => 'neutral',
        };
    }

    /**
     * Memverifikasi bahwa pengguna berhak mengubah/menghapus Order Status tertentu.
     *
     * Aturan bisnis:
     * - Super Admin dapat mengubah/menghapus semua data
     * - Admin Inputer hanya dapat mengubah/menghapus data yang mereka buat (inputer_id cocok)
     * - Peran lain akan mendapat error 403 Forbidden
     *
     * @param  Request  $request  HTTP request dengan info pengguna
     * @param  OrderStatus  $orderStatus  Record yang akan diubah/dihapus
     */
    private function authorizeOwnership(Request $request, OrderStatus $orderStatus): void
    {
        abort_unless($request->user()->isSuperAdmin() || $orderStatus->inputer_id === $request->user()->id, 403);
    }
}
