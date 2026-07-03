<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});
</script>

<template>
    <AppLayout :title="title">
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </AppLayout>
</template>
