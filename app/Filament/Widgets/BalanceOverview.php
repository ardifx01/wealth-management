<?php


namespace App\Filament\Widgets;

use App\Models\Wallet;
use App\Enums\WalletType;
use App\Models\Transaction;
use App\Repositories\CurrencyRepository;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BalanceOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public function getColumnSpan(): int|string
    {
        return 'full';
    }

    public function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $currencyRepository = app(CurrencyRepository::class);

        return collect(WalletType::cases())
        ->map(fn($case) => $case->value)
        ->map(function ($category) use ($currencyRepository) {
            // Get all wallets for the given category
            $wallets = Wallet::where('category', $category)->get();
    
            // Skip if no wallets found
            if ($wallets->isEmpty()) return null;
    
            // Calculate total balance in base currency across all wallets
            $balance = $wallets->sum(function ($wallet) use ($currencyRepository) {
                return $wallet->convertBalanceWithBaseCurrency();
            });
    
            // Simulate chart data: total amount of all transactions per day over the last 7 days
            $chartData = collect(range(6, 0)) // Last 7 days
                ->map(function ($daysAgo) use ($wallets) {
                    $date = now()->subDays($daysAgo)->endOfDay();
    
                    return $wallets->sum(function ($wallet) use ($date) {
                        return $wallet->transactions()
                            ->whereDate('created_at', '<=', $date)
                            ->sum('amount');
                    });
                })
                ->toArray();
    
            // Return a stat block for the category
            return Stat::make(str($category)->headline(), Number::currency($balance, $currencyRepository->localCurrency))
                ->chart($chartData)
                ->color('success')
                ->extraAttributes(['class' => 'whitespace-normal break-words']);
        })
        ->filter()
        ->toArray();
    }
}
