<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->confirmed()
                            ->maxLength(255),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->requiredWith('password')
                            ->dehydrated(false)
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Organization & Role')
                    ->components([
                        Select::make('organization_id')
                            ->label('Organization')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->options(
                                collect(Role::cases())
                                    ->mapWithKeys(fn (Role $role) => [$role->value => $role->value])
                                    ->toArray()
                            )
                            ->preload()
                            ->required(),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified'),
                    ])
                    ->columns(3),
            ]);
    }
}
