<?php

namespace App\Console\Commands;

use App\Models\AIUsageDaily;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateAIUsageDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:aggregate-daily
                            {--date= : The date to aggregate (defaults to yesterday)}
                            {--days= : Number of past days to aggregate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate AI usage logs into daily summaries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dates = $this->getDatesToAggregate();

        $this->info('Aggregating AI usage for '.count($dates).' date(s)...');

        $totalRecords = 0;

        foreach ($dates as $date) {
            $this->line("  Processing {$date->toDateString()}...");
            $count = AIUsageDaily::aggregateForDate($date);
            $totalRecords += $count;
            $this->line("    Created {$count} aggregation record(s)");
        }

        $this->info("Completed! Total records created: {$totalRecords}");

        return self::SUCCESS;
    }

    /**
     * Get the dates to aggregate.
     *
     * @return array<Carbon>
     */
    protected function getDatesToAggregate(): array
    {
        if ($this->option('days')) {
            $days = (int) $this->option('days');
            $dates = [];
            for ($i = 1; $i <= $days; $i++) {
                $dates[] = Carbon::now()->subDays($i)->startOfDay();
            }

            return $dates;
        }

        if ($this->option('date')) {
            return [Carbon::parse($this->option('date'))->startOfDay()];
        }

        // Default to yesterday
        return [Carbon::yesterday()->startOfDay()];
    }
}
