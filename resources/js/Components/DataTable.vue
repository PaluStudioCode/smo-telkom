<script setup>
import { ArrowUpDown } from 'lucide-vue-next';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        required: true,
    },
    meta: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    routeName: {
        type: String,
        default: '',
    },
    sort: {
        type: String,
        default: '',
    },
    direction: {
        type: String,
        default: 'asc',
    },
    emptyTitle: {
        type: String,
        default: 'Data belum tersedia',
    },
    emptyDescription: {
        type: String,
        default: 'Tidak ada data yang sesuai dengan filter saat ini.',
    },
});

const emit = defineEmits(['sort']);
</script>

<template>
    <div class="overflow-hidden rounded-panel border border-border bg-surface shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-telkom-red text-left text-xs font-semibold uppercase text-white">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="whitespace-nowrap px-4 py-3"
                            :class="column.headerClass"
                        >
                            <button
                                v-if="column.sortable"
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-telkom-red"
                                @click="emit('sort', column.key)"
                            >
                                {{ column.label }}
                                <ArrowUpDown class="h-3.5 w-3.5" />
                            </button>
                            <span v-else>{{ column.label }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody v-if="rows.length" class="divide-y divide-border bg-surface">
                    <tr v-for="row in rows" :key="row.id" class="transition hover:bg-telkom-grey-soft/70">
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="whitespace-nowrap px-4 py-3 text-content-secondary"
                            :class="column.class"
                        >
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] ?? '-' }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState
            v-if="!rows.length"
            :title="emptyTitle"
            :description="emptyDescription"
        >
            <template v-if="$slots.emptyAction" #action>
                <slot name="emptyAction" />
            </template>
        </EmptyState>

        <Pagination
            v-if="meta && routeName"
            :meta="meta"
            :filters="filters"
            :route-name="routeName"
        />
    </div>
</template>
