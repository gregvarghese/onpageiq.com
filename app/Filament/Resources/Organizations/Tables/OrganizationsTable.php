<?php

namespace App\Filament\Resources\Organizations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subscription_tier')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enterprise' => 'danger',
                        'team' => 'warning',
                        'pro' => 'info',
                        'free' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('credit_balance')
                    ->label('Credits')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overdraft_balance')
                    ->label('Overdraft')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('subscription_ends_at')
                    ->label('Subscription Ends')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('subscription_tier')
                    ->label('Subscription Tier')
                    ->options([
                        'free' => 'Free',
                        'pro' => 'Pro',
                        'team' => 'Team',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
