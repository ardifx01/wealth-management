<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;

class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::user()->id;
        return $data;
    }

    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
