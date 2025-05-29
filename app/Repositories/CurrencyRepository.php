<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyRepository
{
    protected $currencies = [];
    protected $rates = [];
    protected $details = [];
    public string $localCurrency;

    public function __construct()
    {
        $this->localCurrency = env("LOCAL_CURRENCY", "USD");

        $this->details = Cache::remember("currency_details", now()->addDay(), function () {
            return Http::get("https://gist.githubusercontent.com/ksafranski/2973986/raw/5fda5e87189b066e11c1bf80bbfbecb556cf2cc1/Common-Currency.json")->json();
        });

        $this->currencies = Cache::remember("list_currencies", now()->addMonth(), function () {
            $response = Http::get(sprintf("https://api.exchangerate.host/list?access_key=%s", env("EXCHANGE_RATE_APIKEY")));
            $data = $response->json();
            return $data['currencies'] ?? [];
        });

        $this->rates = Cache::remember("rates", now()->addDay(), function () {
            $response = Http::get(sprintf(
                "https://api.exchangerate.host/live?access_key=%s&base=%s",
                env("EXCHANGE_RATE_APIKEY"),
                strtoupper(env("LOCAL_CURRENCY", "USD"))
            ));
            $data = $response->json();

            return $data['quotes'] ?? [];
        });

        $this->details = array_intersect_key($this->details, $this->currencies);
    }


    public function getCurrencies()
    {
        return $this->currencies;
    }

    public function getRates()
    {
        return $this->rates;
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function detectBaseCurrency(): ?string
    {
        $firstKey = array_key_first($this->rates);

        if ($firstKey && preg_match('/^[A-Z]{6}$/', $firstKey)) {
            // Misalnya: "USDIDR" → ambil "USD"
            return substr($firstKey, 0, 3);
        }

        return null; // fallback kalau format tidak valid
    }

    public function convert(float $amount, string $from, string $to): float
    {
        $base = $this->detectBaseCurrency();

        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        $rateFrom = $this->rates["{$base}{$from}"] ?? null;
        $rateTo = $this->rates["{$base}{$to}"] ?? null;

        if ($to === $base) {
            return $amount / $rateFrom;
        }

        if ($from == $base) {
            return $amount * $rateTo;
        }

        if ($rateFrom && $rateTo) {
            return $amount / $rateFrom * $rateTo;
        }

        return $amount;
    }
}
