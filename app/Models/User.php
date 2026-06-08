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

#[Fillable(['name', 'email', 'password', 'xp', 'level', 'monthly_income', 'budgeting_method', 'custom_budget_percentages'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
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
        ];
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

        return $newlyUnlocked;
    }
}
