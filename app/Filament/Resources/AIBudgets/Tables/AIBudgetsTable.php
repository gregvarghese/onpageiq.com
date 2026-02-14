<?php

namespace App\Filament\Resources\AIBudgets\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AIBudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Target')
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('monthly_limit')
                    ->label('Monthly Limit')
                    ->money('USD')
                    ->placeholder('Unlimited')
                    ->sortable(),
                TextColumn::make('current_month_usage')
                    ->label('Current Usage')
                    ->money('USD', 2)
                    ->sortable(),
                TextColumn::make('usage_percentage')
                    ->label('Usage %')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1).'%' : '-')
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 100 => 'danger',
                        $state >= 80 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('warning_threshold')
                    ->label('Warning At')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('allow_override')
                    ->label('Override')
                    ->boolean(),
                TextColumn::make('current_period_start')
                    ->label('Period Start')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }
}
