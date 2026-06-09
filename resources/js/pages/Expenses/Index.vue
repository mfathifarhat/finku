<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    Edit2,
    Wallet,
    Filter,
    Check,
    AlertCircle,
    ArrowLeft,
    Sparkles,
    Loader2,
    Camera,
    TrendingUp,
    TrendingDown,
    ArrowDownRight,
    ArrowUpRight,
    Coins
} from '@lucide/vue';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Transaksi', href: '/expenses' }
        ]
    }
});

interface Expense {
    id: number;
    amount: number;
    category: string;
    date: string;
    description: string | null;
    type: 'needs' | 'wants';
}

interface Income {
    id: number;
    amount: number;
    category: string;
    date: string;
    description: string | null;
}

const props = defineProps<{
    expenses: Expense[];
    incomes: Income[];
    categories: string[];
    income_categories: string[];
    filters: {
        type?: string;
        category?: string;
        income_category?: string;
    };
}>();

// UI Tabs
const currentTab = ref<'expense' | 'income'>('expense');

// Dialog State
const showAddExpenseDialog = ref(false);
const showEditExpenseDialog = ref(false);
const showAddIncomeDialog = ref(false);
const showEditIncomeDialog = ref(false);

const editingExpense = ref<Expense | null>(null);
const editingIncome = ref<Income | null>(null);

// Forms Setup
const addExpenseForm = useForm({
    amount: '',
    category: '',
    date: new Date().toISOString().split('T')[0],
    description: '',
    type: 'needs',
});

const addIncomeForm = useForm({
    amount: '',
    category: '',
    date: new Date().toISOString().split('T')[0],
    description: '',
});

const editExpenseForm = useForm({
    amount: 0,
    category: '',
    date: '',
    description: '',
    type: 'needs',
});

const editIncomeForm = useForm({
    amount: 0,
    category: '',
    date: '',
    description: '',
});

// Scan Receipt / Struk Setup (only for expenses)
const isScanning = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const scanSuccessMsg = ref('');

const triggerFileSelect = () => {
    fileInput.value?.click();
};

// Scan Transfer Proof Setup (for incomes)
const isScanningIncome = ref(false);
const incomeFileInput = ref<HTMLInputElement | null>(null);
const scanIncomeSuccessMsg = ref('');

const triggerIncomeFileSelect = () => {
    incomeFileInput.value?.click();
};

const onIncomeFileSelected = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    const formData = new FormData();
    formData.append('receipt', file);

    isScanningIncome.value = true;
    scanIncomeSuccessMsg.value = '';

    try {
        const xsrfToken = getCookie('XSRF-TOKEN');
        const response = await fetch('/incomes/scan', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': xsrfToken,
                'Accept': 'application/json',
            }
        });

        const result = await response.json();
        
        if (response.ok && result.success) {
            const data = result.data;
            addIncomeForm.amount = data.amount.toString();
            addIncomeForm.category = data.category;
            addIncomeForm.date = data.date;
            addIncomeForm.description = data.description;
            
            scanIncomeSuccessMsg.value = result.message || 'Bukti transfer berhasil dipindai!';
        } else {
            alert(result.message || 'Gagal memindai bukti transfer. Pastikan file gambar Anda berupa bukti transfer bank.');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan saat memproses gambar bukti transfer.');
    } finally {
        isScanningIncome.value = false;
        if (incomeFileInput.value) {
            incomeFileInput.value.value = '';
        }
    }
};

const getCookie = (name: string): string => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return decodeURIComponent(parts.pop()?.split(';').shift() || '');
    return '';
};

const onFileSelected = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (!target.files || target.files.length === 0) return;

    const file = target.files[0];
    const formData = new FormData();
    formData.append('receipt', file);

    isScanning.value = true;
    scanSuccessMsg.value = '';

    try {
        const xsrfToken = getCookie('XSRF-TOKEN');
        const response = await fetch('/expenses/scan', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': xsrfToken,
                'Accept': 'application/json',
            }
        });

        const result = await response.json();
        
        if (response.ok && result.success) {
            const data = result.data;
            addExpenseForm.amount = data.amount.toString();
            addExpenseForm.category = data.category;
            addExpenseForm.type = data.type;
            addExpenseForm.date = data.date;
            addExpenseForm.description = data.description;
            
            scanSuccessMsg.value = result.message || 'Struk berhasil dipindai!';
        } else {
            alert(result.message || 'Gagal memindai struk. Pastikan file gambar Anda berupa struk belanja.');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan saat memproses gambar struk.');
    } finally {
        isScanning.value = false;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
};

// Filters Setup
const filterType = ref(props.filters.type || 'all');
const filterCategory = ref(props.filters.category || 'all');
const filterIncomeCategory = ref(props.filters.income_category || 'all');

const applyFilters = () => {
    const data: Record<string, string> = {};
    if (currentTab.value === 'expense') {
        if (filterType.value !== 'all') data.type = filterType.value;
        if (filterCategory.value !== 'all') data.category = filterCategory.value;
    } else {
        if (filterIncomeCategory.value !== 'all') data.income_category = filterIncomeCategory.value;
    }
    router.get('/expenses', data, { preserveState: true });
};

const resetFilters = () => {
    filterType.value = 'all';
    filterCategory.value = 'all';
    filterIncomeCategory.value = 'all';
    router.get('/expenses');
};

// Expense actions
const storeExpense = () => {
    addExpenseForm.post('/expenses', {
        onSuccess: () => {
            showAddExpenseDialog.value = false;
            addExpenseForm.reset('amount', 'description');
            scanSuccessMsg.value = '';
        }
    });
};

const editExpense = (expense: Expense) => {
    editingExpense.value = expense;
    editExpenseForm.amount = expense.amount;
    editExpenseForm.category = expense.category;
    editExpenseForm.date = expense.date;
    editExpenseForm.description = expense.description || '';
    editExpenseForm.type = expense.type;
    showEditExpenseDialog.value = true;
};

const updateExpense = () => {
    if (!editingExpense.value) return;
    editExpenseForm.put(`/expenses/${editingExpense.value.id}`, {
        onSuccess: () => {
            showEditExpenseDialog.value = false;
            editingExpense.value = null;
        }
    });
};

const deleteExpense = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')) {
        router.delete(`/expenses/${id}`);
    }
};

// Income actions
const storeIncome = () => {
    addIncomeForm.post('/incomes', {
        onSuccess: () => {
            showAddIncomeDialog.value = false;
            addIncomeForm.reset('amount', 'description');
            scanIncomeSuccessMsg.value = '';
        }
    });
};

const editIncome = (income: Income) => {
    editingIncome.value = income;
    editIncomeForm.amount = income.amount;
    editIncomeForm.category = income.category;
    editIncomeForm.date = income.date;
    editIncomeForm.description = income.description || '';
    showEditIncomeDialog.value = true;
};

const updateIncome = () => {
    if (!editingIncome.value) return;
    editIncomeForm.put(`/incomes/${editingIncome.value.id}`, {
        onSuccess: () => {
            showEditIncomeDialog.value = false;
            editingIncome.value = null;
        }
    });
};

const deleteIncome = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus pemasukan ini?')) {
        router.delete(`/incomes/${id}`);
    }
};

// Utils
const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const totalExpensesSum = () => {
    return props.expenses.reduce((sum, item) => sum + Number(item.amount), 0);
};

const totalIncomesSum = () => {
    return props.incomes.reduce((sum, item) => sum + Number(item.amount), 0);
};
</script>

<template>
    <Head title="Riwayat Transaksi Keuangan - Finku" />

    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <Wallet class="w-6 h-6 text-emerald-500" /> Log Transaksi Keuangan
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Catat dan kelola pemasukan harian serta pengeluaran bulanan Anda secara terpadu di satu tempat.
                </p>
            </div>

            <div class="flex gap-2">
                <Button 
                    variant="outline"
                    class="border-emerald-600/30 text-emerald-700 hover:bg-emerald-50/50 hover:text-emerald-800 font-medium shadow-sm"
                    @click="showAddIncomeDialog = true"
                >
                    <Coins class="w-4 h-4 mr-2" /> Catat Pemasukan
                </Button>
                <Button 
                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow"
                    @click="showAddExpenseDialog = true"
                >
                    <Plus class="w-4 h-4 mr-2" /> Catat Pengeluaran
                </Button>
            </div>
        </div>

        <!-- Quick Statistics Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2">
            <!-- Total Incomes Card -->
            <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
                <CardContent class="p-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pemasukan Bulan Ini</p>
                        <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-500 mt-1.5">{{ formatRupiah(totalIncomesSum()) }}</h3>
                    </div>
                    <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 rounded-2xl">
                        <ArrowUpRight class="w-7 h-7" />
                    </div>
                </CardContent>
            </Card>

            <!-- Total Expenses Card -->
            <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
                <CardContent class="p-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pengeluaran Bulan Ini</p>
                        <h3 class="text-2xl font-extrabold text-rose-600 dark:text-rose-500 mt-1.5">{{ formatRupiah(totalExpensesSum()) }}</h3>
                    </div>
                    <div class="p-3 bg-rose-500/10 text-rose-600 dark:text-rose-500 rounded-2xl">
                        <ArrowDownRight class="w-7 h-7" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex border-b border-slate-200 dark:border-slate-800">
            <button
                @click="currentTab = 'expense'"
                class="py-3 px-6 font-bold text-sm border-b-2 transition-all flex items-center gap-2"
                :class="currentTab === 'expense' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
            >
                <TrendingDown class="w-4 h-4" /> Pengeluaran ({{ expenses.length }})
            </button>
            <button
                @click="currentTab = 'income'"
                class="py-3 px-6 font-bold text-sm border-b-2 transition-all flex items-center gap-2"
                :class="currentTab === 'income' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'"
            >
                <TrendingUp class="w-4 h-4" /> Pemasukan ({{ incomes.length }})
            </button>
        </div>

        <!-- Filters Section -->
        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md flex flex-wrap gap-4 items-end">
            
            <!-- Filters for Expenses Tab -->
            <template v-if="currentTab === 'expense'">
                <!-- Filter Type (Needs/Wants) -->
                <div class="grid gap-1.5 flex-1 min-w-[200px]">
                    <Label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Jenis Kategori</Label>
                    <Select v-model="filterType" @update:model-value="applyFilters">
                        <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                            <SelectValue placeholder="Semua Jenis" />
                        </SelectTrigger>
                        <SelectContent class="bg-white dark:bg-slate-900">
                            <SelectItem value="all">Semua Jenis (Needs/Wants)</SelectItem>
                            <SelectItem value="needs">Needs (Kebutuhan)</SelectItem>
                            <SelectItem value="wants">Wants (Keinginan)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Filter Category -->
                <div class="grid gap-1.5 flex-1 min-w-[200px]">
                    <Label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pos Kategori</Label>
                    <Select v-model="filterCategory" @update:model-value="applyFilters">
                        <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                            <SelectValue placeholder="Semua Kategori" />
                        </SelectTrigger>
                        <SelectContent class="bg-white dark:bg-slate-900">
                            <SelectItem value="all">Semua Kategori</SelectItem>
                            <SelectItem v-for="cat in categories" :key="cat" :value="cat">
                                {{ cat }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </template>

            <!-- Filters for Incomes Tab -->
            <template v-else>
                <!-- Filter Income Category -->
                <div class="grid gap-1.5 flex-1 min-w-[200px]">
                    <Label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Sumber Kategori</Label>
                    <Select v-model="filterIncomeCategory" @update:model-value="applyFilters">
                        <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                            <SelectValue placeholder="Semua Sumber" />
                        </SelectTrigger>
                        <SelectContent class="bg-white dark:bg-slate-900">
                            <SelectItem value="all">Semua Sumber Pemasukan</SelectItem>
                            <SelectItem v-for="cat in income_categories" :key="cat" :value="cat">
                                {{ cat }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </template>

            <!-- Clear button -->
            <Button variant="outline" class="border-slate-200 dark:border-slate-800 h-10" @click="resetFilters">
                Reset Filter
            </Button>
        </div>

        <!-- Table Display Card -->
        <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
            <CardHeader>
                <CardTitle class="text-md font-bold">
                    {{ currentTab === 'expense' ? 'Daftar Pengeluaran' : 'Daftar Pemasukan' }}
                </CardTitle>
                <CardDescription>
                    {{ currentTab === 'expense' ? 'Menampilkan riwayat log pengeluaran keuangan Anda.' : 'Menampilkan riwayat log pemasukan keuangan Anda.' }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                        <!-- Expenses Table Header -->
                        <thead v-if="currentTab === 'expense'" class="text-xs text-slate-700 dark:text-slate-300 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-semibold w-10">Tipe</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Kategori</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Alokasi</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Deskripsi</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Tanggal</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Jumlah</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        
                        <!-- Incomes Table Header -->
                        <thead v-else class="text-xs text-slate-700 dark:text-slate-300 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-semibold">Sumber Kategori</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Deskripsi</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Tanggal</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Jumlah</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody>
                            <!-- Expenses Tab Body -->
                            <template v-if="currentTab === 'expense'">
                                <tr v-if="expenses.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada transaksi pengeluaran yang dicatat.
                                    </td>
                                </tr>
                                <tr v-for="expense in expenses" :key="expense.id" class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800/80 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="px-6 py-4">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full"
                                            :class="expense.type === 'needs' ? 'bg-blue-500' : 'bg-purple-500'"
                                            :title="expense.type === 'needs' ? 'Needs (Kebutuhan)' : 'Wants (Keinginan)'"></span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ expense.category }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                            :class="expense.type === 'needs' ? 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-purple-50 text-purple-800 dark:bg-purple-950 dark:text-purple-300'">
                                            {{ expense.type === 'needs' ? 'Needs' : 'Wants' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 max-w-[200px] truncate text-slate-500 dark:text-slate-400">
                                        {{ expense.description || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                        {{ expense.date }}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-rose-600 dark:text-rose-500">
                                        -{{ formatRupiah(expense.amount) }}
                                    </td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500" @click="editExpense(expense)">
                                            <Edit2 class="w-3.5 h-3.5" />
                                        </Button>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20" @click="deleteExpense(expense.id)">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                    </td>
                                </tr>
                            </template>

                            <!-- Incomes Tab Body -->
                            <template v-else>
                                <tr v-if="incomes.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada transaksi pemasukan yang dicatat.
                                    </td>
                                </tr>
                                <tr v-for="income in incomes" :key="income.id" class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800/80 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ income.category }}
                                    </td>
                                    <td class="px-6 py-4 max-w-[200px] truncate text-slate-500 dark:text-slate-400">
                                        {{ income.description || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                        {{ income.date }}
                                    </td>
                                    <td class="px-6 py-4 font-bold text-emerald-600 dark:text-emerald-500">
                                        +{{ formatRupiah(income.amount) }}
                                    </td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500" @click="editIncome(income)">
                                            <Edit2 class="w-3.5 h-3.5" />
                                        </Button>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20" @click="deleteIncome(income.id)">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- RECORD EXPENSE DIALOG MODAL -->
        <Dialog v-model:open="showAddExpenseDialog">
            <DialogContent class="sm:max-w-[480px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Wallet class="w-5 h-5 text-emerald-500" /> Catat Pengeluaran Baru
                    </DialogTitle>
                    <DialogDescription class="text-slate-500">
                        Catat pengeluaran Anda hari ini. Aksi ini juga akan menambahkan <b>+10 XP</b> ke karakter finansialmu!
                    </DialogDescription>
                </DialogHeader>

                <!-- AI OCR Receipt Scan Area -->
                <div class="mt-2 p-4 rounded-xl border border-dashed border-emerald-300 bg-emerald-50/40 text-center flex flex-col items-center justify-center gap-2 transition-all hover:bg-emerald-50/70 dark:bg-emerald-950/10 dark:border-emerald-800 dark:hover:bg-emerald-950/20">
                    <div v-if="isScanning" class="flex flex-col items-center gap-2 py-2">
                        <Loader2 class="w-7 h-7 text-emerald-600 animate-spin" />
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 animate-pulse">Sedang memindai struk Anda dengan AI...</p>
                    </div>
                    <div v-else class="flex flex-col items-center gap-1.5 w-full cursor-pointer" @click="triggerFileSelect">
                        <div class="p-2 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                            <Camera class="w-5 h-5" />
                        </div>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">Scan Struk / Nota dengan AI</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Unggah foto struk untuk mengisi form di bawah secara otomatis</span>
                        <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="onFileSelected" />
                    </div>
                </div>

                <!-- Toast Success Scan -->
                <div v-if="scanSuccessMsg" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-start gap-2 dark:bg-emerald-950/20 dark:border-emerald-800 dark:text-emerald-300">
                    <Check class="w-4.5 h-4.5 text-emerald-600 shrink-0 mt-0.5" />
                    <div>
                        <span class="font-bold">Berhasil memindai:</span> {{ scanSuccessMsg }}
                        <p class="mt-0.5 text-slate-500 dark:text-slate-400">Silakan tinjau kembali data di bawah sebelum menyimpan.</p>
                    </div>
                </div>

                <div class="grid gap-4 py-2">
                    <!-- Amount -->
                    <div class="grid gap-2">
                        <Label for="amount" class="text-slate-700 dark:text-slate-300 font-medium">Jumlah Pengeluaran (Rupiah)</Label>
                        <Input id="amount" type="number" v-model="addExpenseForm.amount" placeholder="Contoh: 50000" class="bg-slate-50 dark:bg-slate-800" />
                    </div>

                    <!-- Category -->
                    <div class="grid gap-2">
                        <Label for="category" class="text-slate-700 dark:text-slate-300 font-medium">Pos Kategori</Label>
                        <Select v-model="addExpenseForm.category">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih Kategori" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem v-for="cat in categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Type (Needs/Wants) -->
                    <div class="grid gap-2">
                        <Label for="type" class="text-slate-700 dark:text-slate-300 font-medium">Jenis Pengeluaran</Label>
                        <Select v-model="addExpenseForm.type">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih Jenis" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem value="needs">Needs (Kebutuhan Pokok, Transportasi, Cicilan)</SelectItem>
                                <SelectItem value="wants">Wants (Belanja Impulsif, Nongkrong, Liburan, Hiburan)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date -->
                    <div class="grid gap-2">
                        <Label for="date" class="text-slate-700 dark:text-slate-300 font-medium">Tanggal</Label>
                        <Input id="date" type="date" v-model="addExpenseForm.date" class="bg-slate-50 dark:bg-slate-800" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="description" class="text-slate-700 dark:text-slate-300 font-medium">Deskripsi / Keterangan</Label>
                        <Input id="description" v-model="addExpenseForm.description" placeholder="Contoh: Beli makan siang nasi padang" class="bg-slate-50 dark:bg-slate-800" />
                    </div>
                </div>

                <DialogFooter>
                    <div class="flex items-center justify-between w-full">
                        <span class="text-xs text-amber-500 font-semibold flex items-center gap-1.5">
                            <Sparkles class="w-3.5 h-3.5" /> Dapatkan +10 XP
                        </span>
                        <div class="flex gap-2">
                            <Button variant="outline" class="border-slate-200" @click="showAddExpenseDialog = false">Batal</Button>
                            <Button class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow" :disabled="addExpenseForm.processing || isScanning" @click="storeExpense">
                                Simpan Pengeluaran
                            </Button>
                        </div>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- RECORD INCOME DIALOG MODAL -->
        <Dialog v-model:open="showAddIncomeDialog">
            <DialogContent class="sm:max-w-[480px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Coins class="w-5 h-5 text-emerald-500" /> Catat Pemasukan Baru
                    </DialogTitle>
                    <DialogDescription class="text-slate-500">
                        Catat pemasukan finansial yang Anda peroleh hari ini. Aksi ini juga akan menambahkan <b>+10 XP</b> ke karakter finansialmu!
                    </DialogDescription>
                </DialogHeader>

                <!-- AI OCR Bukti Transfer Scan Area -->
                <div class="mt-2 p-4 rounded-xl border border-dashed border-emerald-300 bg-emerald-50/40 text-center flex flex-col items-center justify-center gap-2 transition-all hover:bg-emerald-50/70 dark:bg-emerald-950/10 dark:border-emerald-800 dark:hover:bg-emerald-950/20">
                    <div v-if="isScanningIncome" class="flex flex-col items-center gap-2 py-2">
                        <Loader2 class="w-7 h-7 text-emerald-600 animate-spin" />
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 animate-pulse">Sedang memindai bukti transfer Anda dengan AI...</p>
                    </div>
                    <div v-else class="flex flex-col items-center gap-1.5 w-full cursor-pointer" @click="triggerIncomeFileSelect">
                        <div class="p-2 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                            <Camera class="w-5 h-5" />
                        </div>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-200">Scan Bukti Transfer dengan AI</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Unggah foto bukti transfer untuk mengisi form di bawah secara otomatis</span>
                        <input type="file" ref="incomeFileInput" class="hidden" accept="image/*" @change="onIncomeFileSelected" />
                    </div>
                </div>

                <!-- Toast Success Scan -->
                <div v-if="scanIncomeSuccessMsg" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-start gap-2 dark:bg-emerald-950/20 dark:border-emerald-800 dark:text-emerald-300">
                    <Check class="w-4.5 h-4.5 text-emerald-600 shrink-0 mt-0.5" />
                    <div>
                        <span class="font-bold">Berhasil memindai:</span> {{ scanIncomeSuccessMsg }}
                        <p class="mt-0.5 text-slate-500 dark:text-slate-400">Silakan tinjau kembali data di bawah sebelum menyimpan.</p>
                    </div>
                </div>

                <div class="grid gap-4 py-4">
                    <!-- Amount -->
                    <div class="grid gap-2">
                        <Label for="inc_amount" class="text-slate-700 dark:text-slate-300 font-medium">Jumlah Pemasukan (Rupiah)</Label>
                        <Input id="inc_amount" type="number" v-model="addIncomeForm.amount" placeholder="Contoh: 1500000" class="bg-slate-50 dark:bg-slate-800" />
                    </div>

                    <!-- Category -->
                    <div class="grid gap-2">
                        <Label for="inc_category" class="text-slate-700 dark:text-slate-300 font-medium">Sumber Kategori</Label>
                        <Select v-model="addIncomeForm.category">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih Sumber" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem v-for="cat in income_categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date -->
                    <div class="grid gap-2">
                        <Label for="inc_date" class="text-slate-700 dark:text-slate-300 font-medium">Tanggal</Label>
                        <Input id="inc_date" type="date" v-model="addIncomeForm.date" class="bg-slate-50 dark:bg-slate-800" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="inc_description" class="text-slate-700 dark:text-slate-300 font-medium">Deskripsi / Keterangan</Label>
                        <Input id="inc_description" v-model="addIncomeForm.description" placeholder="Contoh: Pembayaran proyek freelance logo" class="bg-slate-50 dark:bg-slate-800" />
                    </div>
                </div>

                <DialogFooter>
                    <div class="flex items-center justify-between w-full">
                        <span class="text-xs text-amber-500 font-semibold flex items-center gap-1.5">
                            <Sparkles class="w-3.5 h-3.5" /> Dapatkan +10 XP
                        </span>
                        <div class="flex gap-2">
                            <Button variant="outline" class="border-slate-200" @click="showAddIncomeDialog = false">Batal</Button>
                            <Button class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow" :disabled="addIncomeForm.processing" @click="storeIncome">
                                Simpan Pemasukan
                            </Button>
                        </div>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- EDIT EXPENSE DIALOG MODAL -->
        <Dialog v-model:open="showEditExpenseDialog">
            <DialogContent class="sm:max-w-[480px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Edit2 class="w-5 h-5 text-emerald-500" /> Edit Pengeluaran
                    </DialogTitle>
                    <DialogDescription class="text-slate-500">
                        Ubah data pencatatan pengeluaran Anda.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <!-- Amount -->
                    <div class="grid gap-2">
                        <Label for="edit_amount" class="text-slate-700 dark:text-slate-300 font-medium">Jumlah Pengeluaran (Rupiah)</Label>
                        <Input id="edit_amount" type="number" v-model="editExpenseForm.amount" class="bg-slate-50 dark:bg-slate-800" />
                    </div>

                    <!-- Category -->
                    <div class="grid gap-2">
                        <Label for="edit_category" class="text-slate-700 dark:text-slate-300 font-medium">Pos Kategori</Label>
                        <Select v-model="editExpenseForm.category">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih Kategori" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem v-for="cat in categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Type (Needs/Wants) -->
                    <div class="grid gap-2">
                        <Label for="edit_type" class="text-slate-700 dark:text-slate-300 font-medium">Jenis Pengeluaran</Label>
                        <Select v-model="editExpenseForm.type">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih Jenis" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem value="needs">Needs (Kebutuhan Pokok)</SelectItem>
                                <SelectItem value="wants">Wants (Keinginan)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date -->
                    <div class="grid gap-2">
                        <Label for="edit_date" class="text-slate-700 dark:text-slate-300 font-medium">Tanggal</Label>
                        <Input id="edit_date" type="date" v-model="editExpenseForm.date" class="bg-slate-50 dark:bg-slate-800" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="edit_description" class="text-slate-700 dark:text-slate-300 font-medium">Deskripsi / Keterangan</Label>
                        <Input id="edit_description" v-model="editExpenseForm.description" class="bg-slate-50 dark:bg-slate-800" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" class="border-slate-200" @click="showEditExpenseDialog = false">Batal</Button>
                    <Button class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow" :disabled="editExpenseForm.processing" @click="updateExpense">
                        Simpan Perubahan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- EDIT INCOME DIALOG MODAL -->
        <Dialog v-model:open="showEditIncomeDialog">
            <DialogContent class="sm:max-w-[480px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Edit2 class="w-5 h-5 text-emerald-500" /> Edit Pemasukan
                    </DialogTitle>
                    <DialogDescription class="text-slate-500">
                        Ubah data pencatatan pemasukan Anda.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <!-- Amount -->
                    <div class="grid gap-2">
                        <Label for="edit_inc_amount" class="text-slate-700 dark:text-slate-300 font-medium">Jumlah Pemasukan (Rupiah)</Label>
                        <Input id="edit_inc_amount" type="number" v-model="editIncomeForm.amount" class="bg-slate-50 dark:bg-slate-800" />
                    </div>

                    <!-- Category -->
                    <div class="grid gap-2">
                        <Label for="edit_inc_category" class="text-slate-700 dark:text-slate-300 font-medium">Sumber Kategori</Label>
                        <Select v-model="editIncomeForm.category">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih Sumber" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem v-for="cat in income_categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date -->
                    <div class="grid gap-2">
                        <Label for="edit_inc_date" class="text-slate-700 dark:text-slate-300 font-medium">Tanggal</Label>
                        <Input id="edit_inc_date" type="date" v-model="editIncomeForm.date" class="bg-slate-50 dark:bg-slate-800" />
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="edit_inc_description" class="text-slate-700 dark:text-slate-300 font-medium">Deskripsi / Keterangan</Label>
                        <Input id="edit_inc_description" v-model="editIncomeForm.description" class="bg-slate-50 dark:bg-slate-800" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" class="border-slate-200" @click="showEditIncomeDialog = false">Batal</Button>
                    <Button class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow" :disabled="editIncomeForm.processing" @click="updateIncome">
                        Simpan Perubahan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </div>
</template>
