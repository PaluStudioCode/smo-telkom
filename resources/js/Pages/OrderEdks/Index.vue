<!--
    OrderEdks/Index.vue
    ===================
    Halaman daftar (index) Order EDK untuk monitoring progres pekerjaan EDK
    (Evidence Delivery Key / Bukti Serah Terima).
    Fitur utama:
    - Tabel data Order EDK dengan paginasi dan sorting
    - Filter pencarian, inputer, account manager, status, dan periode
    - CRUD (Create, Read, Update, Delete) melalui modal dialog
    - Kartu statistik ringkasan data EDK
    - Pembatasan akses berdasarkan permission user
    - Optimistic locking menggunakan token updated_at

    Struktur mirip dengan OrderStatuses/Index.vue namun dengan field dan
    status yang spesifik untuk alur kerja EDK.
-->

<script setup>
// ============================================================================
// IMPORT KOMPONEN DAN DEPENDENSI
// ============================================================================
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import DataTable from '@/Components/DataTable.vue';
import FilterBar from '@/Components/FilterBar.vue';
import FormError from '@/Components/FormError.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
// FileCheck: ikon khusus EDK yang merepresentasikan dokumen terverifikasi
import { FileCheck, Pencil, Plus, Trash2 } from 'lucide-vue-next';

// ============================================================================
// PROPS - Data dari OrderEdkController
// ============================================================================
const props = defineProps({
    // Data Order EDK dengan paginasi Laravel (data, links, meta)
    orderEdks: {
        type: Object,
        required: true,
    },
    // Array kartu statistik ringkasan EDK (misal: total, belum input, selesai, dll.)
    stats: {
        type: Array,
        required: true,
    },
    // Objek filter yang sedang aktif
    filters: {
        type: Object,
        required: true,
    },
    // Daftar opsi status EDK untuk dropdown (belum_input, proses, complete, tidak_lanjut)
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
const currentUser = computed(() => page.props.auth.user);
const permissions = computed(() => page.props.auth.permissions);
const isSuperAdmin = computed(() => currentUser.value?.role === 'super_admin');
// Permission spesifik untuk CRUD Order EDK
const canCreate = computed(() => permissions.value['order_edk.create']);
const canUpdate = computed(() => permissions.value['order_edk.update']);
const canDelete = computed(() => permissions.value['order_edk.delete']);

// ============================================================================
// PEMBATASAN STATUS FINAL EDK
// ============================================================================
// Status final EDK: 'complete' dan 'tidak_lanjut' (tidak dilanjutkan)
// Hanya super admin yang bisa mengubah ke status-status ini
const finalStatuses = ['complete', 'tidak_lanjut'];

// Memfilter opsi status pada form berdasarkan peran user
// Logika bisnis: user biasa hanya bisa memilih status non-final
// Jika record yang sedang diedit sudah berstatus final, status tersebut tetap ditampilkan
const formStatusOptions = computed(() => {
    const allowed = isSuperAdmin.value
        ? props.statusOptions
        : props.statusOptions.filter((option) => !finalStatuses.includes(option.value));
    const current = props.statusOptions.find((option) => option.value === editingRecord.value?.status);

    // Tambahkan status saat ini ke daftar jika belum ada (untuk keperluan tampilan saat edit)
    if (current && !allowed.some((option) => option.value === current.value)) {
        return [current, ...allowed];
    }

    return allowed;
});

// ============================================================================
// STATE REAKTIF
// ============================================================================
// Kontrol visibilitas modal create/edit
const showModal = ref(false);
// Record yang sedang diedit (null = mode create)
const editingRecord = ref(null);
// State dialog konfirmasi hapus
const confirmState = ref({
    show: false,
    record: null,
    processing: false,
});
// State filter lokal
const filterForm = ref({ ...props.filters });
// Timer debounce untuk filter
let filterTimer = null;

// ============================================================================
// FUNGSI HELPER
// ============================================================================
// Mendapatkan bulan saat ini dalam format YYYY-MM
const currentMonth = () => {
    const date = new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
};

// Nilai default form untuk mode create
// Field berbeda dari OrderStatuses: menggunakan edk_reference bukan order_number
const defaultFormValues = () => ({
    edk_reference: '',       // Nomor referensi EDK
    customer_name: '',
    inputer_id: isSuperAdmin.value ? '' : currentUser.value?.id, // Auto-assign inputer
    account_manager_id: '',
    status: 'belum_input',   // Status default EDK: belum diinput
    period_month: currentMonth(),
    source_system: 'Dashboard NCX',
    notes: '',
    updated_at: '',          // Token optimistic locking
});

// ============================================================================
// INERTIA FORM HANDLER
// ============================================================================
const form = useForm(defaultFormValues());

// ============================================================================
// KONFIGURASI KOLOM TABEL
// ============================================================================
// Kolom EDK: menggunakan edk_reference sebagai identitas utama (bukan order_number)
const columns = [
    { key: 'edk_reference', label: 'Referensi EDK', sortable: true },
    { key: 'customer_name', label: 'Nama Pelanggan', sortable: true },
    { key: 'inputer_name', label: 'Inputer' },
    { key: 'account_manager_name', label: 'Account Manager' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'period_month', label: 'Periode', sortable: true },
    { key: 'updated_at', label: 'Update Terakhir', sortable: true },
    { key: 'actions', label: 'Aksi', headerClass: 'text-right', class: 'text-right' },
];

// Menentukan mode form: edit atau create
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
// Menerapkan filter dengan Inertia GET request
const applyFilters = (overrides = {}) => {
    router.get(route('order-edks.index'), {
        ...filterForm.value,
        ...overrides,
        page: 1,
    }, {
        preserveState: true,
        replace: true,
    });
};

// Toggle sorting berdasarkan kolom yang diklik
const sortBy = (key) => {
    const direction = props.filters.sort === key && props.filters.direction === 'asc' ? 'desc' : 'asc';
    filterForm.value.sort = key;
    filterForm.value.direction = direction;
    applyFilters({ sort: key, direction });
};

// ============================================================================
// FUNGSI MODAL CREATE/EDIT
// ============================================================================
// Membuka modal untuk membuat Order EDK baru
const openCreate = () => {
    editingRecord.value = null;
    form.defaults(defaultFormValues());
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

// Membuka modal untuk mengedit Order EDK yang sudah ada
const openEdit = (record) => {
    editingRecord.value = record;
    form.defaults({
        edk_reference: record.edk_reference,
        customer_name: record.customer_name ?? '',
        inputer_id: record.inputer_id,
        account_manager_id: record.account_manager_id,
        status: record.status,
        period_month: record.period_month,
        source_system: record.source_system ?? 'Dashboard NCX',
        notes: record.notes ?? '',
        updated_at: record.updated_at_token, // Optimistic locking token
    });
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

// Menutup modal dan membersihkan state
const closeModal = () => {
    if (form.processing) return;

    showModal.value = false;
    editingRecord.value = null;
    form.reset();
    form.clearErrors();
};

// ============================================================================
// SUBMIT FORM (CREATE/UPDATE)
// ============================================================================
// Mengirim data ke backend: POST untuk create, PUT untuk update
const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (isEditing.value) {
        form.put(route('order-edks.update', editingRecord.value.id), options);
        return;
    }

    form.post(route('order-edks.store'), options);
};

// ============================================================================
// FUNGSI HAPUS DATA
// ============================================================================
// Membuka dialog konfirmasi hapus
const askDelete = (record) => {
    confirmState.value = { show: true, record, processing: false };
};

// Menutup dialog konfirmasi
const closeConfirm = () => {
    if (confirmState.value.processing) return;
    confirmState.value = { show: false, record: null, processing: false };
};

// Mengeksekusi penghapusan dengan optimistic locking
const confirmDelete = () => {
    if (!confirmState.value.record) return;

    confirmState.value.processing = true;
    router.delete(route('order-edks.destroy', confirmState.value.record.id), {
        data: {
            updated_at: confirmState.value.record.updated_at_token,
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
    <Head title="Order EDK" />

    <AppLayout title="Order EDK">
        <!-- ============================================================ -->
        <!-- HEADER HALAMAN - Judul, deskripsi, dan tombol tambah         -->
        <!-- ============================================================ -->
        <template #header>
            <PageHeader
                title="Order EDK"
                description="Monitoring progres pekerjaan EDK."
            >
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
        <!-- KARTU STATISTIK - Ringkasan data EDK (4 kolom)               -->
        <!-- ============================================================ -->
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
                v-for="stat in stats"
                :key="stat.key"
                :label="stat.label"
                :value="stat.value"
                :tone="stat.tone"
                context="Rekap EDK"
            >
                <template #icon>
                    <FileCheck class="h-5 w-5" />
                </template>
            </StatCard>
        </div>

        <!-- ============================================================ -->
        <!-- BAGIAN FILTER                                                -->
        <!-- ============================================================ -->
        <FilterBar>
            <!-- Pencarian berdasarkan referensi EDK atau nama pelanggan -->
            <div>
                <label for="search" class="text-sm font-medium text-content-secondary">Search</label>
                <input
                    id="search"
                    v-model="filterForm.search"
                    type="search"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                    placeholder="Referensi EDK, pelanggan"
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
            <!-- Filter AM - tidak ditampilkan untuk role account_manager -->
            <div v-if="currentUser.role !== 'account_manager'">
                <label for="account_manager_id" class="text-sm font-medium text-content-secondary">Account Manager</label>
                <select id="account_manager_id" v-model="filterForm.account_manager_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                    <option value="">Semua AM</option>
                    <option v-for="accountManager in accountManagerOptions" :key="accountManager.id" :value="accountManager.id">
                        {{ accountManager.name }}
                    </option>
                </select>
            </div>
            <!-- Filter status EDK -->
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
        <!-- TABEL DATA - Daftar Order EDK                                -->
        <!-- ============================================================ -->
        <DataTable
            :columns="columns"
            :rows="orderEdks.data"
            :meta="orderEdks"
            :filters="filters"
            route-name="order-edks.index"
            :sort="filters.sort"
            :direction="filters.direction"
            empty-title="Belum ada Order EDK"
            empty-description="Data akan tampil saat sudah ditambahkan atau filter disesuaikan."
            @sort="sortBy"
        >
            <!-- Kolom referensi EDK ditampilkan bold -->
            <template #cell-edk_reference="{ row }">
                <span class="font-semibold text-telkom-black">{{ row.edk_reference }}</span>
            </template>
            <template #cell-customer_name="{ row }">
                {{ row.customer_name || '-' }}
            </template>
            <!-- Status ditampilkan sebagai badge berwarna -->
            <template #cell-status="{ row }">
                <StatusBadge :variant="row.status_tone">{{ row.status_label }}</StatusBadge>
            </template>
            <!-- Kolom aksi: edit dan hapus -->
            <template #cell-actions="{ row }">
                <div v-if="canUpdate || canDelete" class="flex justify-end gap-1">
                    <button
                        v-if="canUpdate"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-content-secondary transition-all hover:bg-telkom-grey-soft hover:text-telkom-black active:scale-95"
                        aria-label="Ubah Order EDK"
                        @click="openEdit(row)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button
                        v-if="canDelete"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-status-danger transition-all hover:bg-status-danger-soft active:scale-95"
                        aria-label="Hapus Order EDK"
                        @click="askDelete(row)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
                <span v-else>-</span>
            </template>
        </DataTable>

        <!-- ============================================================ -->
        <!-- MODAL FORM CREATE/EDIT ORDER EDK                             -->
        <!-- ============================================================ -->
        <Modal :show="showModal" max-width="2xl" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <!-- Header modal dinamis -->
                <div class="border-b border-border pb-4">
                    <h2 class="text-lg font-semibold text-telkom-black">
                        {{ isEditing ? 'Ubah Order EDK' : 'Tambah Order EDK' }}
                    </h2>
                </div>

                <!-- Form input grid 2 kolom -->
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <!-- Input referensi EDK (identitas utama) -->
                    <div>
                        <label for="edk_reference" class="text-sm font-medium text-content-secondary">Referensi EDK</label>
                        <input id="edk_reference" v-model="form.edk_reference" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.edk_reference" />
                    </div>
                    <div>
                        <label for="customer_name" class="text-sm font-medium text-content-secondary">Nama Pelanggan</label>
                        <input id="customer_name" v-model="form.customer_name" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.customer_name" />
                    </div>
                    <!-- Dropdown inputer - hanya super admin yang bisa memilih -->
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
                    <!-- Dropdown status dengan filter berdasarkan role -->
                    <div>
                        <label for="form_status" class="text-sm font-medium text-content-secondary">Status</label>
                        <select id="form_status" v-model="form.status" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                            <option v-for="status in formStatusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                        <FormError :message="form.errors.status" />
                    </div>
                    <div>
                        <label for="form_period_month" class="text-sm font-medium text-content-secondary">Periode</label>
                        <input id="form_period_month" v-model="form.period_month" type="month" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.period_month" />
                    </div>
                    <!-- Catatan tambahan -->
                    <div class="md:col-span-2">
                        <label for="notes" class="text-sm font-medium text-content-secondary">Catatan</label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="mt-1 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.notes" />
                        <!-- Error optimistic locking -->
                        <FormError :message="form.errors.updated_at" />
                    </div>
                </div>

                <!-- Tombol aksi: Batal dan Simpan -->
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="h-10 rounded-panel border border-border px-4 text-sm font-medium text-content-secondary hover:bg-telkom-grey-soft" :disabled="form.processing" @click="closeModal">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white transition-all hover:bg-telkom-red-dark active:scale-95 disabled:pointer-events-none disabled:opacity-70" :disabled="form.processing">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- ============================================================ -->
        <!-- DIALOG KONFIRMASI HAPUS ORDER EDK                            -->
        <!-- ============================================================ -->
        <ConfirmDialog
            :show="confirmState.show"
            title="Hapus Order EDK?"
            description="Data akan dihapus dari daftar monitoring."
            confirm-label="Hapus"
            :processing="confirmState.processing"
            destructive
            @close="closeConfirm"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
