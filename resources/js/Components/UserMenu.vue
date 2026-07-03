<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, LogOut, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage();
const open = ref(false);
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-panel border border-border bg-surface px-2 text-left text-sm shadow-soft transition hover:bg-telkom-grey-soft focus:outline-none focus:ring-2 focus:ring-telkom-red focus:ring-offset-2"
            @click="open = !open"
        >
            <img
                v-if="user?.profile_photo_url"
                :src="user.profile_photo_url"
                :alt="user.name"
                class="h-7 w-7 rounded-full object-cover"
            />
            <span
                v-else
                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary-soft text-xs font-semibold text-telkom-red"
            >
                {{ user?.name?.charAt(0) ?? 'U' }}
            </span>
            <span class="hidden max-w-36 truncate font-medium text-telkom-black sm:inline">
                {{ user?.name }}
            </span>
            <ChevronDown class="h-4 w-4 text-content-muted" aria-hidden="true" />
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-40 mt-2 w-64 rounded-panel border border-border bg-surface p-2 shadow-panel"
        >
            <div class="border-b border-border px-3 py-2">
                <p class="truncate text-sm font-semibold text-telkom-black">{{ user?.name }}</p>
                <p class="truncate text-xs text-content-muted">{{ user?.email }}</p>
                <p class="mt-1 text-xs font-medium text-telkom-red">{{ user?.role_label }}</p>
            </div>
            <Link
                :href="route('profile.edit')"
                class="mt-2 flex items-center gap-2 rounded-panel px-3 py-2 text-sm text-content-secondary hover:bg-telkom-grey-soft hover:text-telkom-black"
                @click="open = false"
            >
                <User class="h-4 w-4" />
                Profil Pengguna
            </Link>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="flex w-full items-center gap-2 rounded-panel px-3 py-2 text-left text-sm text-content-secondary hover:bg-telkom-grey-soft hover:text-telkom-black"
            >
                <LogOut class="h-4 w-4" />
                Logout
            </Link>
        </div>
    </div>
</template>
