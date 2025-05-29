<?php

namespace App\Filament\Widgets;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Widgets\ChartWidget;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Auth;

class UserBalanceChart extends ChartWidget
{

    protected static ?string $heading = 'Statistik Saldo';
    public ?string $type = 'monthly';
    public ?string $chartType = 'line';
    public $wallet;

    protected function getFilters(): ?array
    {
        return [
            'daily' => 'Harian (30 hari terakhir)',
            'monthly' => 'Bulanan (12 bulan terakhir)',
            'ytd' => 'Year to Date',
            'all' => 'Semua',
        ];
    }
    protected static bool $isLazy = true;

    protected static ?array $options = [
        'plugins' => [
            'legend' => [
                'display' => true,
            ],
        ],
    ];

    public function getDescription(): ?string
    {
        return 'Statistik saldo kamu.';
    }

    protected function getType(): string
    {
        return $this->chartType ?? 'line';
    }

    public function getColumnSpan(): int|string
    {
        return 'full';
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $walletIds = Wallet::where('user_id', $user->id)->pluck('id');
        $query = Transaction::whereIn('wallet_id', $walletIds)->whereNull('reference_id');
        $type = $this->filter ?? "daily";

        $data = collect();
        $labels = collect();

        if ($type === 'daily') {
            $start = now()->subDays(29)->startOfDay();
            $query->whereDate('created_at', '>=', $start);
            $data = $query->get()->groupBy(fn($tx) => $tx->created_at->format('Y-m-d'))
                ->map(fn($group) => $group->sum(fn($tx) => $tx->type === 'income' ? $tx->amount : -$tx->amount));
            $labels = collect(range(0, 29))->map(fn($i) => $start->copy()->addDays($i)->format('Y-m-d'));
        } elseif ($type === 'monthly') {
            $start = now()->startOfYear()->subMonths(11);
            $query->whereDate('created_at', '>=', $start);
            $data = $query->get()->groupBy(fn($tx) => $tx->created_at->format('Y-m'))
                ->map(fn($group) => $group->sum(fn($tx) => $tx->type === 'income' ? $tx->amount : -$tx->amount));
            $labels = collect(range(0, 11))->map(fn($i) => now()->subMonths(11 - $i)->format('Y-m'));
        } elseif ($type === 'ytd') {
            $start = now()->startOfYear();
            $query->whereDate('created_at', '>=', $start);
            $data = $query->get()->groupBy(fn($tx) => $tx->created_at->format('Y-m'))
                ->map(fn($group) => $group->sum(fn($tx) => $tx->type === 'income' ? $tx->amount : -$tx->amount));
            $labels = collect(range(0, now()->month - 1))->map(fn($i) => now()->startOfYear()->addMonths($i)->format('Y-m'));
        } else {
            // Ambil semua transaksi user tanpa filter tanggal
            $transactions = $query->get();

            if ($transactions->isEmpty()) {
                // Kalau tidak ada data, kosongkan labels dan data
                $labels = collect();
                $data = collect();
            } else {
                // Cari bulan paling awal dan paling akhir dari transaksi
                $minDate = $transactions->min(fn($tx) => $tx->created_at);
                $maxDate = $transactions->max(fn($tx) => $tx->created_at);

                // Buat label bulan mulai dari bulan paling awal sampai paling akhir
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

        return [
            'datasets' => [
                [
                    'label' => 'Saldo',
                    'data' => $balances,
                    'fill' => true,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }
}
