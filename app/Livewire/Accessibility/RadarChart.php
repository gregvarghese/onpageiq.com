<?php

namespace App\Livewire\Accessibility;

use App\Enums\AuditCategory;
use Illuminate\View\View;
use Livewire\Component;

class RadarChart extends Component
{
    /**
     * Scores by category (0-100 scale).
     *
     * @var array<string, float>
     */
    public array $scores = [];

    /**
     * Chart size in pixels.
     */
    public int $size = 300;

    /**
     * Whether to show category labels.
     */
    public bool $showLabels = true;

    /**
     * Whether to show score values.
     */
    public bool $showValues = true;

    public function mount(array $scores = [], int $size = 300, bool $showLabels = true, bool $showValues = true): void
    {
        $this->scores = $scores;
        $this->size = $size;
        $this->showLabels = $showLabels;
        $this->showValues = $showValues;
    }

    /**
     * Get normalized scores (0-1 scale) for SVG rendering.
     *
     * @return array<string, array{label: string, score: float, normalized: float, color: string}>
     */
    public function getChartData(): array
    {
        $data = [];

        foreach (AuditCategory::cases() as $category) {
            $score = $this->scores[$category->value] ?? 0;
            $data[$category->value] = [
                'label' => $category->label(),
                'score' => $score,
                'normalized' => $score / 100,
                'color' => $category->color(),
            ];
        }

        return $data;
    }

    /**
     * Calculate SVG polygon points for the radar chart.
     */
    public function getPolygonPoints(): string
    {
        $data = $this->getChartData();
        $categories = array_keys($data);
        $count = count($categories);

        if ($count === 0) {
            return '';
        }

        $centerX = $this->size / 2;
        $centerY = $this->size / 2;
        $radius = ($this->size / 2) - 40; // Leave margin for labels

        $points = [];
        foreach ($categories as $index => $category) {
            $angle = (M_PI * 2 * $index / $count) - (M_PI / 2); // Start from top
            $value = $data[$category]['normalized'];

            $x = $centerX + ($radius * $value * cos($angle));
            $y = $centerY + ($radius * $value * sin($angle));

            $points[] = round($x, 2).','.round($y, 2);
        }

        return implode(' ', $points);
    }

    /**
     * Get axis lines for the radar chart.
     *
     * @return array<int, array{x1: float, y1: float, x2: float, y2: float}>
     */
    public function getAxisLines(): array
    {
        $data = $this->getChartData();
        $categories = array_keys($data);
        $count = count($categories);

        if ($count === 0) {
            return [];
        }

        $centerX = $this->size / 2;
        $centerY = $this->size / 2;
        $radius = ($this->size / 2) - 40;

        $lines = [];
        foreach ($categories as $index => $category) {
            $angle = (M_PI * 2 * $index / $count) - (M_PI / 2);

            $lines[] = [
                'x1' => $centerX,
                'y1' => $centerY,
                'x2' => round($centerX + ($radius * cos($angle)), 2),
                'y2' => round($centerY + ($radius * sin($angle)), 2),
            ];
        }

        return $lines;
    }

    /**
     * Get label positions for the radar chart.
     *
     * @return array<string, array{x: float, y: float, label: string, score: float, anchor: string}>
     */
    public function getLabelPositions(): array
    {
        $data = $this->getChartData();
        $categories = array_keys($data);
        $count = count($categories);

        if ($count === 0) {
            return [];
        }

        $centerX = $this->size / 2;
        $centerY = $this->size / 2;
        $radius = ($this->size / 2) - 20; // Position labels outside the chart

        $positions = [];
        foreach ($categories as $index => $category) {
            $angle = (M_PI * 2 * $index / $count) - (M_PI / 2);

            $x = $centerX + ($radius * cos($angle));
            $y = $centerY + ($radius * sin($angle));

            // Determine text anchor based on position
            $anchor = 'middle';
            if ($x < $centerX - 10) {
                $anchor = 'end';
            } elseif ($x > $centerX + 10) {
                $anchor = 'start';
            }

            $positions[$category] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'label' => $data[$category]['label'],
                'score' => $data[$category]['score'],
                'anchor' => $anchor,
            ];
        }

        return $positions;
    }

    /**
     * Get concentric grid circles.
     *
     * @return array<int, float>
     */
    public function getGridCircles(): array
    {
        $radius = ($this->size / 2) - 40;

        return [
            $radius * 0.25,
            $radius * 0.5,
            $radius * 0.75,
            $radius,
        ];
    }

    public function render(): View
    {
        return view('livewire.accessibility.radar-chart', [
            'chartData' => $this->getChartData(),
            'polygonPoints' => $this->getPolygonPoints(),
            'axisLines' => $this->getAxisLines(),
            'labelPositions' => $this->getLabelPositions(),
            'gridCircles' => $this->getGridCircles(),
        ]);
    }
}
