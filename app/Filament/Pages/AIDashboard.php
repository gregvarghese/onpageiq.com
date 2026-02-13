<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AICostOverTimeChart;
use App\Filament\Widgets\AIUsageStatsOverview;
use App\Filament\Widgets\CostByCategoryChart;
use App\Filament\Widgets\CostByProviderChart;
use App\Filament\Widgets\TopOrganizationsTable;
use App\Filament\Widgets\TopUsersTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AIDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = 'AI Analytics';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.ai-dashboard';

    public function getTitle(): string
    {
        return 'AI Usage Dashboard';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AIUsageStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AICostOverTimeChart::class,
            CostByProviderChart::class,
            CostByCategoryChart::class,
            TopOrganizationsTable::class,
            TopUsersTable::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 2,
        ];
    }
}
