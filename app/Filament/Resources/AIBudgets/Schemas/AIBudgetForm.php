<?php

namespace App\Filament\Resources\AIBudgets\Schemas;

use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AIBudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Budget Target')
                    ->schema([
                        Select::make('organization_id')
                            ->label('Organization')
                            ->options(Organization::pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('Leave empty for global budget'),
                        Select::make('user_id')
                            ->label('User')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('Leave empty for organization-level budget'),
                    ])
                    ->columns(2),

                Section::make('Budget Limits')
                    ->schema([
                        TextInput::make('monthly_limit')
                            ->label('Monthly Limit ($)')
                            ->numeric()
                            ->prefix('$')
                            ->nullable()
                            ->helperText('Leave empty for unlimited'),
                        TextInput::make('warning_threshold')
                            ->label('Warning Threshold (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(80)
                            ->minValue(0)
                            ->maxValue(100),
                    ])
                    ->columns(2),

                Section::make('Controls')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Toggle::make('allow_override')
                            ->label('Allow Override')
                            ->default(true)
                            ->helperText('Allow users to proceed when over budget with confirmation'),
                    ])
                    ->columns(2),
            ]);
    }
}
