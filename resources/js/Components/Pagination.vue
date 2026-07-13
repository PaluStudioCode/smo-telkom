<!--
  Komponen: Pagination.vue
  Deskripsi: Komponen paginasi yang menampilkan navigasi halaman dan pemilihan jumlah baris per halaman.
             Menggunakan metadata paginasi dari Laravel (links, per_page, current_page, dll)
             dan Inertia.js untuk navigasi tanpa reload halaman penuh.
             Mendukung opsi baris per halaman: 10, 15, 25, dan 50.
  Digunakan oleh: DataTable.vue di bagian bawah tabel untuk navigasi antar halaman data.
-->
<script setup>
// Import Link dari Inertia untuk navigasi paginasi tanpa reload
// Import router untuk melakukan request programatik saat mengubah jumlah baris per halaman
import { Link, router } from '@inertiajs/vue3';

// Definisi props yang diterima dari komponen parent
const props = defineProps({
    // meta: Object metadata paginasi dari Laravel
    // Berisi: current_page, last_page, per_page, total, links (array tombol navigasi)
    meta: {
        type: Object,
        required: true,
    },
    // filters: Object filter aktif saat ini yang akan dipertahankan saat berpindah halaman
    // atau mengubah jumlah baris per halaman
    filters: {
        type: Object,
        default: () => ({}),
    },
    // routeName: Nama route Inertia yang digunakan untuk membuat URL paginasi
    routeName: {
        type: String,
        required: true,
    },
});

// Fungsi untuk mengubah jumlah baris yang ditampilkan per halaman
// Melakukan request GET ke route yang sama dengan parameter per_page yang baru
// Mengatur page ke 1 karena jumlah halaman berubah saat per_page berubah
const changePerPage = (event) => {
    router.get(route(props.routeName), {
        ...props.filters,                    // Pertahankan semua filter yang aktif
        per_page: Number(event.target.value), // Set jumlah baris per halaman yang baru
        page: 1,                              // Reset ke halaman pertama
    }, {
        preserveState: true,  // Pertahankan state komponen (tidak reset form, dll)
        replace: true,        // Ganti history entry (bukan push) agar tombol back tidak mengulangi perubahan
    });
};
</script>

<template>
    <!-- Container paginasi dengan border atas, layout responsif (kolom di mobile, baris di desktop) -->
    <div class="flex flex-col gap-3 border-t border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <!-- ==================== PEMILIHAN BARIS PER HALAMAN ==================== -->
        <!-- Dropdown untuk memilih jumlah data yang ditampilkan per halaman -->
        <div class="flex items-center gap-2 text-sm text-content-secondary">
            <span>Baris</span>
            <select
                class="h-9 rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                :value="filters.per_page ?? meta.per_page"
                @change="changePerPage"
            >
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
            <span>per halaman</span>
        </div>

        <!-- ==================== TOMBOL NAVIGASI HALAMAN ==================== -->
        <!-- Menampilkan link paginasi dari metadata Laravel (Previous, 1, 2, 3, ..., Next) -->
        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in meta.links"
                :key="link.label"
                :href="link.url || '#'"
                preserve-scroll
                preserve-state
                class="inline-flex h-9 min-w-9 items-center justify-center rounded-panel border px-3 text-sm transition"
                :class="[
                    link.active
                        ? 'border-telkom-red bg-telkom-red text-white'          // Style halaman aktif: merah Telkom
                        : 'border-border bg-surface text-content-secondary hover:bg-telkom-grey-soft', // Style halaman tidak aktif
                    !link.url ? 'pointer-events-none opacity-40' : '',           // Nonaktifkan link jika URL kosong (disabled state)
                ]"
                v-html="link.label"
            />
        </div>
    </div>
</template>
