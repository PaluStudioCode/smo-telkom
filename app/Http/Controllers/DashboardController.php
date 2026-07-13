<?php

/**
 * ==========================================================================
 * Controller Dashboard - Sistem SMO Telkom
 * ==========================================================================
 *
 * Controller ini bertanggung jawab untuk menampilkan halaman dashboard utama
 * sistem SMO (Service Management & Operation) Telkom. Dashboard menyajikan
 * ringkasan data operasional berupa:
 *
 * - Kartu metrik (total order, pending BASO, complete, failed, sisa populasi)
 * - Grafik komposisi status (Order Status + Order EDK)
 * - Bar chart rekap per Inputer dan per Account Manager (khusus Super Admin)
 * - Bar chart rekap modul terkait (untuk peran non-Super Admin)
 *
 * Dashboard mendukung filter berdasarkan periode bulan, inputer, dan
 * account manager. Aksesibilitas data dibatasi sesuai peran pengguna
 * melalui scope `visibleTo()` pada tiap model.
 *
 * Controller ini menggunakan pola Single Action Controller (__invoke),
 * karena hanya menangani satu endpoint yaitu halaman dashboard.
 */

namespace App\Http\Controllers;

use App\Models\CompletionRecord;
use App\Models\OrderEdk;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Menangani request halaman dashboard.
     *
     * Alur proses:
     * 1. Otorisasi akses dashboard melalui Gate
     * 2. Validasi filter dari query string (periode, inputer, account manager)
     * 3. Membangun query untuk setiap modul operasional (Order Status, Order EDK, Completion Record)
     * 4. Menghitung metrik ringkasan dan menyusun data chart
     * 5. Jika Super Admin, menghasilkan rekap per Inputer dan per Account Manager
     * 6. Mengembalikan response Inertia dengan semua data yang diperlukan frontend
     *
     * @param  Request  $request  HTTP request dari pengguna
     * @return Response  Response Inertia untuk halaman Dashboard
     */
    public function __invoke(Request $request): Response
    {
        // Pastikan pengguna memiliki izin untuk melihat dashboard
        Gate::authorize('dashboard.view_related');

        // Validasi parameter filter dari query string
        // - period_month: format YYYY-MM (contoh: 2026-07)
        // - inputer_id: ID pengguna inputer (harus ada di tabel users)
        // - account_manager_id: ID account manager (harus ada di tabel users)
        $filters = $request->validate([
            'period_month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'inputer_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $user = $request->user();

        // Membangun query dasar untuk setiap modul operasional dengan menerapkan filter
        // Setiap query sudah menerapkan scope visibleTo() untuk membatasi data sesuai peran pengguna
        $orderStatuses = $this->orderStatusQuery($filters, $user);
        $orderEdks = $this->orderEdkQuery($filters, $user);
        $completionRecords = $this->completionRecordQuery($filters, $user);

        // Menghitung metrik ringkasan dari data Order Status dan Order EDK
        $metrics = $this->metrics($orderStatuses, $orderEdks);

        // Rekap per pengguna hanya tersedia untuk Super Admin
        // Non-Super Admin mendapatkan array kosong untuk efisiensi
        $recaps = $user->isSuperAdmin()
            ? [
                'inputers' => $this->userRecap(User::ROLE_ADMIN_INPUTER, 'inputer_id', $filters, $user),
                'accountManagers' => $this->userRecap(User::ROLE_ACCOUNT_MANAGER, 'account_manager_id', $filters, $user),
            ]
            : [
                'inputers' => [],
                'accountManagers' => [],
            ];

        // Mengembalikan response Inertia dengan semua data yang dibutuhkan komponen Vue Dashboard
        return Inertia::render('Dashboard', [
            'cards' => $this->cards($metrics, $filters['period_month'] ?? null),
            'charts' => [
                // Grafik donut/pie komposisi status dari kedua modul operasional
                'statusComposition' => $this->statusComposition($orderStatuses, $orderEdks),
                // Bar chart untuk rekap berdasarkan pengguna atau modul
                'barCharts' => $this->barCharts($user, $recaps, $metrics, (clone $completionRecords)->count()),
            ],
            'recaps' => $recaps,
            // Mengembalikan filter aktif ke frontend untuk menjaga state form filter
            'filters' => [
                'period_month' => $filters['period_month'] ?? '',
                // Filter inputer dan account manager hanya berlaku untuk Super Admin
                'inputer_id' => $user->isSuperAdmin() ? ($filters['inputer_id'] ?? '') : '',
                'account_manager_id' => $user->isSuperAdmin() ? ($filters['account_manager_id'] ?? '') : '',
            ],
            // Opsi dropdown untuk filter inputer dan account manager
            'inputerOptions' => $this->userOptions(User::ROLE_ADMIN_INPUTER),
            'accountManagerOptions' => $this->userOptions(User::ROLE_ACCOUNT_MANAGER),
            // Flag untuk menentukan tampilan UI yang berbeda antara Super Admin dan peran lain
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    /**
     * Membangun query Order Status dengan filter yang diterapkan.
     *
     * Query menggunakan scope `visibleTo()` untuk memastikan hanya data yang
     * sesuai dengan cakupan akses pengguna yang ditampilkan. Filter inputer
     * dan account manager hanya berlaku jika pengguna adalah Super Admin.
     *
     * @param  array<string, mixed>  $filters  Parameter filter dari request
     * @param  User  $user  Pengguna yang sedang login
     * @return Builder  Query builder yang sudah difilter
     */
    private function orderStatusQuery(array $filters, User $user): Builder
    {
        return OrderStatus::query()
            ->visibleTo($user)
            // Filter berdasarkan periode bulan jika tersedia
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth))
            // Filter inputer hanya berlaku untuk Super Admin (non-Super Admin sudah difilter oleh visibleTo)
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            // Filter account manager hanya berlaku untuk Super Admin
            ->when($user->isSuperAdmin() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId));
    }

    /**
     * Membangun query Order EDK dengan filter yang diterapkan.
     *
     * Logika filter identik dengan orderStatusQuery() tetapi beroperasi
     * pada model OrderEdk.
     *
     * @param  array<string, mixed>  $filters  Parameter filter dari request
     * @param  User  $user  Pengguna yang sedang login
     * @return Builder  Query builder yang sudah difilter
     */
    private function orderEdkQuery(array $filters, User $user): Builder
    {
        return OrderEdk::query()
            ->visibleTo($user)
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth))
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            ->when($user->isSuperAdmin() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId));
    }

    /**
     * Membangun query Completion Record dengan filter yang diterapkan.
     *
     * Logika filter identik dengan orderStatusQuery() tetapi beroperasi
     * pada model CompletionRecord.
     *
     * @param  array<string, mixed>  $filters  Parameter filter dari request
     * @param  User  $user  Pengguna yang sedang login
     * @return Builder  Query builder yang sudah difilter
     */
    private function completionRecordQuery(array $filters, User $user): Builder
    {
        return CompletionRecord::query()
            ->visibleTo($user)
            ->when($filters['period_month'] ?? null, fn (Builder $query, string $periodMonth) => $query->where('period_month', $periodMonth))
            ->when($user->isSuperAdmin() ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->where('inputer_id', $inputerId))
            ->when($user->isSuperAdmin() ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->where('account_manager_id', $accountManagerId));
    }

    /**
     * Menghitung metrik ringkasan dari data Order Status dan Order EDK.
     *
     * Metrik yang dihitung:
     * - total_order: Jumlah total Order Status
     * - pending_baso: Jumlah order yang menunggu BASO (Berita Acara Serah-terima Operasi)
     * - complete: Gabungan jumlah order complete dari Order Status + EDK complete
     * - failed: Jumlah order yang gagal
     * - sisa_populasi: Total EDK dikurangi EDK complete dan EDK tidak lanjut (minimum 0)
     * - total_edk: Jumlah total Order EDK
     *
     * Query di-clone agar query builder asli tidak termodifikasi dan bisa digunakan ulang.
     *
     * @param  Builder  $orderStatuses  Query builder Order Status yang sudah difilter
     * @param  Builder  $orderEdks  Query builder Order EDK yang sudah difilter
     * @return array<string, int>  Array asosiatif berisi metrik-metrik ringkasan
     */
    private function metrics(Builder $orderStatuses, Builder $orderEdks): array
    {
        // Menghitung metrik EDK terlebih dahulu karena digunakan dalam kalkulasi sisa_populasi
        $totalEdk = (clone $orderEdks)->count();
        $edkComplete = (clone $orderEdks)->where('status', OrderEdk::STATUS_COMPLETE)->count();
        $edkTidakLanjut = (clone $orderEdks)->where('status', OrderEdk::STATUS_TIDAK_LANJUT)->count();

        return [
            'total_order' => (clone $orderStatuses)->count(),
            'pending_baso' => (clone $orderStatuses)->where('status', OrderStatus::STATUS_PENDING_BASO)->count(),
            // Complete menggabungkan data dari Order Status dan Order EDK
            'complete' => (clone $orderStatuses)->where('status', OrderStatus::STATUS_COMPLETE)->count() + $edkComplete,
            'failed' => (clone $orderStatuses)->where('status', OrderStatus::STATUS_FAILED)->count(),
            // Sisa populasi = total EDK - yang sudah complete - yang tidak dilanjutkan
            // Menggunakan max() untuk mencegah nilai negatif
            'sisa_populasi' => max($totalEdk - $edkComplete - $edkTidakLanjut, 0),
            'total_edk' => $totalEdk,
        ];
    }

    /**
     * Menyusun data kartu metrik untuk ditampilkan di dashboard.
     *
     * Setiap kartu memiliki properti:
     * - key: Identifier unik untuk frontend
     * - label: Teks yang ditampilkan
     * - value: Nilai numerik
     * - context: Keterangan konteks (periode yang dipilih atau "Semua periode")
     * - tone: Warna/tema visual (primary, warning, success, danger, info)
     *
     * @param  array<string, int>  $metrics  Metrik ringkasan dari method metrics()
     * @param  string|null  $periodMonth  Periode bulan yang dipilih (format YYYY-MM) atau null
     * @return array<int, array{key: string, label: string, value: int, context: string, tone: string}>
     */
    private function cards(array $metrics, ?string $periodMonth): array
    {
        // Menentukan label konteks berdasarkan apakah filter periode aktif atau tidak
        $context = $periodMonth ? 'Periode '.$periodMonth : 'Semua periode';

        return [
            ['key' => 'total_order', 'label' => 'Total Order', 'value' => $metrics['total_order'], 'context' => $context, 'tone' => 'primary'],
            ['key' => 'pending_baso', 'label' => 'Pending BASO', 'value' => $metrics['pending_baso'], 'context' => $context, 'tone' => 'warning'],
            // Kartu Complete menggabungkan data dari dua sumber, sehingga konteksnya berbeda
            ['key' => 'complete', 'label' => 'Complete', 'value' => $metrics['complete'], 'context' => 'Order Status + EDK', 'tone' => 'success'],
            ['key' => 'failed', 'label' => 'Failed', 'value' => $metrics['failed'], 'context' => $context, 'tone' => 'danger'],
            // Sisa Populasi menampilkan berapa EDK yang masih perlu diproses
            ['key' => 'sisa_populasi', 'label' => 'Sisa Populasi', 'value' => $metrics['sisa_populasi'], 'context' => 'Total EDK - Complete - Tidak Lanjut', 'tone' => 'info'],
        ];
    }

    /**
     * Menyusun data komposisi status untuk grafik donut/pie.
     *
     * Menggabungkan jumlah per status dari Order Status dan Order EDK
     * menjadi satu dataset untuk ditampilkan dalam satu grafik.
     * Setiap item diberi prefix "OS" (Order Status) atau "EDK" (Order EDK)
     * agar mudah dibedakan di tampilan.
     *
     * @param  Builder  $orderStatuses  Query builder Order Status yang sudah difilter
     * @param  Builder  $orderEdks  Query builder Order EDK yang sudah difilter
     * @return array<int, array{key: string, label: string, value: int, tone: string, color: string}>
     */
    private function statusComposition(Builder $orderStatuses, Builder $orderEdks): array
    {
        // Menghitung jumlah per status untuk Order Status menggunakan GROUP BY
        $orderStatusCounts = (clone $orderStatuses)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Menghitung jumlah per status untuk Order EDK menggunakan GROUP BY
        $orderEdkCounts = (clone $orderEdks)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Memetakan setiap status Order Status ke format item chart
        $orderStatusItems = collect(OrderStatus::LABELS)
            ->map(fn (string $label, string $status) => $this->chartItem(
                'order_status_'.$status,
                'OS '.$label,
                (int) ($orderStatusCounts[$status] ?? 0),
                $this->orderStatusTone($status),
            ));

        // Memetakan setiap status Order EDK ke format item chart
        $orderEdkItems = collect(OrderEdk::LABELS)
            ->map(fn (string $label, string $status) => $this->chartItem(
                'order_edk_'.$status,
                'EDK '.$label,
                (int) ($orderEdkCounts[$status] ?? 0),
                $this->orderEdkTone($status),
            ));

        // Menggabungkan kedua collection menjadi satu array untuk chart
        return $orderStatusItems
            ->merge($orderEdkItems)
            ->values()
            ->all();
    }

    /**
     * Menyusun data bar chart berdasarkan peran pengguna.
     *
     * Untuk Super Admin: Menampilkan 2 bar chart terpisah
     * - Rekap per Inputer: menunjukkan produktivitas masing-masing inputer
     * - Rekap per Account Manager: menunjukkan beban kerja masing-masing AM
     *
     * Untuk peran lain (Inputer/AM): Menampilkan 1 bar chart
     * - Rekap Modul Terkait: membandingkan jumlah data antar modul yang bisa diakses
     *
     * @param  User  $user  Pengguna yang sedang login
     * @param  array{inputers: array, accountManagers: array}  $recaps  Data rekap per pengguna
     * @param  array<string, int>  $metrics  Metrik ringkasan
     * @param  int  $completionTotal  Total record completion yang terfilter
     * @return array  Data bar chart yang siap ditampilkan
     */
    private function barCharts(User $user, array $recaps, array $metrics, int $completionTotal): array
    {
        if ($user->isSuperAdmin()) {
            return [
                [
                    'key' => 'inputers',
                    'title' => 'Rekap Berdasarkan Inputer',
                    'description' => 'Total data operasional yang dikelola tiap Inputer.',
                    'items' => $this->barItems($recaps['inputers']),
                ],
                [
                    'key' => 'account_managers',
                    'title' => 'Rekap Berdasarkan Account Manager',
                    'description' => 'Total data operasional yang terkait tiap Account Manager.',
                    'items' => $this->barItems($recaps['accountManagers']),
                ],
            ];
        }

        // Untuk non-Super Admin, tampilkan perbandingan antar modul yang bisa diakses
        return [
            [
                'key' => 'related_modules',
                'title' => 'Rekap Modul Terkait',
                'description' => 'Jumlah data yang sesuai cakupan akses pengguna.',
                'items' => [
                    ['label' => 'Order Status', 'value' => $metrics['total_order'], 'color' => '#E42313'],
                    ['label' => 'Order EDK', 'value' => $metrics['total_edk'], 'color' => '#2563EB'],
                    ['label' => 'Modul Complete', 'value' => $completionTotal, 'color' => '#16A34A'],
                ],
            ],
        ];
    }

    /**
     * Mengkonversi data rekap pengguna menjadi format item bar chart.
     *
     * Hanya mengambil 8 pengguna teratas untuk menjaga keterbacaan chart.
     * Warna bar bergantian antara merah Telkom (#E42313) dan biru (#2563EB)
     * berdasarkan index genap/ganjil.
     *
     * @param  array<int, array<string, mixed>>  $recaps  Data rekap dari userRecap()
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function barItems(array $recaps): array
    {
        return collect($recaps)
            ->take(8) // Batasi 8 item untuk keterbacaan visual
            ->map(fn (array $recap, int $index) => [
                'label' => $recap['name'],
                'value' => $recap['total'],
                // Warna bergantian: merah Telkom untuk index genap, biru untuk ganjil
                'color' => $index % 2 === 0 ? '#E42313' : '#2563EB',
            ])
            ->values()
            ->all();
    }

    /**
     * Menghasilkan rekap data operasional per pengguna berdasarkan peran.
     *
     * Method ini melakukan penghitungan komprehensif untuk setiap pengguna
     * dengan peran tertentu, mencakup:
     * - Jumlah Order Status, Order EDK, dan Modul Complete yang dikelola
     * - Breakdown per status: pending BASO, complete, failed
     * - Sisa populasi EDK (total - complete - tidak lanjut)
     * - Total keseluruhan (order_status + order_edk + modul_complete)
     *
     * Penghitungan dilakukan secara efisien menggunakan GROUP BY pada sisi database,
     * bukan iterasi per pengguna, sehingga mengurangi jumlah query secara signifikan.
     *
     * Hasil diurutkan berdasarkan total terbanyak (descending).
     *
     * @param  string  $role  Peran pengguna yang akan direkap (admin_inputer atau account_manager)
     * @param  string  $groupField  Nama kolom untuk GROUP BY (inputer_id atau account_manager_id)
     * @param  array<string, mixed>  $filters  Parameter filter aktif
     * @param  User  $user  Pengguna yang sedang login
     * @return array<int, array<string, mixed>>  Array rekap per pengguna, diurutkan berdasarkan total
     */
    private function userRecap(string $role, string $groupField, array $filters, User $user): array
    {
        // Membangun query dasar untuk setiap modul
        $orderStatuses = $this->orderStatusQuery($filters, $user);
        $orderEdks = $this->orderEdkQuery($filters, $user);
        $completionRecords = $this->completionRecordQuery($filters, $user);

        // Menghitung total per pengguna menggunakan GROUP BY untuk efisiensi
        // Setiap countBy() menghasilkan Collection dengan format [user_id => count]
        $orderStatusCounts = $this->countBy($orderStatuses, $groupField);
        $orderEdkCounts = $this->countBy($orderEdks, $groupField);
        $completionCounts = $this->countBy($completionRecords, $groupField);

        // Menghitung breakdown per status untuk detail rekap
        $pendingBasoCounts = $this->countBy((clone $orderStatuses)->where('status', OrderStatus::STATUS_PENDING_BASO), $groupField);
        $failedCounts = $this->countBy((clone $orderStatuses)->where('status', OrderStatus::STATUS_FAILED), $groupField);
        $orderStatusCompleteCounts = $this->countBy((clone $orderStatuses)->where('status', OrderStatus::STATUS_COMPLETE), $groupField);
        $orderEdkCompleteCounts = $this->countBy((clone $orderEdks)->where('status', OrderEdk::STATUS_COMPLETE), $groupField);
        $orderEdkTidakLanjutCounts = $this->countBy((clone $orderEdks)->where('status', OrderEdk::STATUS_TIDAK_LANJUT), $groupField);

        // Mengambil daftar pengguna aktif dengan peran yang sesuai dan memetakan data rekap
        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            // Jika filter inputer/AM aktif, batasi hanya pengguna tersebut
            ->when($role === User::ROLE_ADMIN_INPUTER ? ($filters['inputer_id'] ?? null) : null, fn (Builder $query, int|string $inputerId) => $query->whereKey($inputerId))
            ->when($role === User::ROLE_ACCOUNT_MANAGER ? ($filters['account_manager_id'] ?? null) : null, fn (Builder $query, int|string $accountManagerId) => $query->whereKey($accountManagerId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $recapUser) use (
                $orderStatusCounts,
                $orderEdkCounts,
                $completionCounts,
                $pendingBasoCounts,
                $failedCounts,
                $orderStatusCompleteCounts,
                $orderEdkCompleteCounts,
                $orderEdkTidakLanjutCounts,
            ): array {
                // Memetakan jumlah per pengguna dari hasil GROUP BY
                $orderStatus = (int) ($orderStatusCounts[$recapUser->id] ?? 0);
                $orderEdk = (int) ($orderEdkCounts[$recapUser->id] ?? 0);
                // Complete menggabungkan dari Order Status dan Order EDK
                $complete = (int) ($orderStatusCompleteCounts[$recapUser->id] ?? 0) + (int) ($orderEdkCompleteCounts[$recapUser->id] ?? 0);
                // Sisa populasi = total EDK - EDK complete - EDK tidak lanjut (minimum 0)
                $sisaPopulasi = max($orderEdk - (int) ($orderEdkCompleteCounts[$recapUser->id] ?? 0) - (int) ($orderEdkTidakLanjutCounts[$recapUser->id] ?? 0), 0);
                $modulComplete = (int) ($completionCounts[$recapUser->id] ?? 0);

                return [
                    'id' => $recapUser->id,
                    'name' => $recapUser->name,
                    'order_status' => $orderStatus,
                    'order_edk' => $orderEdk,
                    'modul_complete' => $modulComplete,
                    'pending_baso' => (int) ($pendingBasoCounts[$recapUser->id] ?? 0),
                    'complete' => $complete,
                    'failed' => (int) ($failedCounts[$recapUser->id] ?? 0),
                    'sisa_populasi' => $sisaPopulasi,
                    // Total = gabungan semua modul untuk menentukan peringkat
                    'total' => $orderStatus + $orderEdk + $modulComplete,
                ];
            })
            // Urutkan berdasarkan total terbanyak untuk menampilkan pengguna paling produktif di atas
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * Menghitung jumlah record per nilai kolom tertentu menggunakan GROUP BY.
     *
     * Method utilitas ini melakukan query COUNT(*) ... GROUP BY untuk
     * menghasilkan Collection dengan format [nilai_kolom => jumlah].
     * Digunakan untuk menghitung jumlah record per pengguna secara efisien
     * dalam satu query, bukan melakukan query terpisah per pengguna.
     *
     * @param  Builder  $query  Query builder yang sudah difilter
     * @param  string  $groupField  Nama kolom untuk GROUP BY
     * @return Collection<int|string, int>  Collection dengan key = nilai kolom, value = jumlah
     */
    private function countBy(Builder $query, string $groupField): Collection
    {
        return (clone $query)
            ->select($groupField, DB::raw('count(*) as total'))
            ->groupBy($groupField)
            ->pluck('total', $groupField);
    }

    /**
     * Mengambil daftar pengguna aktif berdasarkan peran untuk opsi dropdown filter.
     *
     * @param  string  $role  Peran pengguna yang akan diambil
     * @return array<int, array{id: int, name: string}>  Array opsi pengguna untuk dropdown
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
     * Membuat satu item data chart dengan format standar.
     *
     * @param  string  $key  Identifier unik untuk item chart
     * @param  string  $label  Label yang ditampilkan di chart
     * @param  int  $value  Nilai numerik yang ditampilkan
     * @param  string  $tone  Tone/tema warna (info, warning, success, danger, neutral)
     * @return array{key: string, label: string, value: int, tone: string, color: string}
     */
    private function chartItem(string $key, string $label, int $value, string $tone): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'tone' => $tone,
            'color' => $this->toneColor($tone),
        ];
    }

    /**
     * Menentukan tone/tema warna berdasarkan status Order Status.
     *
     * Pemetaan tone mengikuti konvensi semantik:
     * - info (biru): status sedang diproses (Provisioning)
     * - warning (kuning): status menunggu aksi (Pending BASO, Pending Billing)
     * - success (hijau): status selesai berhasil (Complete)
     * - danger (merah): status gagal (Failed)
     * - neutral (abu-abu): status lainnya (Cancel/Abandoned)
     *
     * @param  string  $status  Nilai status Order Status
     * @return string  Nama tone warna
     */
    private function orderStatusTone(string $status): string
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
     * Menentukan tone/tema warna berdasarkan status Order EDK.
     *
     * Pemetaan tone:
     * - info (biru): status sedang diproses (Lanjut, OGP)
     * - warning (kuning): status belum diproses (Belum Input)
     * - success (hijau): status selesai (Complete)
     * - neutral (abu-abu): status lainnya (Tidak Lanjut)
     *
     * @param  string  $status  Nilai status Order EDK
     * @return string  Nama tone warna
     */
    private function orderEdkTone(string $status): string
    {
        return match ($status) {
            OrderEdk::STATUS_LANJUT, OrderEdk::STATUS_OGP => 'info',
            OrderEdk::STATUS_BELUM_INPUT => 'warning',
            OrderEdk::STATUS_COMPLETE => 'success',
            default => 'neutral',
        };
    }

    /**
     * Mengkonversi nama tone menjadi kode warna hex.
     *
     * Warna disesuaikan dengan identitas visual Telkom:
     * - primary (#E42313): Merah Telkom, warna utama
     * - success (#16A34A): Hijau, menandakan keberhasilan
     * - warning (#D97706): Kuning/oranye, menandakan peringatan
     * - danger (#DC2626): Merah, menandakan kegagalan
     * - info (#2563EB): Biru, menandakan informasi
     * - default (#64748B): Abu-abu, untuk status netral
     *
     * @param  string  $tone  Nama tone warna
     * @return string  Kode warna hex
     */
    private function toneColor(string $tone): string
    {
        return match ($tone) {
            'primary' => '#E42313',
            'success' => '#16A34A',
            'warning' => '#D97706',
            'danger' => '#DC2626',
            'info' => '#2563EB',
            default => '#64748B',
        };
    }
}
