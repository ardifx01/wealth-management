<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;

class WalletRepository
{
    public function __construct(
        private Wallet $wallet
    ) {}

    public function all()
    {
        return $this->wallet->all();
    }

    public function getByUser(User $user)
    {
        return Cache::forever(sprintf("user_wallets_%s", $user->id), function () use ($user) {
            return $this->wallet->where('user_id', $user->id)->get();
        });
    }

    public static function refreshUserWallets(User $user)
    {
        Cache::forget(sprintf("user_wallets_%s", $user->id));
    }
    
    public static function refreshWalletById (int $walletId)
    {
        Cache::forget(sprintf("balances.wallet.%s", $walletId));
    }

    public function store(array $data)
    {
        return $this->wallet->create($data);
    }
}
