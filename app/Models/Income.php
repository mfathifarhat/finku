<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['amount', 'category', 'date', 'description', 'user_id'])]
class Income extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
