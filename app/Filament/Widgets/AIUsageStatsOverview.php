<?php

namespace App\Filament\Widgets;

use App\Models\AIUsageDaily;
use App\Models\AIUsageLog;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AIUsageStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        // Today's stats (from raw logs)
        $todayCost = AIUsageLog::whereDate('created_at', $today)->sum('cost');
        $todayRequests = AIUsageLog::whereDate('created_at', $today)->count();

        // This week's stats (from daily aggregates + today)
        $weekCost = AIUsageDaily::where('date', '>=', $weekStart->toDateString())
            ->where('date', '<', $today->toDateString())
            ->sum('total_cost') + $todayCost;

        // This month's stats
        $monthCost = AIUsageDaily::where('date', '>=', $monthStart->toDateString())
            ->where('date', '<', $today->toDateString())
            ->sum('total_cost') + $todayCost;

        // All time
        $allTimeCost = AIUsageDaily::sum('total_cost') + $todayCost;
        $allTimeRequests = AIUsageDaily::sum('request_count') + $todayRequests;

        return [
            Stat::make('Today', '$'.number_format($todayCost, 2))
                ->description($todayRequests.' requests')
                ->color('primary'),
            Stat::make('This Week', '$'.number_format($weekCost, 2))
                ->description('Since '.$weekStart->format('M j'))
                ->color('info'),
            Stat::make('This Month', '$'.number_format($monthCost, 2))
                ->description('Since '.$monthStart->format('M j'))
                ->color('warning'),
            Stat::make('All Time', '$'.number_format($allTimeCost, 2))
                ->description(number_format($allTimeRequests).' total requests')
                ->color('success'),
        ];
    }
}
