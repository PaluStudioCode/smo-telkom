<?php

/**
 * ==========================================================================
 * Controller Order EDK - Sistem SMO Telkom
 * ==========================================================================
 *
 * Controller ini mengelola operasi CRUD untuk modul Order EDK (Existing Data
 * Kecamatan/Kelurahan) dalam sistem SMO Telkom. Order EDK merepresentasikan
 * data populasi pelanggan eksisting yang perlu diproses untuk layanan Telkom.
 *
 * Fitur utama:
 * - Daftar order EDK dengan filter, pencarian, sorting, dan paginasi
 * - Pembuatan, pembaruan, dan penghapusan order EDK
 * - Statistik per status termasuk achievement (persentase penyelesaian) dan sisa populasi
 * - Validasi transisi status untuk memastikan alur proses yang benar
 *
 * Alur status Order EDK:
 * Belum Input → Lanjut → OGP → Complete
 *                  ↓        ↓
 *              Tidak Lanjut  Tidak Lanjut
 *
 * Aturan bisnis:
 * - Achievement = (Complete / Total EDK) × 100%
 * - Sisa Populasi = Total EDK - Complete - Tidak Lanjut
 * - Hanya Admin Inputer yang bisa CRUD, Super Admin bisa semua
 * - Setiap perubahan dicatat di log aktivitas
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AssertsFreshModel;
use App\Http\Requests\Operational\StoreOrderEdkRequest;
use App\Http\Requests\Operational\UpdateOrderEdkRequest;
use App\Models\OrderEdk;
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

class OrderEdkController extends Controller
{
    // Trait untuk mekanisme optimistic locking - mencegah konflik update bersamaan
    use AssertsFreshModel;

    /**
     * Menampilkan daftar Order EDK dengan filter, sorting, dan paginasi.
     *
     * Alur proses:
     * 1. Otorisasi akses melalui Gate
     * 2. Validasi parameter filter dari query string
     * 3. Membangun query utama dan query ringkasan (tanpa filter status)
     * 4. Menjalankan paginasi dan serialisasi data
     * 5. Mengembalikan response Inertia dengan data, statistik, dan opsi filter
     *
     * @param  Request  $request  HTTP request dari pengguna
     * @return Response  Response Inertia untuk halaman daftar Order EDK
     */
    public function index(Request $request): Response
    {
        // Periksa izin melihat data order EDK
        Gate::authorize('order_edk.view');

        // Validasi parameter filter dan sorting dari query string
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'inputer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', Rule::in(OrderEdk::statuses())],
            'period_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'sort' => ['nullable', Rule::in(['edk_reference', 'customer_name', 'status', 'period_month', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $user = $request->user();
        // Default sorting: data terbaru di atas
        $sort = $filters['sort'] ?? 'updated_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);

        // Query utama: data dengan relasi untuk ditampilkan di tabel
        $query = $this->applyFilters(
            OrderEdk::query()
                ->with(['inputer:id,name', 'accountManager:id,name'])
                ->visibleTo($user),
            $filters,
            $user,
        );

        // Query ringkasan: tanpa filter status, digunakan untuk menghitung statistik per status
        // sehingga pengguna tetap bisa melihat distribusi semua status meskipun memfilter satu status
        $summaryQuery = $this->applyFilters(
            OrderEdk::query()->visibleTo($user),
            Arr::except($filters, ['status']),
            $user,
        );

        // Eksekusi paginasi dan transformasi setiap record ke format frontend
        $records = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (OrderEdk $orderEdk) => $this->serializeOrderEdk($orderEdk));

        return Inertia::render('OrderEdks/Index', [
            'orderEdks' => $records,
            'stats' => $this->statusStats($summaryQuery),
            // Filter aktif dikembalikan untuk menjaga state form filter di frontend
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
            'statusOptions' => $this->statusOptions(),
            'inputerOptions' => $this->userOptions(User::ROLE_ADMIN_INPUTER),
            'accountManagerOptions' => $this->userOptions(User::ROLE_ACCOUNT_MANAGER),
        ]);
    }

    /**
     * Menyimpan Order EDK baru ke database.
     *
     * Alur: otorisasi → validasi input → validasi transisi status →
     * simpan dalam transaksi → catat log aktivitas.
     *
     * @param  StoreOrderEdkRequest  $request  Request dengan validasi bawaan
     * @param  StatusTransitionService  $transitions  Service validasi transisi status
     * @param  ActivityLogger  $activityLogger  Service pencatatan log
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function store(
        StoreOrderEdkRequest $request,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('order_edk.create');

        $validated = $request->validated();
        // Validasi status awal: non-Super Admin tidak bisa langsung membuat data dengan status akhir
        $transitions->assertOrderEdkTransition(null, $validated['status'], $request->user());

        // Transaksi atomik: pembuatan record + pencatatan log
        DB::transaction(function () use ($request, $validated, $activityLogger): void {
            $record = OrderEdk::create([
                ...$validated,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $activityLogger->log($request, 'order_edk', 'create', $record, null, $record->getAttributes());
        });

        return back()->with('success', 'Order EDK berhasil ditambahkan.');
    }

    /**
     * Memperbarui data Order EDK yang sudah ada.
     *
     * Alur: otorisasi → verifikasi kepemilikan → validasi input →
     * optimistic locking → validasi transisi status → update dalam transaksi →
     * catat log aktivitas.
     *
     * @param  UpdateOrderEdkRequest  $request  Request dengan validasi bawaan
     * @param  OrderEdk  $orderEdk  Instance model dari route model binding
     * @param  StatusTransitionService  $transitions  Service validasi transisi status
     * @param  ActivityLogger  $activityLogger  Service pencatatan log
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function update(
        UpdateOrderEdkRequest $request,
        OrderEdk $orderEdk,
        StatusTransitionService $transitions,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        Gate::authorize('order_edk.update');
        // Pastikan pengguna adalah pemilik data atau Super Admin
        $this->authorizeOwnership($request, $orderEdk);

        $validated = $request->validated();
        // Cek apakah data sudah dimodifikasi oleh pengguna lain (optimistic locking)
        $this->assertFresh($orderEdk, $validated['updated_at']);
        // Validasi bahwa perubahan status sesuai alur yang diizinkan
        $transitions->assertOrderEdkTransition($orderEdk, $validated['status'], $request->user());

        // Hapus updated_at dari data yang akan disimpan (sudah digunakan untuk locking)
        unset($validated['updated_at']);

        DB::transaction(function () use ($request, $orderEdk, $validated, $activityLogger): void {
            $oldValues = $orderEdk->getOriginal();
            $orderEdk->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);

            // Log perubahan dengan nilai lama dan baru untuk audit trail
            $activityLogger->log($request, 'order_edk', 'update', $orderEdk, $oldValues, $orderEdk->fresh()->getAttributes());
        });

        return back()->with('success', 'Order EDK berhasil diperbarui.');
    }

    /**
     * Menghapus Order EDK dari database.
     *
     * Alur: otorisasi → verifikasi kepemilikan → optimistic locking →
     * hapus dalam transaksi → catat log aktivitas.
     *
     * @param  Request  $request  HTTP request
     * @param  OrderEdk  $orderEdk  Instance model yang akan dihapus
     * @param  ActivityLogger  $activityLogger  Service pencatatan log
     * @return RedirectResponse  Redirect kembali dengan pesan sukses
     */
    public function destroy(Request $request, OrderEdk $orderEdk, ActivityLogger $activityLogger): RedirectResponse
    {
        Gate::authorize('order_edk.delete');
        $this->authorizeOwnership($request, $orderEdk);

        $validated = $request->validate([
            'updated_at' => ['nullable', 'date'],
        ]);

        $this->assertFresh($orderEdk, $validated['updated_at'] ?? null);

        DB::transaction(function () use ($request, $orderEdk, $activityLogger): void {
            $oldValues = $orderEdk->getOriginal();
            $orderEdk->update(['updated_by' => $request->user()->id]);
            $orderEdk->delete();

            $activityLogger->log($request, 'order_edk', 'delete', $orderEdk, $oldValues, null);
        });

        return back()->with('success', 'Order EDK berhasil dihapus.');
    }

    /**
     * Menerapkan filter ke query builder Order EDK.
     *
     * Filter yang didukung:
     * - search: Pencarian berdasarkan referensi EDK atau nama pelanggan
     * - inputer_id: Filter inputer (khusus Super Admin)
     * - account_manager_id: Filter AM (tidak berlaku untuk AM sendiri)
     * - status: Filter berdasarkan status EDK
     * - period_month: Filter berdasarkan periode bulan
     *
     * @param  Builder  $query  Query builder yang akan difilter
     * @param  array<string, mixed>  $filters  Parameter filter
     * @param  User  $user  Pengguna yang sedang login
     * @return Builder  Query builder yang sudah difilter
     */
    private function applyFilters(Builder $query, array $filters, User $user): Builder
    {
        return $query
            // Pencarian berdasarkan referensi EDK atau nama pelanggan
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('edk_reference', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            // Filter inputer hanya untuk Super Admin
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            // Filter AM tidak berlaku jika pengguna adalah AM (sudah difilter oleh visibleTo)
            ->when(! $user->isAccountManager() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth));
    }

    /**
     * Menghitung statistik status Order EDK termasuk achievement dan sisa populasi.
     *
     * Statistik tambahan di luar jumlah per status:
     * - Achievement: persentase EDK yang sudah complete dari total EDK
     *   Rumus: (Complete / Total) × 100, dibulatkan 1 desimal
     * - Sisa Populasi: jumlah EDK yang masih perlu diproses
     *   Rumus: Total - Complete - Tidak Lanjut (minimum 0)
     *
     * @param  Builder  $query  Query builder yang sudah difilter (tanpa filter status)
     * @return array<int, array{key: string, label: string, value: int|string, tone: string}>
     */
    private function statusStats(Builder $query): array
    {
        // Hitung jumlah per status menggunakan GROUP BY
        $counts = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Kalkulasi metrik turunan
        $total = (int) $counts->sum();
        $complete = (int) ($counts[OrderEdk::STATUS_COMPLETE] ?? 0);
        $tidakLanjut = (int) ($counts[OrderEdk::STATUS_TIDAK_LANJUT] ?? 0);
        // Achievement: persentase penyelesaian, 0% jika total = 0 untuk menghindari division by zero
        $achievement = $total > 0 ? round(($complete / $total) * 100, 1) : 0;
        // Sisa populasi: yang masih perlu diproses (exclude complete dan tidak lanjut)
        $remaining = max($total - $complete - $tidakLanjut, 0);

        return collect(OrderEdk::LABELS)
            ->map(fn (string $label, string $status) => [
                'key' => $status,
                'label' => $label,
                'value' => (int) ($counts[$status] ?? 0),
                'tone' => $this->statusTone($status),
            ])
            ->values()
            // Tambahkan statistik achievement di akhir array
            ->push([
                'key' => 'achievement',
                'label' => 'Achievement',
                'value' => number_format($achievement, 1).'%',
                'tone' => 'success',
            ])
            // Tambahkan statistik sisa populasi
            ->push([
                'key' => 'sisa_populasi',
                'label' => 'Sisa Populasi',
                'value' => $remaining,
                'tone' => 'primary',
            ])
            ->all();
    }

    /**
     * Menghasilkan opsi status untuk dropdown filter di frontend.
     *
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    private function statusOptions(): array
    {
        return collect(OrderEdk::LABELS)
            ->map(fn (string $label, string $status) => [
                'value' => $status,
                'label' => $label,
                'tone' => $this->statusTone($status),
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
     * Mengubah model OrderEdk menjadi array untuk dikirim ke frontend.
     *
     * Menyertakan data relasi, label status, tone warna, dan token
     * updated_at untuk optimistic locking.
     *
     * @param  OrderEdk  $orderEdk  Instance model yang akan diserialisasi
     * @return array<string, mixed>  Data yang siap dikirim ke komponen Vue
     */
    private function serializeOrderEdk(OrderEdk $orderEdk): array
    {
        return [
            'id' => $orderEdk->id,
            'edk_reference' => $orderEdk->edk_reference,
            'customer_name' => $orderEdk->customer_name,
            'inputer_id' => $orderEdk->inputer_id,
            'inputer_name' => $orderEdk->inputer?->name,
            'account_manager_id' => $orderEdk->account_manager_id,
            'account_manager_name' => $orderEdk->accountManager?->name,
            'status' => $orderEdk->status,
            'status_label' => OrderEdk::LABELS[$orderEdk->status] ?? $orderEdk->status,
            'status_tone' => $this->statusTone($orderEdk->status),
            'period_month' => $orderEdk->period_month,
            'source_system' => $orderEdk->source_system,
            'notes' => $orderEdk->notes,
            'updated_at' => $orderEdk->updated_at?->format('Y-m-d H:i'),
            // Token untuk optimistic locking
            'updated_at_token' => $this->updatedAtToken($orderEdk),
        ];
    }

    /**
     * Menentukan tone/tema warna berdasarkan status Order EDK.
     *
     * @param  string  $status  Nilai status EDK
     * @return string  Nama tone warna
     */
    private function statusTone(string $status): string
    {
        return match ($status) {
            OrderEdk::STATUS_LANJUT, OrderEdk::STATUS_OGP => 'info',
            OrderEdk::STATUS_BELUM_INPUT => 'warning',
            OrderEdk::STATUS_COMPLETE => 'success',
            default => 'neutral',
        };
    }

    /**
     * Memverifikasi kepemilikan data: hanya pemilik atau Super Admin yang bisa mengubah/menghapus.
     *
     * @param  Request  $request  HTTP request dengan info pengguna
     * @param  OrderEdk  $orderEdk  Record yang akan divalidasi kepemilikannya
     */
    private function authorizeOwnership(Request $request, OrderEdk $orderEdk): void
    {
        abort_unless($request->user()->isSuperAdmin() || $orderEdk->inputer_id === $request->user()->id, 403);
    }
}
