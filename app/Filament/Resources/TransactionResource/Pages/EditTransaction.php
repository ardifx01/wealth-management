<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data["reference_id"] ?? null) {
            Transaction::where("id", intval($data["reference_id"]))->update([
                "amount" => intval($data["amount"]),
                "created_at" => $data["created_at"],
                "updated_at" => $data["created_at"],
                "description" => $data["description"],
                // "type" => $data["type"] === "expense" ? "income" : "expense",
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
