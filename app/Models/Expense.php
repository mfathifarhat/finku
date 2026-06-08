<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['amount', 'category', 'date', 'description', 'type', 'user_id'])]
class Expense extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
