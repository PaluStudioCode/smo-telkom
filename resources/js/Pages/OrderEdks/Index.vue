<script setup>
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
import { FileCheck, Pencil, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    orderEdks: {
        type: Object,
        required: true,
    },
    stats: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
    inputerOptions: {
        type: Array,
        required: true,
    },
    accountManagerOptions: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const permissions = computed(() => page.props.auth.permissions);
const isSuperAdmin = computed(() => currentUser.value?.role === 'super_admin');
const canCreate = computed(() => permissions.value['order_edk.create']);
const canUpdate = computed(() => permissions.value['order_edk.update']);
const canDelete = computed(() => permissions.value['order_edk.delete']);
const finalStatuses = ['complete', 'tidak_lanjut'];
const formStatusOptions = computed(() => {
    const allowed = isSuperAdmin.value
        ? props.statusOptions
        : props.statusOptions.filter((option) => !finalStatuses.includes(option.value));
    const current = props.statusOptions.find((option) => option.value === editingRecord.value?.status);

    if (current && !allowed.some((option) => option.value === current.value)) {
        return [current, ...allowed];
    }

    return allowed;
});

const showModal = ref(false);
const editingRecord = ref(null);
const confirmState = ref({
    show: false,
    record: null,
    processing: false,
});
const filterForm = ref({ ...props.filters });
let filterTimer = null;

const currentMonth = () => {
    const date = new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
};

const defaultFormValues = () => ({
    edk_reference: '',
    customer_name: '',
    inputer_id: isSuperAdmin.value ? '' : currentUser.value?.id,
    account_manager_id: '',
    status: 'belum_input',
    period_month: currentMonth(),
    source_system: 'Dashboard NCX',
    notes: '',
    updated_at: '',
});

const form = useForm(defaultFormValues());

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

const isEditing = computed(() => Boolean(editingRecord.value));

watch(
    filterForm,
    () => {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(() => applyFilters(), 350);
    },
    { deep: true },
);

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

const sortBy = (key) => {
    const direction = props.filters.sort === key && props.filters.direction === 'asc' ? 'desc' : 'asc';
    filterForm.value.sort = key;
    filterForm.value.direction = direction;
    applyFilters({ sort: key, direction });
};

const openCreate = () => {
    editingRecord.value = null;
    form.defaults(defaultFormValues());
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

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
        updated_at: record.updated_at_token,
    });
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    if (form.processing) return;

    showModal.value = false;
    editingRecord.value = null;
    form.reset();
    form.clearErrors();
};

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

const askDelete = (record) => {
    confirmState.value = { show: true, record, processing: false };
};

const closeConfirm = () => {
    if (confirmState.value.processing) return;
    confirmState.value = { show: false, record: null, processing: false };
};

const confirmDelete = () => {
    if (!confirmState.value.record) return;

    confirmState.value.processing = true;
    router.delete(route('order-edks.destroy', confirmState.value.record.id), {
        data: {
            updated_at: confirmState.value.record.updated_at_token,
        },
        preserveScroll: true,
        onFinish: closeConfirm,
    });
};
</script>

<template>
    <Head title="Order EDK" />

    <AppLayout title="Order EDK">
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

        <FilterBar>
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
            <div v-if="isSuperAdmin">
                <label for="inputer_id" class="text-sm font-medium text-content-secondary">Inputer</label>
                <select id="inputer_id" v-model="filterForm.inputer_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                    <option value="">Semua Inputer</option>
                    <option v-for="inputer in inputerOptions" :key="inputer.id" :value="inputer.id">
                        {{ inputer.name }}
                    </option>
                </select>
            </div>
            <div v-if="currentUser.role !== 'account_manager'">
                <label for="account_manager_id" class="text-sm font-medium text-content-secondary">Account Manager</label>
                <select id="account_manager_id" v-model="filterForm.account_manager_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                    <option value="">Semua AM</option>
                    <option v-for="accountManager in accountManagerOptions" :key="accountManager.id" :value="accountManager.id">
                        {{ accountManager.name }}
                    </option>
                </select>
            </div>
            <div>
                <label for="status" class="text-sm font-medium text-content-secondary">Status</label>
                <select id="status" v-model="filterForm.status" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                    <option value="">Semua Status</option>
                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>
            </div>
            <div>
                <label for="period_month" class="text-sm font-medium text-content-secondary">Periode</label>
                <input id="period_month" v-model="filterForm.period_month" type="month" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
            </div>
        </FilterBar>

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
            <template #cell-edk_reference="{ row }">
                <span class="font-semibold text-telkom-black">{{ row.edk_reference }}</span>
            </template>
            <template #cell-customer_name="{ row }">
                {{ row.customer_name || '-' }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :variant="row.status_tone">{{ row.status_label }}</StatusBadge>
            </template>
            <template #cell-actions="{ row }">
                <div v-if="canUpdate || canDelete" class="flex justify-end gap-1">
                    <button
                        v-if="canUpdate"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-content-secondary hover:bg-telkom-grey-soft hover:text-telkom-black"
                        aria-label="Ubah Order EDK"
                        @click="openEdit(row)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button
                        v-if="canDelete"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-status-danger hover:bg-status-danger-soft"
                        aria-label="Hapus Order EDK"
                        @click="askDelete(row)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
                <span v-else>-</span>
            </template>
        </DataTable>

        <Modal :show="showModal" max-width="2xl" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <div class="border-b border-border pb-4">
                    <h2 class="text-lg font-semibold text-telkom-black">
                        {{ isEditing ? 'Ubah Order EDK' : 'Tambah Order EDK' }}
                    </h2>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
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
                    <div class="md:col-span-2">
                        <label for="notes" class="text-sm font-medium text-content-secondary">Catatan</label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="mt-1 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.notes" />
                        <FormError :message="form.errors.updated_at" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="h-10 rounded-panel border border-border px-4 text-sm font-medium text-content-secondary hover:bg-telkom-grey-soft" :disabled="form.processing" @click="closeModal">
                        Batal
                    </button>
                    <button type="submit" class="h-10 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white hover:bg-telkom-red-dark disabled:opacity-70" :disabled="form.processing">
                        Simpan
                    </button>
                </div>
            </form>
        </Modal>

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
