<?php

namespace App\Filament\Widgets;

use App\Enums\AIUsageCategory;
use App\Models\AIUsageDaily;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class CostByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Cost by Category (This Month)';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();

        $data = AIUsageDaily::getCostsByCategory($startDate, $endDate);

        $labels = [];
        $costs = [];
        $colors = [
            '#3b82f6', // blue
            '#10b981', // green
            '#f59e0b', // amber
            '#ef4444', // red
            '#8b5cf6', // purple
            '#ec4899', // pink
            '#06b6d4', // cyan
            '#84cc16', // lime
            '#6b7280', // gray
        ];

        foreach ($data as $index => $item) {
            $category = AIUsageCategory::tryFrom($item->category);
            $labels[] = $category?->label() ?? ucfirst($item->category ?? 'Unknown');
            $costs[] = (float) $item->cost;
        }

        return [
            'datasets' => [
                [
                    'data' => $costs,
                    'backgroundColor' => array_slice($colors, 0, count($costs)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
