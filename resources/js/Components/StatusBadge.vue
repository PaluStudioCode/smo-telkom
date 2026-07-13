<!--
  Komponen: StatusBadge.vue
  Deskripsi: Komponen badge/label status yang menampilkan teks dalam bentuk pill/tag berwarna.
             Digunakan untuk menandai status item seperti "Aktif", "Pending", "Ditolak", dll.
             Mendukung beberapa variasi warna (variant) untuk membedakan jenis status.
  Digunakan oleh: DataTable dan halaman-halaman daftar data untuk menampilkan status visual.
-->
<script setup>
import { computed } from 'vue';

// Definisi props yang diterima dari komponen parent
const props = defineProps({
    // variant: Variasi warna badge yang menentukan tampilan visual
    // Opsi: 'primary' (merah Telkom), 'success' (hijau), 'warning' (kuning),
    //        'danger' (merah bahaya), 'info' (biru), 'neutral' (abu-abu/default)
    variant: {
        type: String,
        default: 'neutral',
    },
});

// Computed property untuk memetakan variant ke kelas CSS Tailwind yang sesuai
// Setiap variant memiliki kombinasi warna background, teks, dan border
// Jika variant tidak dikenali, fallback ke style 'neutral'
const classes = computed(() => {
    const variants = {
        primary: 'bg-primary-soft text-telkom-red border-telkom-red/20',
        success: 'bg-status-success-soft text-status-success-foreground border-status-success/20',
        warning: 'bg-status-warning-soft text-status-warning-foreground border-status-warning/20',
        danger: 'bg-status-danger-soft text-status-danger-foreground border-status-danger/20',
        info: 'bg-status-info-soft text-status-info-foreground border-status-info/20',
        neutral: 'bg-status-neutral-soft text-status-neutral-foreground border-status-neutral/20',
    };

    return variants[props.variant] ?? variants.neutral;
});
</script>

<template>
    <!-- Badge/label status dengan style pill - inline-flex untuk alignment ikon jika ada -->
    <!-- Kelas dinamis ditentukan oleh computed property 'classes' berdasarkan variant -->
    <span
        class="inline-flex h-6 items-center rounded-md border px-2 text-xs font-medium"
        :class="classes"
    >
        <!-- Slot default untuk konten teks badge (contoh: "Aktif", "Pending") -->
        <slot />
    </span>
</template>
