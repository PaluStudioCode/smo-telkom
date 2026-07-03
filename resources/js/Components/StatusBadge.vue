<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'neutral',
    },
});

const classes = computed(() => {
    const variants = {
        primary: 'bg-primary-soft text-telkom-red border-telkom-red/20',
        success: 'bg-status-success-soft text-status-success-foreground border-status-success/20',
        warning: 'bg-status-warning-soft text-status-warning-foreground border-status-warning/20',
        danger: 'bg-status-danger-soft text-status-danger-foreground border-status-danger/20',
        info: 'bg-status-info-soft text-status-info-foreground border-status-info/20',
        neutral: 'bg-status-neutral-soft text-status-neutral-foreground border-status-neutral/20',
    };

    return variants[props.variant] ?? variants.neutral;
});
</script>

<template>
    <span
        class="inline-flex h-6 items-center rounded-md border px-2 text-xs font-medium"
        :class="classes"
    >
        <slot />
    </span>
</template>
