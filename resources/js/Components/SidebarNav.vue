<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import {
    CheckCircle,
    ClipboardList,
    FileCheck,
    LayoutDashboard,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['navigate']);
const page = usePage();
const permissions = computed(() => page.props.auth.permissions ?? {});

const itemClass = (active, indented = false) => [
    'flex h-10 items-center gap-3 rounded-panel px-3 text-sm font-medium transition-all border-l-4',
    indented ? 'pl-8' : '',
    active
        ? 'bg-white text-telkom-red border-white shadow-sm'
        : 'text-white/80 border-transparent hover:bg-white/10 hover:text-white hover:border-white/50',
];
</script>

<template>
    <nav class="flex h-full flex-col gap-1">
        <Link
            :href="route('dashboard')"
            :class="itemClass(route().current('dashboard'))"
            @click="emit('navigate')"
        >
            <LayoutDashboard class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Dashboard</span>
        </Link>

        <div class="mt-5 px-3 text-xs font-semibold uppercase text-white/60">
            <span v-if="!collapsed">Monitoring</span>
        </div>

        <Link
            :href="route('order-statuses.index')"
            :class="itemClass(route().current('order-statuses.*'), true)"
            @click="emit('navigate')"
        >
            <ClipboardList class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Order Status</span>
        </Link>
        <Link
            :href="route('order-edks.index')"
            :class="itemClass(route().current('order-edks.*'), true)"
            @click="emit('navigate')"
        >
            <FileCheck class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Order EDK</span>
        </Link>
        <Link
            :href="route('completion-records.index')"
            :class="itemClass(route().current('completion-records.*'), true)"
            @click="emit('navigate')"
        >
            <CheckCircle class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Modul Complete</span>
        </Link>

        <Link
            v-if="permissions['user.view']"
            :href="route('users.index')"
            :class="itemClass(route().current('users.*'))"
            @click="emit('navigate')"
        >
            <Users class="h-5 w-5 shrink-0" />
            <span v-if="!collapsed">Manajemen Pengguna</span>
        </Link>
    </nav>
</template>
