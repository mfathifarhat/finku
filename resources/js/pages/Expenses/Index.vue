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
    Sparkles
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
            { title: 'Pengeluaran', href: '/expenses' }
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

const props = defineProps<{
    expenses: Expense[];
    categories: string[];
    filters: {
        type?: string;
        category?: string;
    };
}>();

// Form Setup
const showAddDialog = ref(false);
const showEditDialog = ref(false);
const editingExpense = ref<Expense | null>(null);

const addForm = useForm({
    amount: '',
    category: '',
    date: new Date().toISOString().split('T')[0],
    description: '',
    type: 'needs',
});

const editForm = useForm({
    amount: 0,
    category: '',
    date: '',
    description: '',
    type: 'needs',
});

// Filters
const filterType = ref(props.filters.type || 'all');
const filterCategory = ref(props.filters.category || 'all');

const applyFilters = () => {
    const data: Record<string, string> = {};
    if (filterType.value !== 'all') data.type = filterType.value;
    if (filterCategory.value !== 'all') data.category = filterCategory.value;
    router.get('/expenses', data, { preserveState: true });
};

const resetFilters = () => {
    filterType.value = 'all';
    filterCategory.value = 'all';
    router.get('/expenses');
};

const storeExpense = () => {
    addForm.post('/expenses', {
        onSuccess: () => {
            showAddDialog.value = false;
            addForm.reset('amount', 'description');
        }
    });
};

const editExpense = (expense: Expense) => {
    editingExpense.value = expense;
    editForm.amount = expense.amount;
    editForm.category = expense.category;
    editForm.date = expense.date;
    editForm.description = expense.description || '';
    editForm.type = expense.type;
    showEditDialog.value = true;
};

const updateExpense = () => {
    if (!editingExpense.value) return;
    editForm.put(`/expenses/${editingExpense.value.id}`, {
        onSuccess: () => {
            showEditDialog.value = false;
            editingExpense.value = null;
        }
    });
};

const deleteExpense = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')) {
        router.delete(`/expenses/${id}`);
    }
};

const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <Head title="Pencatatan Pengeluaran - Finku" />

    <div class="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <Wallet class="w-6 h-6 text-emerald-500" /> Log Pengeluaran
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Catat pengeluaran harianmu secara rapi dan pisahkan antara Kebutuhan (Needs) & Keinginan (Wants).
                </p>
            </div>
            
            <Button class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow" @click="showAddDialog = true">
                <Plus class="w-4 h-4 mr-2" /> Catat Pengeluaran
            </Button>
        </div>

        <!-- Filters Section -->
        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md flex flex-wrap gap-4 items-end">
            <!-- Filter Type -->
            <div class="grid gap-1.5 flex-1 min-w-[200px]">
                <Label for="filter_type" class="text-xs font-semibold text-slate-500 dark:text-slate-400">Jenis Kategori</Label>
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
                <Label for="filter_category" class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pos Kategori</Label>
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

            <!-- Clear button -->
            <Button variant="outline" class="border-slate-200 dark:border-slate-800 h-10" @click="resetFilters">
                Reset Filter
            </Button>
        </div>

        <!-- Expenses List Card -->
        <Card class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-md">
            <CardHeader>
                <CardTitle class="text-md font-bold">Daftar Pengeluaran</CardTitle>
                <CardDescription>Menampilkan log transaksi pengeluaran keuangan Anda.</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-slate-500 dark:text-slate-400">
                        <thead class="text-xs text-slate-700 dark:text-slate-300 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-semibold">Status</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Kategori</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Tipe</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Deskripsi</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Tanggal</th>
                                <th scope="col" class="px-6 py-3 font-semibold">Jumlah</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="expenses.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                    Belum ada transaksi pengeluaran yang dicatat.
                                </td>
                            </tr>
                            <tr 
                                v-for="expense in expenses" 
                                :key="expense.id"
                                class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800/80 hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                            >
                                <td class="px-6 py-4">
                                    <span 
                                        class="inline-block w-2.5 h-2.5 rounded-full"
                                        :class="expense.type === 'needs' ? 'bg-blue-500' : 'bg-purple-500'"
                                        :title="expense.type === 'needs' ? 'Kebutuhan' : 'Keinginan'"
                                    ></span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ expense.category }}
                                </td>
                                <td class="px-6 py-4">
                                    <span 
                                        class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                        :class="expense.type === 'needs' ? 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-purple-50 text-purple-800 dark:bg-purple-950 dark:text-purple-300'"
                                    >
                                        {{ expense.type === 'needs' ? 'Needs' : 'Wants' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 max-w-[200px] truncate">
                                    {{ expense.description || '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ expense.date }}
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-800 dark:text-slate-200">
                                    {{ formatRupiah(expense.amount) }}
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
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Record Expense Modal Dialog -->
        <Dialog v-model:open="showAddDialog">
            <DialogContent class="sm:max-w-[480px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Wallet class="w-5 h-5 text-emerald-500" /> Catat Pengeluaran Baru
                    </DialogTitle>
                    <DialogDescription class="text-slate-500">
                        Catat pengeluaran Anda hari ini. Aksi ini juga akan menambahkan **+10 XP** ke karakter finansialmu!
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <!-- Jumlah Uang -->
                    <div class="grid gap-2">
                        <Label for="amount" class="text-slate-700 dark:text-slate-300 font-medium">Jumlah Pengeluaran (Rupiah)</Label>
                        <Input 
                            id="amount" 
                            type="number" 
                            v-model="addForm.amount" 
                            placeholder="Contoh: 50000"
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>

                    <!-- Kategori -->
                    <div class="grid gap-2">
                        <Label for="category" class="text-slate-700 dark:text-slate-300 font-medium">Pos Kategori</Label>
                        <Select v-model="addForm.category">
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

                    <!-- Tipe (Needs/Wants) -->
                    <div class="grid gap-2">
                        <Label for="type" class="text-slate-700 dark:text-slate-300 font-medium">Jenis Pengeluaran</Label>
                        <Select v-model="addForm.type">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih Jenis" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem value="needs">Needs (Kebutuhan Pokok, Transportasi, Cicilan)</SelectItem>
                                <SelectItem value="wants">Wants (Belanja Impulsif, Nongkrong, Liburan, Hiburan)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Tanggal -->
                    <div class="grid gap-2">
                        <Label for="date" class="text-slate-700 dark:text-slate-300 font-medium">Tanggal</Label>
                        <Input 
                            id="date" 
                            type="date" 
                            v-model="addForm.date" 
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>

                    <!-- Keterangan -->
                    <div class="grid gap-2">
                        <Label for="description" class="text-slate-700 dark:text-slate-300 font-medium">Deskripsi / Keterangan</Label>
                        <Input 
                            id="description" 
                            v-model="addForm.description" 
                            placeholder="Contoh: Beli makan siang nasi padang"
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>
                </div>

                <DialogFooter class="flex items-center justify-between">
                    <span class="text-xs text-amber-500 font-semibold flex items-center gap-1.5">
                        <Sparkles class="w-3.5 h-3.5" /> Dapatkan +10 XP
                    </span>
                    <div class="flex gap-2">
                        <Button variant="outline" class="border-slate-200" @click="showAddDialog = false">Batal</Button>
                        <Button 
                            class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow"
                            :disabled="addForm.processing"
                            @click="storeExpense"
                        >
                            Simpan Pengeluaran
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Expense Modal Dialog -->
        <Dialog v-model:open="showEditDialog">
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
                    <!-- Jumlah Uang -->
                    <div class="grid gap-2">
                        <Label for="edit_amount" class="text-slate-700 dark:text-slate-300 font-medium">Jumlah Pengeluaran (Rupiah)</Label>
                        <Input 
                            id="edit_amount" 
                            type="number" 
                            v-model="editForm.amount" 
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>

                    <!-- Kategori -->
                    <div class="grid gap-2">
                        <Label for="edit_category" class="text-slate-700 dark:text-slate-300 font-medium">Pos Kategori</Label>
                        <Select v-model="editForm.category">
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

                    <!-- Tipe (Needs/Wants) -->
                    <div class="grid gap-2">
                        <Label for="edit_type" class="text-slate-700 dark:text-slate-300 font-medium">Jenis Pengeluaran</Label>
                        <Select v-model="editForm.type">
                            <SelectTrigger class="bg-slate-50 dark:bg-slate-800">
                                <SelectValue placeholder="Pilih Jenis" />
                            </SelectTrigger>
                            <SelectContent class="bg-white dark:bg-slate-900">
                                <SelectItem value="needs">Needs (Kebutuhan Pokok)</SelectItem>
                                <SelectItem value="wants">Wants (Keinginan)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Tanggal -->
                    <div class="grid gap-2">
                        <Label for="edit_date" class="text-slate-700 dark:text-slate-300 font-medium">Tanggal</Label>
                        <Input 
                            id="edit_date" 
                            type="date" 
                            v-model="editForm.date" 
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>

                    <!-- Keterangan -->
                    <div class="grid gap-2">
                        <Label for="edit_description" class="text-slate-700 dark:text-slate-300 font-medium">Deskripsi / Keterangan</Label>
                        <Input 
                            id="edit_description" 
                            v-model="editForm.description" 
                            class="bg-slate-50 dark:bg-slate-800"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" class="border-slate-200" @click="showEditDialog = false">Batal</Button>
                    <Button 
                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow"
                        :disabled="editForm.processing"
                        @click="updateExpense"
                    >
                        Simpan Perubahan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
