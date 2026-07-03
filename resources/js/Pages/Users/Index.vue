<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import DataTable from '@/Components/DataTable.vue';
import FilterBar from '@/Components/FilterBar.vue';
import FormError from '@/Components/FormError.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Eye, EyeOff, Pencil, Plus, Power, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const showModal = ref(false);
const showPassword = ref(false);
const editingUser = ref(null);
const confirmState = ref({
    show: false,
    user: null,
    type: '',
});

const filterForm = ref({ ...props.filters });
let filterTimer = null;

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'account_manager',
    phone: '',
    bio: '',
    is_active: true,
});

const columns = [
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role', label: 'Peran', sortable: true },
    { key: 'phone', label: 'No. Telepon' },
    { key: 'is_active', label: 'Status Akun', sortable: true },
    { key: 'created_at', label: 'Dibuat Pada', sortable: true },
    { key: 'actions', label: 'Aksi', headerClass: 'text-right', class: 'text-right' },
];

const currentUser = computed(() => page.props.auth.user);
const isEditing = computed(() => Boolean(editingUser.value));

watch(
    filterForm,
    () => {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(() => applyFilters(), 350);
    },
    { deep: true },
);

const applyFilters = (overrides = {}) => {
    router.get(route('users.index'), {
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
    editingUser.value = null;
    showPassword.value = false;
    form.reset();
    form.clearErrors();
    form.role = 'account_manager';
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (user) => {
    editingUser.value = user;
    showPassword.value = false;
    form.clearErrors();
    form.defaults({
        name: user.name,
        email: user.email,
        password: '',
        role: user.role,
        phone: user.phone ?? '',
        bio: user.bio ?? '',
        is_active: Boolean(user.is_active),
    });
    form.reset();
    showModal.value = true;
};

const closeModal = () => {
    if (form.processing) return;

    showModal.value = false;
    editingUser.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (isEditing.value) {
        form.put(route('users.update', editingUser.value.id), options);
        return;
    }

    form.post(route('users.store'), options);
};

const askToggle = (user) => {
    confirmState.value = {
        show: true,
        user,
        type: 'toggle',
    };
};

const askDelete = (user) => {
    confirmState.value = {
        show: true,
        user,
        type: 'delete',
    };
};

const closeConfirm = () => {
    if (form.processing) return;
    confirmState.value = { show: false, user: null, type: '' };
};

const confirmAction = () => {
    const { user, type } = confirmState.value;
    if (!user) return;

    if (type === 'toggle') {
        router.patch(route('users.toggle-active', user.id), {
            is_active: !user.is_active,
        }, {
            preserveScroll: true,
            onFinish: closeConfirm,
        });
        return;
    }

    router.delete(route('users.destroy', user.id), {
        preserveScroll: true,
        onFinish: closeConfirm,
    });
};

const roleBadgeVariant = (role) => ({
    super_admin: 'primary',
    admin_inputer: 'info',
    account_manager: 'neutral',
}[role] ?? 'neutral');
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AppLayout title="Manajemen Pengguna">
        <template #header>
            <PageHeader
                title="Manajemen Pengguna"
                description="Kelola akun, peran, nomor telepon, dan status aktif pengguna sistem."
            >
                <template #actions>
                    <button
                        type="button"
                        class="inline-flex h-10 items-center gap-2 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white shadow-soft transition hover:bg-telkom-red-dark focus:outline-none focus:ring-2 focus:ring-telkom-red focus:ring-offset-2"
                        @click="openCreate"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Pengguna
                    </button>
                </template>
            </PageHeader>
        </template>

        <FilterBar>
            <div>
                <label for="search" class="text-sm font-medium text-content-secondary">Search</label>
                <input
                    id="search"
                    v-model="filterForm.search"
                    type="search"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                    placeholder="Nama, email, telepon"
                />
            </div>
            <div>
                <label for="role" class="text-sm font-medium text-content-secondary">Peran</label>
                <select
                    id="role"
                    v-model="filterForm.role"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                >
                    <option value="">Semua Peran</option>
                    <option v-for="role in roles" :key="role.value" :value="role.value">
                        {{ role.label }}
                    </option>
                </select>
            </div>
            <div>
                <label for="status" class="text-sm font-medium text-content-secondary">Status</label>
                <select
                    id="status"
                    v-model="filterForm.status"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                >
                    <option value="">Semua Status</option>
                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                        {{ status.label }}
                    </option>
                </select>
            </div>
        </FilterBar>

        <DataTable
            :columns="columns"
            :rows="users.data"
            :meta="users"
            :filters="filters"
            route-name="users.index"
            :sort="filters.sort"
            :direction="filters.direction"
            empty-title="Belum ada pengguna"
            empty-description="Tambahkan pengguna untuk mulai mengatur akses sistem."
            @sort="sortBy"
        >
            <template #cell-name="{ row }">
                <div>
                    <p class="font-semibold text-telkom-black">{{ row.name }}</p>
                    <p v-if="row.has_operational_records" class="text-xs text-content-muted">Memiliki data operasional</p>
                </div>
            </template>
            <template #cell-role="{ row }">
                <StatusBadge :variant="roleBadgeVariant(row.role)">
                    {{ row.role_label }}
                </StatusBadge>
            </template>
            <template #cell-phone="{ row }">
                {{ row.phone || '-' }}
            </template>
            <template #cell-is_active="{ row }">
                <StatusBadge :variant="row.is_active ? 'success' : 'neutral'">
                    {{ row.status_label }}
                </StatusBadge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-content-secondary hover:bg-telkom-grey-soft hover:text-telkom-black"
                        aria-label="Ubah pengguna"
                        @click="openEdit(row)"
                    >
                        <Pencil class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-content-secondary hover:bg-telkom-grey-soft hover:text-telkom-black disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="row.id === currentUser.id"
                        :aria-label="row.is_active ? 'Nonaktifkan akun' : 'Aktifkan akun'"
                        @click="askToggle(row)"
                    >
                        <Power class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-panel text-status-danger hover:bg-status-danger-soft disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="row.id === currentUser.id"
                        aria-label="Hapus pengguna"
                        @click="askDelete(row)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </DataTable>

        <Modal :show="showModal" max-width="2xl" @close="closeModal">
            <form class="p-6" @submit.prevent="submit">
                <div class="border-b border-border pb-4">
                    <h2 class="text-lg font-semibold text-telkom-black">
                        {{ isEditing ? 'Ubah Pengguna' : 'Tambah Pengguna' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        Isi data akun dan peran pengguna.
                    </p>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="name" class="text-sm font-medium text-content-secondary">Nama</label>
                        <input id="name" v-model="form.name" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.name" />
                    </div>
                    <div>
                        <label for="email" class="text-sm font-medium text-content-secondary">Email</label>
                        <input id="email" v-model="form.email" type="email" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.email" />
                    </div>
                    <div>
                        <label for="role" class="text-sm font-medium text-content-secondary">Peran</label>
                        <select id="role" v-model="form.role" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red">
                            <option v-for="role in roles" :key="role.value" :value="role.value">
                                {{ role.label }}
                            </option>
                        </select>
                        <FormError :message="form.errors.role" />
                    </div>
                    <div>
                        <label for="phone" class="text-sm font-medium text-content-secondary">No. Telepon</label>
                        <input id="phone" v-model="form.phone" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.phone" />
                    </div>
                    <div>
                        <label for="password" class="text-sm font-medium text-content-secondary">
                            Password {{ isEditing ? '(opsional)' : '' }}
                        </label>
                        <div class="relative mt-1">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="h-10 w-full rounded-panel border-border pr-10 text-sm focus:border-telkom-red focus:ring-telkom-red"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 inline-flex w-10 items-center justify-center text-content-muted hover:text-telkom-red"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <FormError :message="form.errors.password" />
                    </div>
                    <div>
                        <label for="is_active" class="text-sm font-medium text-content-secondary">Status Akun</label>
                        <label class="mt-2 flex h-10 items-center gap-3 rounded-panel border border-border px-3 text-sm">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-border text-telkom-red focus:ring-telkom-red"
                            />
                            Aktif
                        </label>
                        <FormError :message="form.errors.is_active" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="bio" class="text-sm font-medium text-content-secondary">Biodata</label>
                        <textarea id="bio" v-model="form.bio" rows="3" class="mt-1 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                        <FormError :message="form.errors.bio" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        class="h-10 rounded-panel border border-border px-4 text-sm font-medium text-content-secondary hover:bg-telkom-grey-soft"
                        :disabled="form.processing"
                        @click="closeModal"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="h-10 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white hover:bg-telkom-red-dark disabled:opacity-70"
                        :disabled="form.processing"
                    >
                        Simpan
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            :show="confirmState.show"
            :title="confirmState.type === 'delete' ? 'Hapus pengguna?' : 'Ubah status akun?'"
            :description="confirmState.type === 'delete'
                ? 'Pengguna akan dihapus secara soft delete dan tidak tampil pada daftar aktif.'
                : 'Status aktif pengguna akan diperbarui.'"
            :confirm-label="confirmState.type === 'delete' ? 'Hapus' : 'Simpan'"
            :destructive="confirmState.type === 'delete'"
            @close="closeConfirm"
            @confirm="confirmAction"
        />
    </AppLayout>
</template>
