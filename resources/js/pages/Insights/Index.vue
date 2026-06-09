<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    TrendingUp,
    Sparkles,
    AlertTriangle,
    Info,
    CheckCircle,
    AlertOctagon,
    Activity,
    PieChart,
    ArrowLeft
} from '@lucide/vue';

// ChartJS setup
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement
} from 'chart.js';
import { computed } from 'vue';
import { Pie, Bar } from 'vue-chartjs';
import { Button } from '@/components/ui/button';

// UI Components
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    ArcElement,
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Analisis AI', href: '/insights' }
        ]
    }
});

interface CategoryBreakdown {
    category: string;
    total: number;
}

interface DailyTrend {
    date: string;
    total: number;
}

interface Recommendation {
    title: string;
    description: string;
    type: 'success' | 'warning' | 'danger' | 'info';
}

const props = defineProps<{
    categoryBreakdown: CategoryBreakdown[];
    dailyTrend: DailyTrend[];
    needsTotal: number;
    wantsTotal: number;
    savingsTotal: number;
    recommendations: Recommendation[];
}>();

// Chart 1: Allocation Pie Chart Data
const allocationChartData = computed(() => {
    return {
        labels: ['Needs (Kebutuhan)', 'Wants (Keinginan)', 'Savings (Tabungan)'],
        datasets: [
            {
                backgroundColor: ['#3B82F6', '#A855F7', '#F59E0B'],
                borderWidth: 0,
                data: [props.needsTotal, props.wantsTotal, props.savingsTotal]
            }
        ]
    };
});

const allocationChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                boxWidth: 12,
                font: { size: 11 },
                color: '#64748B'
            }
        }
    }
};

// Chart 2: Category Breakdown Bar Chart Data
const categoryChartData = computed(() => {
    const labels = props.categoryBreakdown.map(c => c.category);
    const data = props.categoryBreakdown.map(c => c.total);

    return {
        labels: labels.length > 0 ? labels : ['Belum Ada Data'],
        datasets: [
            {
                label: 'Total Pengeluaran (Rp)',
                backgroundColor: '#10B981',
                borderRadius: 6,
                data: data.length > 0 ? data : [0]
            }
        ]
    };
});

const categoryChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        }
    },
    scales: {
        y: {
            grid: {
                color: 'rgba(148, 163, 184, 0.1)'
            },
            ticks: {
                color: '#64748B'
            }
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: '#64748B'
            }
        }
    }
};

// Utilities
const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const getRecommendationIcon = (type: Recommendation['type']) => {
    switch (type) {
        case 'danger': return AlertOctagon;
        case 'warning': return AlertTriangle;
        case 'info': return Info;
        case 'success': return CheckCircle;
    }
};

const getRecommendationColorClass = (type: Recommendation['type']) => {
    switch (type) {
        case 'danger': return 'border-red-500 bg-red-500/10 text-red-700 dark:text-red-400';
        case 'warning': return 'border-amber-500 bg-amber-500/10 text-amber-700 dark:text-amber-400';
        case 'info': return 'border-blue-500 bg-blue-500/10 text-blue-700 dark:text-blue-400';
        case 'success': return 'border-emerald-500 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
    }
};
</script>

<template>
    <Head title="Analisis Finansial AI - Finku" />

    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="icon" class="h-9 w-9 text-slate-500" @click="router.visit('/dashboard')">
                <ArrowLeft class="w-4 h-4" />
            </Button>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <Sparkles class="w-6 h-6 text-emerald-500" /> Analisis AI & Tren Pengeluaran
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Visualisasikan pola belanjamu dan pelajari rekomendasi finansial cerdas dari AI.
                </p>
            </div>
        </div>

        <!-- AI Insight Recommendations -->
        <div class="p-6 rounded-2xl bg-white dark:bg-gradient-to-r dark:from-slate-900 dark:to-slate-950 dark:text-white shadow-xl relative overflow-hidden border border-slate-850">
            <h2 class="text-lg font-bold flex items-center gap-2 text-emerald-400">
                <Sparkles class="w-5 h-5" /> Laporan Rekomendasi Finansial AI
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Saran otomatis berdasarkan pencatatan transaksi terbarumu.</p>

            <div class="mt-6 flex flex-col gap-4">
                <div
                    v-for="(rec, index) in recommendations"
                    :key="index"
                    class="p-4 rounded-xl border flex gap-3.5 backdrop-blur-sm"
                    :class="getRecommendationColorClass(rec.type)"
                >
                    <component :is="getRecommendationIcon(rec.type)" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <div>
                        <h4 class="text-sm font-bold">{{ rec.title }}</h4>
                        <p class="text-xs mt-1 text-slate-700 dark:text-slate-300 leading-relaxed">{{ rec.description }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Layout -->
        <div class="grid gap-6 md:grid-cols-2">

            <!-- Allocation Pie Chart -->
            <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
                <CardHeader>
                    <CardTitle class="text-md font-bold flex items-center gap-2">
                        <PieChart class="w-5 h-5 text-emerald-500" /> Rasio Anggaran (Needs vs Wants vs Savings)
                    </CardTitle>
                    <CardDescription>Rasio pengeluaran riil bulanan kamu terhadap tabungan.</CardDescription>
                </CardHeader>
                <CardContent class="h-[280px]">
                    <div v-if="needsTotal === 0 && wantsTotal === 0 && savingsTotal === 0" class="h-full flex items-center justify-center text-sm text-slate-400">
                        Belum ada data alokasi yang cukup untuk ditampilkan.
                    </div>
                    <div v-else class="h-full relative">
                        <Pie :data="allocationChartData" :options="allocationChartOptions" />
                    </div>
                </CardContent>
            </Card>

            <!-- Category Bar Chart -->
            <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
                <CardHeader>
                    <CardTitle class="text-md font-bold flex items-center gap-2">
                        <Activity class="w-5 h-5 text-emerald-500" /> Pengeluaran Berdasarkan Kategori
                    </CardTitle>
                    <CardDescription>Pos alokasi pengeluaran mana yang menghabiskan dana paling banyak.</CardDescription>
                </CardHeader>
                <CardContent class="h-[280px]">
                    <div class="h-full">
                        <Bar :data="categoryChartData" :options="categoryChartOptions" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Historical Trend Log Summary -->
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <TrendingUp class="w-5 h-5 text-emerald-500" /> Riwayat Akumulasi 30 Hari Terakhir
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tren pengeluaran harian Anda.</p>

            <div class="mt-4 flex flex-col gap-2.5">
                <div v-if="dailyTrend.length === 0" class="text-center py-6 text-sm text-slate-400">
                    Belum ada riwayat pengeluaran dalam 30 hari terakhir.
                </div>
                <div
                    v-for="trend in dailyTrend"
                    :key="trend.date"
                    class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800"
                >
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-350">{{ trend.date }}</span>
                    <strong class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ formatRupiah(trend.total) }}</strong>
                </div>
            </div>
        </div>
    </div>
</template>
