<?php

namespace App\Livewire\Projects\Components;

use App\Models\Project;
use App\Models\Scan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TrendCharts extends Component
{
    public Project $project;

    public string $dateRange = '30';

    public string $scope = 'project';

    public ?int $selectedUrlId = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Set the date range filter.
     */
    public function setDateRange(string $range): void
    {
        $this->dateRange = $range;
    }

    /**
     * Set the scope (project or URL).
     */
    public function setScope(string $scope, ?int $urlId = null): void
    {
        $this->scope = $scope;
        $this->selectedUrlId = $urlId;
    }

    /**
     * Get the start date based on selected range.
     */
    protected function getStartDate(): Carbon
    {
        return match ($this->dateRange) {
            '7' => now()->subDays(7),
            '14' => now()->subDays(14),
            '30' => now()->subDays(30),
            '90' => now()->subDays(90),
            'all' => now()->subYears(5),
            default => now()->subDays(30),
        };
    }

    /**
     * Get chart data for scores over time.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function scoreChartData(): array
    {
        $startDate = $this->getStartDate();

        $query = Scan::query()
            ->select([
                'scans.completed_at',
                'scan_results.scores',
            ])
            ->join('scan_results', 'scans.id', '=', 'scan_results.scan_id')
            ->whereIn('scans.url_id', $this->project->urls()->pluck('id'))
            ->where('scans.status', 'completed')
            ->where('scans.completed_at', '>=', $startDate)
            ->whereNotNull('scans.completed_at');

        if ($this->scope === 'url' && $this->selectedUrlId) {
            $query->where('scans.url_id', $this->selectedUrlId);
        }

        $scans = $query->orderBy('scans.completed_at')->get();

        // Group by date and calculate averages
        $groupedByDate = $scans->groupBy(fn ($scan) => Carbon::parse($scan->completed_at)->format('Y-m-d'));

        $labels = [];
        $scores = [];

        foreach ($groupedByDate as $date => $dateScans) {
            $labels[] = Carbon::parse($date)->format('M j');

            $avgScore = $dateScans->avg(function ($scan) {
                $scoresData = is_string($scan->scores) ? json_decode($scan->scores, true) : $scan->scores;

                return $scoresData['overall'] ?? 100;
            });

            $scores[] = round($avgScore);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'name' => 'Score',
                    'data' => $scores,
                ],
            ],
        ];
    }

    /**
     * Get chart data for issues over time.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function issueChartData(): array
    {
        $startDate = $this->getStartDate();

        $query = Scan::query()
            ->select([
                DB::raw('DATE(scans.completed_at) as date'),
                DB::raw('SUM(CASE WHEN issues.severity = "error" THEN 1 ELSE 0 END) as error_count'),
                DB::raw('SUM(CASE WHEN issues.severity = "warning" THEN 1 ELSE 0 END) as warning_count'),
                DB::raw('SUM(CASE WHEN issues.severity = "suggestion" THEN 1 ELSE 0 END) as suggestion_count'),
            ])
            ->join('scan_results', 'scans.id', '=', 'scan_results.scan_id')
            ->leftJoin('issues', 'scan_results.id', '=', 'issues.scan_result_id')
            ->whereIn('scans.url_id', $this->project->urls()->pluck('id'))
            ->where('scans.status', 'completed')
            ->where('scans.completed_at', '>=', $startDate)
            ->whereNotNull('scans.completed_at');

        if ($this->scope === 'url' && $this->selectedUrlId) {
            $query->where('scans.url_id', $this->selectedUrlId);
        }

        $data = $query
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $errors = [];
        $warnings = [];
        $suggestions = [];

        foreach ($data as $row) {
            $labels[] = Carbon::parse($row->date)->format('M j');
            $errors[] = (int) $row->error_count;
            $warnings[] = (int) $row->warning_count;
            $suggestions[] = (int) $row->suggestion_count;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'name' => 'Errors',
                    'data' => $errors,
                ],
                [
                    'name' => 'Warnings',
                    'data' => $warnings,
                ],
                [
                    'name' => 'Suggestions',
                    'data' => $suggestions,
                ],
            ],
        ];
    }

    /**
     * Get URLs for the scope selector.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Url>
     */
    #[Computed]
    public function urls(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->project->urls()->orderBy('url')->get();
    }

    public function render(): View
    {
        return view('livewire.projects.components.trend-charts');
    }
}
