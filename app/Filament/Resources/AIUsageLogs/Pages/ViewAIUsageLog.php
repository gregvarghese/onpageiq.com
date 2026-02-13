<?php

namespace App\Filament\Resources\AIUsageLogs\Pages;

use App\Enums\AIUsageCategory;
use App\Filament\Resources\AIUsageLogs\AIUsageLogResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAIUsageLog extends ViewRecord
{
    protected static string $resource = AIUsageLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Details')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime(),
                        TextEntry::make('user.name')
                            ->label('User'),
                        TextEntry::make('organization.name')
                            ->label('Organization'),
                        TextEntry::make('provider')
                            ->badge(),
                        TextEntry::make('model'),
                        TextEntry::make('category')
                            ->badge()
                            ->formatStateUsing(fn (?AIUsageCategory $state) => $state?->label() ?? '-'),
                        TextEntry::make('purpose_detail')
                            ->label('Purpose'),
                    ])
                    ->columns(3),

                Section::make('Token Usage & Cost')
                    ->schema([
                        TextEntry::make('prompt_tokens')
                            ->numeric(),
                        TextEntry::make('completion_tokens')
                            ->numeric(),
                        TextEntry::make('total_tokens')
                            ->numeric(),
                        TextEntry::make('cost')
                            ->money('USD', 6),
                        TextEntry::make('duration_ms')
                            ->label('Duration')
                            ->formatStateUsing(fn (int $state) => number_format($state).'ms'),
                    ])
                    ->columns(5),

                Section::make('Status')
                    ->schema([
                        IconEntry::make('success')
                            ->boolean(),
                        TextEntry::make('error_message')
                            ->visible(fn ($record) => ! $record->success),
                        IconEntry::make('budget_override')
                            ->label('Budget Override')
                            ->boolean(),
                        TextEntry::make('budgetOverrideUser.name')
                            ->label('Approved By')
                            ->visible(fn ($record) => $record->budget_override),
                    ])
                    ->columns(4),

                Section::make('Prompt Content')
                    ->schema([
                        TextEntry::make('prompt_content')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Response Content')
                    ->schema([
                        TextEntry::make('response_content')
                            ->label('')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Redaction Info')
                    ->schema([
                        IconEntry::make('content_redacted')
                            ->boolean(),
                        TextEntry::make('redaction_summary')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '-'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->content_redacted),
            ]);
    }
}
