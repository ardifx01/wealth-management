<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Filament\Resources\WalletResource\RelationManagers;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\WalletType;
use App\Models\Transaction;
use App\Repositories\CurrencyRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    public static function form(Form $form): Form
    {
        $detailCurrencies = app(CurrencyRepository::class)->getDetails();

        return $form
            ->schema([
                Forms\Components\TextInput::make("name")
                    ->required(),
                Forms\Components\Textarea::make("descrption"),
                Forms\Components\Select::make("category")
                    ->options(
                        array_combine(
                            array_map(fn($case) => $case->value, WalletType::cases()),
                            array_map(fn($case) => ucfirst(strtolower($case->value)), WalletType::cases())
                        )
                    )
                    ->required()
                    ->searchable()
                    ->native(false),
                Forms\Components\Select::make("currency")
                    ->options(
                        collect($detailCurrencies)->mapWithKeys(fn($data, $code) => [$code => sprintf("%s (%s)", $data["name"], $code)])->toArray()
                    )
                    ->searchable()
                    ->required()
                    ->native(false),

            ]);
    }

    public static function table(Table $table): Table
    {
        $currencyRepository = app(CurrencyRepository::class);
        $detailCurrencies = $currencyRepository->getDetails();
        // dd(Auth::user()->transactions);
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->description(function (Wallet $record) {
                        return $record->description;
                    }),
                Tables\Columns\TextColumn::make("category")
                    ->description(function (Wallet $record) {
                        return $record->category;
                    }),
                Tables\Columns\TextColumn::make("currency")
                    ->description(function (Wallet $record) use ($detailCurrencies) {
                        return collect($detailCurrencies)->get($record->currency)["name"];
                    }),
                Tables\Columns\TextColumn::make("balance")
                    ->formatStateUsing(fn(Wallet $record) => Number::currency($record->balance, $record->currency)),
                Tables\Columns\TextColumn::make("created_at")
                    ->date(),
                Tables\Columns\TextColumn::make("updated_at")
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make("incomes")
                    ->form([
                        Forms\Components\TextInput::make("amount")
                            ->required(),
                        Forms\Components\Select::make("wallet_id")
                            ->label("Wallet")
                            ->options(Wallet::all()->pluck("name", "id")->toArray())
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $state && $set("currency", Wallet::find($state)->currency))
                            ->native(false)
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make("currency")
                            ->options(
                                collect($detailCurrencies)->mapWithKeys(fn($data, $code) => [$code => sprintf("%s (%s)", $data["name"], $code)])->toArray()
                            )
                            ->searchable()
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make("descrption")
                            ->datalist(fn () => Auth::user()->transactions->pluck("description")->toArray())

                    ])
                    ->requiresConfirmation()
                    ->color("success")
                    ->icon("heroicon-o-banknotes")
                    ->action(function (array $data) use ($currencyRepository) {
                        $wallet = Wallet::find($data["wallet_id"]);

                        if ($data["currency"] != $wallet->currency) {
                            $data["amount"] = $currencyRepository->convert($data["amount"], $data["currency"], $wallet->currency);
                        }

                        $wallet->income($data["amount"], $data["descrption"]);
                    }),
                Tables\Actions\Action::make("expenses")
                    ->form([
                        Forms\Components\TextInput::make("amount")
                            ->required(),
                        Forms\Components\Select::make("wallet_id")
                            ->label("Wallet")
                            ->options(Wallet::all()->pluck("name", "id")->toArray())
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set("currency", Wallet::find($state)->currency))
                            ->native(false)
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make("currency")
                            ->options(
                                collect($detailCurrencies)->mapWithKeys(fn($data, $code) => [$code => sprintf("%s (%s)", $data["name"], $code)])->toArray()
                            )
                            ->searchable()
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make("descrption")
                            ->datalist(fn () => Auth::user()->transactions->pluck("description")->toArray())

                    ])
                    ->requiresConfirmation()
                    ->color("danger")
                    ->icon("heroicon-o-minus-circle")
                    ->action(function (array $data) use ($currencyRepository) {
                        $wallet = Wallet::find($data["wallet_id"]);

                        if ($data["currency"] != $wallet->currency) {
                            $data["amount"] = $currencyRepository->convert($data["amount"], $data["currency"], $wallet->currency);
                        }

                        $wallet->income($data["amount"], $data["descrption"]);
                    }),
                Tables\Actions\Action::make("transfer")
                    ->form([
                        Forms\Components\TextInput::make("amount")
                            ->required(),
                        Forms\Components\Select::make("origin_id")
                            ->label("Origin Wallet")
                            ->options(Wallet::all()->pluck("name", "id")->toArray())
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set("currency", Wallet::find($state)->currency))
                            ->native(false)
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make("destionation_id")
                            ->label("Destination Wallet")
                            ->options(Wallet::all()->pluck("name", "id")->toArray())
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set("currency", Wallet::find($state)->currency))
                            ->native(false)
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make("descrption")
                            ->datalist(fn () => Auth::user()->transactions->pluck("description")->toArray())

                    ])
                    ->requiresConfirmation()
                    ->color("info")
                    ->icon("heroicon-o-arrows-up-down")
                    ->action(function (array $data) use ($currencyRepository) {
                        $from = Wallet::find($data["origin_id"]);
                        $to = Wallet::find($data["destionation_id"]);

                        $originalAmount = $data["amount"];

                        if ($from->currency != $to->currency) {
                            $data["amount"] = $currencyRepository->convert($data["amount"], $from->currency, $to->currency);
                        }

                        $from->transfer($to, $originalAmount, $data["amount"], $data["descrption"]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
