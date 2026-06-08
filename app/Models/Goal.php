<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'target_amount', 'current_amount', 'target_date', 'icon', 'completed', 'user_id'])]
class Goal extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
