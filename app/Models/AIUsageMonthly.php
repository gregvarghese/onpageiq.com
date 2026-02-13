<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AIUsageMonthly extends Model
{
    use HasFactory;

    protected $table = 'ai_usage_monthly';

    protected $fillable = [
        'year',
        'month',
        'organization_id',
        'user_id',
        'category',
        'request_count',
        'success_count',
        'total_tokens',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'request_count' => 'integer',
            'success_count' => 'integer',
            'total_tokens' => 'integer',
            'total_cost' => 'decimal:6',
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
     * Aggregate from daily records for a specific month.
     */
    public static function aggregateForMonth(int $year, int $month): int
    {
        // Delete existing aggregations for this month
        self::where('year', $year)->where('month', $month)->delete();

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Aggregate from ai_usage_daily
        $aggregations = AIUsageDaily::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select([
                'organization_id',
                'user_id',
                'category',
                DB::raw('SUM(request_count) as request_count'),
                DB::raw('SUM(success_count) as success_count'),
                DB::raw('SUM(total_tokens) as total_tokens'),
                DB::raw('SUM(total_cost) as total_cost'),
            ])
            ->groupBy(['organization_id', 'user_id', 'category'])
            ->get();

        $count = 0;
        foreach ($aggregations as $agg) {
            self::create([
                'year' => $year,
                'month' => $month,
                'organization_id' => $agg->organization_id,
                'user_id' => $agg->user_id,
                'category' => $agg->category,
                'request_count' => $agg->request_count,
                'success_count' => $agg->success_count,
                'total_tokens' => $agg->total_tokens ?? 0,
                'total_cost' => $agg->total_cost ?? 0,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Get monthly costs for the past N months.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getMonthlyCosts(int $months = 12, ?int $organizationId = null)
    {
        $query = self::query()
            ->select([
                'year',
                'month',
                DB::raw('SUM(total_cost) as cost'),
                DB::raw('SUM(request_count) as requests'),
            ])
            ->groupBy(['year', 'month'])
            ->orderBy('year')
            ->orderBy('month')
            ->limit($months);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get top organizations by spend for a month.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getTopOrganizations(int $year, int $month, int $limit = 10)
    {
        return self::query()
            ->where('year', $year)
            ->where('month', $month)
            ->whereNotNull('organization_id')
            ->select([
                'organization_id',
                DB::raw('SUM(total_cost) as cost'),
                DB::raw('SUM(request_count) as requests'),
            ])
            ->groupBy('organization_id')
            ->orderByDesc('cost')
            ->limit($limit)
            ->with('organization:id,name')
            ->get();
    }

    /**
     * Get top users by spend for a month.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getTopUsers(int $year, int $month, int $limit = 10)
    {
        return self::query()
            ->where('year', $year)
            ->where('month', $month)
            ->whereNotNull('user_id')
            ->select([
                'user_id',
                DB::raw('SUM(total_cost) as cost'),
                DB::raw('SUM(request_count) as requests'),
            ])
            ->groupBy('user_id')
            ->orderByDesc('cost')
            ->limit($limit)
            ->with('user:id,name,email')
            ->get();
    }

    /**
     * Get the period string (e.g., "2026-02").
     */
    public function getPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
