<!--
  Komponen: Modal.vue
  Deskripsi: Komponen modal (dialog) generik yang dapat digunakan ulang untuk menampilkan konten dalam overlay.
             Menggunakan elemen HTML <dialog> native untuk aksesibilitas yang lebih baik.
             Mendukung berbagai ukuran (sm, md, lg, xl, 2xl), penutupan dengan tombol Escape,
             dan pengelolaan scroll body secara otomatis (mengunci scroll saat modal terbuka).
  Digunakan oleh: Berbagai halaman yang memerlukan tampilan modal seperti form edit, detail item, dll.
-->
<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// Definisi props yang diterima dari komponen parent
const props = defineProps({
    // show: Mengontrol visibilitas modal (true = tampil, false = tersembunyi)
    show: {
        type: Boolean,
        default: false,
    },
    // maxWidth: Ukuran lebar maksimum modal ('sm', 'md', 'lg', 'xl', '2xl')
    maxWidth: {
        type: String,
        default: '2xl',
    },
    // closeable: Menentukan apakah modal bisa ditutup oleh pengguna (klik overlay atau tombol Escape)
    closeable: {
        type: Boolean,
        default: true,
    },
});

// Event yang di-emit saat modal ditutup
const emit = defineEmits(['close']);
// Referensi ke elemen <dialog> HTML native
const dialog = ref();
// State untuk mengontrol rendering slot konten modal
// Digunakan untuk menghindari rendering konten sebelum animasi selesai
const showSlot = ref(props.show);

// Watcher untuk memantau perubahan prop 'show' dan mengelola lifecycle modal
watch(
    () => props.show,
    () => {
        if (props.show) {
            // Saat modal dibuka:
            // 1. Kunci scroll body untuk mencegah scroll di belakang modal
            document.body.style.overflow = 'hidden';
            // 2. Aktifkan rendering konten slot
            showSlot.value = true;

            // 3. Tampilkan dialog menggunakan API native showModal()
            // showModal() juga menangani focus trapping secara otomatis
            dialog.value?.showModal();
        } else {
            // Saat modal ditutup:
            // 1. Kembalikan scroll body ke normal
            document.body.style.overflow = '';

            // 2. Tunda penutupan dialog 200ms agar animasi transisi keluar selesai terlebih dahulu
            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
            }, 200);
        }
    },
);

// Fungsi untuk menutup modal - hanya berfungsi jika prop 'closeable' bernilai true
const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

// Handler untuk menutup modal saat tombol Escape ditekan
// Mencegah perilaku default tombol Escape pada elemen <dialog>
const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

// Lifecycle hooks: daftarkan dan hapus event listener keyboard
// Didaftarkan saat komponen di-mount dan dihapus saat di-unmount untuk mencegah memory leak
onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);

    // Pastikan scroll body dikembalikan ke normal saat komponen dihancurkan
    // untuk mencegah scroll terkunci jika modal masih terbuka saat navigasi halaman
    document.body.style.overflow = '';
});

// Computed property untuk memetakan prop maxWidth ke kelas Tailwind CSS yang sesuai
const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <!-- Menggunakan elemen <dialog> HTML native untuk aksesibilitas yang lebih baik -->
    <!-- Dialog diatur untuk mengisi seluruh layar dengan latar belakang transparan -->
    <dialog
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
        ref="dialog"
    >
        <!-- Container utama modal dengan scroll vertikal -->
        <div
            class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
            scroll-region
        >
            <!-- ==================== OVERLAY LATAR BELAKANG ==================== -->
            <!-- Overlay gelap semi-transparan dengan animasi fade -->
            <!-- Klik pada overlay akan menutup modal (jika closeable) -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 transform transition-all"
                    @click="close"
                >
                    <div
                        class="absolute inset-0 bg-gray-500 opacity-75"
                    />
                </div>
            </Transition>

            <!-- ==================== PANEL KONTEN MODAL ==================== -->
            <!-- Panel modal dengan animasi scale dan translate -->
            <!-- Lebar panel disesuaikan berdasarkan prop maxWidth -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="mb-6 transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:mx-auto sm:w-full"
                    :class="maxWidthClass"
                >
                    <!-- Slot konten modal - hanya dirender saat showSlot bernilai true -->
                    <!-- Ini memastikan konten tidak dirender sebelum animasi pembukaan dimulai -->
                    <slot v-if="showSlot" />
                </div>
            </Transition>
        </div>
    </dialog>
</template>
