<?php

namespace App\Filament\Resources\AIBudgets\Pages;

use App\Filament\Resources\AIBudgets\AIBudgetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAIBudget extends EditRecord
{
    protected static string $resource = AIBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
