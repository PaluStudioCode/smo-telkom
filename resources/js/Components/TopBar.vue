<script setup>
import { Menu, PanelLeftClose, PanelLeftOpen } from 'lucide-vue-next';
import UserMenu from '@/Components/UserMenu.vue';

defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle-sidebar', 'open-mobile']);
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-border bg-background/95 backdrop-blur">
        <div class="flex h-16 items-center justify-between gap-3 px-4 lg:px-6">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-panel border border-border bg-surface text-content-secondary shadow-soft hover:bg-telkom-grey-soft lg:hidden"
                    aria-label="Buka navigasi"
                    @click="emit('open-mobile')"
                >
                    <Menu class="h-5 w-5" />
                </button>
                <button
                    type="button"
                    class="hidden h-9 w-9 items-center justify-center rounded-panel border border-border bg-surface text-content-secondary shadow-soft hover:bg-telkom-grey-soft lg:inline-flex"
                    :aria-label="collapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'"
                    @click="emit('toggle-sidebar')"
                >
                    <PanelLeftOpen v-if="collapsed" class="h-5 w-5" />
                    <PanelLeftClose v-else class="h-5 w-5" />
                </button>
                <slot name="title" />
            </div>

            <UserMenu />
        </div>
    </header>
</template>
