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
        
        $badges = Badge::all()->map(function ($badge) use ($user) {
            $userBadge = $user->badges()->where('badge_id', $badge->id)->first();
            return [
                'id' => $badge->id,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon' => $badge->icon,
                'code' => $badge->code,
                'unlocked' => $userBadge !== null,
                'unlocked_at' => ($userBadge && $userBadge->pivot && $userBadge->pivot->created_at) ? $userBadge->pivot->created_at->diffForHumans() : null,
            ];
        });

        return Inertia::render('Gamification/Profile', [
            'user' => [
                'name' => $user->name,
                'level' => $user->level,
                'xp' => $user->xp,
                'xp_needed' => $user->level * 100,
            ],
            'badges' => $badges,
        ]);
    }
}
