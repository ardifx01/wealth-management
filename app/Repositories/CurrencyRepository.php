<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyRepository
{
    public $currencies = [];
    protected $rates = [];
    public $details = [];
    public string $userId;
    public string $localCurrency;

    public function __construct()
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        if (!Auth::check()) {
            return;
        }
        $this->userId = Auth::id();

        $this->localCurrency = Cache::remember("user_{$this->userId}_currency", now()->addMonth(), function () {
            return env('LOCAL_CURRENCY', 'USD');
        });

        //         if (!$this->details) {
        //           Cache::forget("currency_details");
        //         }
        //         if (!$this->currencies) {
        //           Cache::forget("list_currencies");
        //         }
        //         if (!$this->rates) {
        //           Cache::forget("rates");
        //         }

        $this->details = Cache::rememberForever('currency_details', function () {
            return Http::get('https://gist.githubusercontent.com/ksafranski/2973986/raw/5fda5e87189b066e11c1bf80bbfbecb556cf2cc1/Common-Currency.json')
                ->json();
        });

        $this->currencies = Cache::remember('list_currencies', now()->addMonth(), function () {
            $response = Http::get(sprintf('https://api.exchangerate.host/list?access_key=%s', env('EXCHANGE_RATE_APIKEY')));
            $data = $response->json();
            return $data['currencies'] ?? [];
        });

        $this->rates = Cache::remember('rates', now()->addDay(), function () {
            $response = Http::get('https://api.frankfurter.app/latest?from=USD');
            $data = $response->json();

            return $data ?? [];
        });

        $this->details = array_intersect_key($this->details, $this->currencies);
    }

    public function changeLocalCurrency(string $currency): void
    {
        $this->localCurrency = $currency;

        Cache::forget("user_{$this->userId}_currency");
        Cache::rememberForever("user_{$this->userId}_currency", fn() => $this->localCurrency);
    }

    public function getCurrencies(): array
    {
        return Cache::get('list_currencies');
    }

    public function getRates(): array
    {
        return Cache::get('rates');
    }

    public function getDetails(): array
    {
        return Cache::get('currency_details');
    }

    public function detectBaseCurrency(): ?string
    {
        return $this->rates['base'] ?? 'USD';  // fallback kalau format tidak valid
    }

    public function convert(float $amount, string $from, string $to): float
    {
        $base = $this->detectBaseCurrency();

        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        $rateFrom = $this->rates['rates'][$from] ?? 1;
        $rateTo = $this->rates['rates'][$to] ?? 1;

        if ($from === $base && $rateTo !== null) {
            return $amount * $rateTo;
        }

        if ($to === $base && $rateFrom !== null) {
            return $amount / $rateFrom;
        }

        if ($to === $base && $rateTo !== null) {
            return $amount * $rateTo;
        }

        if ($rateFrom !== null && $rateTo !== null) {
            return ($amount / $rateFrom) * $rateTo;
        }

        return $amount;
    }
}
