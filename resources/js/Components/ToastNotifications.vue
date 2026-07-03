<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const toasts = ref([]);

const flash = computed(() => page.props.flash ?? {});
const tones = {
    success: 'border-status-success/20 bg-status-success-soft text-status-success-foreground',
    error: 'border-status-danger/20 bg-status-danger-soft text-status-danger-foreground',
    warning: 'border-status-warning/20 bg-status-warning-soft text-status-warning-foreground',
    info: 'border-status-info/20 bg-status-info-soft text-status-info-foreground',
};

watch(
    flash,
    (value) => {
        Object.entries(value).forEach(([type, message]) => {
            if (!message) return;

            const id = `${Date.now()}-${type}`;
            toasts.value.push({ id, type, message });

            window.setTimeout(() => {
                toasts.value = toasts.value.filter((toast) => toast.id !== id);
            }, 4200);
        });
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div class="fixed right-4 top-4 z-[60] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2">
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="rounded-panel border px-4 py-3 text-sm font-medium shadow-panel"
            :class="tones[toast.type] ?? tones.info"
        >
            {{ toast.message }}
        </div>
    </div>
</template>
