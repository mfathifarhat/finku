<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $goals = $user->goals()->latest()->get();
        
        return Inertia::render('Goals/Index', [
            'goals' => $goals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|integer|min:1',
            'current_amount' => 'nullable|integer|min:0',
            'target_date' => 'required|date|after_or_equal:today',
            'icon' => 'nullable|string',
        ]);

        $user = $request->user();
        
        $goal = $user->goals()->create([
            'name' => $request->name,
            'target_amount' => $request->target_amount,
            'current_amount' => $request->current_amount ?? 0,
            'target_date' => $request->target_date,
            'icon' => $request->icon ?? 'Target',
            'completed' => ($request->current_amount ?? 0) >= $request->target_amount,
        ]);

        // Award 20 XP for setting a goal
        $xpResult = $user->addXp(20);
        if ($xpResult['leveled_up']) {
            session()->flash('level_up', $xpResult['current_level']);
        }

        // Check achievements
        $newBadges = $user->checkBadges();
        if (count($newBadges) > 0) {
            session()->flash('new_badges', collect($newBadges)->map(fn($b) => [
                'name' => $b->name,
                'icon' => $b->icon,
                'description' => $b->description,
            ])->toArray());
        }

        $msg = 'Target tabungan berhasil dibuat! +20 XP';
        if (count($newBadges) > 0) {
            $badgeNames = collect($newBadges)->pluck('name')->join(', ');
            $msg .= " Lencana Baru Dibuka: {$badgeNames}!";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function topUp(Request $request, Goal $goal): RedirectResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        
        $newAmount = $goal->current_amount + $request->amount;
        $completed = $newAmount >= $goal->target_amount;

        $goal->update([
            'current_amount' => $newAmount,
            'completed' => $completed,
        ]);

        // Award 15 XP for saving money
        $xpResult = $user->addXp(15);
        if ($xpResult['leveled_up']) {
            session()->flash('level_up', $xpResult['current_level']);
        }

        // Check achievements
        $newBadges = $user->checkBadges();
        if (count($newBadges) > 0) {
            session()->flash('new_badges', collect($newBadges)->map(fn($b) => [
                'name' => $b->name,
                'icon' => $b->icon,
                'description' => $b->description,
            ])->toArray());
        }

        $msg = 'Tabungan berhasil ditambahkan! +15 XP';
        if ($completed) {
            $msg .= " Selamat! Target '{$goal->name}' Anda telah tercapai! 🎉";
        }
        
        if (count($newBadges) > 0) {
            $badgeNames = collect($newBadges)->pluck('name')->join(', ');
            $msg .= " Lencana Baru Dibuka: {$badgeNames}!";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroy(Request $request, Goal $goal): RedirectResponse
    {
        if ($goal->user_id !== $request->user()->id) {
            abort(403);
        }

        $goal->delete();

        return redirect()->back()->with('success', 'Target tabungan berhasil dihapus!');
    }
}
