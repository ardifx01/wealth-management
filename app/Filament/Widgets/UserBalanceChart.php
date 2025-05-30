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

class UserBalanceChart extends ChartWidget
{

    protected static ?string $heading = 'Statistik Saldo';
    public ?string $type = '';
    public string $chartType = 'line';
    public $wallet;

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
        'plugins' => [
            'tooltip' => [
                'enabled' => true,
            ],
            'legend' => [
                'display' => true,
            ],
        ],
        'scales' => [
            'y' => [
                'type' => 'linear',
                'display' => true,
                'position' => 'left',
            ],
            'y1' => [
                'type' => 'linear',
                'display' => true,
                'position' => 'right',
                'grid' => [
                    'drawOnChartArea' => false,
                ],

            ],
            'x' => [
                'ticks' => [
                    'autoSkip' => true,
                    'maxTicksLimit' => 0, // batasi jumlah label X biar gak numpuk
                ],
            ],
        ],
    ];

    public function getDescription(): ?string
    {
        $balances = $this->getData(now());
        $currentBalance = array_sum($balances);
        $previousBalance = $balances[0];

        if ($previousBalance == 0) {
            return $currentBalance == 0
                ? "saldo kamu tidak berubah dari selama periodeini!"
                : sprintf("saldo kamu %s dari awal periode ini!", "+" . $currentBalance . "%");
        }

        $percentageChange = (($currentBalance - $previousBalance) / abs($previousBalance)) * 100;
        $formattedChange = ($percentageChange > 0 ? "+" : "") . round($percentageChange, 2) . "%";

        return "saldo kamu {$formattedChange} dari awal periode ini!";
    }


    protected function getType(): string
    {
        return $this->chartType;
    }

    public function getColumnSpan(): int|string
    {
        return 'full';
    }

    protected function getData($customDate = null): array
    {
        $user = Auth::user();
        $walletIds = Wallet::where('user_id', $user->id)->pluck('id');
        $query = Transaction::whereIn('wallet_id', $walletIds)->whereNull('reference_id');
        $type = $this->filter;

        $data = collect();
        $labels = collect();
        $date = $customDate ?? now();

        if (!$this->filter) {
            // Jika tidak ada input type, ambil dari session atau default ke 'daily'
            $this->filter = Session::get('chart_balance', 'daily');
        } else {
            // Jika ada input type, simpan ke session untuk diingat
            Session::put('chart_balance', $this->filter);
        }

        $type = $this->filter;

        if ($type === 'daily') {
            $start = $date->subDays(29)->startOfDay();
            $query->whereDate('created_at', '>=', $start);
            $data = $query->get()->groupBy(fn($tx) => $tx->created_at->format('Y-m-d'))
                ->map(fn($group) => $group->sum(fn($tx) => $tx->type === 'income' ? $tx->amount : -$tx->amount));
            $labels = collect(range(0, 29))->map(fn($i) => $start->copy()->addDays($i)->format('Y-m-d'));
        } elseif ($type === 'weekly') {
            $start = $date->subDays(6)->startOfDay();
            $query->whereDate('created_at', '>=', $start);
            $data = $query->get()->groupBy(fn($tx) => $tx->created_at->format('Y-m-d'))
                ->map(fn($group) => $group->sum(fn($tx) => $tx->type === 'income' ? $tx->amount : -$tx->amount));
            $labels = collect(range(0, 6))->map(fn($i) => $start->copy()->addDays($i)->format('Y-m-d'));
        } elseif ($type === 'monthly') {
            $start = $date->copy()->subYear();
            $query->whereDate('created_at', '>=', $start);

            $data = $query->get()
                ->groupBy(fn($tx) => $tx->created_at->format('Y-m'))
                ->map(fn($group) => $group->sum(fn($tx) => $tx->type === 'income' ? $tx->amount : -$tx->amount));
            $labels = collect(range(0, 11))
                ->map(fn($i) => $date->copy()->subMonths(11 - $i)->format('Y-m'));
        } elseif ($type === 'ytd') {
            $start = $date->copy()->startOfYear();
            $query->whereDate('created_at', '>=', $start);

            $data = $query->get()
                ->groupBy(fn($tx) => $tx->created_at->format('Y-m'))
                ->map(fn($group) => $group->sum(fn($tx) => $tx->type === 'income' ? $tx->amount : -$tx->amount));

            $labels = collect(range(0, $date->month - 1))
                ->map(fn($i) => $date->copy()->startOfYear()->addMonths($i)->format('Y-m'));
        } else {
            // Ambil semua transaksi user tanpa filter tanggal
            $transactions = $query->get();

            if ($transactions->isEmpty()) {
                // Kalau tidak ada data, kosongkan labels dan data
                $labels = collect();
                $data = collect();
            } else {
                // Cari tanggal paling awal dan paling akhir dari transaksi
                $minDate = $transactions->min(fn($tx) => $tx->created_at);
                $maxDate = $transactions->max(fn($tx) => $tx->created_at);

                // Jika selisih bulan terlalu sedikit (misal < 6 bulan), mundurkan setahun
                if ($minDate->diffInMonths($maxDate) < 6) {
                    $minDate = $minDate->copy()->subYear();
                }

                // Buat label bulan dari awal sampai akhir
                $start = $minDate->copy()->startOfMonth();
                $end = $maxDate->copy()->startOfMonth();

                $labels = collect();

                while ($start->lessThanOrEqualTo($end)) {
                    $labels->push($start->format('Y-m'));
                    $start->addMonth();
                }

                // Group transaksi per bulan dan hitung saldo tiap bulan
                $data = $transactions->groupBy(fn($tx) => $tx->created_at->format('Y-m'))
                    ->map(fn($group) => $group->sum(fn($tx) => $tx->type === 'income' ? $tx->amount : -$tx->amount));
            }
        }

        $balances = [];
        $runningTotal = 0;
        foreach ($labels as $label) {
            $runningTotal += $data[$label] ?? 0;
            $balances[] = round($runningTotal, 2);
        }

        if ($customDate) {
            return  $balances;
        }

        $positive =  [
            'label' => 'Saldo',
            'data' => array_map(fn($value) => $value > 0 ? $value : 0, $balances),
            'fill' => true,
            'borderColor' => '#3b82f6',
            'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
        ];

        $negative =  [
            'label' => 'Hutang',
            'data' => array_map(fn($value) => $value < 0 ? $value : 0, $balances),
            'fill' => true,
            'borderColor' => '#eb4034',
            'backgroundColor' => 'rgba(235, 64, 52, 0.2)',
        ];

        return [
            'datasets' => [
                $positive,
                $negative,
            ],
            'labels' => $labels->toArray(),
        ];
    }
}
