<!--
  Komponen: AppLayout.vue
  Deskripsi: Layout utama aplikasi yang digunakan untuk halaman-halaman yang memerlukan autentikasi.
             Menyediakan struktur tata letak lengkap termasuk sidebar navigasi, top bar, dan area konten utama.
             Layout ini mendukung sidebar yang bisa dilipat (collapsed) di desktop dan drawer navigasi di perangkat mobile.
  Digunakan oleh: AuthenticatedLayout.vue sebagai wrapper utama untuk semua halaman terautentikasi.
-->
<script setup>
// Import komponen-komponen child yang diperlukan untuk menyusun layout
import SidebarNav from '@/Components/SidebarNav.vue'; // Komponen navigasi sidebar
import ToastNotifications from '@/Components/ToastNotifications.vue'; // Komponen notifikasi toast
import TopBar from '@/Components/TopBar.vue'; // Komponen bar atas (header)
import { Head } from '@inertiajs/vue3'; // Komponen Head dari Inertia untuk mengatur tag <title> halaman
import { computed, ref } from 'vue';

// Definisi props yang diterima dari komponen parent
const props = defineProps({
    // title: Judul halaman yang akan ditampilkan di tab browser dan di top bar
    title: {
        type: String,
        default: 'Dashboard',
    },
});

// State reaktif untuk mengontrol tampilan sidebar
// collapsed: menentukan apakah sidebar dalam mode terlipat (hanya ikon) di tampilan desktop
const collapsed = ref(false);
// mobileOpen: menentukan apakah drawer navigasi mobile sedang terbuka
const mobileOpen = ref(false);
// Computed property untuk judul halaman, bereaksi terhadap perubahan prop title
const pageTitle = computed(() => props.title);
</script>

<template>
    <!-- Mengatur judul halaman di tab browser menggunakan komponen Head dari Inertia -->
    <Head :title="pageTitle" />

    <!-- Container utama layout - mengisi minimal seluruh tinggi layar -->
    <div class="min-h-screen bg-background text-foreground">
        <!-- ==================== SIDEBAR DESKTOP ==================== -->
        <!-- Sidebar tetap (fixed) di sisi kiri layar, hanya tampil di layar besar (lg+) -->
        <!-- Lebar sidebar berubah sesuai state collapsed: 80px saat terlipat, 288px saat terbuka -->
        <aside
            class="fixed inset-y-0 left-0 z-40 hidden transition-all lg:block bg-telkom-red"
            :class="collapsed ? 'w-20' : 'w-72'"
        >
            <!-- Header sidebar berisi logo dan nama aplikasi -->
            <div class="flex h-16 items-center border-b border-white/20 px-4 overflow-hidden">
                <!-- Logo Telkom Indonesia - ditampilkan dalam mode putih (invert) -->
                <img 
                    src="/storage/telkom-indonesia-logo.svg" 
                    alt="Telkom Indonesia" 
                    class="h-7 shrink-0 object-contain filter brightness-0 invert" 
                    :class="collapsed ? 'w-10' : 'w-auto'"
                />
                <!-- Teks nama aplikasi - disembunyikan saat sidebar terlipat -->
                <div v-if="!collapsed" class="ml-3 min-w-0 border-l border-white/20 pl-3">
                    <p class="truncate text-sm font-semibold text-white">Sistem Monitoring</p>
                    <p class="truncate text-xs text-white/70">Government Service</p>
                </div>
            </div>
            <!-- Area navigasi sidebar yang bisa di-scroll secara vertikal -->
            <div class="h-[calc(100vh-4rem)] overflow-y-auto p-3 text-white">
                <!-- Komponen navigasi sidebar, menerima prop collapsed untuk menyesuaikan tampilan -->
                <SidebarNav :collapsed="collapsed" />
            </div>
        </aside>

        <!-- ==================== SIDEBAR MOBILE (DRAWER) ==================== -->
        <!-- Menggunakan Teleport untuk merender drawer di luar hierarki DOM komponen -->
        <!-- Ini memastikan overlay dan drawer ditampilkan di atas semua elemen lain -->
        <Teleport to="body">
            <!-- Drawer navigasi mobile - hanya tampil saat mobileOpen bernilai true -->
            <div v-if="mobileOpen" class="fixed inset-0 z-50 lg:hidden">
                <!-- Overlay gelap sebagai latar belakang - klik untuk menutup drawer -->
                <button
                    type="button"
                    class="absolute inset-0 bg-telkom-black/40"
                    aria-label="Tutup navigasi"
                    @click="mobileOpen = false"
                />
                <!-- Panel drawer navigasi mobile -->
                <aside class="relative h-full w-80 max-w-[85vw] bg-telkom-red shadow-panel">
                    <!-- Header drawer dengan logo dan nama aplikasi -->
                    <div class="flex h-16 items-center border-b border-white/20 px-4 overflow-hidden">
                        <img 
                            src="/storage/telkom-indonesia-logo.svg" 
                            alt="Telkom Indonesia" 
                            class="h-7 w-auto shrink-0 object-contain filter brightness-0 invert" 
                        />
                        <div class="ml-3 min-w-0 border-l border-white/20 pl-3">
                            <p class="truncate text-sm font-semibold text-white">Sistem Monitoring</p>
                            <p class="truncate text-xs text-white/70">Government Service</p>
                        </div>
                    </div>
                    <!-- Area navigasi mobile - menutup drawer saat pengguna melakukan navigasi -->
                    <div class="h-[calc(100vh-4rem)] overflow-y-auto p-3 text-white">
                        <SidebarNav @navigate="mobileOpen = false" />
                    </div>
                </aside>
            </div>
        </Teleport>

        <!-- ==================== AREA KONTEN UTAMA ==================== -->
        <!-- Container konten utama yang menyesuaikan padding kiri sesuai lebar sidebar -->
        <div class="transition-all" :class="collapsed ? 'lg:pl-20' : 'lg:pl-72'">
            <!-- Top bar dengan tombol toggle sidebar dan menu pengguna -->
            <TopBar
                :collapsed="collapsed"
                @toggle-sidebar="collapsed = !collapsed"
                @open-mobile="mobileOpen = true"
            >
                <!-- Slot title untuk menampilkan judul halaman di top bar -->
                <template #title>
                    <div class="hidden text-sm font-medium text-content-secondary sm:block">
                        {{ pageTitle }}
                    </div>
                </template>
            </TopBar>

            <!-- Area konten halaman utama dengan padding responsif -->
            <main class="p-4 sm:p-6">
                <div class="mx-auto flex max-w-7xl flex-col gap-6">
                    <!-- Slot header untuk komponen header halaman (opsional) -->
                    <slot name="header" />
                    <!-- Slot default untuk konten halaman utama -->
                    <slot />
                </div>
            </main>
        </div>

        <!-- Komponen notifikasi toast - ditampilkan di pojok kanan atas halaman -->
        <ToastNotifications />
    </div>
</template>
