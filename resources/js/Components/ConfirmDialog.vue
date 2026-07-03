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
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
            <button
                type="button"
                class="absolute inset-0 bg-telkom-black/40"
                aria-label="Tutup dialog"
                @click="emit('close')"
            />
            <div class="relative w-full max-w-md rounded-panel border border-border bg-surface p-5 shadow-panel">
                <h2 class="text-lg font-semibold text-telkom-black">{{ title }}</h2>
                <p v-if="description" class="mt-2 text-sm text-content-secondary">{{ description }}</p>
                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        class="h-9 rounded-panel border border-border px-4 text-sm font-medium text-content-secondary hover:bg-telkom-grey-soft"
                        :disabled="processing"
                        @click="emit('close')"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="h-9 rounded-panel px-4 text-sm font-semibold text-white disabled:opacity-70"
                        :class="destructive ? 'bg-status-danger hover:bg-status-danger/90' : 'bg-telkom-red hover:bg-telkom-red-dark'"
                        :disabled="processing"
                        @click="emit('confirm')"
                    >
                        {{ confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
