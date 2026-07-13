<!--
  Komponen: PageHeader.vue
  Deskripsi: Komponen header halaman yang menampilkan judul, deskripsi opsional, dan area aksi.
             Digunakan sebagai header standar di setiap halaman utama aplikasi untuk memberikan
             konteks tentang halaman yang sedang ditampilkan dan menyediakan tombol aksi terkait.
  Digunakan oleh: Halaman-halaman utama seperti Order Status, Order EDK, Completion Records, Users, dll.
-->
<script setup>
// Definisi props yang diterima dari komponen parent
defineProps({
    // title: Judul halaman utama yang ditampilkan dalam ukuran besar
    title: {
        type: String,
        required: true,
    },
    // description: Teks deskripsi tambahan di bawah judul untuk memberikan konteks lebih lanjut
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <!-- Container header dengan layout responsif: kolom di mobile, baris di desktop -->
    <!-- Memastikan judul dan tombol aksi ditampilkan bersebelahan di layar besar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <!-- ==================== BAGIAN KIRI: JUDUL & DESKRIPSI ==================== -->
        <div>
            <!-- Judul halaman utama -->
            <h1 class="text-2xl font-semibold text-telkom-black">
                {{ title }}
            </h1>
            <!-- Deskripsi opsional - hanya ditampilkan jika prop description diisi -->
            <p v-if="description" class="mt-1 max-w-3xl text-sm text-content-secondary">
                {{ description }}
            </p>
        </div>

        <!-- ==================== BAGIAN KANAN: TOMBOL AKSI ==================== -->
        <!-- Hanya ditampilkan jika slot 'actions' disediakan oleh parent -->
        <!-- Contoh isi: tombol "Tambah Data", "Export", "Import", dll -->
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
