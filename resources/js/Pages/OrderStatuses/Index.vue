<!--
    OrderStatuses/Index.vue
    =======================
    Halaman daftar (index) Order Status untuk monitoring status pekerjaan provisioning.
    Fitur utama:
    - Tabel data Order Status dengan paginasi dan sorting
    - Filter pencarian, inputer, account manager, status, dan periode
    - CRUD (Create, Read, Update, Delete) melalui modal dialog
    - Kartu statistik ringkasan data
    - Pembatasan akses berdasarkan permission user
    - Optimistic locking menggunakan token updated_at untuk mencegah konflik data
-->

<script setup>
// ============================================================================
// IMPORT KOMPONEN DAN DEPENDENSI
// ============================================================================
import AppLayout from '@/Layouts/AppLayout.vue';
// Dialog konfirmasi untuk aksi berbahaya seperti hapus data
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
// Komponen tabel data dengan fitur paginasi, sorting, dan empty state
import DataTable from '@/Components/DataTable.vue';
import FilterBar from '@/Components/FilterBar.vue';
// Komponen untuk menampilkan pesan error validasi di bawah input form
import FormError from '@/Components/FormError.vue';
// Komponen modal dialog untuk form create/edit
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
// Head: title halaman, router: navigasi, useForm: form handler Inertia, usePage: akses data halaman
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
// Ikon-ikon aksi: daftar, edit, tambah, hapus
import { ClipboardList, Pencil, Plus, Trash2 } from 'lucide-vue-next';

// ============================================================================
// PROPS - Data dari OrderStatusController
// ============================================================================
const props = defineProps({
    // Data Order Status dengan paginasi dari Laravel (data, links, meta, dll.)
    orderStatuses: {
        type: Object,
        required: true,
    },
    // Array kartu statistik ringkasan (misal: total, provisioning, complete, dll.)
    stats: {
        type: Array,
        required: true,
    },
    // Objek filter yang sedang aktif (search, inputer_id, status, period_month, sort, direction)
    filters: {
        type: Object,
        required: true,
    },
    // Daftar opsi status yang tersedia untuk dropdown (value dan label)
    statusOptions: {
        type: Array,
        required: true,
    },
    // Daftar opsi inputer untuk dropdown filter dan form
    inputerOptions: {
        type: Array,
        required: true,
    },
    // Daftar opsi account manager untuk dropdown filter dan form
    accountManagerOptions: {
        type: Array,
        required: true,
    },
});

// ============================================================================
// AKSES DATA HALAMAN DAN PERMISSION
// ============================================================================
const page = usePage();
// Mengambil data user yang sedang login dari props Inertia
const currentUser = computed(() => page.props.auth.user);
// Mengambil daftar permission user untuk kontrol akses UI
const permissions = computed(() => page.props.auth.permissions);
// Mengecek apakah user adalah super admin (punya akses penuh)
const isSuperAdmin = computed(() => currentUser.value?.role === 'super_admin');
// Permission spesifik untuk CRUD Order Status
const canCreate = computed(() => permissions.value['order_status.create']);
const canUpdate = computed(() => permissions.value['order_status.update']);
const canDelete = computed(() => permissions.value['order_status.delete']);

// ============================================================================
// PEMBATASAN STATUS FINAL
// ============================================================================
// Status-status final yang tidak boleh dipilih oleh user non-super_admin
// Hanya super admin yang bisa mengubah status ke complete, failed, atau cancel_abandoned
const finalStatuses = ['complete', 'failed', 'cancel_abandoned'];

// Computed untuk memfilter opsi status pada form berdasarkan peran user
// Logika bisnis: user biasa tidak boleh langsung mengubah ke status final
// Namun jika sedang mengedit record yang sudah berstatus final, status tersebut tetap ditampilkan
const formStatusOptions = computed(() => {
    // Super admin bisa memilih semua status, user lain tidak bisa pilih status final
    const allowed = isSuperAdmin.value
        ? props.statusOptions
        : props.statusOptions.filter((option) => !finalStatuses.includes(option.value));

    // Cari status saat ini dari record yang sedang diedit
    const current = props.statusOptions.find((option) => option.value === editingRecord.value?.status);

    // Jika status saat ini tidak ada di daftar yang diizinkan, tambahkan agar tetap bisa dilihat
    if (current && !allowed.some((option) => option.value === current.value)) {
        return [current, ...allowed];
    }

    return allowed;
});

// ============================================================================
// STATE REAKTIF
// ============================================================================
// Mengontrol visibilitas modal form create/edit
const showModal = ref(false);
// Menyimpan record yang sedang diedit (null jika mode create)
const editingRecord = ref(null);
// State untuk dialog konfirmasi hapus
const confirmState = ref({
    show: false,       // Visibilitas dialog
    record: null,      // Record yang akan dihapus
    processing: false, // Status loading saat proses hapus
});
// State filter lokal yang disalin dari props
const filterForm = ref({ ...props.filters });
// Timer untuk debounce filter
let filterTimer = null;

// ============================================================================
// FUNGSI HELPER
// ============================================================================
// Mendapatkan bulan saat ini dalam format YYYY-MM untuk default periode
const currentMonth = () => {
    const date = new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
};

// Nilai default form untuk mode create baru
// Inputer otomatis diisi dengan user saat ini jika bukan super admin
const defaultFormValues = () => ({
    order_number: '',
    customer_name: '',
    service_name: '',
    inputer_id: isSuperAdmin.value ? '' : currentUser.value?.id, // Auto-assign inputer untuk non-super admin
    account_manager_id: '',
    status: 'provisioning', // Status default untuk order baru
    provisioning_stage: '',
    period_month: currentMonth(),
    source_system: 'Dashboard NCX', // Sistem sumber data default
    notes: '',
    updated_at: '', // Token optimistic locking (kosong untuk create, diisi saat edit)
});

// ============================================================================
// INERTIA FORM HANDLER
// ============================================================================
// useForm menyediakan state form reaktif dengan fitur: processing, errors, reset, dll.
const form = useForm(defaultFormValues());

// ============================================================================
// KONFIGURASI KOLOM TABEL
// ============================================================================
// Definisi kolom untuk komponen DataTable
// key: nama field data, label: judul kolom, sortable: bisa diurutkan
const columns = [
    { key: 'order_number', label: 'Nomor Order', sortable: true },
    { key: 'customer_name', label: 'Nama Pelanggan', sortable: true },
    { key: 'service_name', label: 'Layanan', sortable: true },
    { key: 'inputer_name', label: 'Inputer' },
    { key: 'account_manager_name', label: 'Account Manager' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'period_month', label: 'Periode', sortable: true },
    { key: 'updated_at', label: 'Update Terakhir', sortable: true },
    { key: 'actions', label: 'Aksi', headerClass: 'text-right', class: 'text-right' },
];

// Computed untuk menentukan apakah sedang dalam mode edit atau create
const isEditing = computed(() => Boolean(editingRecord.value));

// ============================================================================
// WATCHER - Debounce filter 350ms
// ============================================================================
watch(
    filterForm,
    () => {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(() => applyFilters(), 350);
    },
    { deep: true },
);

// ============================================================================
// FUNGSI FILTER DAN SORTING
// ============================================================================
// Menerapkan filter ke backend dengan Inertia GET request
// Selalu reset ke halaman 1 saat filter berubah agar tidak kosong
const applyFilters = (overrides = {}) => {
    router.get(route('order-statuses.index'), {
        ...filterForm.value,
        ...overrides,
        page: 1, // Reset ke halaman pertama saat filter berubah
    }, {
        preserveState: true, // Menjaga state Vue (modal terbuka, dll.)
        replace: true,       // Ganti URL tanpa menambah history baru
    });
};

// Menangani klik header kolom untuk sorting
// Toggle arah sort: jika kolom sama dan asc maka ubah ke desc, sebaliknya
const sortBy = (key) => {
    const direction = props.filters.sort === key && props.filters.direction === 'asc' ? 'desc' : 'asc';
    filterForm.value.sort = key;
    filterForm.value.direction = direction;
    applyFilters({ sort: key, direction });
};

// ============================================================================
// FUNGSI MODAL CREATE/EDIT
// ============================================================================
// Membuka modal untuk membuat Order Status baru
const openCreate = () => {
    editingRecord.value = null;        // Set mode create (bukan edit)
    form.defaults(defaultFormValues()); // Set nilai default form
    form.reset();                       // Reset form ke nilai default
    form.clearErrors();                 // Bersihkan pesan error validasi
    showModal.value = true;            // Tampilkan modal
};

// Membuka modal untuk mengedit Order Status yang sudah ada
// Mengisi form dengan data record yang dipilih
const openEdit = (record) => {
    editingRecord.value = record; // Set mode edit dengan record yang dipilih
    form.defaults({
        order_number: record.order_number,
        customer_name: record.customer_name ?? '',
        service_name: record.service_name ?? '',
        inputer_id: record.inputer_id,
        account_manager_id: record.account_manager_id,
        status: record.status,
        provisioning_stage: record.provisioning_stage ?? '',
        period_month: record.period_month,
        source_system: record.source_system ?? 'Dashboard NCX',
        notes: record.notes ?? '',
        updated_at: record.updated_at_token, // Token untuk optimistic locking
    });
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

// Menutup modal dan membersihkan state form
// Mencegah penutupan jika form sedang diproses (submit)
const closeModal = () => {
    if (form.processing) return; // Cegah tutup saat sedang submit

    showModal.value = false;
    editingRecord.value = null;
    form.reset();
    form.clearErrors();
};

// ============================================================================
// SUBMIT FORM (CREATE/UPDATE)
// ============================================================================
// Mengirim form ke backend, memilih antara POST (create) atau PUT (update)
const submit = () => {
    const options = {
        preserveScroll: true,          // Jaga posisi scroll setelah submit
        onSuccess: () => closeModal(), // Tutup modal jika berhasil
    };

    // Jika mode edit, kirim PUT request ke endpoint update
    if (isEditing.value) {
        form.put(route('order-statuses.update', editingRecord.value.id), options);
        return;
    }

    // Jika mode create, kirim POST request ke endpoint store
    form.post(route('order-statuses.store'), options);
};

// ============================================================================
// FUNGSI HAPUS DATA
// ============================================================================
// Membuka dialog konfirmasi hapus
const askDelete = (record) => {
    confirmState.value = { show: true, record, processing: false };
};

// Menutup dialog konfirmasi hapus
// Mencegah penutupan jika sedang memproses penghapusan
const closeConfirm = () => {
    if (confirmState.value.processing) return;
    confirmState.value = { show: false, record: null, processing: false };
};

// Mengeksekusi penghapusan data setelah dikonfirmasi
// Mengirim DELETE request dengan token updated_at untuk optimistic locking
const confirmDelete = () => {
    if (!confirmState.value.record) return;

    confirmState.value.processing = true;
    router.delete(route('order-statuses.destroy', confirmState.value.record.id), {
        data: {
            updated_at: confirmState.value.record.updated_at_token, // Optimistic locking
        },
        preserveScroll: true,
        onFinish: () => {
            confirmState.value.processing = false;
            closeConfirm();
        },
    });
};
</script>

<template>
    <!-- Mengatur judul tab browser -->
    <Head title="Order Status" />

    <AppLayout title="Order Status">
        <!-- ============================================================ -->
        <!-- HEADER HALAMAN - Judul, deskripsi, dan tombol tambah         -->
        <!-- ============================================================ -->
        <template #header>
            <PageHeader
                title="Order Status"
                description="Monitoring status pekerjaan provisioning."
            >
                <!-- Tombol tambah hanya ditampilkan jika user punya permission create -->
                <template v-if="canCreate" #actions>
                    <button
                        type="button"
                        class="inline-flex h-10 items-center gap-2 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white shadow-soft transition hover:bg-telkom-red-dark focus:outline-none focus:ring-2 focus:ring-telkom-red focus:ring-offset-2"
                        @click="openCreate"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah
                    </button>
                </template>
            </PageHeader>
        </template>

        <!-- ============================================================ -->
        <!-- KARTU STATISTIK - Ringkasan jumlah data per status           -->
        <!-- ============================================================ -->
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <StatCard
                v-for="stat in stats"
                :key="stat.key"
                :label="stat.label"
                :value="stat.value"
                :tone="stat.tone"
                context="Jumlah data"
            >
                <template #icon>
                    <ClipboardList class="h-5 w-5" />
                </template>
            </StatCard>
        </div>

        <!-- ============================================================ -->
        <!-- BAGIAN FILTER - Pencarian, inputer, AM, status, periode      -->
        <!-- ============================================================ -->
        <FilterBar>
            <!-- Input pencarian berdasarkan nomor order atau nama pelanggan -->
            <div>
                <label for="search" class="text-sm font-medium text-content-secondary">Search</label>
                <input
                    id="search"
                    v-model="filterForm.search"
                    type="search"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                    placeholder="Nomor order, pelanggan"
                />
            </div>
            <!-- Filter inputer - hanya untuk super admin -->
            <div v-if="isSuperAdmin">
                <label for="inputer_id" class="text-sm font-medium text-content-secondary">Inputer</label>
                <select id="inputer_id" v-model="filterForm.inputer_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                    <option value="">Semua Inputer</option>
                    <option v-for="inputer in inputerOptions" :key="inputer.id" :value="inputer.id">
                        {{ inputer.name }}
                    </option>
                </select>
            </div>
            <!-- Filter account manager - tidak ditampilkan untuk role account_manager -->
            <div v-if="currentUser.role !== 'account_manager'">
                <label for="account_manager_id" class="text-sm font-medium text-content-secondary">Account Manager</label>
                <select id="account_manager_id" v-model="filterForm.account_manager_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                    <option value="">Semua AM</option>
                    <option v-for="accountManager in accountManagerOptions" :key="accountManager.id" :value="accountManager.id">
                        {{ accountManager.name }}
                    </option>
                </select>
            </div>
            <!-- Filter status order -->
            <div>
                <label for="status" class="text-sm font-medium text-content-secondary">Status</label>
                <select id="status" v-model="filterForm.status" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                    <option value="">Semua Status</option>
                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>
            </div>
            <!-- Filter periode bulan -->
            <div>
                <label for="period_month" class="text-sm font-medium text-content-secondary">Periode</label>
                <input id="period_month" v-model="filterForm.period_month" type="month" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
            </div>
        </FilterBar>

        <!-- ============================================================ -->
        <!-- TABEL DATA - Daftar Order Status dengan paginasi dan sorting -->
        <!-- ============================================================ -->
        <DataTable
            :columns="columns"
            :rows="orderStatuses.data"
            :meta="orderStatuses"
            :filters="filters"
            route-name="order-statuses.index"
            :sort="filters.sort"
            :direction="filters.direction"
            empty-title="Belum ada Order Status"
            empty-description="Data akan tampil saat sudah ditambahkan atau filter disesuaikan."
            @sort="sortBy"
        >
            <!-- Slot kustom untuk kolom nomor order - ditampilkan bold -->
            <template #cell-order_number="{ row }">
                <span class="font-semibold text-telkom-black">{{ row.order_number }}</span>
            </template>
            <!-- Slot kustom untuk kolom nama pelanggan - fallback ke '-' jika kosong -->
            <template #cell-customer_name="{ row }">
                {{ row.customer_name || '-' }}
            </template>
            <template #cell-service_name="{ row }">
                {{ row.service_name || '-' }}
            </template>
            <!-- Slot kustom untuk kolom status - ditampilkan sebagai badge berwarna -->
            <template #cell-status="{ row }">
                <StatusBadge :variant="row.status_tone">{{ row.status_label }}</StatusBadge>
            </template>
            <!-- Slot kustom untuk kolom aksi - tombol edit dan hapus -->
            <template #cell-actions="{ row }">
                <div v-if="canUpdate || canDelete" class="flex justify-end gap-1">
                    <!-- Tombol edit - hanya jika punya permission update -->
                    <button
                        v-if="canUpdate"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-content-secondary transition-all hover:bg-telkom-grey-soft hover:text-telkom-black active:scale-95"
                        aria-label="Ubah Order Status"
                        @click="openEdit(row)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <!-- Tombol hapus - hanya jika punya permission delete -->
                    <button
                        v-if="canDelete"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-status-danger transition-all hover:bg-status-danger-soft active:scale-95"
                        aria-label="Hapus Order Status"
                        @click="askDelete(row)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
                <!-- Tampilkan '-' jika user tidak punya permission apapun -->
                <span v-else>-</span>
            </template>
        </DataTable>

        <!-- ============================================================ -->
        <!-- MODAL FORM CREATE/EDIT ORDER STATUS                          -->
        <!-- ============================================================ -->
        <Modal :show="showModal" max-width="2xl" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <!-- Header modal - judul dinamis sesuai mode (create/edit) -->
                <div class="border-b border-border pb-4">
                    <h2 class="text-lg font-semibold text-telkom-black">
                        {{ isEditing ? 'Ubah Order Status' : 'Tambah Order Status' }}
                    </h2>
                </div>

                <!-- Grid form input 2 kolom -->
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <!-- Input nomor order -->
                    <div>
                        <label for="order_number" class="text-sm font-medium text-content-secondary">Nomor Order</label>
                        <input id="order_number" v-model="form.order_number" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.order_number" />
                    </div>
                    <!-- Input nama pelanggan -->
                    <div>
                        <label for="customer_name" class="text-sm font-medium text-content-secondary">Nama Pelanggan</label>
                        <input id="customer_name" v-model="form.customer_name" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.customer_name" />
                    </div>
                    <!-- Input nama layanan -->
                    <div>
                        <label for="service_name" class="text-sm font-medium text-content-secondary">Layanan</label>
                        <input id="service_name" v-model="form.service_name" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.service_name" />
                    </div>
                    <!-- Dropdown inputer - hanya ditampilkan untuk super admin -->
                    <div v-if="isSuperAdmin">
                        <label for="form_inputer_id" class="text-sm font-medium text-content-secondary">Inputer</label>
                        <select id="form_inputer_id" v-model="form.inputer_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                            <option value="">Pilih Inputer</option>
                            <option v-for="inputer in inputerOptions" :key="inputer.id" :value="inputer.id">
                                {{ inputer.name }}
                            </option>
                        </select>
                        <FormError :message="form.errors.inputer_id" />
                    </div>
                    <!-- Dropdown account manager -->
                    <div>
                        <label for="form_account_manager_id" class="text-sm font-medium text-content-secondary">Account Manager</label>
                        <select id="form_account_manager_id" v-model="form.account_manager_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                            <option value="">Pilih AM</option>
                            <option v-for="accountManager in accountManagerOptions" :key="accountManager.id" :value="accountManager.id">
                                {{ accountManager.name }}
                            </option>
                        </select>
                        <FormError :message="form.errors.account_manager_id" />
                    </div>
                    <!-- Dropdown status - opsi difilter berdasarkan peran user -->
                    <div>
                        <label for="form_status" class="text-sm font-medium text-content-secondary">Status</label>
                        <select id="form_status" v-model="form.status" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                            <option v-for="status in formStatusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                        <FormError :message="form.errors.status" />
                    </div>
                    <!-- Input tahap provisioning -->
                    <div>
                        <label for="provisioning_stage" class="text-sm font-medium text-content-secondary">Tahap Provisioning</label>
                        <input id="provisioning_stage" v-model="form.provisioning_stage" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.provisioning_stage" />
                    </div>
                    <!-- Input periode bulan -->
                    <div>
                        <label for="form_period_month" class="text-sm font-medium text-content-secondary">Periode</label>
                        <input id="form_period_month" v-model="form.period_month" type="month" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.period_month" />
                    </div>
                    <!-- Textarea catatan - lebar penuh (col-span-2) -->
                    <div class="md:col-span-2">
                        <label for="notes" class="text-sm font-medium text-content-secondary">Catatan</label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="mt-1 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.notes" />
                        <!-- Error optimistic locking ditampilkan jika data sudah diubah user lain -->
                        <FormError :message="form.errors.updated_at" />
                    </div>
                </div>

                <!-- Tombol aksi form: Batal dan Simpan -->
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="h-10 rounded-panel border border-border px-4 text-sm font-medium text-content-secondary hover:bg-telkom-grey-soft" :disabled="form.processing" @click="closeModal">
                        Batal
                    </button>
                    <!-- Tombol simpan dengan loading spinner saat processing -->
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white transition-all hover:bg-telkom-red-dark active:scale-95 disabled:pointer-events-none disabled:opacity-70" :disabled="form.processing">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- ============================================================ -->
        <!-- DIALOG KONFIRMASI HAPUS ORDER STATUS                         -->
        <!-- ============================================================ -->
        <ConfirmDialog
            :show="confirmState.show"
            title="Hapus Order Status?"
            description="Data akan dihapus dari daftar monitoring."
            confirm-label="Hapus"
            :processing="confirmState.processing"
            destructive
            @close="closeConfirm"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
