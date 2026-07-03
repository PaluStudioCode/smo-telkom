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
import { Check, CheckCircle, Pencil, Plus, RotateCcw, Trash2, X } from 'lucide-vue-next';

const props = defineProps({
    completionRecords: {
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
    approvalStatusOptions: {
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
    orderStatusOptions: {
        type: Array,
        required: true,
    },
    orderEdkOptions: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const permissions = computed(() => page.props.auth.permissions);
const isSuperAdmin = computed(() => currentUser.value?.role === 'super_admin');
const canCreate = computed(() => permissions.value['complete.create']);
const canUpdate = computed(() => permissions.value['complete.update']);
const canDelete = computed(() => permissions.value['complete.delete']);
const canApprove = computed(() => permissions.value['complete.approve']
    || permissions.value['complete.reject']
    || permissions.value['complete.request_revision']);

const showModal = ref(false);
const editingRecord = ref(null);
const confirmState = ref({
    show: false,
    record: null,
    processing: false,
});
const approvalState = ref({
    show: false,
    record: null,
});
const filterForm = ref({ ...props.filters });
let filterTimer = null;

const currentMonth = () => {
    const date = new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
};

const defaultFormValues = () => ({
    completion_number: '',
    order_status_id: '',
    order_edk_id: '',
    inputer_id: isSuperAdmin.value ? '' : currentUser.value?.id,
    account_manager_id: '',
    approval_status: 'menunggu_persetujuan',
    completed_at: '',
    revision_note: '',
    period_month: currentMonth(),
    notes: '',
    updated_at: '',
});

const form = useForm(defaultFormValues());
const approvalForm = useForm({
    approval_status: 'disetujui',
    revision_note: '',
    updated_at: '',
});

const columns = [
    { key: 'completion_number', label: 'Nomor Complete', sortable: true },
    { key: 'inputer_name', label: 'Inputer' },
    { key: 'account_manager_name', label: 'Account Manager' },
    { key: 'approval_status', label: 'Status Persetujuan', sortable: true },
    { key: 'completed_at', label: 'Tanggal Complete', sortable: true },
    { key: 'revision_note', label: 'Catatan Revisi', class: 'max-w-xs whitespace-normal' },
    { key: 'period_month', label: 'Periode', sortable: true },
    { key: 'updated_at', label: 'Update Terakhir', sortable: true },
    { key: 'actions', label: 'Aksi', headerClass: 'text-right', class: 'text-right' },
];

const isEditing = computed(() => Boolean(editingRecord.value));
const approvalTitle = computed(() => {
    const label = props.approvalStatusOptions.find((option) => option.value === approvalForm.approval_status)?.label;
    return label ? `Ubah status ke ${label}?` : 'Ubah status persetujuan?';
});

watch(
    filterForm,
    () => {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(() => applyFilters(), 350);
    },
    { deep: true },
);

watch(
    () => [form.order_status_id, form.order_edk_id],
    () => syncOwnerFromLinkedOrder(),
);

const applyFilters = (overrides = {}) => {
    router.get(route('completion-records.index'), {
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

const syncOwnerFromLinkedOrder = () => {
    const orderStatus = props.orderStatusOptions.find((option) => option.id === Number(form.order_status_id));
    const orderEdk = props.orderEdkOptions.find((option) => option.id === Number(form.order_edk_id));
    const linkedOrder = orderStatus ?? orderEdk;

    if (!linkedOrder) return;

    form.inputer_id = linkedOrder.inputer_id;
    form.account_manager_id = linkedOrder.account_manager_id;
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
        completion_number: record.completion_number,
        order_status_id: record.order_status_id ?? '',
        order_edk_id: record.order_edk_id ?? '',
        inputer_id: record.inputer_id,
        account_manager_id: record.account_manager_id,
        approval_status: record.approval_status,
        completed_at: record.completed_at ?? '',
        revision_note: record.revision_note ?? '',
        period_month: record.period_month,
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
        form.put(route('completion-records.update', editingRecord.value.id), options);
        return;
    }

    form.post(route('completion-records.store'), options);
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
    router.delete(route('completion-records.destroy', confirmState.value.record.id), {
        data: {
            updated_at: confirmState.value.record.updated_at_token,
        },
        preserveScroll: true,
        onFinish: closeConfirm,
    });
};

const openApproval = (record, status) => {
    approvalState.value = { show: true, record };
    approvalForm.defaults({
        approval_status: status,
        revision_note: status === 'revisi' ? (record.revision_note ?? '') : '',
        updated_at: record.updated_at_token,
    });
    approvalForm.reset();
    approvalForm.clearErrors();
};

const closeApproval = () => {
    if (approvalForm.processing) return;

    approvalState.value = { show: false, record: null };
    approvalForm.reset();
    approvalForm.clearErrors();
};

const submitApproval = () => {
    if (!approvalState.value.record) return;

    approvalForm.patch(route('completion-records.approval', approvalState.value.record.id), {
        preserveScroll: true,
        onSuccess: () => closeApproval(),
    });
};
</script>

<template>
    <Head title="Modul Complete" />

    <AppLayout title="Modul Complete">
        <template #header>
            <PageHeader
                title="Modul Complete"
                description="Monitoring hasil penyelesaian pekerjaan dan status persetujuan."
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
                context="Rekap complete"
            >
                <template #icon>
                    <CheckCircle class="h-5 w-5" />
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
                    placeholder="Nomor complete, order"
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
                <label for="approval_status" class="text-sm font-medium text-content-secondary">Status Persetujuan</label>
                <select id="approval_status" v-model="filterForm.approval_status" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                    <option value="">Semua Status</option>
                    <option v-for="status in approvalStatusOptions" :key="status.value" :value="status.value">
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
            :rows="completionRecords.data"
            :meta="completionRecords"
            :filters="filters"
            route-name="completion-records.index"
            :sort="filters.sort"
            :direction="filters.direction"
            empty-title="Belum ada data Modul Complete"
            empty-description="Data akan tampil saat sudah ditambahkan atau filter disesuaikan."
            @sort="sortBy"
        >
            <template #cell-completion_number="{ row }">
                <div>
                    <p class="font-semibold text-telkom-black">{{ row.completion_number }}</p>
                    <p v-if="row.order_status_label" class="text-xs text-content-muted">{{ row.order_status_label }}</p>
                    <p v-if="row.order_edk_label" class="text-xs text-content-muted">{{ row.order_edk_label }}</p>
                </div>
            </template>
            <template #cell-approval_status="{ row }">
                <StatusBadge :variant="row.approval_status_tone">{{ row.approval_status_label }}</StatusBadge>
            </template>
            <template #cell-completed_at="{ row }">
                {{ row.completed_at || '-' }}
            </template>
            <template #cell-revision_note="{ row }">
                {{ row.revision_note || '-' }}
            </template>
            <template #cell-actions="{ row }">
                <div v-if="canUpdate || canDelete || canApprove" class="flex justify-end gap-1">
                    <button
                        v-if="canUpdate"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-content-secondary hover:bg-telkom-grey-soft hover:text-telkom-black"
                        aria-label="Ubah data complete"
                        @click="openEdit(row)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button
                        v-if="canApprove"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-status-success hover:bg-status-success-soft"
                        aria-label="Setujui"
                        title="Setujui"
                        @click="openApproval(row, 'disetujui')"
                    >
                        <Check class="h-4 w-4" />
                    </button>
                    <button
                        v-if="canApprove"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-status-danger hover:bg-status-danger-soft"
                        aria-label="Tolak"
                        title="Tolak"
                        @click="openApproval(row, 'tidak_disetujui')"
                    >
                        <X class="h-4 w-4" />
                    </button>
                    <button
                        v-if="canApprove"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-status-warning hover:bg-status-warning-soft"
                        aria-label="Minta revisi"
                        title="Minta revisi"
                        @click="openApproval(row, 'revisi')"
                    >
                        <RotateCcw class="h-4 w-4" />
                    </button>
                    <button
                        v-if="canDelete"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-status-danger hover:bg-status-danger-soft"
                        aria-label="Hapus data complete"
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
                        {{ isEditing ? 'Ubah Data Complete' : 'Tambah Data Complete' }}
                    </h2>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="completion_number" class="text-sm font-medium text-content-secondary">Nomor Complete</label>
                        <input id="completion_number" v-model="form.completion_number" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.completion_number" />
                    </div>
                    <div>
                        <label for="completed_at" class="text-sm font-medium text-content-secondary">Tanggal Complete</label>
                        <input id="completed_at" v-model="form.completed_at" type="date" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.completed_at" />
                    </div>
                    <div>
                        <label for="order_status_id" class="text-sm font-medium text-content-secondary">Order Status</label>
                        <select id="order_status_id" v-model="form.order_status_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                            <option value="">Tanpa Order Status</option>
                            <option v-for="orderStatus in orderStatusOptions" :key="orderStatus.id" :value="orderStatus.id">
                                {{ orderStatus.label }}
                            </option>
                        </select>
                        <FormError :message="form.errors.order_status_id" />
                    </div>
                    <div>
                        <label for="order_edk_id" class="text-sm font-medium text-content-secondary">Order EDK</label>
                        <select id="order_edk_id" v-model="form.order_edk_id" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                            <option value="">Tanpa Order EDK</option>
                            <option v-for="orderEdk in orderEdkOptions" :key="orderEdk.id" :value="orderEdk.id">
                                {{ orderEdk.label }}
                            </option>
                        </select>
                        <FormError :message="form.errors.order_edk_id" />
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
                        <label for="form_period_month" class="text-sm font-medium text-content-secondary">Periode</label>
                        <input id="form_period_month" v-model="form.period_month" type="month" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.period_month" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes" class="text-sm font-medium text-content-secondary">Catatan</label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="mt-1 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.notes" />
                        <FormError :message="form.errors.approval_status" />
                        <FormError :message="form.errors.revision_note" />
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

        <Modal :show="approvalState.show" max-width="md" @close="closeApproval">
            <form class="p-6" @submit.prevent="submitApproval">
                <div class="border-b border-border pb-4">
                    <h2 class="text-lg font-semibold text-telkom-black">{{ approvalTitle }}</h2>
                </div>

                <div class="mt-5">
                    <StatusBadge :variant="approvalStatusOptions.find((option) => option.value === approvalForm.approval_status)?.tone">
                        {{ approvalStatusOptions.find((option) => option.value === approvalForm.approval_status)?.label }}
                    </StatusBadge>
                </div>

                <div v-if="approvalForm.approval_status === 'revisi'" class="mt-4">
                    <label for="revision_note" class="text-sm font-medium text-content-secondary">Catatan Revisi</label>
                    <textarea id="revision_note" v-model="approvalForm.revision_note" rows="4" class="mt-1 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                    <FormError :message="approvalForm.errors.revision_note" />
                </div>
                <FormError :message="approvalForm.errors.approval_status" />
                <FormError :message="approvalForm.errors.updated_at" />

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="h-10 rounded-panel border border-border px-4 text-sm font-medium text-content-secondary hover:bg-telkom-grey-soft" :disabled="approvalForm.processing" @click="closeApproval">
                        Batal
                    </button>
                    <button type="submit" class="h-10 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white hover:bg-telkom-red-dark disabled:opacity-70" :disabled="approvalForm.processing">
                        Simpan
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            :show="confirmState.show"
            title="Hapus data complete?"
            description="Data akan dihapus dari daftar monitoring."
            confirm-label="Hapus"
            :processing="confirmState.processing"
            destructive
            @close="closeConfirm"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
