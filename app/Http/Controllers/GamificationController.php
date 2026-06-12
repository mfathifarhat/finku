<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GamificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Eager load user's unlocked badges to avoid N+1 query
        $userBadges = $user->badges()->get()->keyBy('id');
        
        $badges = Badge::all()->map(function ($badge) use ($userBadges) {
            $userBadge = $userBadges->get($badge->id);
            
            $rawUnlockedAt = null;
            if ($userBadge && $userBadge->pivot && $userBadge->pivot->created_at) {
                $ts = $userBadge->pivot->created_at;
                if ($ts instanceof \Carbon\Carbon) {
                    $rawUnlockedAt = $ts->toDateTimeString();
                } else {
                    $rawUnlockedAt = \Carbon\Carbon::parse($ts)->toDateTimeString();
                }
            }
                
            return [
                'id' => $badge->id,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon' => $badge->icon,
                'code' => $badge->code,
                'unlocked' => $userBadge !== null,
                'raw_unlocked_at' => $rawUnlockedAt,
                'unlocked_at' => $rawUnlockedAt ? \Carbon\Carbon::parse($rawUnlockedAt)->diffForHumans() : null,
            ];
        });

        // Sort badges: unlocked first, then by unlock date descending (newest first), and locked badges last
        $sortedBadges = $badges->sort(function ($a, $b) {
            if ($a['unlocked'] !== $b['unlocked']) {
                return $b['unlocked'] <=> $a['unlocked']; // true (1) comes before false (0)
            }
            if ($a['unlocked']) {
                // Both are unlocked: sort by unlock date descending (newest first)
                return strcmp($b['raw_unlocked_at'], $a['raw_unlocked_at']);
            }
            // Both are locked: preserve default seeded database order (ID ascending)
            return $a['id'] <=> $b['id'];
        })->values();

        return Inertia::render('Gamification/Profile', [
            'user' => [
                'name' => $user->name,
                'level' => $user->level,
                'level_label' => $user->level_label,
                'streak_count' => $user->streak_count,
                'last_activity_date' => $user->last_activity_date,
                'xp' => $user->xp,
                'xp_needed' => $user->level * 100,
            ],
            'badges' => $sortedBadges,
        ]);
    }
}
