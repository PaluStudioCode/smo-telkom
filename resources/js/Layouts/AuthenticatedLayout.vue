<!--
  Komponen: AuthenticatedLayout.vue
  Deskripsi: Layout wrapper untuk halaman-halaman yang memerlukan autentikasi.
             Komponen ini berfungsi sebagai lapisan abstraksi tipis (thin wrapper) di atas AppLayout,
             memudahkan penggunaan layout terautentikasi di halaman-halaman Inertia tanpa perlu
             mengimpor AppLayout secara langsung.
  Digunakan oleh: Halaman-halaman terautentikasi seperti Dashboard, Order Status, User Management, dll.
-->
<script setup>
// Import komponen layout utama aplikasi
import AppLayout from '@/Layouts/AppLayout.vue';

// Definisi props yang diterima dari halaman Inertia
defineProps({
    // title: Judul halaman yang akan diteruskan ke AppLayout untuk ditampilkan di tab browser dan top bar
    title: {
        type: String,
        default: 'Dashboard',
    },
});
</script>

<template>
    <!-- Menggunakan AppLayout sebagai layout dasar dan meneruskan prop title -->
    <AppLayout :title="title">
        <!-- Meneruskan slot header ke AppLayout jika ada konten header yang disediakan -->
        <!-- Menggunakan v-if untuk memastikan slot header hanya dirender jika memang ada kontennya -->
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <!-- Meneruskan slot default (konten utama halaman) ke AppLayout -->
        <slot />
    </AppLayout>
</template>
