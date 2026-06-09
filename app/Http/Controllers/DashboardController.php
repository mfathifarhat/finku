<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Expense;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Current month's expenses
        $expenses = $user->expenses()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->get();
            
        $spentNeeds = $expenses->where('type', 'needs')->sum('amount');
        $spentWants = $expenses->where('type', 'wants')->sum('amount');
        
        // Current month's actual incomes
        $actualIncomes = $user->incomes()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->get();
        $totalActualIncome = $actualIncomes->sum('amount');
        
        // Savings contribution: sum of current_amount of active goals
        $spentSavings = $user->goals()->sum('current_amount');
        
        // Determine budget percentages
        $needsPercent = 50;
        $wantsPercent = 30;
        $savingsPercent = 20;
        
        if ($user->budgeting_method === '70-20-10') {
            $needsPercent = 70;
            $wantsPercent = 20;
            $savingsPercent = 10;
        } elseif ($user->budgeting_method === 'custom') {
            $custom = $user->custom_budget_percentages;
            $needsPercent = $custom['needs'] ?? 50;
            $wantsPercent = $custom['wants'] ?? 30;
            $savingsPercent = $custom['savings'] ?? 20;
        }
        
        $income = $user->monthly_income;
        
        $limitNeeds = ($income * $needsPercent) / 100;
        $limitWants = ($income * $wantsPercent) / 100;
        $limitSavings = ($income * $savingsPercent) / 100;
        
        // AI Insights (Gemini API with local Rule-based fallback)
        $aiInsights = [];
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-1.5-flash');

        if ($income == 0) {
            $aiInsights[] = "Kamu belum mengatur pendapatan bulanan. Yuk, atur pendapatanmu terlebih dahulu di dashboard untuk mengaktifkan alokasi otomatis!";
        } elseif (!empty($apiKey)) {
            try {
                $prompt = "Kamu adalah Finku-AI, asisten keuangan pribadi yang cerdas, ramah, dan memotivasi untuk pengguna muda di Indonesia.
Analisis ringkas data finansial pengguna berikut untuk bulan ini:
- Pendapatan Bulanan: Rp " . number_format($income, 0, ',', '.') . "
- Metode Budgeting: {$user->budgeting_method}
- Kebutuhan (Needs) Terpakai: Rp " . number_format($spentNeeds, 0, ',', '.') . " (Limit: Rp " . number_format($limitNeeds, 0, ',', '.') . ")
- Keinginan (Wants) Terpakai: Rp " . number_format($spentWants, 0, ',', '.') . " (Limit: Rp " . number_format($limitWants, 0, ',', '.') . ")
- Tabungan (Savings) Terkumpul: Rp " . number_format($spentSavings, 0, ',', '.') . " (Target: Rp " . number_format($limitSavings, 0, ',', '.') . ")

Berikan 1 atau 2 kalimat rekomendasi finansial yang ramah, ringkas, memotivasi, dan spesifik dalam Bahasa Indonesia (tanpa tambahan markdown).";

                $response = Http::timeout(5)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if (!empty($text)) {
                        $aiInsights[] = trim($text);
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Gemini API error in DashboardController: " . $e->getMessage());
            }
        }

        // Local Rule-Based fallback if Gemini API is empty or failed
        if (empty($aiInsights)) {
            if ($income == 0) {
                $aiInsights[] = "Kamu belum mengatur pendapatan bulanan. Yuk, atur pendapatanmu terlebih dahulu di dashboard untuk mengaktifkan alokasi otomatis!";
            } else {
                $wantsRatio = $income > 0 ? ($spentWants / $income) * 100 : 0;
                $needsRatio = $income > 0 ? ($spentNeeds / $income) * 100 : 0;
                
                if ($wantsRatio > $wantsPercent) {
                    $aiInsights[] = sprintf("Wah, pengeluaran Keinginan (Wants) kamu sudah mencapai %.1f%% dari pendapatan (melebihi batas %d%%)! Coba kurangi jajan minggu ini agar cash flow tetap sehat.", $wantsRatio, $wantsPercent);
                }
                if ($needsRatio > $needsPercent) {
                    $aiInsights[] = sprintf("Waduh, pengeluaran Kebutuhan (Needs) kamu sudah melebihi batas %d%% (mencapai %.1f%%). Coba tinjau kembali pengeluaran tetap bulananmu.", $needsPercent, $needsRatio);
                }
                if (count($aiInsights) === 0) {
                    $aiInsights[] = "Hebat! Rasio pengeluaranmu sangat sehat dan sesuai dengan rencana budgeting. Pertahankan konsistensi ini untuk masa depan cerah!";
                }
            }
        }
        
        // Recent expenses
        $recentExpenses = $user->expenses()
            ->latest('date')
            ->take(5)
            ->get();
            
        // Recent goals
        $recentGoals = $user->goals()
            ->latest()
            ->take(3)
            ->get();
            
        // Unlocked Badges
        $unlockedBadges = $user->badges()->latest()->get();

        return Inertia::render('Dashboard', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'xp' => $user->xp,
                'level' => $user->level,
                'monthly_income' => $user->monthly_income,
                'budgeting_method' => $user->budgeting_method,
                'custom_budget_percentages' => $user->custom_budget_percentages,
                'xp_needed' => $user->level * 100,
            ],
            'summary' => [
                'income' => $income,
                'actual_income' => $totalActualIncome,
                'spent_needs' => $spentNeeds,
                'spent_wants' => $spentWants,
                'spent_savings' => $spentSavings,
                'limit_needs' => $limitNeeds,
                'limit_wants' => $limitWants,
                'limit_savings' => $limitSavings,
                'percent_needs' => $needsPercent,
                'percent_wants' => $wantsPercent,
                'percent_savings' => $savingsPercent,
            ],
            'recent_expenses' => $recentExpenses,
            'recent_goals' => $recentGoals,
            'unlocked_badges' => $unlockedBadges,
            'ai_insights' => $aiInsights,
        ]);
    }
    
    public function updateBudgetSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'monthly_income' => 'required|integer|min:0',
            'budgeting_method' => 'required|in:50-30-20,70-20-10,custom',
            'needs' => 'required_if:budgeting_method,custom|integer|min:0|max:100',
            'wants' => 'required_if:budgeting_method,custom|integer|min:0|max:100',
            'savings' => 'required_if:budgeting_method,custom|integer|min:0|max:100',
        ]);
        
        $user = $request->user();
        
        $user->monthly_income = $request->monthly_income;
        $user->budgeting_method = $request->budgeting_method;
        
        if ($request->budgeting_method === 'custom') {
            $total = intval($request->needs) + intval($request->wants) + intval($request->savings);
            if ($total !== 100) {
                return back()->withErrors(['custom' => 'Total persentase custom harus sama dengan 100%']);
            }
            $user->custom_budget_percentages = [
                'needs' => intval($request->needs),
                'wants' => intval($request->wants),
                'savings' => intval($request->savings),
            ];
        }
        
        $user->save();
        
        // Earn XP for configuring budgeting
        $user->addXp(30);
        
        // Check achievements
        $newBadges = $user->checkBadges();
        
        if (count($newBadges) > 0) {
            $badgeNames = collect($newBadges)->pluck('name')->join(', ');
            return back()->with('success', "Anggaran diperbarui! Anda juga mendapatkan lencana baru: {$badgeNames}!");
        }
        
        return back()->with('success', 'Anggaran berhasil diperbarui!');
    }
}
