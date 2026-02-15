<?php

namespace App\Services\Accessibility;

use App\Enums\FixComplexity;
use App\Enums\ImpactLevel;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubIntegrationService
{
    protected ?string $token = null;

    protected ?string $owner = null;

    protected ?string $repo = null;

    /**
     * Configure the GitHub connection.
     */
    public function configure(string $token, string $owner, string $repo): self
    {
        $this->token = $token;
        $this->owner = $owner;
        $this->repo = $repo;

        return $this;
    }

    /**
     * Configure from project settings.
     */
    public function configureFromProject(Project $project): self
    {
        $settings = $project->settings ?? [];
        $github = $settings['github'] ?? [];

        $this->token = $github['token'] ?? null;
        $this->owner = $github['owner'] ?? null;
        $this->repo = $github['repo'] ?? null;

        return $this;
    }

    /**
     * Check if GitHub is configured.
     */
    public function isConfigured(): bool
    {
        return $this->token && $this->owner && $this->repo;
    }

    /**
     * Create a GitHub issue for an audit check.
     *
     * @return array<string, mixed>|null
     */
    public function createIssue(AuditCheck $check): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning('GitHub integration not configured');

            return null;
        }

        $title = $this->generateIssueTitle($check);
        $body = $this->generateIssueBody($check);
        $labels = $this->generateLabels($check);

        try {
            $response = Http::withToken($this->token)
                ->post("https://api.github.com/repos/{$this->owner}/{$this->repo}/issues", [
                    'title' => $title,
                    'body' => $body,
                    'labels' => $labels,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Store the GitHub issue URL in check metadata
                $check->update([
                    'metadata' => array_merge($check->metadata ?? [], [
                        'github_issue_url' => $data['html_url'],
                        'github_issue_number' => $data['number'],
                    ]),
                ]);

                return $data;
            }

            Log::error('Failed to create GitHub issue', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('GitHub API error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Create multiple GitHub issues for an audit.
     *
     * @return array<int, array<string, mixed>>
     */
    public function createIssuesForAudit(AccessibilityAudit $audit, ?ImpactLevel $minImpact = null): array
    {
        $query = $audit->checks()->where('status', 'fail');

        if ($minImpact) {
            $impactLevels = $this->getImpactLevelsAtOrAbove($minImpact);
            $query->whereIn('impact', $impactLevels);
        }

        $checks = $query->get();
        $created = [];

        foreach ($checks as $check) {
            // Skip if already has GitHub issue
            if (! empty($check->metadata['github_issue_url'])) {
                continue;
            }

            $result = $this->createIssue($check);
            if ($result) {
                $created[] = $result;
            }

            // Rate limiting - GitHub allows 30 requests per minute for issues
            usleep(100000); // 100ms delay
        }

        return $created;
    }

    /**
     * Generate issue title.
     */
    protected function generateIssueTitle(AuditCheck $check): string
    {
        $impact = $check->impact?->value ?? 'unknown';

        return sprintf(
            '[A11y] [%s] %s: %s',
            strtoupper($impact),
            $check->criterion_id,
            $this->truncate($check->message, 60)
        );
    }

    /**
     * Generate issue body in markdown.
     */
    protected function generateIssueBody(AuditCheck $check): string
    {
        $complexity = FixComplexity::fromCriterion($check->criterion_id);

        $body = "## Accessibility Issue\n\n";
        $body .= "**WCAG Criterion:** {$check->criterion_id} - {$check->criterion_name}\n";
        $body .= "**Level:** {$check->wcag_level?->value}\n";
        $body .= "**Impact:** {$check->impact?->label()}\n";
        $body .= "**Fix Complexity:** {$complexity->label()}\n";
        $body .= "**Estimated Effort:** {$complexity->effortMinutes()} minutes\n\n";

        $body .= "### Issue Description\n\n";
        $body .= $check->message."\n\n";

        if ($check->element_selector) {
            $body .= "### Element\n\n";
            $body .= "**Selector:** `{$check->element_selector}`\n\n";
        }

        if ($check->element_html) {
            $body .= "### Current HTML\n\n";
            $body .= "```html\n{$check->getTruncatedHtml(500)}\n```\n\n";
        }

        if ($check->suggestion) {
            $body .= "### Suggested Fix\n\n";
            $body .= $check->suggestion."\n\n";
        }

        if ($check->code_snippet) {
            $body .= "### Example Fix\n\n";
            $body .= "```html\n{$check->code_snippet}\n```\n\n";
        }

        $body .= "### Resources\n\n";
        $body .= "- [WCAG {$check->criterion_id} Documentation]({$check->getWcagUrl()})\n";

        if ($check->documentation_url && $check->documentation_url !== $check->getWcagUrl()) {
            $body .= "- [Additional Documentation]({$check->documentation_url})\n";
        }

        $body .= "\n---\n";
        $body .= "_Generated by OnPageIQ Accessibility Audit_\n";

        return $body;
    }

    /**
     * Generate labels for the issue.
     *
     * @return array<string>
     */
    protected function generateLabels(AuditCheck $check): array
    {
        $labels = ['accessibility', 'a11y'];

        // Impact label
        if ($check->impact) {
            $labels[] = "impact:{$check->impact->value}";
        }

        // WCAG level
        if ($check->wcag_level) {
            $labels[] = "wcag:{$check->wcag_level->value}";
        }

        // Complexity
        $complexity = FixComplexity::fromCriterion($check->criterion_id);
        $labels[] = "effort:{$complexity->value}";

        // Category
        if ($check->category) {
            $labels[] = "category:{$check->category->value}";
        }

        return $labels;
    }

    /**
     * Get impact levels at or above the specified level.
     *
     * @return array<string>
     */
    protected function getImpactLevelsAtOrAbove(ImpactLevel $minLevel): array
    {
        $order = [
            ImpactLevel::Critical->value => 4,
            ImpactLevel::Serious->value => 3,
            ImpactLevel::Moderate->value => 2,
            ImpactLevel::Minor->value => 1,
        ];

        $minOrder = $order[$minLevel->value] ?? 0;

        return collect($order)
            ->filter(fn ($o) => $o >= $minOrder)
            ->keys()
            ->toArray();
    }

    /**
     * Truncate string.
     */
    protected function truncate(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        return substr($string, 0, $length - 3).'...';
    }

    /**
     * Sync issue status from GitHub.
     */
    public function syncIssueStatus(AuditCheck $check): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $issueNumber = $check->metadata['github_issue_number'] ?? null;
        if (! $issueNumber) {
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->get("https://api.github.com/repos/{$this->owner}/{$this->repo}/issues/{$issueNumber}");

            if ($response->successful()) {
                $data = $response->json();

                return $data['state']; // 'open' or 'closed'
            }
        } catch (\Exception $e) {
            Log::error('GitHub sync error', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
