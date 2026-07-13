<!--
  Komponen: ConfirmDialog.vue
  Deskripsi: Komponen dialog konfirmasi yang dapat digunakan ulang untuk meminta konfirmasi pengguna
             sebelum melakukan aksi penting (seperti menghapus data, mengubah status, dll).
             Mendukung mode destruktif (warna merah bahaya) dan indikator pemrosesan (loading spinner).
             Dialog ditampilkan sebagai modal overlay dengan animasi transisi masuk/keluar.
  Digunakan oleh: Halaman-halaman yang memerlukan konfirmasi aksi seperti hapus pengguna, ubah status order, dll.
-->
<script setup>
// Definisi props yang diterima dari komponen parent
defineProps({
    // show: Mengontrol visibilitas dialog (true = tampil, false = tersembunyi)
    show: {
        type: Boolean,
        default: false,
    },
    // title: Judul dialog konfirmasi yang ditampilkan di bagian atas
    title: {
        type: String,
        default: 'Konfirmasi aksi',
    },
    // description: Teks deskripsi tambahan untuk menjelaskan detail aksi yang akan dilakukan
    description: {
        type: String,
        default: '',
    },
    // confirmLabel: Teks label pada tombol konfirmasi
    confirmLabel: {
        type: String,
        default: 'Konfirmasi',
    },
    // processing: Indikator apakah aksi sedang diproses (menampilkan spinner dan menonaktifkan tombol)
    processing: {
        type: Boolean,
        default: false,
    },
    // destructive: Jika true, tombol konfirmasi akan berwarna merah (bahaya)
    // Digunakan untuk aksi yang tidak dapat dibatalkan seperti penghapusan data
    destructive: {
        type: Boolean,
        default: false,
    },
});

// Definisi event yang di-emit ke komponen parent
// 'close': di-emit saat pengguna menutup dialog (klik overlay atau tombol Batal)
// 'confirm': di-emit saat pengguna mengkonfirmasi aksi (klik tombol konfirmasi)
const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <!-- Menggunakan Teleport untuk merender dialog di luar hierarki DOM, langsung ke body -->
    <!-- Ini memastikan dialog overlay selalu di atas semua elemen halaman -->
    <Teleport to="body">
        <!-- Transisi fade untuk seluruh container dialog (overlay + panel) -->
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <!-- Container dialog - ditampilkan hanya saat prop 'show' bernilai true -->
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
                <!-- ==================== OVERLAY LATAR BELAKANG ==================== -->
                <!-- Overlay gelap semi-transparan - klik untuk menutup dialog -->
                <!-- Overlay -->
                <button
                    type="button"
                    class="absolute inset-0 bg-telkom-black/40 transition-opacity"
                    aria-label="Tutup dialog"
                    @click="emit('close')"
                />

                <!-- ==================== PANEL DIALOG ==================== -->
                <!-- Transisi terpisah untuk panel dengan efek scale dan translate -->
                <!-- Panel -->
                <Transition
                    enter-active-class="duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                    appear
                >
                    <div
                        v-if="show"
                        class="relative w-full max-w-md rounded-panel border border-border bg-surface p-5 shadow-panel"
                    >
                        <!-- Judul dialog konfirmasi -->
                        <h2 class="text-lg font-semibold text-telkom-black">{{ title }}</h2>
                        <!-- Deskripsi opsional - hanya ditampilkan jika ada -->
                        <p v-if="description" class="mt-2 text-sm text-content-secondary">{{ description }}</p>
                        <!-- Area tombol aksi -->
                        <div class="mt-6 flex justify-end gap-2">
                            <!-- Tombol Batal - menutup dialog tanpa melakukan aksi -->
                            <!-- Dinonaktifkan saat sedang memproses untuk mencegah interaksi ganda -->
                            <button
                                type="button"
                                class="h-9 rounded-panel border border-border px-4 text-sm font-medium text-content-secondary transition-all duration-200 hover:bg-telkom-grey-soft hover:shadow-sm active:scale-[0.97] disabled:opacity-50 disabled:pointer-events-none"
                                :disabled="processing"
                                @click="emit('close')"
                            >
                                Batal
                            </button>
                            <!-- Tombol Konfirmasi - menjalankan aksi yang dikonfirmasi -->
                            <!-- Warna berubah berdasarkan prop 'destructive': merah bahaya atau merah Telkom -->
                            <button
                                type="button"
                                class="inline-flex h-9 items-center gap-2 rounded-panel px-4 text-sm font-semibold text-white transition-all duration-200 active:scale-[0.97] disabled:opacity-70 disabled:pointer-events-none"
                                :class="destructive ? 'bg-status-danger hover:bg-status-danger/90 hover:shadow-md hover:shadow-status-danger/25' : 'bg-telkom-red hover:bg-telkom-red-dark hover:shadow-md hover:shadow-telkom-red/25'"
                                :disabled="processing"
                                @click="emit('confirm')"
                            >
                                <!-- Ikon spinner animasi ditampilkan saat sedang memproses -->
                                <svg
                                    v-if="processing"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <!-- Teks tombol berubah saat sedang memproses -->
                                {{ processing ? 'Memproses...' : confirmLabel }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
