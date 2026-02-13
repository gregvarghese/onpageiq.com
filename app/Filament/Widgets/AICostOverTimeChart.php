<?php

namespace App\Filament\Widgets;

use App\Models\AIUsageDaily;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AICostOverTimeChart extends ChartWidget
{
    protected ?string $heading = 'AI Costs (Last 30 Days)';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = AIUsageDaily::getDailyCosts(30);

        // Fill in missing dates with zeros
        $dates = [];
        $costs = [];

        $startDate = Carbon::now()->subDays(29)->startOfDay();

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateString = $date->toDateString();

            $dates[] = $date->format('M j');

            $dayData = $data->firstWhere('date', $dateString);
            $costs[] = $dayData ? (float) $dayData->cost : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cost ($)',
                    'data' => $costs,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
