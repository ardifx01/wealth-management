<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua wallet user, atau buat dummy wallet jika belum ada
        $wallets = Wallet::all();

        if ($wallets->isEmpty()) {
            $this->command->info("No wallets found, creating a dummy wallet...");
            $wallet = Wallet::factory()->create();
            $wallets = collect([$wallet]);
        }

        foreach ($wallets as $wallet) {
            // Buat transaksi random sebanyak 50 transaksi per wallet dalam rentang waktu 1 tahun terakhir
            for ($i = 0; $i < 100; $i++) {
                $type = ['income', 'expense'][array_rand(['income', 'expense'])];
                $amount = $type === 'income' ? rand(100000, 10000000) : rand(100000, 10000000);

                // Random tanggal dalam 365 hari terakhir
                $createdAt = Carbon::now()->subDays(rand(0, 365))->setTime(rand(0, 23), rand(0, 59), rand(0, 59));

                Transaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'reference_id' => null,
                    'type' => $type,
                    'amount' => $amount,
                    'description' => 'Dummy transaction ' . Str::random(5),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        $this->command->info('Dummy transactions created successfully!');
    }
}
