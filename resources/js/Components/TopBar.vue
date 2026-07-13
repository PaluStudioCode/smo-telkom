<!--
  Komponen: TopBar.vue
  Deskripsi: Komponen bar navigasi atas (header) yang menampilkan tombol toggle sidebar,
             judul halaman, dan menu pengguna. Bar ini bersifat sticky (tetap di atas saat scroll).
             Menyediakan dua tombol terpisah: satu untuk toggle sidebar di desktop (collapse/expand),
             dan satu untuk membuka drawer navigasi di perangkat mobile.
  Digunakan oleh: AppLayout.vue sebagai header utama aplikasi.
-->
<script setup>
// Import ikon-ikon untuk tombol toggle sidebar
import { Menu, PanelLeftClose, PanelLeftOpen } from 'lucide-vue-next';
// Import komponen menu pengguna (dropdown profil dan logout)
import UserMenu from '@/Components/UserMenu.vue';

// Definisi props yang diterima dari komponen parent
defineProps({
    // collapsed: Status sidebar saat ini (terlipat atau terbuka)
    // Digunakan untuk menentukan ikon yang ditampilkan pada tombol toggle
    collapsed: {
        type: Boolean,
        default: false,
    },
});

// Definisi event yang di-emit ke komponen parent
// 'toggle-sidebar': di-emit saat tombol toggle sidebar desktop diklik
// 'open-mobile': di-emit saat tombol menu mobile diklik untuk membuka drawer
const emit = defineEmits(['toggle-sidebar', 'open-mobile']);
</script>

<template>
    <!-- Header sticky yang tetap di atas saat halaman di-scroll -->
    <!-- Menggunakan backdrop-blur untuk efek transparansi elegan -->
    <header class="sticky top-0 z-30 border-b border-border bg-background/95 backdrop-blur">
        <div class="flex h-16 items-center justify-between gap-3 px-4 lg:px-6">
            <!-- ==================== BAGIAN KIRI: TOMBOL NAVIGASI & JUDUL ==================== -->
            <div class="flex items-center gap-2">
                <!-- Tombol buka navigasi mobile - hanya tampil di layar kecil (< lg) -->
                <!-- Menampilkan ikon hamburger menu -->
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-panel border border-border bg-surface text-content-secondary shadow-soft hover:bg-telkom-grey-soft lg:hidden"
                    aria-label="Buka navigasi"
                    @click="emit('open-mobile')"
                >
                    <Menu class="h-5 w-5" />
                </button>
                <!-- Tombol toggle sidebar desktop - hanya tampil di layar besar (lg+) -->
                <!-- Ikon berubah sesuai status: PanelLeftOpen saat terlipat, PanelLeftClose saat terbuka -->
                <button
                    type="button"
                    class="hidden h-9 w-9 items-center justify-center rounded-panel border border-border bg-surface text-content-secondary shadow-soft hover:bg-telkom-grey-soft lg:inline-flex"
                    :aria-label="collapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'"
                    @click="emit('toggle-sidebar')"
                >
                    <PanelLeftOpen v-if="collapsed" class="h-5 w-5" />
                    <PanelLeftClose v-else class="h-5 w-5" />
                </button>
                <!-- Slot untuk judul halaman - diisi oleh AppLayout -->
                <slot name="title" />
            </div>

            <!-- ==================== BAGIAN KANAN: MENU PENGGUNA ==================== -->
            <!-- Komponen dropdown menu pengguna (profil, logout) -->
            <UserMenu />
        </div>
    </header>
</template>
