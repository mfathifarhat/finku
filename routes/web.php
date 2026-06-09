<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/budget', [DashboardController::class, 'updateBudgetSettings'])->name('dashboard.budget.update');

    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::post('expenses/scan', [ExpenseController::class, 'scanReceipt'])->name('expenses.scan');
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::post('incomes', [IncomeController::class, 'store'])->name('incomes.store');
    Route::post('incomes/scan', [IncomeController::class, 'scanTransferProof'])->name('incomes.scan');
    Route::put('incomes/{income}', [IncomeController::class, 'update'])->name('incomes.update');
    Route::delete('incomes/{income}', [IncomeController::class, 'destroy'])->name('incomes.destroy');

    Route::get('goals', [GoalController::class, 'index'])->name('goals.index');
    Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
    Route::post('goals/{goal}/topup', [GoalController::class, 'topUp'])->name('goals.topup');
    Route::delete('goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');

    Route::get('gamification', [GamificationController::class, 'index'])->name('gamification.index');

    Route::get('insights', [InsightController::class, 'index'])->name('insights.index');

    Route::post('ai/chat', [ChatController::class, 'chat'])->name('ai.chat');
});

require __DIR__.'/settings.php';
