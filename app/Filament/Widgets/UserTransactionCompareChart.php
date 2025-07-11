<?php

namespace App\Filament\Widgets;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Widgets\ChartWidget;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Repositories\CurrencyRepository;

class UserTransactionCompareChart extends ChartWidget
{

    protected static ?string $heading = 'Perbandingan Income vs Expense';
    public ?string $type = '';
    public string $chartType = 'doughnut';
    public $wallet;
    protected static ?int $sort = 4;

    protected function getFilters(): ?array
    {
        return [
            'daily' => 'Harian (30 hari terakhir)',
            'weekly' => 'Mingguan (7 hari terakhir)',
            'monthly' => 'Bulanan (12 bulan terakhir)',
            'ytd' => 'Year to Date',
            'all' => 'Semua',
        ];
    }

    protected static bool $isLazy = true;

    protected static ?array $options = [
        'responsive' => true,
        'maintainAspectRatio' => true,
        'cutout' => '60%',
        'plugins' => [
            'tooltip' => [
                'enabled' => true,
            ],
            'legend' => [
                'display' => true,
            ],
        ],
    ];

    public function getDescription(): ?string
    {
        $data = $this->getChartData();
        $totalIncome = $data['income'];
        $totalExpense = $data['expense'];
        $netBalance = $totalIncome - $totalExpense;

        if ($totalIncome == 0 && $totalExpense == 0) {
            return "Tidak ada transaksi dalam periode ini.";
        }

        $incomePercentage = $totalIncome > 0 ? round(($totalIncome / ($totalIncome + $totalExpense)) * 100, 1) : 0;
        $expensePercentage = $totalExpense > 0 ? round(($totalExpense / ($totalIncome + $totalExpense)) * 100, 1) : 0;

        $status = $netBalance >= 0 ? "surplus" : "defisit";
        $statusAmount = "Rp " . number_format(abs($netBalance), 0, ',', '.');

        return "Income: {$incomePercentage}% | Expense: {$expensePercentage}% | Net {$status}: {$statusAmount}";
    }

    protected function getType(): string
    {
        return $this->chartType;
    }

    public function getColumnSpan(): int|string
    {
        return '1';
    }

    protected function getChartData(): array
  {
      $user = Auth::user();
      $currencyRepository = app(CurrencyRepository::class);
      $baseCurrency = $currencyRepository->localCurrency;
  
      // Get user's wallet IDs
      $walletIds = Wallet::where('user_id', $user->id)->pluck('id');
  
      // Base transaction query
      $query = Transaction::whereIn('wallet_id', $walletIds)->with('wallet');
  
      // Session-based filter
      $this->filter = $this->filter ?? Session::get('chart_balance', 'daily');
      Session::put('chart_balance', $this->filter);
  
      $date = now();
  
      switch ($this->filter) {
          case 'daily':
              $query->whereDate('created_at', '>=', $date->copy()->subDays(29)->startOfDay());
              break;
          case 'weekly':
              $query->whereDate('created_at', '>=', $date->copy()->subDays(6)->startOfDay());
              break;
          case 'monthly':
              $query->whereDate('created_at', '>=', $date->copy()->subYear());
              break;
          case 'ytd':
              $query->whereDate('created_at', '>=', $date->copy()->startOfYear());
              break;
      }
  
      $transactions = $query->get();
  
      // Find transfer parents (referenced by child transactions)
      $childRefs = $transactions
          ->whereNotNull('reference_id')
          ->pluck('reference_id')
          ->unique();
  
      // Only include external transactions
      $externalTransactions = $transactions->filter(function ($tx) use ($childRefs) {
          return is_null($tx->reference_id) && !$childRefs->contains($tx->id);
      });
  
      // Sum income with currency conversion
      $totalIncome = $externalTransactions
          ->where('type', 'income')
          ->sum(function ($tx) use ($currencyRepository, $baseCurrency) {
              $fromCurrency = $tx->wallet->currency ?? $baseCurrency;
              return $currencyRepository->convert($tx->amount, $fromCurrency, $baseCurrency);
          });
  
      // Sum expense with currency conversion
      $totalExpense = $externalTransactions
          ->where('type', 'expense')
          ->sum(function ($tx) use ($currencyRepository, $baseCurrency) {
              $fromCurrency = $tx->wallet->currency ?? $baseCurrency;
              return $currencyRepository->convert($tx->amount, $fromCurrency, $baseCurrency);
          });
  
      return [
          'income' => $totalIncome,
          'expense' => $totalExpense,
      ];
  }

    protected function getData(): array
    {
        $data = $this->getChartData();

        $totalIncome = $data['income'];
        $totalExpense = $data['expense'];

        // If no data, show placeholder
        if ($totalIncome == 0 && $totalExpense == 0) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderColor' => ['#d1d5db'],
                        'borderWidth' => 1,
                    ]
                ],
                'labels' => ['Tidak ada data'],
            ];
        }

        $chartData = [];
        $labels = [];
        $backgroundColors = [];
        $borderColors = [];

        if ($totalIncome > 0) {
            $chartData[] = $totalIncome;
            $labels[] = 'Income';
            $backgroundColors[] = '#10b981'; // Green
            $borderColors[] = '#059669';
        }

        if ($totalExpense > 0) {
            $chartData[] = $totalExpense;
            $labels[] = 'Expense';
            $backgroundColors[] = '#f59e0b'; // Orange/Yellow
            $borderColors[] = '#d97706';
        }

        return [
            'datasets' => [
                [
                    'data' => $chartData,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                ]
            ],
            'labels' => $labels,
        ];
    }
}
