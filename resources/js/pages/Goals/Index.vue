<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Plus, 
    Target, 
    Trash2, 
    Coins, 
    Calendar,
    CheckCircle2, 
    AlertCircle, 
    Sparkles, 
    TrendingUp
} from '@lucide/vue';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Target Tabungan', href: '/goals' }
        ]
    }
});

interface Goal {
    id: number;
    name: string;
    target_amount: number;
    current_amount: number;
    target_date: string;
    icon: string;
    completed: boolean;
}

defineProps<{
    goals: Goal[];
}>();

// Form Setup
const showAddGoalDialog = ref(false);
const showTopUpDialog = ref(false);
const activeGoal = ref<Goal | null>(null);

const addGoalForm = useForm({
    name: '',
    target_amount: '',
    current_amount: '',
    target_date: new Date(new Date().setMonth(new Date().getMonth() + 3)).toISOString().split('T')[0], // 3 months from now
    icon: '🎯',
});

const topUpForm = useForm({
    amount: '',
});

const storeGoal = () => {
    addGoalForm.post('/goals', {
        onSuccess: () => {
            showAddGoalDialog.value = false;
            addGoalForm.reset('name', 'target_amount', 'current_amount');
        }
    });
};

const openTopUp = (goal: Goal) => {
    activeGoal.value = goal;
    showTopUpDialog.value = true;
};

const submitTopUp = () => {
    if (!activeGoal.value) return;
    topUpForm.post(`/goals/${activeGoal.value.id}/topup`, {
        onSuccess: () => {
            showTopUpDialog.value = false;
            topUpForm.reset('amount');
            activeGoal.value = null;
        }
    });
};

const deleteGoal = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus target tabungan ini?')) {
        router.delete(`/goals/${id}`);
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

const getPercent = (current: number, target: number) => {
    if (target <= 0) return 0;
    const p = (current / target) * 100;
    return Math.min(Math.round(p), 100);
};

const getDaysRemaining = (dateStr: string) => {
    const target = new Date(dateStr);
    const today = new Date();
    // Reset hours
    target.setHours(0,0,0,0);
    today.setHours(0,0,0,0);
    
    const diffTime = target.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) return 'Sudah melewati tenggat';
    if (diffDays === 0) return 'Hari ini!';
    return `${diffDays} hari lagi`;
};

const iconsList = ['🎯', '🏠', '🚗', '🎓', '✈️', '💻', '💼', '💍', '🛡️', '💰'];
</script>

<template>
    <Head title="Target Tabungan - Finku" />

    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <Target class="w-6 h-6 text-amber-500" /> Target Tabungan (Quests)
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Buat target menabung, kumpulkan dana darurat, dan raih reward XP yang lebih tinggi!
                </p>
            </div>
            
            <Button class="bg-amber-600 hover:bg-amber-500 text-white font-medium shadow" @click="showAddGoalDialog = true">
                <Plus class="w-4 h-4 mr-2" /> Buat Target Baru
            </Button>
        </div>

        <!-- Goals Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div v-if="goals.length === 0" class="col-span-full text-center py-12 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-8">
                <div class="w-16 h-16 bg-amber-500/10 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-500/20 shadow-inner">
                    <Target class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-850 dark:text-slate-200">Belum ada target tabungan</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    Ayo mulai buat rencana masa depanmu! Beli laptop baru, jalan-jalan, atau siapkan dana darurat pertama kamu.
                </p>
                <Button class="mt-4 bg-amber-600 hover:bg-amber-500 text-white font-medium shadow" @click="showAddGoalDialog = true">
                    Buat Target Sekarang (+20 XP)
                </Button>
            </div>

            <!-- Goal Card -->
            <Card 
                v-for="goal in goals" 
                :key="goal.id"
                class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md relative overflow-hidden flex flex-col justify-between"
                :class="{ 'opacity-85 border-emerald-500/40 dark:border-emerald-950/40': goal.completed }"
            >
                <div v-if="goal.completed" class="absolute -right-16 -top-16 w-32 h-32 bg-emerald-500/10 dark:bg-emerald-950/30 rounded-full flex items-end justify-center rotate-45 pointer-events-none">
                    <CheckCircle2 class="w-8 h-8 text-emerald-500 mb-4 mr-4 -rotate-45" />
                </div>

                <CardHeader class="pb-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850/60 border border-slate-100 dark:border-slate-800/80 shadow-sm">{{ goal.icon || '🎯' }}</span>
                            <div>
                                <CardTitle class="text-md font-bold text-slate-800 dark:text-slate-200">{{ goal.name }}</CardTitle>
                                <CardDescription class="text-xs flex items-center gap-1 mt-0.5">
                                    <Calendar class="w-3.5 h-3.5 text-slate-400" />
                                    <span>Tenggat: {{ goal.target_date }} ({{ getDaysRemaining(goal.target_date) }})</span>
                                </CardDescription>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                
                <CardContent class="pb-4">
                    <div class="flex justify-between text-xs text-slate-500 mb-1.5 font-medium">
                        <span>Progress Tabungan</span>
                        <span>{{ getPercent(goal.current_amount, goal.target_amount) }}%</span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200/50 dark:border-slate-800">
                        <div 
                            class="h-full bg-gradient-to-r transition-all duration-500 rounded-full" 
                            :class="goal.completed ? 'from-emerald-500 to-teal-400' : 'from-amber-500 to-yellow-400'"
                            :style="{ width: `${getPercent(goal.current_amount, goal.target_amount)}%` }"
                        ></div>
                    </div>

                    <div class="mt-4 flex justify-between items-baseline">
                        <div>
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">Terkumpul</div>
                            <div class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ formatRupiah(goal.current_amount) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">Target</div>
                            <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ formatRupiah(goal.target_amount) }}</div>
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between gap-3">
                    <Button variant="ghost" size="sm" class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20" @click="deleteGoal(goal.id)">
                        <Trash2 class="w-4 h-4 mr-1.5" /> Hapus
                    </Button>
                    
                    <Button 
                        v-if="!goal.completed"
                        size="sm" 
                        class="bg-amber-600 hover:bg-amber-500 text-white shadow font-medium"
                        @click="openTopUp(goal)"
                    >
                        <Coins class="w-4 h-4 mr-1.5" /> Top Up Tabungan
                    </Button>
                    <span v-else class="text-xs text-emerald-600 dark:text-emerald-500 font-bold flex items-center gap-1">
                        <CheckCircle2 class="w-4 h-4" /> Quest Selesai!
                    </span>
                </CardFooter>
            </Card>
        </div>

        <!-- Create Goal Modal Dialog -->
        <Dialog v-model:open="showAddGoalDialog">
            <DialogContent class="sm:max-w-[480px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Target class="w-5 h-5 text-amber-500" /> Buat Target Tabungan Baru
                    </DialogTitle>
                    <DialogDescription class="text-slate-500">
                        Atur target menabung kamu. Menyelesaikan target tabungan memberikan XP dan bonus pencapaian tinggi!
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <!-- Icon emoji selector -->
                    <div class="grid gap-2">
                        <Label class="text-slate-700 dark:text-slate-300 font-medium">Pilih Ikon Target</Label>
                        <div class="flex flex-wrap gap-2.5">
                            <button 
                                v-for="ic in iconsList" 
                                :key="ic"
                                type="button"
                                class="text-2xl p-2 rounded-lg border hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                :class="addGoalForm.icon === ic ? 'border-amber-500 bg-amber-500/10 dark:bg-amber-500/15' : 'border-slate-200 dark:border-slate-800'"
                                @click="addGoalForm.icon = ic"
                            >
                                {{ ic }}
                            </button>
                        </div>
                    </div>

                    <!-- Nama Target -->
                    <div class="grid gap-2">
                        <Label for="goal_name" class="text-slate-700 dark:text-slate-300 font-medium">Nama Target Tabungan</Label>
                        <Input 
                            id="goal_name" 
                            v-model="addGoalForm.name" 
                            placeholder="Contoh: Dana Darurat, Beli Laptop, Liburan Bali"
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>

                    <!-- Jumlah Target -->
                    <div class="grid gap-2">
                        <Label for="target_amount" class="text-slate-700 dark:text-slate-300 font-medium">Jumlah Target Tabungan (Rupiah)</Label>
                        <Input 
                            id="target_amount" 
                            type="number" 
                            v-model="addGoalForm.target_amount" 
                            placeholder="Contoh: 10000000"
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>

                    <!-- Saldo Awal (opsional) -->
                    <div class="grid gap-2">
                        <Label for="current_amount" class="text-slate-700 dark:text-slate-300 font-medium">Saldo Awal (Opsional, Rupiah)</Label>
                        <Input 
                            id="current_amount" 
                            type="number" 
                            v-model="addGoalForm.current_amount" 
                            placeholder="Contoh: 500000"
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>

                    <!-- Tanggal Target -->
                    <div class="grid gap-2">
                        <Label for="target_date" class="text-slate-700 dark:text-slate-300 font-medium">Tanggal Target Tercapai</Label>
                        <Input 
                            id="target_date" 
                            type="date" 
                            v-model="addGoalForm.target_date" 
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>
                </div>

                <DialogFooter class="flex items-center justify-between">
                    <span class="text-xs text-amber-500 font-semibold flex items-center gap-1.5">
                        <Sparkles class="w-3.5 h-3.5" /> Dapatkan +20 XP
                    </span>
                    <div class="flex gap-2">
                        <Button variant="outline" class="border-slate-200" @click="showAddGoalDialog = false">Batal</Button>
                        <Button 
                            class="bg-amber-600 hover:bg-amber-500 text-white font-medium shadow"
                            :disabled="addGoalForm.processing"
                            @click="storeGoal"
                        >
                            Buat Target
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Top Up Savings Modal Dialog -->
        <Dialog v-model:open="showTopUpDialog">
            <DialogContent class="sm:max-w-[420px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800" v-if="activeGoal">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Coins class="w-5 h-5 text-amber-500" /> Top Up Tabungan
                    </DialogTitle>
                    <DialogDescription class="text-slate-500">
                        Tambahkan dana tabungan untuk target **'{{ activeGoal.name }}'**. Aksi ini juga akan menambahkan **+15 XP** ke akunmu!
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex justify-between text-sm">
                        <span class="text-slate-500">Saldo Saat Ini:</span>
                        <strong class="text-slate-800 dark:text-slate-200">{{ formatRupiah(activeGoal.current_amount) }} / {{ formatRupiah(activeGoal.target_amount) }}</strong>
                    </div>

                    <div class="grid gap-2">
                        <Label for="topup_amount" class="text-slate-700 dark:text-slate-300 font-medium">Jumlah Setoran (Rupiah)</Label>
                        <Input 
                            id="topup_amount" 
                            type="number" 
                            v-model="topUpForm.amount" 
                            placeholder="Contoh: 100000"
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>
                </div>

                <DialogFooter class="flex items-center justify-between">
                    <span class="text-xs text-amber-500 font-semibold flex items-center gap-1.5">
                        <Sparkles class="w-3.5 h-3.5" /> Dapatkan +15 XP
                    </span>
                    <div class="flex gap-2">
                        <Button variant="outline" class="border-slate-200" @click="showTopUpDialog = false">Batal</Button>
                        <Button 
                            class="bg-amber-600 hover:bg-amber-500 text-white font-medium shadow"
                            :disabled="topUpForm.processing"
                            @click="submitTopUp"
                        >
                            Konfirmasi Setoran
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
