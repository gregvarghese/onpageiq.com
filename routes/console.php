<?php

use App\Jobs\ProcessScheduledScansJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| Define your scheduled commands here. Commands are scheduled using
| a fluent, expressive interface.
|
*/

// Process scheduled scans (runs every minute)
Schedule::job(new ProcessScheduledScansJob)->everyMinute();

// Clean up old scan history based on retention policies (runs daily at 2 AM)
Schedule::command('scans:cleanup')->dailyAt('02:00');

// Clean up orphaned screenshots (runs daily at 3 AM)
Schedule::command('screenshots:cleanup --days=7')->dailyAt('03:00');

// AI Usage Aggregation
Schedule::command('ai:aggregate-daily')->dailyAt('01:00');
Schedule::command('ai:aggregate-monthly')->monthlyOn(1, '02:00');
Schedule::command('ai:reset-budgets')->monthlyOn(1, '00:05');
