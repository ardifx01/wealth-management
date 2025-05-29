<?php

namespace App\Services;

use App\Models\Recurring;
use App\Models\Wallet;
use App\Repositories\CurrencyRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RecurringTransactionService
{
    public function __construct(
        private CurrencyRepository $currencyRepository
    ) {}

    public function generate()
    {
        $now = now();
        $user = Auth::user();
        $recurrings = Recurring::with("wallet")
            ->where('user_id', $user->id)
            ->where(function ($query) use ($now) {
                $query->whereNull('last_generated')
                    ->orWhereDate('last_generated', '<', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $now);
            })
            ->get();

        foreach ($recurrings as $recurring) {
            $last = $recurring->last_generated ?? $recurring->start_date;
            $nextDates = $this->calculateDueDates($recurring, Carbon::parse($last), $now);
            $originalAmount = $recurring->fixed ?  $recurring->fixed : $recurring->wallet->balance *  $recurring->percentage / 100;
            $destinationWallet = Wallet::where('id', $recurring->destination_id)->first();
            $amount = $originalAmount;

            if ($destinationWallet) {
                if ($recurring->wallet->currency != $destinationWallet->currency) {
                    $amount = $this->currencyRepository->convert($originalAmount, $recurring->wallet->currency, $destinationWallet->currency);
                }
            } else {
                if ($recurring->currency && $recurring->wallet->currency != ($recurring->currency ?? null)) {
                    $amount = $this->currencyRepository->convert($originalAmount, $recurring->wallet->currency, $recurring->currency);
                }
            }

            foreach ($nextDates as $date) {
                if ($recurring->type == "transfer") {
                    $recurring->wallet->transfer($destinationWallet, $originalAmount, $amount, $recurring->title);
                } else {
                    if ($recurring->type == "expense") {
                        $recurring->wallet->expense($amount, $recurring->title);
                    } else {
                        $recurring->wallet->income($amount, $recurring->title);
                    }
                }

                $recurring->update(['last_generated' => $now]);
            }
        }
    }

    private function calculateDueDates(Recurring $recurring, Carbon $from, Carbon $to): array
    {
        $dates = [];

        $date = $from->copy()->startOfDay(); // pastikan waktu nol
        $end = $to->copy()->startOfDay();

        switch ($recurring->interval) {
            case 'daily':
                while ($date->lte($end)) {
                    $dates[] = $date->copy();
                    $date->addDay();
                }
                break;

            case 'weekly':
                while ($date->lte($end)) {
                    $dates[] = $date->copy();
                    $date->addWeek();
                }
                break;

            case 'monthly':
                while ($date->lte($end)) {
                    $dates[] = $date->copy();
                    $date->addMonth();
                }
                break;

            case 'yearly':
                while ($date->lte($end)) {
                    $dates[] = $date->copy();
                    $date->addYear();
                }
                break;

            case 'weekday':
                while ($date->lte($end)) {
                    if ($date->isWeekday()) {
                        $dates[] = $date->copy();
                    }
                    $date->addDay();
                }
                break;

            case 'weekend':
                while ($date->lte($end)) {
                    if ($date->isWeekend()) {
                        $dates[] = $date->copy();
                    }
                    $date->addDay();
                }
                break;
        }

        return $dates;
    }
}
