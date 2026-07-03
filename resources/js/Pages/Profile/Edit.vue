<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FormError from '@/Components/FormError.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Eye, EyeOff, KeyRound, Save, User } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const previewUrl = ref(user.value?.profile_photo_url ?? null);
const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const profileForm = useForm({
    name: user.value?.name ?? '',
    email: user.value?.email ?? '',
    phone: user.value?.phone ?? '',
    bio: user.value?.bio ?? '',
    profile_photo: null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const choosePhoto = (event) => {
    const [file] = event.target.files;
    profileForm.profile_photo = file ?? null;

    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submitProfile = () => {
    profileForm.post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            profileForm.profile_photo = null;
        },
    });
};

const submitPassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};
</script>

<template>
    <Head title="Profil Pengguna" />

    <AppLayout title="Profil Pengguna">
        <template #header>
            <PageHeader
                title="Profil Pengguna"
                description="Perbarui informasi akun dan password Anda dari satu halaman."
            />
        </template>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6">
                <section class="rounded-panel border border-border bg-surface p-5 shadow-soft">
                    <div class="mb-5 flex items-center gap-2 border-b border-border pb-4">
                        <User class="h-5 w-5 text-telkom-red" />
                        <h2 class="text-lg font-semibold text-telkom-black">Informasi Profil</h2>
                    </div>

                    <form class="space-y-5" @submit.prevent="submitProfile">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <img
                                v-if="previewUrl"
                                :src="previewUrl"
                                :alt="user.name"
                                class="h-20 w-20 rounded-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-20 w-20 items-center justify-center rounded-full bg-primary-soft text-xl font-semibold text-telkom-red"
                            >
                                {{ user.name.charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <label for="profile_photo" class="inline-flex h-9 cursor-pointer items-center rounded-panel border border-border px-3 text-sm font-medium text-content-secondary hover:bg-telkom-grey-soft">
                                    Pilih Foto
                                </label>
                                <input
                                    id="profile_photo"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="sr-only"
                                    @change="choosePhoto"
                                />
                                <p class="mt-2 text-xs text-content-muted">JPG, JPEG, PNG, atau WEBP. Maksimal 2MB.</p>
                                <FormError :message="profileForm.errors.profile_photo" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="name" class="text-sm font-medium text-content-secondary">Nama</label>
                                <input id="name" v-model="profileForm.name" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                                <FormError :message="profileForm.errors.name" />
                            </div>
                            <div>
                                <label for="email" class="text-sm font-medium text-content-secondary">Email</label>
                                <input id="email" v-model="profileForm.email" type="email" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                                <FormError :message="profileForm.errors.email" />
                            </div>
                            <div>
                                <label for="phone" class="text-sm font-medium text-content-secondary">No. Telepon</label>
                                <input id="phone" v-model="profileForm.phone" type="text" class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                                <FormError :message="profileForm.errors.phone" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <span class="text-sm font-medium text-content-secondary">Informasi Akun</span>
                                <div class="flex h-10 items-center gap-2">
                                    <StatusBadge :variant="user.role === 'super_admin' ? 'primary' : user.role === 'admin_inputer' ? 'info' : 'neutral'">
                                        {{ user.role_label }}
                                    </StatusBadge>
                                    <StatusBadge :variant="user.is_active ? 'success' : 'neutral'">
                                        {{ user.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </StatusBadge>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label for="bio" class="text-sm font-medium text-content-secondary">Biodata</label>
                                <textarea id="bio" v-model="profileForm.bio" rows="4" class="mt-1 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red" />
                                <FormError :message="profileForm.errors.bio" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex h-10 items-center gap-2 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white hover:bg-telkom-red-dark disabled:opacity-70"
                                :disabled="profileForm.processing"
                            >
                                <Save class="h-4 w-4" />
                                Simpan Profil
                            </button>
                        </div>
                    </form>
                </section>

                <section class="rounded-panel border border-border bg-surface p-5 shadow-soft">
                    <div class="mb-5 flex items-center gap-2 border-b border-border pb-4">
                        <KeyRound class="h-5 w-5 text-telkom-red" />
                        <h2 class="text-lg font-semibold text-telkom-black">Ubah Password</h2>
                    </div>

                    <form class="space-y-4" @submit.prevent="submitPassword">
                        <div>
                            <label for="current_password" class="text-sm font-medium text-content-secondary">Password Saat Ini</label>
                            <div class="relative mt-1">
                                <input
                                    id="current_password"
                                    v-model="passwordForm.current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    class="h-10 w-full rounded-panel border-border pr-10 text-sm focus:border-telkom-red focus:ring-telkom-red"
                                />
                                <button type="button" class="absolute inset-y-0 right-0 inline-flex w-10 items-center justify-center text-content-muted hover:text-telkom-red" @click="showCurrentPassword = !showCurrentPassword">
                                    <EyeOff v-if="showCurrentPassword" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <FormError :message="passwordForm.errors.current_password" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="password" class="text-sm font-medium text-content-secondary">Password Baru</label>
                                <div class="relative mt-1">
                                    <input
                                        id="password"
                                        v-model="passwordForm.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="h-10 w-full rounded-panel border-border pr-10 text-sm focus:border-telkom-red focus:ring-telkom-red"
                                    />
                                    <button type="button" class="absolute inset-y-0 right-0 inline-flex w-10 items-center justify-center text-content-muted hover:text-telkom-red" @click="showPassword = !showPassword">
                                        <EyeOff v-if="showPassword" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <FormError :message="passwordForm.errors.password" />
                            </div>
                            <div>
                                <label for="password_confirmation" class="text-sm font-medium text-content-secondary">Konfirmasi Password Baru</label>
                                <div class="relative mt-1">
                                    <input
                                        id="password_confirmation"
                                        v-model="passwordForm.password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        class="h-10 w-full rounded-panel border-border pr-10 text-sm focus:border-telkom-red focus:ring-telkom-red"
                                    />
                                    <button type="button" class="absolute inset-y-0 right-0 inline-flex w-10 items-center justify-center text-content-muted hover:text-telkom-red" @click="showPasswordConfirmation = !showPasswordConfirmation">
                                        <EyeOff v-if="showPasswordConfirmation" class="h-4 w-4" />
                                        <Eye v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <FormError :message="passwordForm.errors.password_confirmation" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex h-10 items-center gap-2 rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white hover:bg-telkom-red-dark disabled:opacity-70"
                                :disabled="passwordForm.processing"
                            >
                                <Save class="h-4 w-4" />
                                Simpan Password
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <aside class="rounded-panel border border-border bg-surface p-5 shadow-soft lg:sticky lg:top-20 lg:self-start">
                <h2 class="text-base font-semibold text-telkom-black">Ringkasan Akun</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-content-muted">Nama</dt>
                        <dd class="font-medium text-telkom-black">{{ user.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-content-muted">Email</dt>
                        <dd class="break-all font-medium text-telkom-black">{{ user.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-content-muted">Peran</dt>
                        <dd class="font-medium text-telkom-black">{{ user.role_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-content-muted">Status</dt>
                        <dd class="font-medium text-telkom-black">{{ user.is_active ? 'Aktif' : 'Tidak Aktif' }}</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </AppLayout>
</template>
