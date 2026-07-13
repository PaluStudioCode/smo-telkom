<!--
    Dashboard.vue
    =============
    Halaman utama dashboard untuk Sistem Monitoring Operasional (SMO) Telkom.
    Menampilkan ringkasan data operasional berupa:
    - Kartu statistik (total order, pending BASO, complete, failed, sisa populasi)
    - Grafik donut komposisi status operasional
    - Grafik bar (bar chart) untuk rekap per kategori
    - Tabel rekap inputer dan account manager (khusus super admin)

    Data diambil dari backend melalui props Inertia dan diperbarui secara reaktif
    ketika filter (periode, inputer, account manager) diubah oleh pengguna.
-->

<script setup>
// ============================================================================
// IMPORT KOMPONEN DAN DEPENDENSI
// ============================================================================
// Layout utama aplikasi yang membungkus semua halaman dengan sidebar, navbar, dll.
import AppLayout from '@/Layouts/AppLayout.vue';
// Komponen bar filter yang menyediakan area untuk menempatkan input-input filter
import FilterBar from '@/Components/FilterBar.vue';
// Komponen header halaman yang menampilkan judul dan deskripsi halaman
import PageHeader from '@/Components/PageHeader.vue';
// Komponen kartu statistik untuk menampilkan angka ringkasan
import StatCard from '@/Components/StatCard.vue';
// Komponen badge status untuk menampilkan label berwarna sesuai status
import StatusBadge from '@/Components/StatusBadge.vue';
// Head: mengatur tag <title> halaman, router: navigasi Inertia, usePage: akses data halaman
import { Head, router, usePage } from '@inertiajs/vue3';
// computed: properti reaktif turunan, ref: state reaktif, watch: pengamat perubahan state
import { computed, ref, watch } from 'vue';
// Ikon-ikon dari library lucide-vue-next untuk menghias kartu statistik dan grafik
import { BarChart3, CheckCircle, ClipboardList, Clock, Database, Donut, XCircle } from 'lucide-vue-next';

// ============================================================================
// PROPS - Data yang dikirim dari controller backend (DashboardController)
// ============================================================================
const props = defineProps({
    // Array kartu statistik, setiap item berisi key, label, value, context, dan tone
    cards: {
        type: Array,
        required: true,
    },
    // Objek berisi data grafik: statusComposition (donut) dan barCharts (bar)
    charts: {
        type: Object,
        required: true,
    },
    // Objek berisi data tabel rekap: inputers dan accountManagers (hanya untuk super admin)
    recaps: {
        type: Object,
        required: true,
    },
    // Objek filter aktif saat ini (period_month, inputer_id, account_manager_id)
    filters: {
        type: Object,
        required: true,
    },
    // Daftar opsi inputer untuk dropdown filter (id dan name)
    inputerOptions: {
        type: Array,
        required: true,
    },
    // Daftar opsi account manager untuk dropdown filter (id dan name)
    accountManagerOptions: {
        type: Array,
        required: true,
    },
    // Flag apakah user yang login adalah super admin, untuk menampilkan filter/tabel tambahan
    isSuperAdmin: {
        type: Boolean,
        required: true,
    },
});

// ============================================================================
// STATE REAKTIF DAN KONFIGURASI
// ============================================================================
// Mengakses data halaman Inertia (termasuk auth user, flash messages, dll.)
const page = usePage();
// Menyalin filter dari props ke state lokal agar bisa diubah tanpa langsung mengubah props
const filterForm = ref({ ...props.filters });
// Timer untuk debounce filter - mencegah request berulang saat user mengetik cepat
let filterTimer = null;
// Radius lingkaran SVG untuk grafik donut (dalam satuan viewBox)
const radius = 46;
// Keliling lingkaran donut, digunakan untuk menghitung stroke-dasharray tiap segmen
const circumference = 2 * Math.PI * radius;
// Pemetaan key kartu statistik ke komponen ikon yang sesuai
const cardIcons = {
    total_order: ClipboardList,   // Ikon daftar untuk total order
    pending_baso: Clock,          // Ikon jam untuk pending BASO
    complete: CheckCircle,        // Ikon centang untuk order selesai
    failed: XCircle,              // Ikon silang untuk order gagal
    sisa_populasi: Database,      // Ikon database untuk sisa populasi
};

// ============================================================================
// COMPUTED PROPERTIES - Properti turunan yang dihitung otomatis saat data berubah
// ============================================================================
// Memfilter item komposisi status yang memiliki nilai > 0 (hanya tampilkan yang punya data)
const statusItems = computed(() => props.charts.statusComposition.filter((item) => item.value > 0));

// Menghitung total dari semua nilai status untuk perhitungan persentase donut
const statusTotal = computed(() => statusItems.value.reduce((sum, item) => sum + Number(item.value), 0));

// Menghitung segmen-segmen donut chart berdasarkan proporsi masing-masing status
// Setiap segmen memiliki: dash (panjang garis), offset (posisi mulai garis)
const donutSegments = computed(() => {
    // Offset kumulatif untuk menentukan posisi awal setiap segmen
    let offset = 0;

    return statusItems.value.map((item) => {
        // Menghitung panjang dash berdasarkan proporsi nilai terhadap total
        const dash = (item.value / statusTotal.value) * circumference;
        const segment = {
            ...item,
            dash,    // Panjang garis stroke untuk segmen ini
            offset,  // Posisi mulai segmen pada lingkaran
        };

        // Menggeser offset untuk segmen berikutnya
        offset += dash;

        return segment;
    });
});

// ============================================================================
// WATCHER - Mengawasi perubahan filter dan menerapkan debounce
// ============================================================================
// Memantau setiap perubahan pada filterForm (deep: true untuk objek bersarang)
// Menggunakan debounce 350ms agar tidak mengirim request terlalu sering
watch(
    filterForm,
    () => {
        // Batalkan timer sebelumnya jika ada perubahan baru sebelum 350ms
        window.clearTimeout(filterTimer);
        // Set timer baru untuk menerapkan filter setelah jeda 350ms
        filterTimer = window.setTimeout(() => applyFilters(), 350);
    },
    { deep: true },
);

// ============================================================================
// FUNGSI-FUNGSI
// ============================================================================
// Menerapkan filter dengan melakukan request GET ke route dashboard
// preserveState: true menjaga state komponen Vue tetap sama
// replace: true mengganti history browser (bukan push baru)
const applyFilters = () => {
    router.get(route('dashboard'), filterForm.value, {
        preserveState: true,
        replace: true,
    });
};

// Menghitung lebar bar chart item relatif terhadap nilai maksimum
// Hasilnya berupa string persentase CSS (misal: "75%")
// Minimum 4% jika item punya nilai > 0 agar tetap terlihat
const barWidth = (item, items) => {
    // Mencari nilai maksimum dari semua item, minimal 1 untuk menghindari pembagian nol
    const maxValue = Math.max(...items.map((bar) => Number(bar.value)), 1);
    const percentage = (Number(item.value) / maxValue) * 100;

    // Jika item punya nilai > 0, minimal lebar 4% agar terlihat di UI
    return `${Math.max(percentage, item.value > 0 ? 4 : 0)}%`;
};

// Mengecek apakah ada data pada bar chart (minimal satu item bernilai > 0)
// Digunakan untuk menampilkan pesan "data belum tersedia" jika tidak ada data
const hasBarData = (items) => items.some((item) => Number(item.value) > 0);

// Menghitung total label dari semua item pada bar chart
const totalLabel = (items) => items.reduce((sum, item) => sum + Number(item.value), 0);
</script>

<template>
    <!-- Mengatur judul tab browser -->
    <Head title="Dashboard" />

    <!-- Layout utama aplikasi dengan judul "Dashboard" -->
    <AppLayout title="Dashboard">
        <!-- Slot header: menampilkan judul halaman dan deskripsi sesuai peran user -->
        <template #header>
            <PageHeader
                title="Dashboard"
                :description="`Ringkasan monitoring operasional untuk ${page.props.auth.user.role_label}.`"
            />
        </template>

        <!-- ============================================================ -->
        <!-- BAGIAN FILTER - Input filter periode, inputer, dan AM        -->
        <!-- ============================================================ -->
        <FilterBar>
            <!-- Filter periode bulan -->
            <div>
                <label for="period_month" class="text-sm font-medium text-content-secondary">Periode</label>
                <input
                    id="period_month"
                    v-model="filterForm.period_month"
                    type="month"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                />
            </div>
            <!-- Filter inputer - hanya ditampilkan untuk super admin -->
            <div v-if="isSuperAdmin">
                <label for="inputer_id" class="text-sm font-medium text-content-secondary">Inputer</label>
                <select
                    id="inputer_id"
                    v-model="filterForm.inputer_id"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                >
                    <option value="">Semua Inputer</option>
                    <option v-for="inputer in inputerOptions" :key="inputer.id" :value="inputer.id">
                        {{ inputer.name }}
                    </option>
                </select>
            </div>
            <!-- Filter account manager - hanya ditampilkan untuk super admin -->
            <div v-if="isSuperAdmin">
                <label for="account_manager_id" class="text-sm font-medium text-content-secondary">Account Manager</label>
                <select
                    id="account_manager_id"
                    v-model="filterForm.account_manager_id"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                >
                    <option value="">Semua AM</option>
                    <option v-for="accountManager in accountManagerOptions" :key="accountManager.id" :value="accountManager.id">
                        {{ accountManager.name }}
                    </option>
                </select>
            </div>
        </FilterBar>

        <!-- ============================================================ -->
        <!-- BAGIAN KARTU STATISTIK - Grid 5 kolom menampilkan ringkasan  -->
        <!-- ============================================================ -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Iterasi setiap kartu statistik dari data backend -->
            <StatCard
                v-for="card in cards"
                :key="card.key"
                :label="card.label"
                :value="card.value"
                :context="card.context"
                :tone="card.tone"
            >
                <!-- Slot ikon: menampilkan ikon dinamis berdasarkan key kartu -->
                <template #icon>
                    <component :is="cardIcons[card.key]" class="h-5 w-5" />
                </template>
            </StatCard>
        </div>

        <!-- ============================================================ -->
        <!-- BAGIAN GRAFIK - Donut chart dan bar charts                   -->
        <!-- ============================================================ -->
        <section class="grid gap-4 xl:grid-cols-2">
            <!-- GRAFIK DONUT - Komposisi status operasional -->
            <div class="h-fit rounded-panel border border-border bg-surface p-5 shadow-soft">
                <!-- Header grafik donut -->
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-telkom-black">Komposisi Status Operasional</h2>
                        <p class="mt-1 text-sm text-content-secondary">Order Status dan Order EDK pada cakupan filter aktif.</p>
                    </div>
                    <Donut class="h-5 w-5 text-content-muted" />
                </div>

                <!-- Konten donut chart - ditampilkan jika ada data (statusTotal > 0) -->
                <div v-if="statusTotal > 0" class="mt-6 flex flex-col gap-6">
                    <!-- SVG Donut Chart - lingkaran dengan segmen-segmen berwarna -->
                    <div class="relative mx-auto h-52 w-52">
                        <svg class="h-full w-full" viewBox="0 0 120 120" role="img" aria-label="Komposisi status operasional">
                            <!-- Lingkaran latar belakang (abu-abu) -->
                            <circle
                                cx="60"
                                cy="60"
                                :r="radius"
                                fill="none"
                                stroke="#F1F5F9"
                                stroke-width="16"
                            />
                            <!-- Segmen donut - setiap segmen merepresentasikan satu status -->
                            <!-- stroke-dasharray menentukan panjang garis dan celah -->
                            <!-- stroke-dashoffset menentukan titik awal garis -->
                            <!-- transform rotate(-90) agar dimulai dari posisi jam 12 -->
                            <circle
                                v-for="segment in donutSegments"
                                :key="segment.key"
                                cx="60"
                                cy="60"
                                :r="radius"
                                fill="none"
                                :stroke="segment.color"
                                stroke-width="16"
                                stroke-linecap="round"
                                :stroke-dasharray="`${segment.dash} ${circumference - segment.dash}`"
                                :stroke-dashoffset="-segment.offset"
                                transform="rotate(-90 60 60)"
                            >
                                <title>{{ segment.label }}: {{ segment.value }}</title>
                            </circle>
                        </svg>
                        <!-- Label total di tengah donut -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-semibold text-telkom-black">{{ statusTotal }}</span>
                            <span class="text-xs text-content-muted">Total status</span>
                        </div>
                    </div>

                    <!-- Legend donut - menampilkan detail setiap status dengan warna dan persentase -->
                    <div class="grid max-h-52 gap-2 overflow-y-auto sm:grid-cols-2">
                        <div
                            v-for="item in statusItems"
                            :key="item.key"
                            class="flex items-center justify-between gap-3 rounded-panel border border-border px-3 py-2"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <!-- Indikator warna (dot) sesuai warna segmen donut -->
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: item.color }" />
                                    <p class="truncate text-sm font-medium text-telkom-black">{{ item.label }}</p>
                                </div>
                                <StatusBadge class="mt-1" :variant="item.tone">{{ item.value }}</StatusBadge>
                            </div>
                            <!-- Persentase item terhadap total -->
                            <p class="text-sm text-content-secondary">
                                {{ Math.round((item.value / statusTotal) * 100) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pesan ketika tidak ada data grafik -->
                <div v-else class="mt-6 rounded-panel border border-dashed border-border px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-telkom-black">Data grafik belum tersedia</p>
                    <p class="mt-1 text-sm text-content-secondary">Komposisi akan muncul saat data operasional sesuai filter tersedia.</p>
                </div>
            </div>

            <!-- GRAFIK BAR - Rekap data per kategori (misal: per AM, per status) -->
            <div class="grid h-fit gap-4">
                <div
                    v-for="chart in charts.barCharts"
                    :key="chart.key"
                    class="rounded-panel border border-border bg-surface p-5 shadow-soft"
                >
                    <!-- Header bar chart -->
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-telkom-black">{{ chart.title }}</h2>
                            <p class="mt-1 text-sm text-content-secondary">{{ chart.description }}</p>
                        </div>
                        <BarChart3 class="h-5 w-5 text-content-muted" />
                    </div>

                    <!-- Konten bar chart - ditampilkan jika ada data -->
                    <div v-if="hasBarData(chart.items)" class="mt-5 max-h-64 space-y-3 overflow-y-auto pr-1">
                        <!-- Setiap item bar chart: label, nilai, dan bar horizontal -->
                        <div v-for="item in chart.items" :key="item.label" class="grid gap-2">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-telkom-black">{{ item.label }}</span>
                                <span class="text-content-secondary">{{ item.value }}</span>
                            </div>
                            <!-- Bar horizontal dengan lebar proporsional terhadap nilai maksimum -->
                            <div class="h-3 rounded-full bg-telkom-grey-soft">
                                <div
                                    class="h-3 rounded-full"
                                    :style="{ width: barWidth(item, chart.items), backgroundColor: item.color }"
                                />
                            </div>
                        </div>
                        <!-- Total semua item -->
                        <p class="text-xs text-content-muted">Total: {{ totalLabel(chart.items) }}</p>
                    </div>

                    <!-- Pesan ketika bar chart tidak ada data -->
                    <div v-else class="mt-5 rounded-panel border border-dashed border-border px-6 py-8 text-center">
                        <p class="text-sm font-semibold text-telkom-black">Data bar chart belum tersedia</p>
                        <p class="mt-1 text-sm text-content-secondary">Rekap akan tampil setelah data operasional tersedia.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- BAGIAN TABEL REKAP - Hanya ditampilkan untuk super admin     -->
        <!-- Menampilkan rekap per inputer dan per account manager         -->
        <!-- ============================================================ -->
        <section v-if="isSuperAdmin" class="grid gap-4 xl:grid-cols-2">
            <!-- TABEL REKAP INPUTER -->
            <div class="overflow-hidden rounded-panel border border-border bg-surface shadow-soft">
                <div class="border-b border-border px-4 py-3">
                    <h2 class="text-base font-semibold text-telkom-black">Tabel Rekap Inputer</h2>
                </div>
                <div class="max-h-80 overflow-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <!-- Header tabel dengan sticky agar tetap terlihat saat scroll -->
                        <thead class="sticky top-0 z-10 bg-telkom-red text-left text-xs font-semibold uppercase text-white">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3">Nama</th>
                                <th class="whitespace-nowrap px-4 py-3">Order</th>
                                <th class="whitespace-nowrap px-4 py-3">EDK</th>
                                <th class="whitespace-nowrap px-4 py-3">Complete</th>
                                <th class="whitespace-nowrap px-4 py-3">Pending BASO</th>
                                <th class="whitespace-nowrap px-4 py-3">Failed</th>
                                <th class="whitespace-nowrap px-4 py-3">Sisa</th>
                            </tr>
                        </thead>
                        <!-- Body tabel berisi data rekap per inputer -->
                        <tbody class="divide-y divide-border bg-surface">
                            <tr v-for="row in recaps.inputers" :key="row.id" class="hover:bg-telkom-grey-soft/70">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-telkom-black">{{ row.name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.order_status }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.order_edk }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.modul_complete }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.pending_baso }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.failed }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.sisa_populasi }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TABEL REKAP ACCOUNT MANAGER -->
            <div class="overflow-hidden rounded-panel border border-border bg-surface shadow-soft">
                <div class="border-b border-border px-4 py-3">
                    <h2 class="text-base font-semibold text-telkom-black">Tabel Rekap Account Manager</h2>
                </div>
                <div class="max-h-80 overflow-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="sticky top-0 z-10 bg-telkom-red text-left text-xs font-semibold uppercase text-white">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3">Nama</th>
                                <th class="whitespace-nowrap px-4 py-3">Order</th>
                                <th class="whitespace-nowrap px-4 py-3">EDK</th>
                                <th class="whitespace-nowrap px-4 py-3">Complete</th>
                                <th class="whitespace-nowrap px-4 py-3">Pending BASO</th>
                                <th class="whitespace-nowrap px-4 py-3">Failed</th>
                                <th class="whitespace-nowrap px-4 py-3">Sisa</th>
                            </tr>
                        </thead>
                        <!-- Body tabel berisi data rekap per account manager -->
                        <tbody class="divide-y divide-border bg-surface">
                            <tr v-for="row in recaps.accountManagers" :key="row.id" class="hover:bg-telkom-grey-soft/70">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-telkom-black">{{ row.name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.order_status }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.order_edk }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.modul_complete }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.pending_baso }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.failed }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.sisa_populasi }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
