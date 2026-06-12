<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Coins,
    TrendingUp,
    Wallet,
    Award,
    Trophy,
    Target,
    Plus,
    Sparkles,
    Info,
    Check,
    DollarSign,
    Lock,
    Settings,
    Flame
} from '@lucide/vue';
import { dashboard } from '@/routes';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

// Define layout breadcrumbs
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
        ],
    },
});

interface User {
    name: string;
    email: string;
    xp: number;
    level: number;
    level_label: string;
    streak_count: number;
    monthly_income: number;
    budgeting_method: string;
    custom_budget_percentages: {
        needs?: number;
        wants?: number;
        savings?: number;
    } | null;
    xp_needed: number;
}

interface Summary {
    income: number;
    actual_income: number;
    spent_needs: number;
    spent_wants: number;
    spent_savings: number;
    limit_needs: number;
    limit_wants: number;
    limit_savings: number;
    percent_needs: number;
    percent_wants: number;
    percent_savings: number;
}

interface Expense {
    id: number;
    amount: number;
    category: string;
    date: string;
    description: string | null;
    type: 'needs' | 'wants';
}

interface Goal {
    id: number;
    name: string;
    target_amount: number;
    current_amount: number;
    target_date: string;
    icon: string;
    completed: boolean;
}

interface Badge {
    id: number;
    name: string;
    description: string;
    icon: string;
    code: string;
}

const props = defineProps<{
    user: User;
    summary: Summary;
    recent_expenses: Expense[];
    recent_goals: Goal[];
    unlocked_badges: Badge[];
    ai_insights: string[];
}>();

// Budget Update Form Setup
const showSettingsDialog = ref(false);
const form = useForm({
    monthly_income: props.user.monthly_income,
    budgeting_method: props.user.budgeting_method,
    needs: props.user.custom_budget_percentages?.needs ?? 50,
    wants: props.user.custom_budget_percentages?.wants ?? 30,
    savings: props.user.custom_budget_percentages?.savings ?? 20,
});

const saveSettings = () => {
    form.post('/dashboard/budget', {
        onSuccess: () => {
            showSettingsDialog.value = false;
        }
    });
};

// Utilities
const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

const getPercent = (spent: number, limit: number) => {
    if (limit <= 0) return 0;
    const p = (spent / limit) * 100;
    return Math.min(Math.round(p), 100);
};

const getProgressBarColor = (percent: number) => {
    if (percent >= 100) return 'bg-red-500';
    if (percent >= 85) return 'bg-amber-500';
    return 'bg-emerald-500';
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">

        <!-- Header Section with Gamification Level & XP -->
        <div class="grid gap-6 md:grid-cols-3">

            <!-- Welcome User -->
            <div class="md:col-span-2 flex flex-col justify-between p-6 rounded-2xl bg-gradient-to-r from-emerald-600/90 to-teal-600/90 text-white shadow-xl overflow-hidden relative group">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500"></div>
                <div>
                    <!-- Header Badges -->
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/20 backdrop-blur-md">
                            <Sparkles class="w-3.5 h-3.5" /> Gamified Finance
                        </span>
                        <span v-if="user.streak_count > 0" class="inline-flex items-center gap-1 bg-amber-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md animate-pulse">
                            <Flame class="w-3.5 h-3.5 fill-red-500 text-red-500" /> 🔥 {{ user.streak_count }} Hari Beruntun
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-white/70">
                            <Flame class="w-3.5 h-3.5 text-white/50" /> Mulai Streak Hari Ini!
                        </span>
                    </div>

                    <!-- Greeting & Cash Flow -->
                    <h1 class="text-3xl font-extrabold tracking-tight mt-3">Halo, {{ user.name }}!</h1>
                    <p class="text-white/80 mt-1">Siap menaikkan level finansialmu hari ini? Catat keuanganmu & kumpulkan XP!</p>

                    <!-- Dynamic Cash Flow Indicator -->
                    <div class="mt-4 flex items-center gap-2 text-sm bg-white/15 backdrop-blur-md rounded-xl p-3 border border-white/10 w-fit">
                        <TrendingUp v-if="summary.actual_income >= (summary.spent_needs + summary.spent_wants)" class="w-4 h-4 text-emerald-300" />
                        <TrendingUp v-else class="w-4 h-4 text-red-300 rotate-180" />
                        <span>Sisa Uang Anda (Cash Flow): <strong class="text-yellow-300">{{ formatRupiah(summary.actual_income - (summary.spent_needs + summary.spent_wants)) }}</strong></span>
                    </div>
                </div>

                <!-- Quick Navigation Action -->
                <div class="flex flex-wrap items-center gap-3 mt-6">
                    <Button variant="secondary" class="bg-white text-emerald-800 hover:bg-white/95 shadow-md font-medium" @click="router.visit('/expenses')">
                        <Plus class="w-4 h-4 mr-2" /> Catat Pengeluaran
                    </Button>
                    <Button variant="outline" class="bg-white text-emerald-800 hover:bg-white/95 shadow-md font-medium" @click="showSettingsDialog = true">
                        <Settings class="w-4 h-4 mr-2" /> Atur Anggaran
                    </Button>
                </div>
            </div>

            <!-- Level Profile Widget -->
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center border border-amber-500/20 shadow-inner">
                            <Trophy class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Level Keuangan</div>
                            <div class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                                Level {{ user.level }} 
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded bg-amber-500/10 text-amber-600 border border-amber-500/20 whitespace-nowrap">
                                    {{ user.level_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                        {{ user.xp }} / {{ user.xp_needed }} XP
                    </span>
                </div>

                <!-- XP Bar -->
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-slate-500 mb-1.5 font-medium">
                        <span>Progress Level</span>
                        <span>{{ Math.round((user.xp / user.xp_needed) * 100) }}%</span>
                    </div>
                    <div class="w-full h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200/50 dark:border-slate-800">
                        <div
                            class="h-full bg-gradient-to-r from-amber-500 to-yellow-400 transition-all duration-500 rounded-full"
                            :style="{ width: `${(user.xp / user.xp_needed) * 100}%` }"
                        ></div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 flex items-center gap-1.5">
                    <Award class="w-4 h-4 text-emerald-500" />
                    <span>Perlu <strong>{{ user.xp_needed - user.xp }} XP</strong> lagi untuk naik level.</span>
                </div>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <!-- Pendapatan -->
            <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
                <CardContent class="p-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pemasukan Riil</p>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ formatRupiah(summary.actual_income) }}</h3>
                        <p class="text-[10px] text-slate-400 mt-1">Rencana: {{ formatRupiah(summary.income) }}</p>
                    </div>
                    <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 rounded-xl">
                        <Coins class="w-6 h-6" />
                    </div>
                </CardContent>
            </Card>

            <!-- Kebutuhan (Needs) -->
            <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
                <CardContent class="p-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Needs ({{ summary.percent_needs }}%)</p>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ formatRupiah(summary.spent_needs) }}</h3>
                        <p class="text-xs text-slate-400 mt-1">Limit: {{ formatRupiah(summary.limit_needs) }}</p>
                    </div>
                    <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-500 rounded-xl">
                        <Wallet class="w-6 h-6" />
                    </div>
                </CardContent>
            </Card>

            <!-- Keinginan (Wants) -->
            <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
                <CardContent class="p-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Wants ({{ summary.percent_wants }}%)</p>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ formatRupiah(summary.spent_wants) }}</h3>
                        <p class="text-xs text-slate-400 mt-1">Limit: {{ formatRupiah(summary.limit_wants) }}</p>
                    </div>
                    <div class="p-3 bg-purple-500/10 text-purple-600 dark:text-purple-500 rounded-xl">
                        <TrendingUp class="w-6 h-6" />
                    </div>
                </CardContent>
            </Card>

            <!-- Tabungan (Savings) -->
            <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
                <CardContent class="p-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Savings ({{ summary.percent_savings }}%)</p>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ formatRupiah(summary.spent_savings) }}</h3>
                        <p class="text-xs text-slate-400 mt-1">Target: {{ formatRupiah(summary.limit_savings) }}</p>
                    </div>
                    <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-500 rounded-xl">
                        <Target class="w-6 h-6" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Budget Progress & AI Insights -->
        <div class="grid gap-6 md:grid-cols-5">

            <!-- Budget Allocation Tracker -->
            <div class="md:col-span-3 p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl">
                <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100">Status Alokasi Anggaran</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Persentase pengeluaran terhadap batas alokasi metode <strong>{{ user.budgeting_method }}</strong></p>

                <div class="mt-6 flex flex-col gap-6">
                    <!-- Needs Progress -->
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Kebutuhan (Needs)
                            </span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">
                                {{ formatRupiah(summary.spent_needs) }} / {{ formatRupiah(summary.limit_needs) }}
                                <span class="text-xs text-slate-500 font-normal">({{ getPercent(summary.spent_needs, summary.limit_needs) }}%)</span>
                            </span>
                        </div>
                        <div class="w-full h-3.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200/50 dark:border-slate-800">
                            <div
                                class="h-full transition-all duration-500 rounded-full"
                                :class="getProgressBarColor(getPercent(summary.spent_needs, summary.limit_needs))"
                                :style="{ width: `${getPercent(summary.spent_needs, summary.limit_needs)}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Wants Progress -->
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Keinginan (Wants)
                            </span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">
                                {{ formatRupiah(summary.spent_wants) }} / {{ formatRupiah(summary.limit_wants) }}
                                <span class="text-xs text-slate-500 font-normal">({{ getPercent(summary.spent_wants, summary.limit_wants) }}%)</span>
                            </span>
                        </div>
                        <div class="w-full h-3.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200/50 dark:border-slate-800">
                            <div
                                class="h-full transition-all duration-500 rounded-full"
                                :class="getProgressBarColor(getPercent(summary.spent_wants, summary.limit_wants))"
                                :style="{ width: `${getPercent(summary.spent_wants, summary.limit_wants)}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Savings Progress -->
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Tabungan & Investasi
                            </span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">
                                {{ formatRupiah(summary.spent_savings) }} / {{ formatRupiah(summary.limit_savings) }}
                                <span class="text-xs text-slate-500 font-normal">({{ getPercent(summary.spent_savings, summary.limit_savings) }}%)</span>
                            </span>
                        </div>
                        <div class="w-full h-3.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200/50 dark:border-slate-800">
                            <div
                                class="h-full bg-gradient-to-r from-amber-500 to-yellow-500 transition-all duration-500 rounded-full"
                                :style="{ width: `${getPercent(summary.spent_savings, summary.limit_savings)}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Insight Widget -->
            <div class="md:col-span-2 p-6 rounded-2xl bg-gradient-to-b from-slate-900 to-slate-950 text-white shadow-xl relative overflow-hidden flex flex-col justify-between border border-slate-800">
                <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
                    <Sparkles class="w-48 h-48 text-emerald-400" />
                </div>

                <div>
                    <div class="flex items-center gap-2 text-emerald-400 text-sm font-semibold tracking-wide uppercase">
                        <Sparkles class="w-4.5 h-4.5" /> AI Financial Advisor
                    </div>
                    <h3 class="text-lg font-bold mt-2">Rekomendasi Cerdas</h3>

                    <div class="mt-4 flex flex-col gap-3">
                        <div
                            v-for="(insight, index) in ai_insights"
                            :key="index"
                            class="p-3 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 text-sm leading-relaxed"
                        >
                            {{ insight }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-3 border-t border-white/10 flex items-center justify-between">
                    <span class="text-xs text-slate-400">Analisis berdasarkan pengeluaran bulan ini</span>
                    <Button variant="link" class="text-emerald-400 hover:text-emerald-300 text-xs p-0 h-auto" @click="router.visit('/insights')">
                        Selengkapnya &rarr;
                    </Button>
                </div>
            </div>
        </div>

        <!-- Recent Expenses and Saving Goals -->
        <div class="grid gap-6 md:grid-cols-2">

            <!-- Recent Expenses -->
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Wallet class="w-5 h-5 text-emerald-500" /> Pengeluaran Terbaru
                    </h2>
                    <Button variant="link" class="text-xs text-slate-500 p-0 hover:text-slate-700" @click="router.visit('/expenses')">
                        Lihat Semua
                    </Button>
                </div>

                <div class="flex flex-col gap-3">
                    <div v-if="recent_expenses.length === 0" class="text-center py-6 text-sm text-slate-400">
                        Belum ada pengeluaran dicatat bulan ini.
                    </div>
                    <div
                        v-for="expense in recent_expenses"
                        :key="expense.id"
                        class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="w-2.5 h-2.5 rounded-full"
                                :class="expense.type === 'needs' ? 'bg-blue-500' : 'bg-purple-500'"
                                :title="expense.type === 'needs' ? 'Kebutuhan' : 'Keinginan'"
                            ></span>
                            <div>
                                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ expense.category }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ expense.description || 'Tidak ada deskripsi' }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ formatRupiah(expense.amount) }}</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ expense.date }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Savings Goals -->
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Target class="w-5 h-5 text-amber-500" /> Target Tabungan
                    </h2>
                    <Button variant="link" class="text-xs text-slate-500 p-0 hover:text-slate-700" @click="router.visit('/goals')">
                        Atur Target
                    </Button>
                </div>

                <div class="flex flex-col gap-4">
                    <div v-if="recent_goals.length === 0" class="text-center py-6 text-sm text-slate-400">
                        Belum ada target tabungan dibuat.
                    </div>
                    <div
                        v-for="goal in recent_goals"
                        :key="goal.id"
                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 flex flex-col gap-2"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">{{ goal.icon || '🎯' }}</span>
                                <div>
                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ goal.name }}</div>
                                    <div class="text-xs text-slate-400">Target: {{ goal.target_date }}</div>
                                </div>
                            </div>
                            <span
                                class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                :class="goal.completed ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'"
                            >
                                {{ goal.completed ? 'Tercapai' : 'Aktif' }}
                            </span>
                        </div>

                        <!-- Mini progress bar -->
                        <div>
                            <div class="flex justify-between text-xs text-slate-500 mb-1">
                                <span>Terkumpul: {{ formatRupiah(goal.current_amount) }}</span>
                                <span>{{ getPercent(goal.current_amount, goal.target_amount) }}%</span>
                            </div>
                            <div class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-amber-500 transition-all duration-300"
                                    :style="{ width: `${getPercent(goal.current_amount, goal.target_amount)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budgeting Setup Dialog Modal -->
        <Dialog v-model:open="showSettingsDialog">
            <DialogContent class="sm:max-w-[480px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-800 dark:text-slate-100">Atur Anggaran Keuangan</DialogTitle>
                    <DialogDescription class="text-slate-500">
                        Atur pendapatan bulanan kamu dan pilih metode alokasi anggaran otomatis yang diinginkan.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <!-- Pendapatan input -->
                    <div class="grid gap-2">
                        <Label for="monthly_income" class="text-slate-700 dark:text-slate-300 font-medium">Pendapatan Bulanan (Rupiah)</Label>
                        <Input
                            id="monthly_income"
                            type="number"
                            v-model="form.monthly_income"
                            placeholder="Contoh: 5000000"
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>

                    <!-- Metode selection -->
                    <div class="grid gap-2">
                        <Label for="budget_method" class="text-slate-700 dark:text-slate-300 font-medium">Metode Budgeting</Label>
                        <Select v-model="form.budgeting_method">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih metode budgeting" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem value="50-30-20">Metode 50% Needs / 30% Wants / 20% Savings</SelectItem>
                                <SelectItem value="70-20-10">Metode 70% Needs / 20% Wants / 10% Savings</SelectItem>
                                <SelectItem value="custom">Persentase Kustom (Tentukan Sendiri)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Custom Percentages input (only shown if method is custom) -->
                    <div v-if="form.budgeting_method === 'custom'" class="grid grid-cols-3 gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                        <div class="grid gap-1.5">
                            <Label for="custom_needs" class="text-xs text-slate-500 font-semibold">Needs (%)</Label>
                            <Input id="custom_needs" type="number" v-model="form.needs" class="bg-white dark:bg-slate-800" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="custom_wants" class="text-xs text-slate-500 font-semibold">Wants (%)</Label>
                            <Input id="custom_wants" type="number" v-model="form.wants" class="bg-white dark:bg-slate-800" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="custom_savings" class="text-xs text-slate-500 font-semibold">Savings (%)</Label>
                            <Input id="custom_savings" type="number" v-model="form.savings" class="bg-white dark:bg-slate-800" />
                        </div>
                        <div class="col-span-3 text-[10px]" :class="(parseInt(form.needs) + parseInt(form.wants) + parseInt(form.savings) === 100) ? 'text-emerald-500' : 'text-red-500'">
                            Total persentase: {{ parseInt(form.needs) + parseInt(form.wants) + parseInt(form.savings) }}% (harus sama dengan 100%)
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" class="border-slate-200" @click="showSettingsDialog = false">Batal</Button>
                    <Button
                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow"
                        :disabled="form.processing || (form.budgeting_method === 'custom' && (parseInt(form.needs) + parseInt(form.wants) + parseInt(form.savings) !== 100))"
                        @click="saveSettings"
                    >
                        Simpan Perubahan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
