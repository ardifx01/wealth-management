<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringResource\Pages;
use App\Filament\Resources\RecurringResource\RelationManagers;
use App\Models\Recurring;
use App\Models\Wallet;
use App\Repositories\CurrencyRepository;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecurringResource extends Resource
{
    protected static ?string $model = Recurring::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    public static function form(Form $form): Form
    {
        $currencyRepository = app(CurrencyRepository::class);
        $detailCurrencies = $currencyRepository->getDetails();

        return $form
            ->schema([
                Forms\Components\TextInput::make("title")
                    ->required(),
                Forms\Components\Select::make("type_amount")
                    ->options([
                        "fixed" => "fixed",
                        "percentage" => "percentage",
                    ])
                    ->live()
                    ->native(false)
                    ->required(fn(string $context) => $context === 'create'),
                Forms\Components\Select::make("wallet_id")
                    ->label("Wallet")
                    ->options(Wallet::all()->pluck("name", "id")->toArray())
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state, callable $set) => $set("currency", Wallet::find($state)->currency))
                    ->native(false)
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make("type")
                    ->options([
                        'income' => "income",
                        'expense' => "expense",
                        'transfer' => "transfer"
                    ])
                    ->native(false)
                    ->required(),
                Forms\Components\TextInput::make("percentage")
                    ->numeric()
                    ->live()
                    ->hidden(fn(Get $get) => $get("type_amount") !== "percentage")
                    ->required(fn(Get $get) => $get("type_amount") === "percentage"),
                Forms\Components\TextInput::make("fixed")
                    ->numeric()
                    ->live()
                    ->hidden(fn(Get $get) => $get("type_amount") !== "fixed")
                    ->required(fn(Get $get) => $get("type_amount") === "fixed"),
                Forms\Components\Select::make("currency")
                    ->options(
                        collect($detailCurrencies)->mapWithKeys(fn($data, $code) => [$code => sprintf("%s (%s)", $data["name"], $code)])->toArray()
                    )
                    ->searchable()
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make("interval")
                    ->options([
                        'daily' => "daily",
                        'weekly' => "weekly",
                        'monthly' => "monthly",
                        'yearly' => "yearly",
                        'weekday' => "weekday",
                        'weekend' => "weekend",
                    ])
                    ->native(false)
                    ->required(),
                Forms\Components\DatePicker::make("start_date")
                    ->native(false)
                    ->required(),
                Forms\Components\DatePicker::make("end_date")
                    ->native(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("title"),
                Tables\Columns\TextColumn::make("wallet.name"),
                Tables\Columns\TextColumn::make("id")
                    ->label("Status")
                    ->formatStateUsing(fn(Recurring $record) => $record->end_date ? (now()->greaterThan($record->end_date) ? "active" : "inactive") : "infinite"),
                Tables\Columns\TextColumn::make("interval"),
                Tables\Columns\TextColumn::make("start_date")
                    ->date(),
                Tables\Columns\TextColumn::make("end_date")
                    ->date()
                    ->hidden(fn(?Recurring $record) => $record?->end_date === null),
                Tables\Columns\TextColumn::make("last_generated")
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListRecurrings::route('/'),
            'create' => Pages\CreateRecurring::route('/create'),
            'edit' => Pages\EditRecurring::route('/{record}/edit'),
        ];
    }
}
