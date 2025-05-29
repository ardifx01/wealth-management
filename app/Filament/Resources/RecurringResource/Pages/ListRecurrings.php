<?php

namespace App\Filament\Resources\RecurringResource\Pages;

use App\Filament\Resources\RecurringResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecurrings extends ListRecords
{
    protected static string $resource = RecurringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
