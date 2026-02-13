<?php

namespace App\Filament\Resources\AIBudgets\Pages;

use App\Filament\Resources\AIBudgets\AIBudgetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAIBudgets extends ListRecords
{
    protected static string $resource = AIBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
