<script setup>
import SidebarNav from '@/Components/SidebarNav.vue';
import ToastNotifications from '@/Components/ToastNotifications.vue';
import TopBar from '@/Components/TopBar.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

const collapsed = ref(false);
const mobileOpen = ref(false);
const pageTitle = computed(() => props.title);
</script>

<template>
    <Head :title="pageTitle" />

    <div class="min-h-screen bg-background text-foreground">
        <aside
            class="fixed inset-y-0 left-0 z-40 hidden border-r border-border bg-surface transition-all lg:block"
            :class="collapsed ? 'w-20' : 'w-72'"
        >
            <div class="flex h-16 items-center border-b border-border px-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-panel bg-telkom-red text-sm font-bold text-white">
                    SMO
                </div>
                <div v-if="!collapsed" class="ml-3 min-w-0">
                    <p class="truncate text-sm font-semibold text-telkom-black">Sistem Monitoring</p>
                    <p class="truncate text-xs text-content-muted">Government Service</p>
                </div>
            </div>
            <div class="h-[calc(100vh-4rem)] overflow-y-auto p-3">
                <SidebarNav :collapsed="collapsed" />
            </div>
        </aside>

        <Teleport to="body">
            <div v-if="mobileOpen" class="fixed inset-0 z-50 lg:hidden">
                <button
                    type="button"
                    class="absolute inset-0 bg-telkom-black/40"
                    aria-label="Tutup navigasi"
                    @click="mobileOpen = false"
                />
                <aside class="relative h-full w-80 max-w-[85vw] border-r border-border bg-surface shadow-panel">
                    <div class="flex h-16 items-center border-b border-border px-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-panel bg-telkom-red text-sm font-bold text-white">
                            SMO
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="truncate text-sm font-semibold text-telkom-black">Sistem Monitoring</p>
                            <p class="truncate text-xs text-content-muted">Government Service</p>
                        </div>
                    </div>
                    <div class="h-[calc(100vh-4rem)] overflow-y-auto p-3">
                        <SidebarNav @navigate="mobileOpen = false" />
                    </div>
                </aside>
            </div>
        </Teleport>

        <div class="transition-all" :class="collapsed ? 'lg:pl-20' : 'lg:pl-72'">
            <TopBar
                :collapsed="collapsed"
                @toggle-sidebar="collapsed = !collapsed"
                @open-mobile="mobileOpen = true"
            >
                <template #title>
                    <div class="hidden text-sm font-medium text-content-secondary sm:block">
                        {{ pageTitle }}
                    </div>
                </template>
            </TopBar>

            <main class="p-4 sm:p-6">
                <div class="mx-auto flex max-w-7xl flex-col gap-6">
                    <slot name="header" />
                    <slot />
                </div>
            </main>
        </div>

        <ToastNotifications />
    </div>
</template>
