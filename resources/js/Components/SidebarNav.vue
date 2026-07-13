<!--
  Komponen: SidebarNav.vue
  Deskripsi: Komponen navigasi sidebar yang menampilkan menu-menu utama aplikasi.
             Mendukung tampilan normal (dengan teks) dan terlipat (hanya ikon).
             Navigasi dibagi menjadi beberapa grup: Dashboard, Monitoring (Order Status, Order EDK, Modul Complete),
             dan Manajemen Pengguna (hanya ditampilkan jika user memiliki permission 'user.view').
             Menggunakan Inertia Link untuk navigasi SPA (Single Page Application).
  Digunakan oleh: AppLayout.vue untuk sidebar desktop dan drawer mobile.
-->
<script setup>
// Import komponen Link dari Inertia untuk navigasi tanpa reload halaman
// usePage untuk mengakses data shared dari server (termasuk data autentikasi)
import { Link, usePage } from '@inertiajs/vue3';
// Import ikon-ikon dari Lucide untuk setiap item navigasi
import {
    CheckCircle,      // Ikon untuk Modul Complete
    ClipboardList,     // Ikon untuk Order Status
    FileCheck,         // Ikon untuk Order EDK
    LayoutDashboard,   // Ikon untuk Dashboard
    Users,             // Ikon untuk Manajemen Pengguna
} from 'lucide-vue-next';
import { computed } from 'vue';

// Definisi props yang diterima dari komponen parent
defineProps({
    // collapsed: Menentukan apakah sidebar dalam mode terlipat
    // Saat terlipat, hanya ikon yang ditampilkan (teks disembunyikan)
    collapsed: {
        type: Boolean,
        default: false,
    },
});

// Event yang di-emit saat pengguna melakukan navigasi
// Digunakan oleh drawer mobile untuk menutup drawer setelah navigasi
const emit = defineEmits(['navigate']);
// Mengakses data halaman dari Inertia untuk mendapatkan permissions pengguna
const page = usePage();
// Computed property untuk mengambil daftar permissions pengguna yang sedang login
// Digunakan untuk mengontrol visibilitas menu berdasarkan hak akses
const permissions = computed(() => page.props.auth.permissions ?? {});

// Fungsi helper untuk menghasilkan kelas CSS pada item navigasi
// Menentukan tampilan item berdasarkan status aktif (halaman saat ini) dan indentasi
// active: boolean - apakah item sedang aktif (halaman saat ini cocok dengan route item)
// indented: boolean - apakah item perlu diindentasi (untuk sub-menu di bawah grup)
const itemClass = (active, indented = false) => [
    'flex h-10 items-center gap-3 rounded-panel px-3 text-sm font-medium transition-all border-l-4',
    indented ? 'pl-8' : '',
    active
        ? 'bg-white text-telkom-red border-white shadow-sm'          // Style aktif: latar putih, teks merah
        : 'text-white/80 border-transparent hover:bg-white/10 hover:text-white hover:border-white/50', // Style tidak aktif
];
</script>

<template>
    <!-- Container navigasi utama dengan layout kolom flex -->
    <nav class="flex h-full flex-col gap-1">
        <!-- ==================== MENU DASHBOARD ==================== -->
        <!-- Item navigasi Dashboard - selalu ditampilkan untuk semua pengguna -->
        <Link
            :href="route('dashboard')"
            :class="itemClass(route().current('dashboard'))"
            @click="emit('navigate')"
        >
            <LayoutDashboard class="h-5 w-5 shrink-0" />
            <!-- Teks disembunyikan saat sidebar dalam mode terlipat -->
            <span v-if="!collapsed">Dashboard</span>
        </Link>

        <!-- ==================== GRUP MONITORING ==================== -->
        <!-- Label grup navigasi - disembunyikan saat sidebar terlipat -->
        <div class="mt-5 px-3 text-xs font-semibold uppercase text-white/60">
            <span v-if="!collapsed">Monitoring</span>
        </div>

        <!-- Item navigasi Order Status - menampilkan daftar status order -->
        <!-- Menggunakan indentasi (parameter kedua = true) sebagai sub-menu monitoring -->
        <Link
            :href="route('order-statuses.index')"
            :class="itemClass(route().current('order-statuses.*'), true)"
            @click="emit('navigate')"
        >
            <ClipboardList class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Order Status</span>
        </Link>
        <!-- Item navigasi Order EDK - menampilkan daftar order EDK -->
        <Link
            :href="route('order-edks.index')"
            :class="itemClass(route().current('order-edks.*'), true)"
            @click="emit('navigate')"
        >
            <FileCheck class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Order EDK</span>
        </Link>
        <!-- Item navigasi Modul Complete - menampilkan catatan penyelesaian -->
        <Link
            :href="route('completion-records.index')"
            :class="itemClass(route().current('completion-records.*'), true)"
            @click="emit('navigate')"
        >
            <CheckCircle class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Modul Complete</span>
        </Link>

        <!-- ==================== MENU MANAJEMEN PENGGUNA ==================== -->
        <!-- Hanya ditampilkan jika pengguna memiliki permission 'user.view' -->
        <!-- Menggunakan v-if untuk kontrol akses berbasis permission -->
        <Link
            v-if="permissions['user.view']"
            :href="route('users.index')"
            :class="itemClass(route().current('users.*'))"
            @click="emit('navigate')"
        >
            <Users class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Manajemen Pengguna</span>
        </Link>
    </nav>
</template>
