<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    context: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'primary',
    },
});
</script>

<template>
    <div class="rounded-panel border border-border bg-surface p-4 shadow-soft">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-content-secondary">{{ label }}</p>
                <p class="mt-2 text-3xl font-semibold text-telkom-black">{{ value }}</p>
                <p v-if="context" class="mt-1 text-xs text-content-muted">{{ context }}</p>
            </div>
            <div
                class="flex h-10 w-10 items-center justify-center rounded-panel"
                :class="{
                    'bg-primary-soft text-telkom-red': tone === 'primary',
                    'bg-status-success-soft text-status-success': tone === 'success',
                    'bg-status-warning-soft text-status-warning': tone === 'warning',
                    'bg-status-danger-soft text-status-danger': tone === 'danger',
                    'bg-status-info-soft text-status-info': tone === 'info',
                    'bg-status-neutral-soft text-status-neutral': tone === 'neutral',
                }"
            >
                <slot name="icon" />
            </div>
        </div>
    </div>
</template>
