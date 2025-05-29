<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recurring extends Model
{
    /** @use HasFactory<\Database\Factories\RecurringFactory> */
    use HasFactory;

    protected $guarded = ["id"];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
