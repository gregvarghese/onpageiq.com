<?php

namespace App\Filament\Resources\AIUsageLogs\Tables;

use App\Enums\AIUsageCategory;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AIUsageLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->badge()
                    ->sortable(),
                TextColumn::make('model')
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (?AIUsageCategory $state) => $state?->label() ?? '-'),
                TextColumn::make('total_tokens')
                    ->label('Tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cost')
                    ->money('USD', 6)
                    ->sortable(),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn (int $state) => number_format($state).'ms')
                    ->sortable(),
                IconColumn::make('success')
                    ->boolean(),
                IconColumn::make('budget_override')
                    ->label('Override')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)
                    ->trueColor('warning'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('provider')
                    ->options([
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic',
                    ]),
                SelectFilter::make('category')
                    ->options(AIUsageCategory::options()),
                SelectFilter::make('success')
                    ->options([
                        '1' => 'Successful',
                        '0' => 'Failed',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }
}
