<?php

namespace App\Console\Commands;

use App\Models\AIUsageMonthly;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateAIUsageMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:aggregate-monthly
                            {--year= : The year to aggregate}
                            {--month= : The month to aggregate}
                            {--previous : Aggregate previous month (default behavior)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate AI usage daily records into monthly summaries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        [$year, $month] = $this->getYearAndMonth();

        $this->info("Aggregating AI usage for {$year}-{$month}...");

        $count = AIUsageMonthly::aggregateForMonth($year, $month);

        $this->info("Completed! Created {$count} aggregation record(s)");

        return self::SUCCESS;
    }

    /**
     * Get the year and month to aggregate.
     *
     * @return array{int, int}
     */
    protected function getYearAndMonth(): array
    {
        if ($this->option('year') && $this->option('month')) {
            return [
                (int) $this->option('year'),
                (int) $this->option('month'),
            ];
        }

        // Default to previous month
        $previousMonth = Carbon::now()->subMonth();

        return [
            $previousMonth->year,
            $previousMonth->month,
        ];
    }
}
