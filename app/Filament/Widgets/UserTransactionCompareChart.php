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
        $walletIds = Wallet::where('user_id', $user->id)->pluck('id');
        $query = Transaction::whereIn('wallet_id', $walletIds)->whereNull('reference_id');

        if (!$this->filter) {
            $this->filter = Session::get('chart_balance', 'daily');
        } else {
            Session::put('chart_balance', $this->filter);
        }

        $type = $this->filter;
        $date = now();

        // Apply date filters based on selected period
        if ($type === 'daily') {
            $start = $date->copy()->subDays(29)->startOfDay();
            $query->whereDate('created_at', '>=', $start);
        } elseif ($type === 'weekly') {
            $start = $date->copy()->subDays(6)->startOfDay();
            $query->whereDate('created_at', '>=', $start);
        } elseif ($type === 'monthly') {
            $start = $date->copy()->subYear();
            $query->whereDate('created_at', '>=', $start);
        } elseif ($type === 'ytd') {
            $start = $date->copy()->startOfYear();
            $query->whereDate('created_at', '>=', $start);
        }
        // For 'all', no date filter is applied

        $transactions = $query->get();

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');

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
