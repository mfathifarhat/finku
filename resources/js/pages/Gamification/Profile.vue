<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Trophy,
    Award,
    Lock,
    Sparkles,
    ArrowLeft,
    ShieldCheck,
    Target,
    TrendingUp,
    HelpCircle
} from '@lucide/vue';

// UI Components
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Lencana & Level', href: '/gamification' }
        ]
    }
});

interface Badge {
    id: number;
    name: string;
    description: string;
    icon: string; // Target, ShieldCheck, TrendingUp, Award
    code: string;
    unlocked: boolean;
    unlocked_at: string | null;
}

interface User {
    name: string;
    level: number;
    xp: number;
    xp_needed: number;
}

defineProps<{
    user: User;
    badges: Badge[];
}>();

// Helper to get the Lucide component or default
const getBadgeIconComponent = (iconName: string) => {
    switch (iconName) {
        case 'Target': return Target;
        case 'ShieldCheck': return ShieldCheck;
        case 'TrendingUp': return TrendingUp;
        case 'Award': return Award;
        default: return Trophy;
    }
};

const getBadgeIconColor = (iconName: string) => {
    switch (iconName) {
        case 'Target': return 'text-rose-500 bg-rose-500/10 border-rose-500/20';
        case 'ShieldCheck': return 'text-blue-500 bg-blue-500/10 border-blue-500/20';
        case 'TrendingUp': return 'text-amber-500 bg-amber-500/10 border-amber-500/20';
        case 'Award': return 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
        default: return 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20';
    }
};
</script>

<template>
    <Head title="Level & Lencana Pencapaian - Finku" />

    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">

        <!-- Header -->
        <div class="flex items-center gap-4">
                <!-- <Button variant="ghost" size="icon" class="h-9 w-9 text-slate-500" @click="router.visit('/dashboard')">
                    <ArrowLeft class="w-4 h-4" />
                </Button> -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <Trophy class="w-6 h-6 text-amber-500" /> Karakter Finansial & Lencana
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Lihat level karakter keuanganmu saat ini dan daftar lencana pencapaian yang sudah berhasil kamu buka.
                </p>
            </div>
        </div>

        <!-- Level Big Dashboard Widget -->
        <div class="p-8 rounded-3xl bg-white dark:bg-gradient-to-r dark:from-slate-900 dark:to-slate-950 dark:text-white shadow-2xl relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row items-center gap-8 relative">

                <!-- Huge Badge Icon -->
                <div class="w-32 h-32 rounded-3xl bg-gradient-to-br from-amber-500 to-yellow-300 p-0.5 shadow-2xl relative animate-pulse">
                    <div class="w-full h-full rounded-[22px] bg-slate-950 flex flex-col items-center justify-center border border-white/5">
                        <Trophy class="w-14 h-14 text-amber-400" />
                        <span class="text-xs font-black text-amber-400 tracking-widest uppercase mt-1">Finku Elite</span>
                    </div>
                    <div class="absolute -bottom-3 -right-3 bg-amber-500 text-slate-950 text-xs font-black px-2.5 py-1 rounded-full shadow-lg border border-slate-950">
                        LV.{{ user.level }}
                    </div>
                </div>

                <!-- XP Info -->
                <div class="flex-1 w-full">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-600 border border-indigo-500/35">
                        <Sparkles class="w-3.5 h-3.5" /> Leveling Karakter Aktif
                    </span>
                    <h2 class="text-2xl font-black mt-3">Level Keuangan: Level {{ user.level }}</h2>
                    <p class="text-sm text-slate-400 mt-1">Selamat! Kamu terus melatih disiplin keuanganmu. Catat lebih banyak untuk naik tingkat!</p>

                    <!-- XP Progress Bar -->
                    <div class="mt-6">
                        <div class="flex justify-between text-xs text-slate-400 mb-2 font-medium">
                            <span>Sisa XP Menuju Level {{ user.level + 1 }}</span>
                            <span>{{ user.xp }} / {{ user.xp_needed }} XP ({{ Math.round((user.xp / user.xp_needed) * 100) }}%)</span>
                        </div>
                        <div class="w-full h-4 bg-white/5 rounded-full overflow-hidden border border-white/10 p-0.5">
                            <div
                                class="h-full bg-gradient-to-r from-amber-500 to-yellow-400 transition-all duration-500 rounded-full"
                                :style="{ width: `${(user.xp / user.xp_needed) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Badges Grid Section -->
        <div>
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-4">
                <Award class="w-5 h-5 text-emerald-500" /> Galeri Lencana Pencapaian
            </h2>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Badge Card -->
                <Card
                    v-for="badge in badges"
                    :key="badge.id"
                    class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md relative overflow-hidden transition-all duration-300 group hover:shadow-xl hover:-translate-y-1"
                    :class="{ 'opacity-60 bg-slate-50/50 dark:bg-slate-950/20': !badge.unlocked }"
                >
                    <CardContent class="p-6 flex flex-col items-center text-center justify-between h-full gap-4">

                        <!-- Badge Icon -->
                        <div class="relative">
                            <!-- Icon wrap -->
                            <div
                                class="w-20 h-20 rounded-2xl border flex items-center justify-center transition-all duration-300"
                                :class="badge.unlocked ? getBadgeIconColor(badge.icon) : 'bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-800 dark:border-slate-800'"
                            >
                                <component :is="getBadgeIconComponent(badge.icon)" class="w-9 h-9" />
                            </div>

                            <!-- Lock icon overlay if locked -->
                            <div v-if="!badge.unlocked" class="absolute -bottom-1.5 -right-1.5 bg-slate-700 text-white p-1 rounded-full shadow border border-white dark:border-slate-900">
                                <Lock class="w-3.5 h-3.5" />
                            </div>
                        </div>

                        <!-- Badge Info -->
                        <div>
                            <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-amber-500 transition-colors">
                                {{ badge.name }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-[180px] mx-auto leading-relaxed">
                                {{ badge.description }}
                            </p>
                        </div>

                        <!-- Date unlocked / lock notice -->
                        <div class="w-full pt-3 border-t border-slate-100 dark:border-slate-800/80 text-[10px]">
                            <span v-if="badge.unlocked" class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center justify-center gap-1">
                                <Sparkles class="w-3 h-3" /> Unlocked ({{ badge.unlocked_at }})
                            </span>
                            <span v-else class="text-slate-400 font-medium flex items-center justify-center gap-1">
                                <Lock class="w-3 h-3" /> Masih Terkunci
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
