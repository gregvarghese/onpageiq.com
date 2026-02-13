<?php

namespace App\Console\Commands;

use App\Services\AI\AIBudgetService;
use Illuminate\Console\Command;

class ResetMonthlyAIBudgets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:reset-budgets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset AI budgets for the new month';

    /**
     * Execute the console command.
     */
    public function handle(AIBudgetService $budgetService): int
    {
        $this->info('Resetting monthly AI budgets...');

        $count = $budgetService->resetMonthlyBudgets();

        $this->info("Completed! Reset {$count} budget(s)");

        return self::SUCCESS;
    }
}
