<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Trophy,
    Award,
    Lock,
    Sparkles,
    ShieldCheck,
    Target,
    TrendingUp,
    HelpCircle,
    Flame,
    Coins,
    Edit2,
    Check,
    Calendar,
    ChevronRight
} from '@lucide/vue';

// UI Components
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MascotFox from '@/components/MascotFox.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gamifikasi & Toko', href: '/gamification' }
        ]
    }
});

interface Badge {
    id: number;
    name: string;
    description: string;
    icon: string; // Target, ShieldCheck, TrendingUp, Award, Flame
    code: string;
    unlocked: boolean;
    unlocked_at: string | null;
}

interface User {
    name: string;
    level: number;
    level_label: string;
    streak_count: number;
    last_activity_date: string | null;
    coins: number;
    mascot_name: string;
    owned_accessories: string[];
    equipped_accessories: string[];
    xp: number;
    xp_needed: number;
}

const props = defineProps<{
    user: User;
    badges: Badge[];
}>();

// Navigation Tabs
const activeTab = ref<'achievements' | 'shop'>('achievements');

// Mascot Rename Form
const isRenaming = ref(false);
const renameForm = useForm({
    name: props.user.mascot_name || 'Finku Fox'
});

// Sync name when user prop updates
watch(() => props.user.mascot_name, (newVal) => {
    renameForm.name = newVal;
});

const saveMascotName = () => {
    renameForm.post('/gamification/mascot/rename', {
        preserveScroll: true,
        onSuccess: () => {
            isRenaming.value = false;
        }
    });
};

// Shop Accessories Catalog
const shopItems = [
    { code: 'tie_fancy', name: 'Dasi Kupu-Kupu', description: 'Dasi elegan untuk perencana keuangan berkelas.', price: 30, slot: 'neck', icon: '🎀' },
    { code: 'scarf_winter', name: 'Syal Rajut', description: 'Syal rajut hangat agar tetap tenang di tanggal tua.', price: 40, slot: 'neck', icon: '🧣' },
    { code: 'glasses_cool', name: 'Kacamata Kece', description: 'Kacamata hitam untuk gaya finansial modis.', price: 50, slot: 'glasses', icon: '🕶️' },
    { code: 'hat_detective', name: 'Topi Detektif', description: 'Membantumu menyelidiki kebocoran budget bulanan.', price: 75, slot: 'hat', icon: '🕵️' },
    { code: 'crown_gold', name: 'Mahkota Emas', description: 'Tanda kehormatan para penyelamat dana darurat.', price: 150, slot: 'hat', icon: '👑' },
    { code: 'cape_royal', name: 'Jubah Kerajaan', description: 'Jubah beludru merah megah untuk sang raja hemat.', price: 200, slot: 'back', icon: '🧥' },
];

const buyItem = (item: any) => {
    router.post('/gamification/mascot/buy', {
        code: item.code,
        price: item.price
    }, {
        preserveScroll: true
    });
};

const toggleEquip = (item: any) => {
    const isEquipped = props.user.equipped_accessories?.includes(item.code);
    router.post('/gamification/mascot/equip', {
        code: item.code,
        slot: item.slot,
        action: isEquipped ? 'unequip' : 'equip'
    }, {
        preserveScroll: true
    });
};

// Helper for Lucide icons
const getBadgeIconComponent = (iconName: string) => {
    switch (iconName) {
        case 'Target': return Target;
        case 'ShieldCheck': return ShieldCheck;
        case 'TrendingUp': return TrendingUp;
        case 'Award': return Award;
        case 'Flame': return Flame;
        default: return Trophy;
    }
};

const getBadgeIconColor = (iconName: string) => {
    switch (iconName) {
        case 'Target': return 'text-rose-500 bg-rose-500/10 border-rose-500/20';
        case 'ShieldCheck': return 'text-blue-500 bg-blue-500/10 border-blue-500/20';
        case 'TrendingUp': return 'text-amber-500 bg-amber-500/10 border-amber-500/20';
        case 'Award': return 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
        case 'Flame': return 'text-orange-500 bg-orange-500/10 border-orange-500/20';
        default: return 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20';
    }
};
</script>

<template>
    <Head title="Level & Lencana Pencapaian" />

    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <Trophy class="w-6 h-6 text-amber-500" /> Karakter Keuangan & Toko Maskot
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Lihat level karakter keuanganmu, atur aksesoris maskot setiamu, dan belanja item premium menggunakan Finku Coins.
                </p>
            </div>
            
            <!-- Coins Widget -->
            <div class="flex items-center gap-2 bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-950 font-bold px-4 py-2 rounded-2xl shadow-lg border border-yellow-300 w-fit shrink-0">
                <Coins class="w-5 h-5 animate-spin-slow fill-current" />
                <span>{{ user.coins }} <span class="text-xs font-semibold">Coins</span></span>
            </div>
        </div>

        <!-- Level Big Dashboard Widget -->
        <div class="p-8 rounded-3xl bg-white dark:bg-gradient-to-r dark:from-slate-900 dark:to-slate-950 dark:text-white shadow-2xl relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row items-center gap-8 relative">

                <!-- Huge Mascot evolution avatar -->
                <div class="w-36 h-36 rounded-3xl bg-slate-900 border border-slate-800 p-0.5 shadow-2xl relative flex items-center justify-center">
                    <MascotFox :level="user.level" :equipped="user.equipped_accessories" :size="130" />
                    <div class="absolute -bottom-3 -right-3 bg-amber-500 text-slate-950 text-xs font-black px-2.5 py-1 rounded-full shadow-lg border border-slate-950">
                        LV.{{ user.level }}
                    </div>
                </div>

                <!-- XP Info -->
                <div class="flex-1 w-full">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-600 border border-indigo-500/35 dark:text-indigo-300">
                        <Sparkles class="w-3.5 h-3.5" /> Leveling Karakter Aktif
                    </span>
                    <h2 class="text-2xl font-black mt-3 flex items-center gap-2">
                        Level Keuangan: {{ user.level_label }}
                    </h2>
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

        <!-- Premium Tab Switchers -->
        <div class="flex border-b border-slate-200 dark:border-slate-800">
            <button 
                @click="activeTab = 'achievements'"
                class="px-5 py-3 text-sm font-bold border-b-2 transition-all duration-200 flex items-center gap-2 cursor-pointer"
                :class="activeTab === 'achievements' 
                    ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' 
                    : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
            >
                <Award class="w-4 h-4" /> Lencana & Level
            </button>
            <button 
                @click="activeTab = 'shop'"
                class="px-5 py-3 text-sm font-bold border-b-2 transition-all duration-200 flex items-center gap-2 cursor-pointer"
                :class="activeTab === 'shop' 
                    ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' 
                    : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
            >
                <Coins class="w-4 h-4" /> Maskot & Toko Koin
            </button>
        </div>

        <!-- TAB CONTENT 1: ACHIEVEMENTS & BADGES -->
        <div v-if="activeTab === 'achievements'" class="space-y-6">
            <!-- Daily Streak Tracker -->
            <div class="grid gap-6 md:grid-cols-3">
                <div class="md:col-span-2 p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-orange-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-orange-500/10 text-orange-600 border border-orange-500/20">
                                <Flame class="w-3.5 h-3.5 fill-current" /> Daily Streak
                            </span>
                            <span class="text-xs text-slate-400">
                                Terakhir mencatat: {{ user.last_activity_date || 'Belum pernah' }}
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mt-4 gap-4">
                            <div>
                                <h3 class="text-3xl font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                    🔥 {{ user.streak_count }} Hari <span class="text-lg font-semibold text-slate-500 dark:text-slate-400">Beruntun</span>
                                </h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                    {{ user.streak_count > 0 
                                        ? 'Kerja bagus! Jaga streak harian kamu dengan mencatat pendapatan atau pengeluaran baru setiap hari.'
                                        : 'Ayo mulai streak harian pertamamu hari ini! Catat pemasukan atau pengeluaran untuk menyalakan api streak.' 
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Day circles to show visual streak progress -->
                    <div class="mt-6 border-t border-slate-100 dark:border-slate-800/80 pt-6">
                        <div class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-3">Api Streak Mingguan</div>
                        <div class="grid grid-cols-7 gap-2 max-w-md">
                            <div 
                                v-for="day in 7" 
                                :key="day"
                                class="flex flex-col items-center gap-1.5"
                            >
                                <div 
                                    class="w-10 h-10 rounded-xl flex items-center justify-center border transition-all duration-300 shadow-sm"
                                    :class="user.streak_count >= day 
                                        ? 'bg-gradient-to-br from-orange-500 to-rose-500 text-white border-transparent ring-2 ring-orange-500/20' 
                                        : 'bg-slate-50 border-slate-200 text-slate-400 dark:bg-slate-800 dark:border-slate-700'"
                                >
                                    <Flame v-if="user.streak_count >= day" class="w-5 h-5 fill-current" />
                                    <span v-else class="text-xs font-semibold">{{ day }}</span>
                                </div>
                                <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500">Day {{ day }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Streak Reward Card -->
                <Card class="bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-slate-900 dark:to-slate-950 border-orange-200/50 dark:border-slate-800 shadow-xl flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -left-12 -top-12 w-32 h-32 bg-orange-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg font-bold text-orange-800 dark:text-orange-400 flex items-center gap-1.5">
                            <Sparkles class="w-5 h-5 text-orange-500" /> Tantangan Streak
                        </CardTitle>
                        <CardDescription class="text-orange-700/80 dark:text-slate-400 text-xs">
                            Buka Lencana Khusus dengan menjaga streak harianmu!
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="text-xs text-orange-950/80 dark:text-slate-300 space-y-4 flex-1">
                        <div class="flex items-start gap-2.5 p-2 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-orange-200/30 dark:border-slate-800">
                            <div class="p-1.5 bg-orange-500 text-white rounded-lg shadow-md shrink-0">
                                <Flame class="w-4 h-4 fill-current" />
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 dark:text-slate-100">3 Hari Beruntun</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Dapatkan Lencana <strong>Pejuang Konsisten</strong> & +100 XP bonus!</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5 p-2 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-orange-200/30 dark:border-slate-800">
                            <div class="p-1.5 bg-rose-500 text-white rounded-lg shadow-md shrink-0">
                                <Flame class="w-4 h-4 fill-current" />
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 dark:text-slate-100">7 Hari Beruntun</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Dapatkan Lencana <strong>Master Streak</strong> & +200 XP bonus!</div>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="pt-2">
                        <div class="text-[10px] text-orange-700/70 dark:text-slate-400 flex items-center gap-1.5">
                            <HelpCircle class="w-3.5 h-3.5" />
                            <span>Streak dihitung berdasarkan hari beruntun Anda mencatat transaksi.</span>
                        </div>
                    </CardFooter>
                </Card>
            </div>

            <!-- Badges Grid Section -->
            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-4">
                    <Award class="w-5 h-5 text-emerald-500" /> Galeri Lencana Pencapaian
                </h2>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card
                        v-for="badge in badges"
                        :key="badge.id"
                        class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md relative overflow-hidden transition-all duration-300 group hover:shadow-xl hover:-translate-y-1"
                        :class="{ 'opacity-60 bg-slate-50/50 dark:bg-slate-950/20': !badge.unlocked }"
                    >
                        <CardContent class="p-6 flex flex-col items-center text-center justify-between h-full gap-4">
                            <div class="relative">
                                <div
                                    class="w-20 h-20 rounded-2xl border flex items-center justify-center transition-all duration-300"
                                    :class="badge.unlocked ? getBadgeIconColor(badge.icon) : 'bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-800 dark:border-slate-800'"
                                >
                                    <component :is="getBadgeIconComponent(badge.icon)" class="w-9 h-9" />
                                </div>
                                <div v-if="!badge.unlocked" class="absolute -bottom-1.5 -right-1.5 bg-slate-700 text-white p-1 rounded-full shadow border border-white dark:border-slate-900">
                                    <Lock class="w-3.5 h-3.5" />
                                </div>
                            </div>

                            <div>
                                <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-amber-500 transition-colors">
                                    {{ badge.name }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-[180px] mx-auto leading-relaxed">
                                    {{ badge.description }}
                                </p>
                            </div>

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

        <!-- TAB CONTENT 2: MASCOT & COINS SHOP -->
        <div v-else class="grid gap-6 md:grid-cols-3">
            
            <!-- LEFT COLUMN: Mascot Customize Center -->
            <div class="md:col-span-1 flex flex-col gap-6">
                <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden">
                    <CardHeader class="pb-2 border-b border-slate-100 dark:border-slate-800">
                        <CardTitle class="text-base font-bold flex items-center gap-2">
                            🦊 Karakter Maskot
                        </CardTitle>
                        <CardDescription class="text-xs">Dandani asistenmu agar tampil beda.</CardDescription>
                    </CardHeader>
                    <CardContent class="p-6 flex flex-col items-center">
                        
                        <!-- Mascot SVG Display -->
                        <div class="w-48 h-48 bg-slate-950 dark:bg-slate-950/60 rounded-3xl border border-slate-800 flex items-center justify-center shadow-inner relative overflow-hidden group">
                            <MascotFox :level="user.level" :equipped="user.equipped_accessories" :size="160" />
                        </div>

                        <!-- Mascot Custom Naming Form -->
                        <div class="w-full mt-6">
                            <div v-if="!isRenaming" class="flex items-center justify-between bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 p-3 rounded-xl">
                                <div>
                                    <span class="text-[10px] text-slate-400 block font-semibold uppercase tracking-wider">Nama Panggilan</span>
                                    <span class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ user.mascot_name || 'Finku Fox' }}</span>
                                </div>
                                <Button size="icon" variant="ghost" class="h-8 w-8 text-slate-500 hover:text-emerald-500" @click="isRenaming = true">
                                    <Edit2 class="w-4 h-4" />
                                </Button>
                            </div>

                            <form v-else @submit.prevent="saveMascotName" class="flex items-center gap-2">
                                <div class="flex-1">
                                    <Input 
                                        type="text" 
                                        v-model="renameForm.name"
                                        placeholder="Beri nama rubahmu..." 
                                        maxlength="20"
                                        class="h-9 text-xs font-semibold bg-slate-50 dark:bg-slate-800 focus-visible:ring-emerald-500"
                                    />
                                </div>
                                <Button type="submit" size="sm" class="bg-emerald-600 hover:bg-emerald-500 text-white shadow h-9" :disabled="renameForm.processing">
                                    <Check class="w-4 h-4" />
                                </Button>
                                <Button type="button" size="sm" variant="outline" class="h-9 text-xs" @click="isRenaming = false">
                                    Batal
                                </Button>
                            </form>
                        </div>
                    </CardContent>
                    
                    <!-- Equipped items list -->
                    <CardFooter class="bg-slate-50/50 dark:bg-slate-950/20 border-t border-slate-100 dark:border-slate-800 p-4 flex flex-col gap-3">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400 w-full">Aksesoris Terpasang:</span>
                        <div class="flex flex-wrap gap-2 w-full">
                            <div v-if="!user.equipped_accessories || user.equipped_accessories.length === 0" class="text-xs text-slate-400 py-1.5">
                                Belum ada aksesoris yang dipasang.
                            </div>
                            <div 
                                v-for="equippedCode in user.equipped_accessories" 
                                :key="equippedCode"
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 text-xs font-semibold rounded-full border border-emerald-250/20"
                            >
                                <span>{{ shopItems.find(item => item.code === equippedCode)?.name || equippedCode }}</span>
                                <button 
                                    @click="toggleEquip(shopItems.find(item => item.code === equippedCode))"
                                    class="text-emerald-500 hover:text-red-500 font-bold ml-0.5 cursor-pointer"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                    </CardFooter>
                </Card>
            </div>

            <!-- RIGHT COLUMN: Avatar Customization Shop -->
            <div class="md:col-span-2">
                <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-xl">
                    <CardHeader class="border-b border-slate-100 dark:border-slate-800">
                        <CardTitle class="text-base font-bold flex items-center gap-2">
                            🛍️ Toko Aksesoris Maskot
                        </CardTitle>
                        <CardDescription class="text-xs">Beli item kustomisasi dengan Finku Coins yang kamu kumpulkan.</CardDescription>
                    </CardHeader>
                    <CardContent class="p-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card 
                                v-for="item in shopItems" 
                                :key="item.code"
                                class="bg-slate-50/50 dark:bg-slate-800/20 border border-slate-100 dark:border-slate-800 p-4 relative overflow-hidden transition-all duration-300 hover:border-amber-300 hover:shadow-md"
                            >
                                <div class="flex items-start gap-4">
                                    <!-- Accessory visual circle -->
                                    <div class="w-14 h-14 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-center text-3xl shadow-sm shrink-0">
                                        {{ item.icon }}
                                    </div>
                                    
                                    <!-- Info and pricing -->
                                    <div class="flex-1 flex flex-col justify-between h-full min-h-[56px] gap-2">
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ item.name }}</h4>
                                            <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">{{ item.description }}</p>
                                        </div>

                                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-200/50 dark:border-slate-850">
                                            <!-- Cost or Slot label -->
                                            <div class="flex items-center gap-1 text-xs font-black text-amber-500">
                                                <Coins class="w-4 h-4 fill-current" />
                                                <span>{{ item.price }} <span class="text-[10px] text-slate-400 font-bold uppercase">koin</span></span>
                                            </div>

                                            <!-- Button Trigger -->
                                            <div>
                                                <!-- Case 1: Equipped -->
                                                <Button 
                                                    v-if="user.equipped_accessories?.includes(item.code)"
                                                    size="sm" 
                                                    variant="outline" 
                                                    class="h-7 text-xs border-emerald-500 bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20 font-bold px-3"
                                                    @click="toggleEquip(item)"
                                                >
                                                    Lepas
                                                </Button>
                                                <!-- Case 2: Owned, not equipped -->
                                                <Button 
                                                    v-else-if="user.owned_accessories?.includes(item.code)"
                                                    size="sm" 
                                                    class="h-7 text-xs bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 shadow"
                                                    @click="toggleEquip(item)"
                                                >
                                                    Gunakan
                                                </Button>
                                                <!-- Case 3: Locked, need purchase -->
                                                <Button 
                                                    v-else
                                                    size="sm" 
                                                    class="h-7 text-xs bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-slate-950 font-black px-3 shadow"
                                                    :disabled="user.coins < item.price"
                                                    @click="buyItem(item)"
                                                >
                                                    Beli Item
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-spin-slow {
    animation: spin 8s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
