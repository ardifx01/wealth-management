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

class BalanceAllocationChart extends ChartWidget
{

    protected static ?string $heading = 'Alokasi Saldo Wallet';
    public ?string $type = '';
    public string $chartType = 'doughnut';
    public $wallet;
    protected static ?int $sort = 3;
    protected function getFilters(): ?array
    {
        return [
            'all' => 'Semua Wallet',
            'positive' => 'Saldo Positif',
            'negative' => 'Saldo Negatif',
            'active' => 'Wallet Aktif',
        ];
    }

    protected static bool $isLazy = true;

    protected static ?array $options = [
        'responsive' => true,
        'maintainAspectRatio' => true,
        'cutout' => '60%', // Ini yang membuat donut hole
        'plugins' => [
            'tooltip' => [
                'enabled' => true,
                'callbacks' => [
                    'label' => 'function(context) {
                        var label = context.label || "";
                        var value = context.parsed;
                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                        var percentage = ((value / total) * 100).toFixed(1);
                        return label + ": Rp " + value.toLocaleString("id-ID") + " (" + percentage + "%)";
                    }'
                ]
            ],
            'legend' => [
                'display' => true,
                'position' => 'bottom',
            ],
        ],
    ];

    public function getDescription(): ?string
    {
        $walletData = $this->getWalletData();
        $totalBalance = array_sum(array_column($walletData, 'balance'));
        $walletCount = count($walletData);

        if ($totalBalance == 0) {
            return "Total {$walletCount} wallet dengan saldo kosong.";
        }

        $positiveWallets = array_filter($walletData, fn($wallet) => $wallet['balance'] > 0);
        $negativeWallets = array_filter($walletData, fn($wallet) => $wallet['balance'] < 0);

        $positiveCount = count($positiveWallets);
        $negativeCount = count($negativeWallets);
        $totalBalanceFormatted = "Rp " . number_format($totalBalance, 0, ',', '.');

        return "Total saldo: {$totalBalanceFormatted} | Wallet positif: {$positiveCount} | Wallet negatif: {$negativeCount}";
    }

    protected function getType(): string
    {
        return $this->chartType;
    }

    public function getColumnSpan(): int|string
    {
        return '1';
    }

    protected function getWalletData(): array
    {
        $user = Auth::user();
        $wallets = Wallet::where('user_id', $user->id)->get();

        if (!$this->filter) {
            $this->filter = Session::get('chart_balance', 'all');
        } else {
            Session::put('chart_balance', $this->filter);
        }

        $type = $this->filter;

        // Filter wallets based on selected type
        $filteredWallets = $wallets->filter(function ($wallet) use ($type) {
            $balance = $wallet->convertBalanceWithBaseCurrency();

            switch ($type) {
                case 'positive':
                    return $balance > 0;
                case 'negative':
                    return $balance < 0;
                case 'active':
                    return $balance != 0; // Non-zero balance
                case 'all':
                default:
                    return true;
            }
        });

        return $filteredWallets->map(function ($wallet) {
            return [
                'name' => $wallet->name,
                'balance' => $wallet->convertBalanceWithBaseCurrency(),
                'currency' => $wallet->currency,
                'id' => $wallet->id,
            ];
        })->toArray();
    }

    protected function getData(): array
    {
        $walletData = $this->getWalletData();

        if (empty($walletData)) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderColor' => ['#d1d5db'],
                        'borderWidth' => 1,
                    ]
                ],
                'labels' => ['Tidak ada wallet'],
            ];
        }

        // Generate colors for each wallet
        $colors = [
            '#3b82f6',  // Blue
            '#10b981',  // Green
            '#f59e0b',  // Orange
            '#ef4444',  // Red
            '#8b5cf6',  // Purple
            '#06b6d4',  // Cyan
            '#84cc16',  // Lime
            '#f97316',  // Orange-600
            '#ec4899',  // Pink
            '#6366f1',  // Indigo
            '#14b8a6',  // Teal
            '#eab308',  // Yellow
        ];

        $borderColors = [
            '#1d4ed8',  // Blue-700
            '#059669',  // Green-600
            '#d97706',  // Orange-600
            '#dc2626',  // Red-600
            '#7c3aed',  // Purple-600
            '#0891b2',  // Cyan-600
            '#65a30d',  // Lime-600
            '#ea580c',  // Orange-700
            '#db2777',  // Pink-600
            '#4f46e5',  // Indigo-600
            '#0d9488',  // Teal-600
            '#ca8a04',  // Yellow-600
        ];

        $chartData = [];
        $labels = [];
        $backgroundColors = [];
        $chartBorderColors = [];

        foreach ($walletData as $index => $wallet) {
            $balance = abs($wallet['balance']); // Use absolute value for chart display

            if ($balance > 0) { // Only show wallets with non-zero balance
                $chartData[] = $balance;
                $labels[] = $wallet['name'] . ($wallet['balance'] < 0 ? ' (Hutang)' : '');
                $backgroundColors[] = $colors[$index % count($colors)];
                $chartBorderColors[] = $borderColors[$index % count($borderColors)];
            }
        }

        // If no wallets with balance, show placeholder
        if (empty($chartData)) {
            return [
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderColor' => ['#d1d5db'],
                        'borderWidth' => 1,
                    ]
                ],
                'labels' => ['Tidak ada saldo'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $chartData,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $chartBorderColors,
                    'borderWidth' => 2,
                ]
            ],
            'labels' => $labels,
        ];
    }
}
