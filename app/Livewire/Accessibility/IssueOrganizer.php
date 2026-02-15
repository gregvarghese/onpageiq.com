<?php

namespace App\Livewire\Accessibility;

use App\Models\AccessibilityAudit;
use App\Services\Accessibility\AccessibilityExportService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class IssueOrganizer extends Component
{
    public AccessibilityAudit $audit;

    public string $activeView = 'by_wcag';

    public string $expandedGroup = '';

    public string $searchQuery = '';

    /**
     * Available view options.
     *
     * @var array<string, array<string, string>>
     */
    protected array $viewOptions = [
        'by_wcag' => [
            'label' => 'By WCAG Criterion',
            'icon' => 'document-check',
            'description' => 'Group issues by WCAG success criterion',
        ],
        'by_impact' => [
            'label' => 'By Impact',
            'icon' => 'exclamation-triangle',
            'description' => 'Group issues by severity of impact on users',
        ],
        'by_category' => [
            'label' => 'By Category',
            'icon' => 'squares-2x2',
            'description' => 'Group issues by accessibility category',
        ],
        'by_complexity' => [
            'label' => 'By Complexity',
            'icon' => 'wrench-screwdriver',
            'description' => 'Group issues by fix complexity',
        ],
        'by_element' => [
            'label' => 'By Element',
            'icon' => 'code-bracket',
            'description' => 'Group issues by page element',
        ],
    ];

    public function mount(AccessibilityAudit $audit): void
    {
        $this->audit = $audit;
    }

    /**
     * Set the active view.
     */
    public function setView(string $view): void
    {
        if (array_key_exists($view, $this->viewOptions)) {
            $this->activeView = $view;
            $this->expandedGroup = '';
        }
    }

    /**
     * Toggle a group's expanded state.
     */
    public function toggleGroup(string $groupKey): void
    {
        $this->expandedGroup = $this->expandedGroup === $groupKey ? '' : $groupKey;
    }

    /**
     * Get organized issues.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function organizedIssues(): array
    {
        $service = app(AccessibilityExportService::class);

        return $service->organizeIssues($this->audit);
    }

    /**
     * Get issues for the active view.
     *
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function activeIssues(): array
    {
        $issues = $this->organizedIssues[$this->activeView] ?? [];

        // Filter by search query if present
        if ($this->searchQuery) {
            $query = strtolower($this->searchQuery);

            return array_filter($issues, function ($group) use ($query) {
                // Search in group key/name
                $groupKey = $group['criterion_id']
                    ?? $group['impact']
                    ?? $group['category']
                    ?? $group['complexity']
                    ?? $group['selector']
                    ?? '';

                if (str_contains(strtolower($groupKey), $query)) {
                    return true;
                }

                // Search in group name
                $groupName = $group['criterion_name'] ?? $group['complexity_label'] ?? '';
                if (str_contains(strtolower($groupName), $query)) {
                    return true;
                }

                // Search in check messages
                foreach ($group['checks'] ?? [] as $check) {
                    if (str_contains(strtolower($check['message'] ?? ''), $query)) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $issues;
    }

    /**
     * Get total issue count.
     */
    #[Computed]
    public function totalIssues(): int
    {
        $issues = $this->organizedIssues[$this->activeView] ?? [];

        return array_sum(array_column($issues, 'count'));
    }

    /**
     * Get group count for active view.
     */
    #[Computed]
    public function groupCount(): int
    {
        return count($this->activeIssues);
    }

    /**
     * Get view options for display.
     *
     * @return array<string, array<string, string>>
     */
    #[Computed]
    public function viewOptions(): array
    {
        return $this->viewOptions;
    }

    /**
     * Get impact color.
     */
    public function getImpactColor(string $impact): string
    {
        return match ($impact) {
            'critical' => 'red',
            'serious' => 'orange',
            'moderate' => 'yellow',
            'minor' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get category color.
     */
    public function getCategoryColor(string $category): string
    {
        return match ($category) {
            'vision' => 'purple',
            'motor' => 'blue',
            'cognitive' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get complexity color.
     */
    public function getComplexityColor(string $complexity): string
    {
        return match ($complexity) {
            'quick' => 'green',
            'easy' => 'blue',
            'medium' => 'yellow',
            'complex' => 'orange',
            'architectural' => 'red',
            default => 'gray',
        };
    }

    public function render(): View
    {
        return view('livewire.accessibility.issue-organizer');
    }
}
