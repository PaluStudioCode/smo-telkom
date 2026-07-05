<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi aksi',
    },
    description: {
        type: String,
        default: '',
    },
    confirmLabel: {
        type: String,
        default: 'Konfirmasi',
    },
    processing: {
        type: Boolean,
        default: false,
    },
    destructive: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
                <!-- Overlay -->
                <button
                    type="button"
                    class="absolute inset-0 bg-telkom-black/40 transition-opacity"
                    aria-label="Tutup dialog"
                    @click="emit('close')"
                />

                <!-- Panel -->
                <Transition
                    enter-active-class="duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                    appear
                >
                    <div
                        v-if="show"
                        class="relative w-full max-w-md rounded-panel border border-border bg-surface p-5 shadow-panel"
                    >
                        <h2 class="text-lg font-semibold text-telkom-black">{{ title }}</h2>
                        <p v-if="description" class="mt-2 text-sm text-content-secondary">{{ description }}</p>
                        <div class="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                class="h-9 rounded-panel border border-border px-4 text-sm font-medium text-content-secondary transition-all duration-200 hover:bg-telkom-grey-soft hover:shadow-sm active:scale-[0.97] disabled:opacity-50 disabled:pointer-events-none"
                                :disabled="processing"
                                @click="emit('close')"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-9 items-center gap-2 rounded-panel px-4 text-sm font-semibold text-white transition-all duration-200 active:scale-[0.97] disabled:opacity-70 disabled:pointer-events-none"
                                :class="destructive ? 'bg-status-danger hover:bg-status-danger/90 hover:shadow-md hover:shadow-status-danger/25' : 'bg-telkom-red hover:bg-telkom-red-dark hover:shadow-md hover:shadow-telkom-red/25'"
                                :disabled="processing"
                                @click="emit('confirm')"
                            >
                                <svg
                                    v-if="processing"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                {{ processing ? 'Memproses...' : confirmLabel }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
