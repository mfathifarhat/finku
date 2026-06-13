<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'xp', 'level', 'streak_count', 'last_activity_date', 'coins', 'mascot_name', 'owned_accessories', 'equipped_accessories', 'monthly_income', 'budgeting_method', 'custom_budget_percentages'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $attributes = [
        'custom_budget_percentages' => '{"needs": 50, "wants": 30, "savings": 20}',
    ];

    protected $appends = [
        'level_label',
    ];

    public function getLevelLabelAttribute(): string
    {
        if ($this->level <= 2) {
            return 'Rookie Finansial';
        } elseif ($this->level <= 4) {
            return 'Budget Tracker';
        } elseif ($this->level <= 6) {
            return 'Pejuang Tabungan';
        } elseif ($this->level <= 9) {
            return 'Perencana Ulung';
        } else {
            return 'Suhu Finansial';
        }
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'custom_budget_percentages' => 'array',
            'owned_accessories' => 'array',
            'equipped_accessories' => 'array',
        ];
    }

    public function addCoins(int $amount): void
    {
        $this->coins += $amount;
        $this->save();
    }

    public function deductCoins(int $amount): bool
    {
        if ($this->coins >= $amount) {
            $this->coins -= $amount;
            $this->save();
            return true;
        }
        return false;
    }

    public function buyAccessory(string $code, int $price): bool
    {
        $owned = $this->owned_accessories ?? [];
        if (in_array($code, $owned)) {
            return false;
        }

        if ($this->deductCoins($price)) {
            $owned[] = $code;
            $this->owned_accessories = $owned;
            $this->save();
            return true;
        }

        return false;
    }

    public function equipAccessory(string $code, string $slot): bool
    {
        $owned = $this->owned_accessories ?? [];
        if (!in_array($code, $owned)) {
            return false;
        }

        $equipped = $this->equipped_accessories ?? [];

        // Remove any currently equipped items in the same category slot (e.g. hat, glasses, neck, back)
        // This is done by looking up prefix or passed slot type
        $equipped = array_filter($equipped, function($item) use ($slot) {
            return !str_starts_with($item, $slot . '_');
        });

        $equipped[] = $code;
        $this->equipped_accessories = array_values($equipped);
        $this->save();
        return true;
    }

    public function unequipAccessory(string $code): void
    {
        $equipped = $this->equipped_accessories ?? [];
        $equipped = array_filter($equipped, fn($item) => $item !== $code);
        $this->equipped_accessories = array_values($equipped);
        $this->save();
    }

    public function addXp(int $amount): array
    {
        $this->xp += $amount;
        $leveledUp = false;
        
        // XP required for level L is L * 100
        while ($this->xp >= $this->level * 100) {
            $this->xp -= $this->level * 100;
            $this->level++;
            $leveledUp = true;
        }
        
        $this->save();
        
        return [
            'leveled_up' => $leveledUp,
            'current_level' => $this->level,
            'current_xp' => $this->xp,
        ];
    }

    public function updateStreak(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        
        $lastActivity = $this->last_activity_date ? \Carbon\Carbon::parse($this->last_activity_date)->startOfDay() : null;
        
        $streakUpdated = false;
        $bonusXp = 0;
        
        if (null === $lastActivity) {
            // First time ever
            $this->streak_count = 1;
            $this->last_activity_date = $today->toDateString();
            $streakUpdated = true;
            $bonusXp = 15; // Starting bonus
        } elseif ($lastActivity->equalTo($yesterday)) {
            // Consecutive day
            $this->streak_count += 1;
            $this->last_activity_date = $today->toDateString();
            $streakUpdated = true;
            $bonusXp = 15 + ($this->streak_count * 2); // Dynamic bonus
        } elseif ($lastActivity->lessThan($yesterday)) {
            // Streak broken
            $this->streak_count = 1;
            $this->last_activity_date = $today->toDateString();
            $streakUpdated = true;
            $bonusXp = 15; // Reset streak starting bonus
        }
        
        if ($streakUpdated) {
            $this->save();
            $xpResult = $this->addXp($bonusXp);
            return [
                'updated' => true,
                'streak_count' => $this->streak_count,
                'bonus_xp' => $bonusXp,
                'leveled_up' => $xpResult['leveled_up'],
                'current_level' => $xpResult['current_level'],
                'current_xp' => $xpResult['current_xp'],
            ];
        }
        
        return [
            'updated' => false,
            'streak_count' => $this->streak_count,
            'bonus_xp' => 0,
            'leveled_up' => false,
        ];
    }

    public function checkBadges(): array
    {
        $newlyUnlocked = [];

        // 1. Sang Visioner (Membuat target tabungan pertama)
        $sangVisionerBadge = Badge::where('code', 'sang_visioner')->first();
        if ($sangVisionerBadge && !$this->badges()->where('badge_id', $sangVisionerBadge->id)->exists()) {
            if ($this->goals()->count() >= 1) {
                $this->badges()->attach($sangVisionerBadge->id);
                $newlyUnlocked[] = $sangVisionerBadge;
                $this->addXp(50); // reward 50 XP
            }
        }

        // 2. Hemat Pangkal Kaya (Mencatat minimal 5 pengeluaran)
        $hematBadge = Badge::where('code', 'hemat_pangkal_kaya')->first();
        if ($hematBadge && !$this->badges()->where('badge_id', $hematBadge->id)->exists()) {
            if ($this->expenses()->count() >= 5) {
                $this->badges()->attach($hematBadge->id);
                $newlyUnlocked[] = $hematBadge;
                $this->addXp(75); // reward 75 XP
            }
        }

        // 3. Dana Darurat Master (Target tabungan Dana Darurat mencapai 50%)
        $danaDaruratBadge = Badge::where('code', 'dana_darurat_master')->first();
        if ($danaDaruratBadge && !$this->badges()->where('badge_id', $danaDaruratBadge->id)->exists()) {
            $hasDanaDaruratGoal = $this->goals()
                ->where('name', 'like', '%dana darurat%')
                ->where(function ($query) {
                    $query->whereRaw('current_amount >= (target_amount / 2)');
                })
                ->exists();
            if ($hasDanaDaruratGoal) {
                $this->badges()->attach($danaDaruratBadge->id);
                $newlyUnlocked[] = $danaDaruratBadge;
                $this->addXp(100); // reward 100 XP
            }
        }

        // 4. Suhu Budgeting (Mengatur monthly_income pertama)
        $suhuBadge = Badge::where('code', 'suhu_budgeting')->first();
        if ($suhuBadge && !$this->badges()->where('badge_id', $suhuBadge->id)->exists()) {
            if ($this->monthly_income > 0) {
                $this->badges()->attach($suhuBadge->id);
                $newlyUnlocked[] = $suhuBadge;
                $this->addXp(50); // reward 50 XP
            }
        }

        // 5. Pejuang Konsisten (Mencapai 3 hari daily streak)
        $pejuangStreakBadge = Badge::where('code', 'pejuang_konsisten')->first();
        if ($pejuangStreakBadge && !$this->badges()->where('badge_id', $pejuangStreakBadge->id)->exists()) {
            if ($this->streak_count >= 3) {
                $this->badges()->attach($pejuangStreakBadge->id);
                $newlyUnlocked[] = $pejuangStreakBadge;
                $this->addXp(100); // reward 100 XP
            }
        }

        // 6. Master Streak (Mencapai 7 hari daily streak)
        $masterStreakBadge = Badge::where('code', 'master_streak')->first();
        if ($masterStreakBadge && !$this->badges()->where('badge_id', $masterStreakBadge->id)->exists()) {
            if ($this->streak_count >= 7) {
                $this->badges()->attach($masterStreakBadge->id);
                $newlyUnlocked[] = $masterStreakBadge;
                $this->addXp(200); // reward 200 XP
            }
        }

        return $newlyUnlocked;
    }
}
