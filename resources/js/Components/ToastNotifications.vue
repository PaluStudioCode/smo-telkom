<!--
  Komponen: ToastNotifications.vue
  Deskripsi: Komponen notifikasi toast yang menampilkan pesan flash dari server (Laravel session flash messages).
             Pesan ditampilkan sebagai notifikasi sementara di pojok kanan atas layar dan otomatis
             menghilang setelah 4.2 detik. Mendukung beberapa tipe: success, error, warning, dan info,
             masing-masing dengan tampilan warna yang berbeda.
  Digunakan oleh: AppLayout.vue - ditempatkan secara global agar semua halaman terautentikasi bisa menampilkan notifikasi.
-->
<script setup>
// Import usePage dari Inertia untuk mengakses flash messages dari server
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

// Mengakses data halaman Inertia
const page = usePage();
// Array reaktif yang menyimpan daftar toast yang sedang ditampilkan
// Setiap toast berisi: id (unik), type (success/error/warning/info), message (teks pesan)
const toasts = ref([]);

// Computed property untuk mengambil data flash messages dari props Inertia
// Flash messages dikirim dari controller Laravel menggunakan session()->flash()
const flash = computed(() => page.props.flash ?? {});

// Pemetaan tipe notifikasi ke kelas CSS Tailwind untuk tampilan warna yang berbeda
// Setiap tipe memiliki kombinasi border, background, dan warna teks yang berbeda
const tones = {
    success: 'border-status-success/20 bg-status-success-soft text-status-success-foreground',  // Hijau - untuk pesan sukses
    error: 'border-status-danger/20 bg-status-danger-soft text-status-danger-foreground',       // Merah - untuk pesan error
    warning: 'border-status-warning/20 bg-status-warning-soft text-status-warning-foreground',  // Kuning - untuk pesan peringatan
    info: 'border-status-info/20 bg-status-info-soft text-status-info-foreground',              // Biru - untuk pesan informasi
};

// Watcher yang memantau perubahan pada flash messages
// Setiap kali ada flash message baru dari server, toast akan dibuat dan ditampilkan
watch(
    flash,
    (value) => {
        // Iterasi setiap entry flash message (contoh: { success: 'Data berhasil disimpan' })
        Object.entries(value).forEach(([type, message]) => {
            // Lewati jika pesan kosong/null
            if (!message) return;

            // Buat ID unik untuk toast menggunakan timestamp + tipe
            // ID ini digunakan untuk mengidentifikasi toast saat menghapusnya
            const id = `${Date.now()}-${type}`;
            // Tambahkan toast baru ke array
            toasts.value.push({ id, type, message });

            // Atur timer untuk menghapus toast secara otomatis setelah 4.2 detik
            // Menggunakan filter untuk menghapus toast spesifik berdasarkan ID
            window.setTimeout(() => {
                toasts.value = toasts.value.filter((toast) => toast.id !== id);
            }, 4200);
        });
    },
    { deep: true, immediate: true }, // deep: pantau perubahan nested, immediate: jalankan saat komponen dimuat
);
</script>

<template>
    <!-- Container toast - posisi tetap di pojok kanan atas layar dengan z-index tinggi -->
    <!-- Lebar responsif: maksimum 24rem atau menyesuaikan lebar layar dikurangi margin -->
    <div class="fixed right-4 top-4 z-[60] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2">
        <!-- Iterasi setiap toast yang aktif -->
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="rounded-panel border px-4 py-3 text-sm font-medium shadow-panel"
            :class="tones[toast.type] ?? tones.info"
        >
            <!-- Tampilkan pesan toast -->
            {{ toast.message }}
        </div>
    </div>
</template>
