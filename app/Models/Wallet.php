<?php

namespace App\Models;

use App\Repositories\CurrencyRepository;
use App\Repositories\WalletRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class Wallet extends Model
{
    /** @use HasFactory<\Database\Factories\WalletFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function getBalanceAttribute()
    {
        $balance = Cache::remember(sprintf('balances.wallet.%s', $this->id), now()->addHour(), function () {
            return $this
                ->transactions()
                ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance")
                ->value('balance');
        });

        return floatval($balance);
    }

    public function getBalance()
    {
        return Number::currency(floatval($this->balance), $this->currency);
    }

    public function convertBalanceWithBaseCurrency()
    {
        $currencyRepository = app(CurrencyRepository::class);

        if ($this->currency === $currencyRepository->localCurrency) {
            return $this->balance;
        }

        return $currencyRepository->convert($this->balance, $this->currency, $currencyRepository->localCurrency);
    }

    public function income(int $amount, ?string $description)
    {
        $this->transactions()->create([
            'type' => 'income',
            'amount' => $amount,
            'user_id' => Auth::user()->id,
            'wallet_id' => $this->id,
            'description' => $description,
        ]);
    }

    public function expense(int $amount, ?string $description)
    {
        $this->transactions()->create([
            'type' => 'expense',
            'amount' => $amount,
            'user_id' => Auth::user()->id,
            'wallet_id' => $this->id,
            'description' => $description,
        ]);
    }

    public function transfer(Wallet $wallet, int $originalAmount, int $amount, ?string $description)
    {
        $expense = Transaction::create([
            'type' => 'expense',
            'amount' => $originalAmount,
            'user_id' => Auth::user()->id,
            'wallet_id' => $this->id,
            'description' => $description,
        ]);

        Transaction::create([
            'type' => 'income',
            'amount' => $amount,
            'user_id' => Auth::user()->id,
            'wallet_id' => $wallet->id,
            'description' => $description,
            'reference_id' => $expense->id
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wallet_id', 'id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($wallet) {
            $wallet->transactions()->delete();
            WalletRepository::refreshUserWallets($wallet->user);
            WalletRepository::refreshWalletById($wallet->id);
        });

        static::created(function ($wallet) {
            WalletRepository::refreshUserWallets($wallet->user);
            WalletRepository::refreshWalletById($wallet->id);
        });

        static::updated(function ($wallet) {
            WalletRepository::refreshUserWallets($wallet->user);
            WalletRepository::refreshWalletById($wallet->id);
        });
    }
}
