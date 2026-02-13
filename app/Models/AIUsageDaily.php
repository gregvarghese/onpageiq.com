<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AIUsageDaily extends Model
{
    use HasFactory;

    protected $table = 'ai_usage_daily';

    protected $fillable = [
        'date',
        'organization_id',
        'user_id',
        'category',
        'provider',
        'model',
        'request_count',
        'success_count',
        'failure_count',
        'total_prompt_tokens',
        'total_completion_tokens',
        'total_tokens',
        'total_cost',
        'total_duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'request_count' => 'integer',
            'success_count' => 'integer',
            'failure_count' => 'integer',
            'total_prompt_tokens' => 'integer',
            'total_completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'total_cost' => 'decimal:6',
            'total_duration_ms' => 'integer',
        ];
    }

    /**
     * Get the organization for this aggregation.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user for this aggregation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Aggregate AI usage logs for a specific date.
     */
    public static function aggregateForDate(Carbon|string $date): int
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateString = $date->toDateString();

        // Delete existing aggregations for this date
        self::whereDate('date', $dateString)->delete();

        // Aggregate from ai_usage_logs
        $aggregations = AIUsageLog::query()
            ->whereDate('created_at', $dateString)
            ->select([
                DB::raw('DATE(created_at) as date'),
                'organization_id',
                'user_id',
                'category',
                'provider',
                'model',
                DB::raw('COUNT(*) as request_count'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failure_count'),
                DB::raw('SUM(prompt_tokens) as total_prompt_tokens'),
                DB::raw('SUM(completion_tokens) as total_completion_tokens'),
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('SUM(duration_ms) as total_duration_ms'),
            ])
            ->groupBy([
                DB::raw('DATE(created_at)'),
                'organization_id',
                'user_id',
                'category',
                'provider',
                'model',
            ])
            ->get();

        $count = 0;
        foreach ($aggregations as $agg) {
            self::create([
                'date' => $dateString,
                'organization_id' => $agg->organization_id,
                'user_id' => $agg->user_id,
                'category' => $agg->category,
                'provider' => $agg->provider,
                'model' => $agg->model,
                'request_count' => $agg->request_count,
                'success_count' => $agg->success_count,
                'failure_count' => $agg->failure_count,
                'total_prompt_tokens' => $agg->total_prompt_tokens ?? 0,
                'total_completion_tokens' => $agg->total_completion_tokens ?? 0,
                'total_tokens' => $agg->total_tokens ?? 0,
                'total_cost' => $agg->total_cost ?? 0,
                'total_duration_ms' => $agg->total_duration_ms ?? 0,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Get total cost for a date range.
     */
    public static function getTotalCostForRange(Carbon $startDate, Carbon $endDate, ?int $organizationId = null): float
    {
        $query = self::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return (float) $query->sum('total_cost');
    }

    /**
     * Get daily costs for charting.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getDailyCosts(int $days = 30, ?int $organizationId = null)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        $query = self::query()
            ->where('date', '>=', $startDate->toDateString())
            ->select([
                'date',
                DB::raw('SUM(total_cost) as cost'),
                DB::raw('SUM(request_count) as requests'),
            ])
            ->groupBy('date')
            ->orderBy('date');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get costs grouped by provider.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getCostsByProvider(Carbon $startDate, Carbon $endDate, ?int $organizationId = null)
    {
        $query = self::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select([
                'provider',
                DB::raw('SUM(total_cost) as cost'),
                DB::raw('SUM(request_count) as requests'),
            ])
            ->groupBy('provider')
            ->orderByDesc('cost');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get costs grouped by category.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getCostsByCategory(Carbon $startDate, Carbon $endDate, ?int $organizationId = null)
    {
        $query = self::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotNull('category')
            ->select([
                'category',
                DB::raw('SUM(total_cost) as cost'),
                DB::raw('SUM(request_count) as requests'),
            ])
            ->groupBy('category')
            ->orderByDesc('cost');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }
}
