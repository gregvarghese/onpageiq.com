<?php

namespace App\Filament\Widgets;

use App\Models\AIUsageMonthly;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopUsersTable extends BaseWidget
{
    protected static ?string $heading = 'Top Users (This Month)';

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $now = Carbon::now();

        return $table
            ->query(
                AIUsageMonthly::query()
                    ->where('year', $now->year)
                    ->where('month', $now->month)
                    ->whereNotNull('user_id')
                    ->selectRaw('user_id, SUM(total_cost) as total_cost, SUM(request_count) as request_count')
                    ->groupBy('user_id')
                    ->orderByDesc('total_cost')
                    ->limit(10)
                    ->with('user:id,name,email')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('User'),
                TextColumn::make('total_cost')
                    ->label('Cost')
                    ->money('USD', 2),
                TextColumn::make('request_count')
                    ->label('Requests')
                    ->numeric(),
            ])
            ->paginated(false);
    }
}
