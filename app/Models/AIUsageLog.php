<?php

namespace App\Models;

use App\Enums\AIUsageCategory;
use App\Services\AI\AIBudgetService;
use App\Services\AI\AIUsageRedactionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AIUsageLog extends Model
{
    use HasFactory;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'organization_id',
        'user_id',
        'project_id',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'duration_ms',
        'prompt_content',
        'response_content',
        'content_redacted',
        'redaction_summary',
        'category',
        'purpose_detail',
        'task_type',
        'loggable_type',
        'loggable_id',
        'success',
        'error_message',
        'budget_override',
        'budget_override_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'cost' => 'decimal:6',
            'duration_ms' => 'integer',
            'content_redacted' => 'boolean',
            'redaction_summary' => 'array',
            'category' => AIUsageCategory::class,
            'success' => 'boolean',
            'budget_override' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the organization that owns this log.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user that triggered this usage.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with this usage.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who approved the budget override.
     */
    public function budgetOverrideUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'budget_override_by');
    }

    /**
     * Get the loggable model (polymorphic).
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log AI usage with redaction and budget tracking.
     */
    public static function logUsage(
        string $provider,
        string $model,
        int $promptTokens,
        int $completionTokens,
        int $durationMs = 0,
        ?string $taskType = null,
        ?Model $loggable = null,
        bool $success = true,
        ?string $errorMessage = null,
        ?array $metadata = null,
        ?string $promptContent = null,
        ?string $responseContent = null,
        ?AIUsageCategory $category = null,
        ?string $purposeDetail = null,
        ?int $projectId = null,
        bool $budgetOverride = false,
        ?int $budgetOverrideBy = null,
    ): self {
        $user = auth()->user();
        $organization = $user?->organization;

        // Calculate cost based on provider/model pricing
        $cost = self::calculateCost($provider, $model, $promptTokens, $completionTokens);

        // Redact sensitive content
        $redactionService = app(AIUsageRedactionService::class);
        $promptResult = $redactionService->redact($promptContent);
        $responseResult = $redactionService->redact($responseContent);

        $contentRedacted = $promptResult->wasRedacted || $responseResult->wasRedacted;
        $redactionSummary = array_merge_recursive(
            $promptResult->redactionSummary,
            $responseResult->redactionSummary
        );

        // Create the log entry
        $log = self::create([
            'organization_id' => $organization?->id,
            'user_id' => $user?->id,
            'project_id' => $projectId,
            'provider' => $provider,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
            'cost' => $cost,
            'duration_ms' => $durationMs,
            'prompt_content' => $promptResult->content,
            'response_content' => $responseResult->content,
            'content_redacted' => $contentRedacted,
            'redaction_summary' => $contentRedacted ? $redactionSummary : null,
            'category' => $category,
            'purpose_detail' => $purposeDetail,
            'task_type' => $taskType,
            'loggable_type' => $loggable ? get_class($loggable) : null,
            'loggable_id' => $loggable?->getKey(),
            'success' => $success,
            'error_message' => $errorMessage,
            'budget_override' => $budgetOverride,
            'budget_override_by' => $budgetOverrideBy,
            'metadata' => $metadata,
        ]);

        // Update budget usage
        if ($success && $cost > 0) {
            app(AIBudgetService::class)->recordUsage($cost, $organization, $user);
        }

        return $log;
    }

    /**
     * Calculate cost based on provider and model pricing.
     */
    public static function calculateCost(string $provider, string $model, int $promptTokens, int $completionTokens): float
    {
        // OpenAI pricing per 1M tokens (as of 2024)
        $pricing = [
            'openai' => [
                'gpt-4o' => ['prompt' => 2.50, 'completion' => 10.00],
                'gpt-4o-mini' => ['prompt' => 0.15, 'completion' => 0.60],
                'gpt-4-turbo' => ['prompt' => 10.00, 'completion' => 30.00],
                'gpt-3.5-turbo' => ['prompt' => 0.50, 'completion' => 1.50],
            ],
            'anthropic' => [
                'claude-3-opus' => ['prompt' => 15.00, 'completion' => 75.00],
                'claude-3-sonnet' => ['prompt' => 3.00, 'completion' => 15.00],
                'claude-3-haiku' => ['prompt' => 0.25, 'completion' => 1.25],
            ],
        ];

        $modelPricing = $pricing[$provider][$model] ?? ['prompt' => 1.00, 'completion' => 2.00];

        $promptCost = ($promptTokens / 1_000_000) * $modelPricing['prompt'];
        $completionCost = ($completionTokens / 1_000_000) * $modelPricing['completion'];

        return $promptCost + $completionCost;
    }

    /**
     * Scope to filter by organization.
     */
    public function scopeForOrganization($query, Organization $organization)
    {
        return $query->where('organization_id', $organization->id);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeForCategory($query, AIUsageCategory $category)
    {
        return $query->where('category', $category->value);
    }

    /**
     * Scope to filter successful requests only.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to filter failed requests only.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }
}
