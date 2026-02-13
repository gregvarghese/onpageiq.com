<?php

namespace App\Filament\Resources\AIBudgets;

use App\Filament\Resources\AIBudgets\Pages\CreateAIBudget;
use App\Filament\Resources\AIBudgets\Pages\EditAIBudget;
use App\Filament\Resources\AIBudgets\Pages\ListAIBudgets;
use App\Filament\Resources\AIBudgets\Schemas\AIBudgetForm;
use App\Filament\Resources\AIBudgets\Tables\AIBudgetsTable;
use App\Models\AIBudget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AIBudgetResource extends Resource
{
    protected static ?string $model = AIBudget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'AI Analytics';

    protected static ?string $navigationLabel = 'Budgets';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return AIBudgetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AIBudgetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAIBudgets::route('/'),
            'create' => CreateAIBudget::route('/create'),
            'edit' => EditAIBudget::route('/{record}/edit'),
        ];
    }
}
