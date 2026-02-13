<?php

namespace App\Filament\Resources\AIUsageLogs;

use App\Filament\Resources\AIUsageLogs\Pages\ListAIUsageLogs;
use App\Filament\Resources\AIUsageLogs\Pages\ViewAIUsageLog;
use App\Filament\Resources\AIUsageLogs\Tables\AIUsageLogsTable;
use App\Models\AIUsageLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AIUsageLogResource extends Resource
{
    protected static ?string $model = AIUsageLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static UnitEnum|string|null $navigationGroup = 'AI Analytics';

    protected static ?string $navigationLabel = 'Usage Logs';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return AIUsageLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAIUsageLogs::route('/'),
            'view' => ViewAIUsageLog::route('/{record}'),
        ];
    }
}
