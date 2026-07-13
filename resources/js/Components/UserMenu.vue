<!--
  Komponen: UserMenu.vue
  Deskripsi: Komponen dropdown menu pengguna yang menampilkan informasi profil pengguna yang sedang login,
             beserta tautan ke halaman profil dan tombol logout.
             Menampilkan avatar pengguna (foto profil atau inisial nama), nama, email, dan label role.
             Dropdown dibuka/ditutup dengan mengklik tombol trigger.
  Digunakan oleh: TopBar.vue di bagian kanan header aplikasi.
-->
<script setup>
// Import komponen dan utilitas dari Inertia
import { Link, usePage } from '@inertiajs/vue3';
// Import ikon-ikon dari Lucide
import { ChevronDown, LogOut, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// Mengakses data halaman Inertia untuk mendapatkan data pengguna yang login
const page = usePage();
// State reaktif untuk mengontrol visibilitas dropdown menu
const open = ref(false);
// Computed property untuk mengambil data pengguna dari props autentikasi Inertia
// Data ini dikirim dari server melalui middleware HandleInertiaRequests
const user = computed(() => page.props.auth.user);
</script>

<template>
    <!-- Container relatif untuk positioning dropdown -->
    <div class="relative">
        <!-- ==================== TOMBOL TRIGGER DROPDOWN ==================== -->
        <!-- Tombol yang menampilkan avatar dan nama pengguna -->
        <!-- Klik untuk membuka/menutup dropdown menu -->
        <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-panel border border-border bg-surface px-2 text-left text-sm shadow-soft transition hover:bg-telkom-grey-soft focus:outline-none focus:ring-2 focus:ring-telkom-red focus:ring-offset-2"
            @click="open = !open"
        >
            <!-- Avatar pengguna: menampilkan foto profil jika tersedia -->
            <img
                v-if="user?.profile_photo_url"
                :src="user.profile_photo_url"
                :alt="user.name"
                class="h-7 w-7 rounded-full object-cover"
            />
            <!-- Fallback avatar: menampilkan huruf pertama nama pengguna jika tidak ada foto -->
            <span
                v-else
                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-soft text-xs font-semibold text-telkom-red"
            >
                {{ user?.name?.charAt(0) ?? 'U' }}
            </span>
            <!-- Nama pengguna - disembunyikan di layar kecil untuk menghemat ruang -->
            <span class="hidden max-w-36 truncate font-medium text-telkom-black sm:inline">
                {{ user?.name }}
            </span>
            <!-- Ikon panah bawah sebagai indikator dropdown -->
            <ChevronDown class="h-4 w-4 text-content-muted" aria-hidden="true" />
        </button>

        <!-- ==================== PANEL DROPDOWN MENU ==================== -->
        <!-- Ditampilkan saat state 'open' bernilai true -->
        <div
            v-if="open"
            class="absolute right-0 z-40 mt-2 w-64 rounded-panel border border-border bg-surface p-2 shadow-panel"
        >
            <!-- Informasi pengguna: nama, email, dan role -->
            <div class="border-b border-border px-3 py-2">
                <p class="truncate text-sm font-semibold text-telkom-black">{{ user?.name }}</p>
                <p class="truncate text-xs text-content-muted">{{ user?.email }}</p>
                <!-- Label role pengguna dengan warna merah Telkom -->
                <p class="mt-1 text-xs font-medium text-telkom-red">{{ user?.role_label }}</p>
            </div>
            <!-- Link ke halaman profil pengguna -->
            <!-- Menutup dropdown setelah navigasi -->
            <Link
                :href="route('profile.edit')"
                class="mt-2 flex items-center gap-2 rounded-panel px-3 py-2 text-sm text-content-secondary hover:bg-telkom-grey-soft hover:text-telkom-black"
                @click="open = false"
            >
                <User class="h-4 w-4" />
                Profil Pengguna
            </Link>
            <!-- Tombol logout - menggunakan method POST untuk keamanan (CSRF protection) -->
            <!-- Menggunakan 'as="button"' agar Inertia Link berfungsi sebagai tombol form -->
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="flex w-full items-center gap-2 rounded-panel px-3 py-2 text-left text-sm text-content-secondary hover:bg-telkom-grey-soft hover:text-telkom-black"
            >
                <LogOut class="h-4 w-4" />
                Logout
            </Link>
        </div>
    </div>
</template>
