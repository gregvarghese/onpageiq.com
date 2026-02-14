<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\ScanScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'url_group_id',
        'frequency',
        'scan_type',
        'preferred_time',
        'day_of_week',
        'day_of_month',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_time' => 'datetime:H:i',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    /**
     * Get the project this schedule belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the URL group this schedule targets (if any).
     */
    public function urlGroup(): BelongsTo
    {
        return $this->belongsTo(UrlGroup::class);
    }

    /**
     * Scope to find active schedules.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find schedules due to run.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            });
    }

    /**
     * Calculate and set the next run time based on frequency.
     */
    public function calculateNextRunAt(): Carbon
    {
        $baseTime = $this->preferred_time
            ? Carbon::parse($this->preferred_time)
            : now();

        $nextRun = match ($this->frequency) {
            'hourly' => now()->addHour()->setMinute($baseTime->minute)->setSecond(0),
            'daily' => now()->addDay()->setTime($baseTime->hour, $baseTime->minute, 0),
            'weekly' => $this->calculateWeeklyNextRun($baseTime),
            'monthly' => $this->calculateMonthlyNextRun($baseTime),
            default => now()->addDay(),
        };

        return $nextRun;
    }

    /**
     * Calculate next run for weekly schedule.
     */
    protected function calculateWeeklyNextRun(Carbon $baseTime): Carbon
    {
        $dayOfWeek = $this->day_of_week ?? 1; // Default to Monday
        $nextRun = now()->next($dayOfWeek)->setTime($baseTime->hour, $baseTime->minute, 0);

        if ($nextRun->isPast()) {
            $nextRun->addWeek();
        }

        return $nextRun;
    }

    /**
     * Calculate next run for monthly schedule.
     */
    protected function calculateMonthlyNextRun(Carbon $baseTime): Carbon
    {
        $dayOfMonth = $this->day_of_month ?? 1;
        $nextRun = now()->setDay(min($dayOfMonth, now()->daysInMonth))
            ->setTime($baseTime->hour, $baseTime->minute, 0);

        if ($nextRun->isPast()) {
            $nextRun->addMonth();
            $nextRun->setDay(min($dayOfMonth, $nextRun->daysInMonth));
        }

        return $nextRun;
    }

    /**
     * Mark the schedule as having just run.
     */
    public function markAsRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRunAt(),
        ]);
    }

    /**
     * Get a human-readable description of the schedule.
     */
    public function getDescription(): string
    {
        $time = $this->preferred_time
            ? Carbon::parse($this->preferred_time)->format('g:i A')
            : 'any time';

        return match ($this->frequency) {
            'hourly' => 'Every hour',
            'daily' => "Daily at {$time}",
            'weekly' => 'Weekly on '.Carbon::create()->next($this->day_of_week ?? 1)->format('l')." at {$time}",
            'monthly' => "Monthly on day {$this->day_of_month} at {$time}",
            default => 'Unknown schedule',
        };
    }

    /**
     * Get the frequency label for display.
     */
    public function getFrequencyLabel(): string
    {
        return match ($this->frequency) {
            'hourly' => 'Hourly',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => ucfirst($this->frequency),
        };
    }
}
