<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization Details')
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Leave blank to auto-generate from name'),
                    ])
                    ->columns(2),

                Section::make('Subscription')
                    ->components([
                        Select::make('subscription_tier')
                            ->options([
                                'free' => 'Free',
                                'pro' => 'Pro',
                                'team' => 'Team',
                                'enterprise' => 'Enterprise',
                            ])
                            ->default('free')
                            ->required(),
                        DateTimePicker::make('subscription_ends_at')
                            ->label('Subscription Ends'),
                        TextInput::make('stripe_id')
                            ->label('Stripe Customer ID')
                            ->maxLength(255),
                        TextInput::make('stripe_subscription_id')
                            ->label('Stripe Subscription ID')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Credits')
                    ->components([
                        TextInput::make('credit_balance')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('overdraft_balance')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('free_credits_used')
                            ->label('Free credits already used')
                            ->default(false),
                    ])
                    ->columns(3),
            ]);
    }
}
