<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        $query = $user->expenses()->latest('date');
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        $expenses = $query->get();
        
        $categories = ['Makanan', 'Transportasi', 'Belanja', 'Hiburan', 'Tagihan', 'Kesehatan', 'Pendidikan', 'Lainnya'];
        
        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'filters' => $request->only(['type', 'category']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'category' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:needs,wants',
        ]);

        $user = $request->user();
        
        $expense = $user->expenses()->create([
            'amount' => $request->amount,
            'category' => $request->category,
            'date' => $request->date,
            'description' => $request->description,
            'type' => $request->type,
        ]);

        // Award 10 XP for logging an expense
        $user->addXp(10);
        
        // Check for new achievements
        $newBadges = $user->checkBadges();
        
        // Calculate limit warnings
        $totalSpent = $user->expenses()
            ->where('type', $request->type)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');
            
        $needsPercent = 50;
        $wantsPercent = 30;
        
        if ($user->budgeting_method === '70-20-10') {
            $needsPercent = 70;
            $wantsPercent = 20;
        } elseif ($user->budgeting_method === 'custom') {
            $custom = $user->custom_budget_percentages;
            $needsPercent = $custom['needs'] ?? 50;
            $wantsPercent = $custom['wants'] ?? 30;
        }
        
        $limit = $request->type === 'needs' 
            ? ($user->monthly_income * $needsPercent) / 100 
            : ($user->monthly_income * $wantsPercent) / 100;
            
        $msg = 'Pengeluaran berhasil dicatat! +10 XP';
        if ($user->monthly_income > 0 && $totalSpent > $limit) {
            $typeLabel = $request->type === 'needs' ? 'Kebutuhan (Needs)' : 'Keinginan (Wants)';
            $msg .= sprintf(" (Peringatan: Pengeluaran %s Anda melebihi batas anggaran!)", $typeLabel);
        }
        
        if (count($newBadges) > 0) {
            $badgeNames = collect($newBadges)->pluck('name')->join(', ');
            $msg .= " Lencana Baru Dibuka: {$badgeNames}!";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|integer|min:1',
            'category' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:needs,wants',
        ]);

        $expense->update([
            'amount' => $request->amount,
            'category' => $request->category,
            'date' => $request->date,
            'description' => $request->description,
            'type' => $request->type,
        ]);

        return redirect()->back()->with('success', 'Pengeluaran berhasil diperbarui!');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            abort(403);
        }

        $expense->delete();

        return redirect()->back()->with('success', 'Pengeluaran berhasil dihapus!');
    }
}
