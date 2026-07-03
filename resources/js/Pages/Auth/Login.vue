<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Login" />

        <div class="rounded-panel border border-border bg-surface p-6 shadow-panel sm:p-8">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-panel bg-telkom-red text-lg font-bold text-white">
                    SMO
                </div>
                <h1 class="text-2xl font-semibold text-telkom-black">
                    Sistem Monitoring Operasional
                </h1>
                <p class="mt-2 text-sm text-content-secondary">
                    Divisi Government Service Regional Sulbagteng
                </p>
            </div>

            <div
                v-if="status"
                class="mb-5 rounded-panel border border-status-success/20 bg-status-success-soft px-4 py-3 text-sm font-medium text-status-success-foreground"
            >
                {{ status }}
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <InputLabel for="email" value="Email" />

                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-2 block h-10 w-full rounded-panel border-border text-sm shadow-soft focus:border-telkom-red focus:ring-telkom-red"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@telkom.co.id"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="Password" />

                    <div class="relative mt-2">
                        <TextInput
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block h-10 w-full rounded-panel border-border pr-11 text-sm shadow-soft focus:border-telkom-red focus:ring-telkom-red"
                            required
                            autocomplete="current-password"
                        />

                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 inline-flex w-10 items-center justify-center rounded-r-panel text-content-muted transition hover:text-telkom-red focus:outline-none focus:ring-2 focus:ring-telkom-red focus:ring-offset-2"
                            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            @click="showPassword = !showPassword"
                        >
                            <EyeOff v-if="showPassword" class="h-4 w-4" aria-hidden="true" />
                            <Eye v-else class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <button
                    type="submit"
                    class="inline-flex h-10 w-full items-center justify-center rounded-panel bg-telkom-red px-4 text-sm font-semibold text-white shadow-soft transition hover:bg-telkom-red-dark focus:outline-none focus:ring-2 focus:ring-telkom-red focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="form.processing"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="mr-2 h-4 w-4 animate-spin"
                        aria-hidden="true"
                    />
                    Login
                </button>
            </form>
        </div>
    </GuestLayout>
</template>
