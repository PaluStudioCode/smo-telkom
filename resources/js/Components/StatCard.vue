<!--
  Komponen: StatCard.vue
  Deskripsi: Komponen kartu statistik yang menampilkan metrik/angka penting dalam format kartu.
             Digunakan untuk menampilkan ringkasan data di halaman dashboard seperti jumlah order,
             total pengguna, status penyelesaian, dll.
             Mendukung berbagai variasi warna (tone) untuk membedakan kategori metrik.
  Digunakan oleh: Halaman Dashboard dan halaman-halaman ringkasan data lainnya.
-->
<script setup>
// Definisi props yang diterima dari komponen parent
defineProps({
    // label: Judul/label metrik yang ditampilkan (contoh: "Total Order", "Pengguna Aktif")
    label: {
        type: String,
        required: true,
    },
    // value: Nilai metrik yang ditampilkan dalam ukuran besar (contoh: 150, "25%")
    // Mendukung tipe String dan Number untuk fleksibilitas format
    value: {
        type: [String, Number],
        required: true,
    },
    // context: Teks kontekstual tambahan di bawah nilai (contoh: "dari 200 target", "+12% dari bulan lalu")
    context: {
        type: String,
        default: '',
    },
    // tone: Variasi warna kartu untuk mengelompokkan/membedakan metrik
    // Opsi: 'primary' (merah Telkom), 'success' (hijau), 'warning' (kuning),
    //        'danger' (merah bahaya), 'info' (biru), 'neutral' (abu-abu)
    tone: {
        type: String,
        default: 'primary',
    },
});
</script>

<template>
    <!-- Container kartu statistik dengan border, shadow, dan padding -->
    <div class="rounded-panel border border-border bg-surface p-4 shadow-soft">
        <div class="flex items-start justify-between gap-4">
            <!-- ==================== BAGIAN KIRI: LABEL, NILAI, DAN KONTEKS ==================== -->
            <div>
                <!-- Label/judul metrik -->
                <p class="text-sm font-medium text-content-secondary">{{ label }}</p>
                <!-- Nilai metrik utama - ditampilkan dalam ukuran besar dan tebal -->
                <p class="mt-2 text-3xl font-semibold text-telkom-black">{{ value }}</p>
                <!-- Teks konteks tambahan - hanya ditampilkan jika ada -->
                <p v-if="context" class="mt-1 text-xs text-content-muted">{{ context }}</p>
            </div>
            <!-- ==================== BAGIAN KANAN: IKON ==================== -->
            <!-- Container ikon dengan warna latar belakang sesuai tone -->
            <!-- Kelas CSS dipilih berdasarkan nilai prop 'tone' menggunakan object binding -->
            <div
                class="flex h-10 w-10 items-center justify-center rounded-panel"
                :class="{
                    'bg-primary-soft text-telkom-red': tone === 'primary',
                    'bg-status-success-soft text-status-success': tone === 'success',
                    'bg-status-warning-soft text-status-warning': tone === 'warning',
                    'bg-status-danger-soft text-status-danger': tone === 'danger',
                    'bg-status-info-soft text-status-info': tone === 'info',
                    'bg-status-neutral-soft text-status-neutral': tone === 'neutral',
                }"
            >
                <!-- Slot untuk ikon kustom - parent menyediakan ikon sesuai konteks metrik -->
                <slot name="icon" />
            </div>
        </div>
    </div>
</template>
