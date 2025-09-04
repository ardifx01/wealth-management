<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Repositories\WalletRepository;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $guarded = ["id"];
    protected $with = ["wallet"];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($trx) {
            WalletRepository::refreshUserWallets($trx->wallet->user);
            WalletRepository::refreshWalletById($trx->wallet->id);
        });

        static::created(function ($trx) {
            WalletRepository::refreshUserWallets($trx->wallet->user);
            WalletRepository::refreshWalletById($trx->wallet->id);
        });

        static::updated(function ($trx) {
            WalletRepository::refreshUserWallets($trx->wallet->user);
            WalletRepository::refreshWalletById($trx->wallet->id);
        });
    }
}
