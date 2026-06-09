<script setup lang="ts">
import { watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { LayoutGrid, Wallet, Target, Trophy, TrendingUp } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import { Toaster } from '@/components/ui/sonner';
import ChatWidget from '@/components/ChatWidget.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const mobileNavItems = [
    { title: 'Home', href: '/dashboard', icon: LayoutGrid },
    { title: 'Catat', href: '/expenses', icon: Wallet },
    { title: 'Target', href: '/goals', icon: Target },
    { title: 'Lencana', href: '/gamification', icon: Trophy },
    { title: 'Analisis', href: '/insights', icon: TrendingUp },
];

watch(
    () => page.props.flash,
    (flash: any) => {
        if (!flash) return;

        if (flash.success) {
            toast.success(flash.success);
        }

        if (flash.error) {
            toast.error(flash.error);
        }

        if (flash.level_up) {
            toast('🎉 NAIK LEVEL!', {
                description: `Selamat! Karakter finansialmu naik ke Level ${flash.level_up}. Keren banget! 🚀`,
                duration: 6000,
            });
        }

        if (flash.new_badges && Array.isArray(flash.new_badges)) {
            flash.new_badges.forEach((badge: any) => {
                toast(`🏆 LENCANA BARU DIBUKA!`, {
                    description: `Kamu berhasil membuka lencana "${badge.name}": ${badge.description}`,
                    duration: 6000,
                });
            });
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header" class="pb-24 lg:pb-0">
            <slot />
        </AppContent>
        <Toaster />
        
        <!-- Bottom Nav for Mobile -->
        <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200/80 px-2 py-1 flex justify-around items-center shadow-lg pb-safe">
            <Link 
                v-for="item in mobileNavItems" 
                :key="item.title"
                :href="item.href"
                class="flex flex-col items-center gap-1 py-1 px-3 text-[10px] font-bold transition-all duration-200 cursor-pointer"
                :class="isCurrentUrl(item.href) ? 'text-emerald-600 scale-105' : 'text-slate-400 hover:text-slate-600'"
            >
                <component 
                    :is="item.icon" 
                    class="w-5 h-5 transition-transform" 
                    :class="isCurrentUrl(item.href) ? 'text-emerald-600' : 'text-slate-400'"
                />
                <span>{{ item.title }}</span>
            </Link>
        </div>

        <ChatWidget />
    </AppShell>
</template>
