<!--
  Komponen: DataTable.vue
  Deskripsi: Komponen tabel data yang dapat digunakan ulang (reusable) untuk menampilkan data dalam format tabel.
             Mendukung fitur sorting kolom, paginasi, slot kustom untuk setiap sel, dan tampilan kosong (empty state).
             Komponen ini dirancang agar fleksibel dan bisa digunakan di berbagai halaman dengan konfigurasi berbeda.
  Digunakan oleh: Halaman-halaman daftar data seperti Order Status, Order EDK, Completion Records, Manajemen Pengguna, dll.
-->
<script setup>
// Import ikon panah untuk indikator sorting pada header kolom
import { ArrowUpDown } from 'lucide-vue-next';
// Import komponen untuk tampilan saat data kosong
import EmptyState from '@/Components/EmptyState.vue';
// Import komponen paginasi untuk navigasi halaman data
import Pagination from '@/Components/Pagination.vue';

// Definisi props yang diterima dari komponen parent
defineProps({
    // columns: Array konfigurasi kolom tabel
    // Setiap item berisi: key (nama field), label (judul kolom), sortable (boolean), class, headerClass
    columns: {
        type: Array,
        required: true,
    },
    // rows: Array data baris yang akan ditampilkan di tabel
    // Setiap item harus memiliki properti 'id' sebagai key unik
    rows: {
        type: Array,
        required: true,
    },
    // meta: Object metadata paginasi dari Laravel (current_page, last_page, per_page, links, dll)
    // Bernilai null jika paginasi tidak digunakan
    meta: {
        type: Object,
        default: null,
    },
    // filters: Object berisi filter aktif yang akan diteruskan ke komponen Pagination
    // untuk mempertahankan state filter saat berpindah halaman
    filters: {
        type: Object,
        default: () => ({}),
    },
    // routeName: Nama route Inertia yang digunakan oleh Pagination untuk navigasi halaman
    routeName: {
        type: String,
        default: '',
    },
    // sort: Nama kolom yang sedang diurutkan saat ini
    sort: {
        type: String,
        default: '',
    },
    // direction: Arah pengurutan saat ini ('asc' untuk naik, 'desc' untuk turun)
    direction: {
        type: String,
        default: 'asc',
    },
    // emptyTitle: Judul yang ditampilkan saat tabel tidak memiliki data
    emptyTitle: {
        type: String,
        default: 'Data belum tersedia',
    },
    // emptyDescription: Deskripsi tambahan yang ditampilkan saat tabel kosong
    emptyDescription: {
        type: String,
        default: 'Tidak ada data yang sesuai dengan filter saat ini.',
    },
});

// Emit event 'sort' ke parent saat pengguna mengklik header kolom yang bisa diurutkan
// Parent bertanggung jawab untuk melakukan request data yang sudah diurutkan
const emit = defineEmits(['sort']);
</script>

<template>
    <!-- Container tabel dengan border, shadow, dan sudut membulat -->
    <div class="overflow-hidden rounded-panel border border-border bg-surface shadow-soft">
        <!-- Wrapper horizontal scroll untuk tabel responsif pada layar kecil -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-sm">
                <!-- ==================== HEADER TABEL ==================== -->
                <!-- Header dengan background merah Telkom dan teks putih -->
                <thead class="bg-telkom-red text-left text-xs font-semibold uppercase text-white">
                    <tr>
                        <!-- Iterasi setiap kolom untuk membuat header -->
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="whitespace-nowrap px-4 py-3"
                            :class="column.headerClass"
                        >
                            <!-- Jika kolom bisa diurutkan, tampilkan sebagai tombol dengan ikon sorting -->
                            <button
                                v-if="column.sortable"
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-telkom-red"
                                @click="emit('sort', column.key)"
                            >
                                {{ column.label }}
                                <ArrowUpDown class="h-3.5 w-3.5" />
                            </button>
                            <!-- Jika kolom tidak bisa diurutkan, tampilkan label sebagai teks biasa -->
                            <span v-else>{{ column.label }}</span>
                        </th>
                    </tr>
                </thead>
                <!-- ==================== BODY TABEL ==================== -->
                <!-- Body tabel hanya dirender jika ada data (rows.length > 0) -->
                <tbody v-if="rows.length" class="divide-y divide-border bg-surface">
                    <!-- Iterasi setiap baris data -->
                    <tr v-for="row in rows" :key="row.id" class="transition hover:bg-telkom-grey-soft/70">
                        <!-- Iterasi setiap kolom untuk menampilkan data sel -->
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="whitespace-nowrap px-4 py-3 text-content-secondary"
                            :class="column.class"
                        >
                            <!-- Slot dinamis untuk kustomisasi tampilan sel -->
                            <!-- Nama slot: 'cell-{namaKolom}', menerima prop 'row' untuk akses data baris -->
                            <!-- Jika slot tidak disediakan, tampilkan nilai default dari data baris -->
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] ?? '-' }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ==================== TAMPILAN DATA KOSONG ==================== -->
        <!-- Ditampilkan jika tidak ada data baris sama sekali -->
        <EmptyState
            v-if="!rows.length"
            :title="emptyTitle"
            :description="emptyDescription"
        >
            <!-- Slot opsional untuk aksi tambahan pada tampilan kosong (misalnya tombol "Tambah Data") -->
            <template v-if="$slots.emptyAction" #action>
                <slot name="emptyAction" />
            </template>
        </EmptyState>

        <!-- ==================== PAGINASI ==================== -->
        <!-- Komponen paginasi ditampilkan jika ada metadata paginasi dan nama route -->
        <Pagination
            v-if="meta && routeName"
            :meta="meta"
            :filters="filters"
            :route-name="routeName"
        />
    </div>
</template>
