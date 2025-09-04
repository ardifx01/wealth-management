<?php

namespace App\Filament\Widgets;

use App\Repositories\CurrencyRepository;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

class CurrencySwitcher extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.currency-switcher';

    public array $currencies = [];

    public ?string $currency = null;

    public function mount(CurrencyRepository $currencyRepository): void
    {
        $detailCurrencies = $currencyRepository->getDetails();
        $this->currencies = collect($detailCurrencies)
            ->mapWithKeys(
                fn($data, $code) => [
                    $code => sprintf(
                        '%s (%s)',
                        $data['name'],
                        $code,
                    ),
                ],
            )
            ->toArray();
        $this->currency = $currencyRepository->localCurrency;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('currency')
                ->options($this->currencies)
                ->native(false)
                ->searchable()
                ->default($this->currency)
                ->live()
                ->afterStateUpdated(function ($state) {
                    app(CurrencyRepository::class)->changeLocalCurrency($state);
                    $this->dispatch('currencyChanged');
                }),
        ];
    }
}
