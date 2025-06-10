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
                $wallet = Wallet::where('category', $category)->first();

                if (!$wallet) return null;

                $balance = $wallet->convertBalanceWithBaseCurrency();

                // Simulasi data chart. Gantilah ini dengan data historis saldo jika tersedia.
                $chartData = collect(range(6, 0)) // 7 hari terakhir
                    ->map(function ($daysAgo) use ($wallet) {
                        $date = now()->subDays($daysAgo)->endOfDay();

                        return $wallet->transactions()
                            ->where('created_at', '<=', $date)
                            ->sum('amount');
                    })
                    ->toArray();

                return Stat::make(str($category)->headline(), Number::currency($balance, $currencyRepository->localCurrency))
                    ->chart($chartData)
                    ->color('success')
                    ->extraAttributes(['class' => 'whitespace-normal break-words']);
            })
            ->filter()
            ->toArray();
    }
}
