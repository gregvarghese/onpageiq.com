<?php

namespace App\Jobs\Accessibility;

use App\Enums\AuditCategory;
use App\Enums\CheckStatus;
use App\Enums\ImpactLevel;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Services\Accessibility\PatternMatchingService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PatternAnalysisJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AccessibilityAudit $audit,
        public string $url
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PatternMatchingService $patternService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            // Fetch the HTML content
            $response = Http::timeout(30)->get($this->url);

            if (! $response->successful()) {
                Log::warning('PatternAnalysisJob: Failed to fetch URL', [
                    'audit_id' => $this->audit->id,
                    'url' => $this->url,
                    'status' => $response->status(),
                ]);

                return;
            }

            $html = $response->body();
            $organization = $this->audit->project->organization;

            // Run pattern analysis
            $result = $patternService->analyze($html, $organization);

            // Create audit checks for pattern deviations
            $this->createChecksFromDeviations($result['deviations']);

            // Store summary in audit metadata
            $this->updateAuditMetadata($result['summary']);

            Log::info('PatternAnalysisJob: Completed', [
                'audit_id' => $this->audit->id,
                'patterns_detected' => $result['summary']['total_patterns_detected'],
                'deviations_found' => $result['summary']['total_deviations'],
            ]);
        } catch (\Throwable $e) {
            Log::error('PatternAnalysisJob: Error', [
                'audit_id' => $this->audit->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create audit checks from pattern deviations.
     *
     * @param  array<int, array<string, mixed>>  $deviations
     */
    protected function createChecksFromDeviations(array $deviations): void
    {
        foreach ($deviations as $deviation) {
            foreach ($deviation['issues'] as $issue) {
                $criterionId = $this->mapIssueToCriterion($issue['type'], $deviation['wcag_criteria']);

                AuditCheck::create([
                    'accessibility_audit_id' => $this->audit->id,
                    'criterion_id' => $criterionId,
                    'status' => $this->mapIssueToStatus($issue['type']),
                    'wcag_level' => WcagLevel::A,
                    'category' => AuditCategory::General,
                    'impact' => $this->mapIssueToImpact($issue['type']),
                    'element_selector' => $deviation['element_selector'],
                    'element_html' => substr($deviation['element_html'], 0, 1000),
                    'message' => $this->formatIssueMessage($deviation['pattern'], $issue),
                    'suggestion' => $this->formatSuggestion($deviation['pattern'], $issue),
                    'documentation_url' => $deviation['documentation_url'],
                    'fingerprint' => $this->generateFingerprint($deviation, $issue),
                    'metadata' => [
                        'source' => 'pattern_analysis',
                        'pattern' => $deviation['pattern_slug'],
                        'issue_type' => $issue['type'],
                    ],
                ]);
            }
        }
    }

    /**
     * Map issue type to WCAG criterion.
     */
    protected function mapIssueToCriterion(string $issueType, ?string $wcagCriteria): string
    {
        // If pattern has specific WCAG criteria, use the first one
        if ($wcagCriteria) {
            $criteria = explode(',', $wcagCriteria);

            return trim($criteria[0]);
        }

        // Default mappings based on issue type
        return match ($issueType) {
            'missing_role' => '4.1.2',
            'missing_attribute' => '4.1.2',
            'missing_keyboard' => '2.1.1',
            'focus_management' => '2.4.3',
            default => '4.1.2',
        };
    }

    /**
     * Map issue type to check status.
     */
    protected function mapIssueToStatus(string $issueType): CheckStatus
    {
        return match ($issueType) {
            'missing_role', 'missing_attribute' => CheckStatus::Fail,
            'missing_keyboard' => CheckStatus::Warning,
            'focus_management' => CheckStatus::Warning,
            default => CheckStatus::Warning,
        };
    }

    /**
     * Map issue type to impact level.
     */
    protected function mapIssueToImpact(string $issueType): ImpactLevel
    {
        return match ($issueType) {
            'missing_role' => ImpactLevel::Serious,
            'missing_attribute' => ImpactLevel::Serious,
            'missing_keyboard' => ImpactLevel::Critical,
            'focus_management' => ImpactLevel::Moderate,
            default => ImpactLevel::Moderate,
        };
    }

    /**
     * Format the issue message.
     *
     * @param  array<string, mixed>  $issue
     */
    protected function formatIssueMessage(string $patternName, array $issue): string
    {
        $baseMessage = $issue['message'] ?? 'Pattern deviation detected';

        return sprintf(
            '%s pattern: %s',
            $patternName,
            $baseMessage
        );
    }

    /**
     * Format the suggestion for fixing the issue.
     *
     * @param  array<string, mixed>  $issue
     */
    protected function formatSuggestion(string $patternName, array $issue): string
    {
        $type = $issue['type'] ?? 'unknown';

        return match ($type) {
            'missing_role' => sprintf(
                'Add one of the required ARIA roles for the %s pattern: %s',
                $patternName,
                is_array($issue['expected']) ? implode(', ', $issue['expected']) : $issue['expected']
            ),
            'missing_attribute' => sprintf(
                'Add the required attribute "%s" to this element. See the WAI-ARIA Authoring Practices Guide for the %s pattern.',
                $issue['attribute'] ?? 'unknown',
                $patternName
            ),
            'missing_keyboard' => sprintf(
                'Implement keyboard support for "%s": %s. This is essential for keyboard-only users.',
                $issue['key'] ?? 'unknown key',
                $issue['description'] ?? 'action'
            ),
            'focus_management' => sprintf(
                'Implement proper focus management for the %s pattern. %s',
                $patternName,
                $issue['description'] ?? ''
            ),
            default => sprintf(
                'Review the WAI-ARIA Authoring Practices Guide for the %s pattern and ensure compliance.',
                $patternName
            ),
        };
    }

    /**
     * Generate a fingerprint for issue deduplication.
     *
     * @param  array<string, mixed>  $deviation
     * @param  array<string, mixed>  $issue
     */
    protected function generateFingerprint(array $deviation, array $issue): string
    {
        return md5(implode(':', [
            'pattern',
            $deviation['pattern_slug'],
            $issue['type'],
            $deviation['element_selector'],
            $issue['attribute'] ?? $issue['key'] ?? '',
        ]));
    }

    /**
     * Update audit metadata with pattern analysis summary.
     *
     * @param  array<string, mixed>  $summary
     */
    protected function updateAuditMetadata(array $summary): void
    {
        $metadata = $this->audit->metadata ?? [];
        $metadata['pattern_analysis'] = $summary;

        $this->audit->update(['metadata' => $metadata]);
    }
}
