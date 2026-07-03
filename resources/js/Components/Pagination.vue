<script setup>
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    routeName: {
        type: String,
        required: true,
    },
});

const changePerPage = (event) => {
    router.get(route(props.routeName), {
        ...props.filters,
        per_page: Number(event.target.value),
        page: 1,
    }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <div class="flex flex-col gap-3 border-t border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2 text-sm text-content-secondary">
            <span>Baris</span>
            <select
                class="h-9 rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                :value="filters.per_page ?? meta.per_page"
                @change="changePerPage"
            >
                <option :value="10">10</option>
                <option :value="15">15</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
            <span>per halaman</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-for="link in meta.links"
                :key="link.label"
                :href="link.url || '#'"
                preserve-scroll
                preserve-state
                class="inline-flex h-9 min-w-9 items-center justify-center rounded-panel border px-3 text-sm transition"
                :class="[
                    link.active
                        ? 'border-telkom-red bg-telkom-red text-white'
                        : 'border-border bg-surface text-content-secondary hover:bg-telkom-grey-soft',
                    !link.url ? 'pointer-events-none opacity-40' : '',
                ]"
                v-html="link.label"
            />
        </div>
    </div>
</template>
