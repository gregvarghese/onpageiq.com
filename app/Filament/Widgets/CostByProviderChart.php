<?php

namespace App\Filament\Widgets;

use App\Models\AIUsageDaily;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class CostByProviderChart extends ChartWidget
{
    protected ?string $heading = 'Cost by Provider (This Month)';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();

        $data = AIUsageDaily::getCostsByProvider($startDate, $endDate);

        $labels = [];
        $costs = [];
        $colors = [
            'openai' => '#10a37f',
            'anthropic' => '#d97706',
        ];
        $backgroundColors = [];

        foreach ($data as $item) {
            $labels[] = ucfirst($item->provider ?? 'Unknown');
            $costs[] = (float) $item->cost;
            $backgroundColors[] = $colors[$item->provider] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'data' => $costs,
                    'backgroundColor' => $backgroundColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
