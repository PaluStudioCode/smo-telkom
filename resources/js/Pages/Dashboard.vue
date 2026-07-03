<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FilterBar from '@/Components/FilterBar.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { BarChart3, CheckCircle, ClipboardList, Clock, Database, Donut, XCircle } from 'lucide-vue-next';

const props = defineProps({
    cards: {
        type: Array,
        required: true,
    },
    charts: {
        type: Object,
        required: true,
    },
    recaps: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    inputerOptions: {
        type: Array,
        required: true,
    },
    accountManagerOptions: {
        type: Array,
        required: true,
    },
    isSuperAdmin: {
        type: Boolean,
        required: true,
    },
});

const page = usePage();
const filterForm = ref({ ...props.filters });
let filterTimer = null;
const radius = 46;
const circumference = 2 * Math.PI * radius;
const cardIcons = {
    total_order: ClipboardList,
    pending_baso: Clock,
    complete: CheckCircle,
    failed: XCircle,
    sisa_populasi: Database,
};

const statusItems = computed(() => props.charts.statusComposition.filter((item) => item.value > 0));
const statusTotal = computed(() => statusItems.value.reduce((sum, item) => sum + Number(item.value), 0));
const donutSegments = computed(() => {
    let offset = 0;

    return statusItems.value.map((item) => {
        const dash = (item.value / statusTotal.value) * circumference;
        const segment = {
            ...item,
            dash,
            offset,
        };

        offset += dash;

        return segment;
    });
});

watch(
    filterForm,
    () => {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(() => applyFilters(), 350);
    },
    { deep: true },
);

const applyFilters = () => {
    router.get(route('dashboard'), filterForm.value, {
        preserveState: true,
        replace: true,
    });
};

const barWidth = (item, items) => {
    const maxValue = Math.max(...items.map((bar) => Number(bar.value)), 1);
    const percentage = (Number(item.value) / maxValue) * 100;

    return `${Math.max(percentage, item.value > 0 ? 4 : 0)}%`;
};

const hasBarData = (items) => items.some((item) => Number(item.value) > 0);

const totalLabel = (items) => items.reduce((sum, item) => sum + Number(item.value), 0);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout title="Dashboard">
        <template #header>
            <PageHeader
                title="Dashboard"
                :description="`Ringkasan monitoring operasional untuk ${page.props.auth.user.role_label}.`"
            />
        </template>

        <FilterBar>
            <div>
                <label for="period_month" class="text-sm font-medium text-content-secondary">Periode</label>
                <input
                    id="period_month"
                    v-model="filterForm.period_month"
                    type="month"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                />
            </div>
            <div v-if="isSuperAdmin">
                <label for="inputer_id" class="text-sm font-medium text-content-secondary">Inputer</label>
                <select
                    id="inputer_id"
                    v-model="filterForm.inputer_id"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                >
                    <option value="">Semua Inputer</option>
                    <option v-for="inputer in inputerOptions" :key="inputer.id" :value="inputer.id">
                        {{ inputer.name }}
                    </option>
                </select>
            </div>
            <div v-if="isSuperAdmin">
                <label for="account_manager_id" class="text-sm font-medium text-content-secondary">Account Manager</label>
                <select
                    id="account_manager_id"
                    v-model="filterForm.account_manager_id"
                    class="mt-1 h-10 w-full rounded-panel border-border text-sm focus:border-telkom-red focus:ring-telkom-red"
                >
                    <option value="">Semua AM</option>
                    <option v-for="accountManager in accountManagerOptions" :key="accountManager.id" :value="accountManager.id">
                        {{ accountManager.name }}
                    </option>
                </select>
            </div>
        </FilterBar>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard
                v-for="card in cards"
                :key="card.key"
                :label="card.label"
                :value="card.value"
                :context="card.context"
                :tone="card.tone"
            >
                <template #icon>
                    <component :is="cardIcons[card.key]" class="h-5 w-5" />
                </template>
            </StatCard>
        </div>

        <section class="grid gap-4 xl:grid-cols-2">
            <div class="h-fit rounded-panel border border-border bg-surface p-5 shadow-soft">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-telkom-black">Komposisi Status Operasional</h2>
                        <p class="mt-1 text-sm text-content-secondary">Order Status dan Order EDK pada cakupan filter aktif.</p>
                    </div>
                    <Donut class="h-5 w-5 text-content-muted" />
                </div>

                <div v-if="statusTotal > 0" class="mt-6 flex flex-col gap-6">
                    <div class="relative mx-auto h-52 w-52">
                        <svg class="h-full w-full" viewBox="0 0 120 120" role="img" aria-label="Komposisi status operasional">
                            <circle
                                cx="60"
                                cy="60"
                                :r="radius"
                                fill="none"
                                stroke="#F1F5F9"
                                stroke-width="16"
                            />
                            <circle
                                v-for="segment in donutSegments"
                                :key="segment.key"
                                cx="60"
                                cy="60"
                                :r="radius"
                                fill="none"
                                :stroke="segment.color"
                                stroke-width="16"
                                stroke-linecap="round"
                                :stroke-dasharray="`${segment.dash} ${circumference - segment.dash}`"
                                :stroke-dashoffset="-segment.offset"
                                transform="rotate(-90 60 60)"
                            >
                                <title>{{ segment.label }}: {{ segment.value }}</title>
                            </circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-semibold text-telkom-black">{{ statusTotal }}</span>
                            <span class="text-xs text-content-muted">Total status</span>
                        </div>
                    </div>

                    <div class="grid max-h-52 gap-2 overflow-y-auto sm:grid-cols-2">
                        <div
                            v-for="item in statusItems"
                            :key="item.key"
                            class="flex items-center justify-between gap-3 rounded-panel border border-border px-3 py-2"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: item.color }" />
                                    <p class="truncate text-sm font-medium text-telkom-black">{{ item.label }}</p>
                                </div>
                                <StatusBadge class="mt-1" :variant="item.tone">{{ item.value }}</StatusBadge>
                            </div>
                            <p class="text-sm text-content-secondary">
                                {{ Math.round((item.value / statusTotal) * 100) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-6 rounded-panel border border-dashed border-border px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-telkom-black">Data grafik belum tersedia</p>
                    <p class="mt-1 text-sm text-content-secondary">Komposisi akan muncul saat data operasional sesuai filter tersedia.</p>
                </div>
            </div>

            <div class="grid h-fit gap-4">
                <div
                    v-for="chart in charts.barCharts"
                    :key="chart.key"
                    class="rounded-panel border border-border bg-surface p-5 shadow-soft"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-telkom-black">{{ chart.title }}</h2>
                            <p class="mt-1 text-sm text-content-secondary">{{ chart.description }}</p>
                        </div>
                        <BarChart3 class="h-5 w-5 text-content-muted" />
                    </div>

                    <div v-if="hasBarData(chart.items)" class="mt-5 max-h-64 space-y-3 overflow-y-auto pr-1">
                        <div v-for="item in chart.items" :key="item.label" class="grid gap-2">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-telkom-black">{{ item.label }}</span>
                                <span class="text-content-secondary">{{ item.value }}</span>
                            </div>
                            <div class="h-3 rounded-full bg-telkom-grey-soft">
                                <div
                                    class="h-3 rounded-full"
                                    :style="{ width: barWidth(item, chart.items), backgroundColor: item.color }"
                                />
                            </div>
                        </div>
                        <p class="text-xs text-content-muted">Total: {{ totalLabel(chart.items) }}</p>
                    </div>

                    <div v-else class="mt-5 rounded-panel border border-dashed border-border px-6 py-8 text-center">
                        <p class="text-sm font-semibold text-telkom-black">Data bar chart belum tersedia</p>
                        <p class="mt-1 text-sm text-content-secondary">Rekap akan tampil setelah data operasional tersedia.</p>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="isSuperAdmin" class="grid gap-4 xl:grid-cols-2">
            <div class="overflow-hidden rounded-panel border border-border bg-surface shadow-soft">
                <div class="border-b border-border px-4 py-3">
                    <h2 class="text-base font-semibold text-telkom-black">Tabel Rekap Inputer</h2>
                </div>
                <div class="max-h-80 overflow-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="sticky top-0 z-10 bg-telkom-red text-left text-xs font-semibold uppercase text-white">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3">Nama</th>
                                <th class="whitespace-nowrap px-4 py-3">Order</th>
                                <th class="whitespace-nowrap px-4 py-3">EDK</th>
                                <th class="whitespace-nowrap px-4 py-3">Complete</th>
                                <th class="whitespace-nowrap px-4 py-3">Pending BASO</th>
                                <th class="whitespace-nowrap px-4 py-3">Failed</th>
                                <th class="whitespace-nowrap px-4 py-3">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-surface">
                            <tr v-for="row in recaps.inputers" :key="row.id" class="hover:bg-telkom-grey-soft/70">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-telkom-black">{{ row.name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.order_status }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.order_edk }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.modul_complete }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.pending_baso }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.failed }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.sisa_populasi }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-panel border border-border bg-surface shadow-soft">
                <div class="border-b border-border px-4 py-3">
                    <h2 class="text-base font-semibold text-telkom-black">Tabel Rekap Account Manager</h2>
                </div>
                <div class="max-h-80 overflow-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="sticky top-0 z-10 bg-telkom-red text-left text-xs font-semibold uppercase text-white">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3">Nama</th>
                                <th class="whitespace-nowrap px-4 py-3">Order</th>
                                <th class="whitespace-nowrap px-4 py-3">EDK</th>
                                <th class="whitespace-nowrap px-4 py-3">Complete</th>
                                <th class="whitespace-nowrap px-4 py-3">Pending BASO</th>
                                <th class="whitespace-nowrap px-4 py-3">Failed</th>
                                <th class="whitespace-nowrap px-4 py-3">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-surface">
                            <tr v-for="row in recaps.accountManagers" :key="row.id" class="hover:bg-telkom-grey-soft/70">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-telkom-black">{{ row.name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.order_status }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.order_edk }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.modul_complete }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.pending_baso }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.failed }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-content-secondary">{{ row.sisa_populasi }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
