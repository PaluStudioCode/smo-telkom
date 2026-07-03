<script setup>
defineProps({
    title: {
        type: String,
        default: 'Data belum tersedia',
    },
    description: {
        type: String,
        default: 'Tidak ada data yang sesuai dengan kondisi saat ini.',
    },
});
</script>

<template>
    <div class="flex min-h-48 flex-col items-center justify-center rounded-panel border border-dashed border-border bg-surface px-6 py-10 text-center">
        <div v-if="$slots.icon" class="mb-3 text-content-muted">
            <slot name="icon" />
        </div>
        <h3 class="text-base font-semibold text-telkom-black">{{ title }}</h3>
        <p class="mt-1 max-w-md text-sm text-content-secondary">{{ description }}</p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
