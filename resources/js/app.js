// Mengimpor file CSS utama (biasanya berisi Tailwind CSS dan styling global)
import '../css/app.css';
import './bootstrap';

// Mengimpor fungsi-fungsi utama dari library Inertia dan Vue
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
// Mengimpor Ziggy, library untuk memanggil route Laravel di frontend Vue (menggunakan fungsi route())
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// Inisialisasi aplikasi Inertia
createInertiaApp({
    // Mengatur title default atau title halaman (misal: "Halaman | Sistem Monitoring Operasional")
    title: (title) => title || 'Sistem Monitoring Operasional',
    // resolve: Menentukan komponen Vue mana yang akan dimuat berdasarkan nama halamannya (disimpan di folder resources/js/Pages)
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    // Setup aplikasi Vue, mendaftarkan plugin dan melampirkan ke elemen root
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin) // Menggunakan plugin Inertia
            .use(ZiggyVue) // Menggunakan ZiggyVue agar bisa memakai route('nama.rute') di template
            .mount(el); // Melampirkan Vue ke id="app" di halaman Blade
    },
    progress: {
        color: '#4B5563',
    },
});
