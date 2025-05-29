<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Wallet;

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
        return $this->wallet->where('user_id', $user->id)->get();
    }

    public function store(array $data)
    {
        return $this->wallet->create($data);
    }
}
