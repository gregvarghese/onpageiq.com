<?php

namespace App\Livewire\Projects;

use App\Jobs\ScanUrlJob;
use App\Models\ArchitectureIssue;
use App\Models\DismissedIssue;
use App\Models\Issue;
use App\Models\IssueAssignment;
use App\Models\Project;
use App\Models\Scan;
use App\Models\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectDashboard extends Component
{
    public Project $project;

    public string $newUrl = '';

    public string $scanType = 'quick';

    /**
     * Active overview card filter: null, 'typos', 'issues', 'good'
     */
    public ?string $activeCardFilter = null;

    /**
     * Active findings table category filter.
     */
    public string $findingsFilter = 'all';

    /**
     * Selected issue IDs for bulk actions.
     *
     * @var array<int>
     */
    public array $selectedIssues = [];

    /**
     * Page filter for findings table (URL IDs).
     *
     * @var array<int>
     */
    public array $pageFilter = [];

    /**
     * Search term for page filter dropdown.
     */
    public string $pageSearch = '';

    /**
     * Track which sections are collapsed.
     *
     * @var array<string, bool>
     */
    public array $collapsedSections = [
        'dashboard' => false,
        'charts' => false,
        'urls' => false,
        'queue' => false,
        'issues' => false,
        'schedules' => false,
        'timeline' => false,
    ];

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    /**
     * Toggle a section's collapsed state.
     */
    public function toggleSection(string $section): void
    {
        if (isset($this->collapsedSections[$section])) {
            $this->collapsedSections[$section] = ! $this->collapsedSections[$section];
        }
    }

    /**
     * Set or toggle the active card filter.
     */
    public function setCardFilter(?string $filter): void
    {
        $this->activeCardFilter = $this->activeCardFilter === $filter ? null : $filter;
    }

    /**
     * Set the findings category filter.
     */
    public function setFindingsFilter(string $filter): void
    {
        $this->findingsFilter = $filter;
        $this->selectedIssues = [];
    }

    /**
     * Toggle selection of an issue.
     */
    public function toggleIssueSelection(int $issueId): void
    {
        if (in_array($issueId, $this->selectedIssues)) {
            $this->selectedIssues = array_values(array_diff($this->selectedIssues, [$issueId]));
        } else {
            $this->selectedIssues[] = $issueId;
        }
    }

    /**
     * Select all visible issues.
     */
    public function selectAllIssues(): void
    {
        $this->selectedIssues = $this->findings->pluck('id')->toArray();
    }

    /**
     * Deselect all issues.
     */
    public function deselectAllIssues(): void
    {
        $this->selectedIssues = [];
    }

    /**
     * Toggle page filter.
     */
    public function togglePageFilter(int $urlId): void
    {
        if (in_array($urlId, $this->pageFilter)) {
            $this->pageFilter = array_values(array_diff($this->pageFilter, [$urlId]));
        } else {
            $this->pageFilter[] = $urlId;
        }
    }

    /**
     * Clear page filter.
     */
    public function clearPageFilter(): void
    {
        $this->pageFilter = [];
        $this->pageSearch = '';
    }

    /**
     * Bulk mark selected issues as fixed.
     */
    public function bulkMarkAsFixed(): void
    {
        $this->authorize('update', $this->project);

        if (empty($this->selectedIssues)) {
            return;
        }

        $issues = Issue::whereIn('id', $this->selectedIssues)->get();

        foreach ($issues as $issue) {
            // Create or update assignment to mark as resolved
            IssueAssignment::updateOrCreate(
                ['issue_id' => $issue->id],
                [
                    'assigned_to_user_id' => Auth::id(),
                    'assigned_by_user_id' => Auth::id(),
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]
            );
        }

        $count = $issues->count();
        $this->selectedIssues = [];
        $this->dispatch('issues-updated');
        session()->flash('message', "{$count} issue(s) marked as fixed.");
    }

    /**
     * Bulk ignore/dismiss selected issues.
     */
    public function bulkIgnoreIssues(): void
    {
        $this->authorize('update', $this->project);

        if (empty($this->selectedIssues)) {
            return;
        }

        $issues = Issue::whereIn('id', $this->selectedIssues)->get();

        foreach ($issues as $issue) {
            DismissedIssue::firstOrCreate([
                'issue_id' => $issue->id,
                'project_id' => $this->project->id,
            ], [
                'dismissed_by_user_id' => Auth::id(),
                'reason' => 'bulk_dismiss',
                'scope' => 'project',
            ]);
        }

        $count = $issues->count();
        $this->selectedIssues = [];
        $this->dispatch('issues-updated');
        session()->flash('message', "{$count} issue(s) dismissed.");
    }

    /**
     * Bulk add selected issue words to dictionary.
     */
    public function bulkAddToDictionary(): void
    {
        $this->authorize('update', $this->project);

        if (empty($this->selectedIssues)) {
            return;
        }

        $issues = Issue::whereIn('id', $this->selectedIssues)
            ->whereIn('category', ['spelling', 'grammar'])
            ->get();

        $addedWords = [];
        $dictionary = $this->project->dictionary ?? [];

        foreach ($issues as $issue) {
            // Extract the word from the issue
            $word = $this->extractWordFromIssue($issue);

            if ($word && ! in_array(strtolower($word), array_map('strtolower', $dictionary))) {
                $dictionary[] = $word;
                $addedWords[] = $word;

                // Also dismiss the issue
                DismissedIssue::firstOrCreate([
                    'issue_id' => $issue->id,
                    'project_id' => $this->project->id,
                ], [
                    'dismissed_by_user_id' => Auth::id(),
                    'reason' => 'added_to_dictionary',
                    'scope' => 'project',
                ]);
            }
        }

        if (! empty($addedWords)) {
            $this->project->update(['dictionary' => $dictionary]);
        }

        $count = count($addedWords);
        $this->selectedIssues = [];
        $this->dispatch('issues-updated');
        $this->dispatch('dictionary-updated');
        session()->flash('message', "{$count} word(s) added to dictionary.");
    }

    /**
     * Extract the misspelled/flagged word from an issue.
     */
    protected function extractWordFromIssue(Issue $issue): ?string
    {
        // Try to get from metadata first
        if (isset($issue->metadata['word'])) {
            return $issue->metadata['word'];
        }

        // Try to extract from text_excerpt (usually the misspelled word)
        if ($issue->text_excerpt) {
            // If it's a single word, use it directly
            $excerpt = trim($issue->text_excerpt);
            if (! str_contains($excerpt, ' ') && strlen($excerpt) <= 50) {
                return $excerpt;
            }
        }

        // Try to extract from context or description
        if (preg_match('/["\']([^"\']+)["\']/', $issue->description ?? '', $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Bulk assign selected issues to a user.
     */
    public function bulkAssignTo(int $userId): void
    {
        $this->authorize('update', $this->project);

        if (empty($this->selectedIssues)) {
            return;
        }

        $issues = Issue::whereIn('id', $this->selectedIssues)->get();

        foreach ($issues as $issue) {
            IssueAssignment::updateOrCreate(
                ['issue_id' => $issue->id],
                [
                    'assigned_to_user_id' => $userId,
                    'assigned_by_user_id' => Auth::id(),
                    'status' => 'open',
                ]
            );
        }

        $count = $issues->count();
        $this->selectedIssues = [];
        $this->dispatch('issues-updated');
        session()->flash('message', "{$count} issue(s) assigned.");
    }

    /**
     * Mark a single issue as fixed.
     */
    public function markIssueAsFixed(int $issueId): void
    {
        $this->authorize('update', $this->project);

        $issue = Issue::findOrFail($issueId);

        IssueAssignment::updateOrCreate(
            ['issue_id' => $issue->id],
            [
                'assigned_to_user_id' => Auth::id(),
                'assigned_by_user_id' => Auth::id(),
                'status' => 'resolved',
                'resolved_at' => now(),
            ]
        );

        $this->dispatch('issues-updated');
        session()->flash('message', 'Issue marked as fixed.');
    }

    /**
     * Ignore/dismiss a single issue.
     */
    public function ignoreIssue(int $issueId): void
    {
        $this->authorize('update', $this->project);

        $issue = Issue::findOrFail($issueId);

        DismissedIssue::firstOrCreate([
            'issue_id' => $issue->id,
            'project_id' => $this->project->id,
        ], [
            'dismissed_by_user_id' => Auth::id(),
            'reason' => 'user_dismissed',
            'scope' => 'project',
        ]);

        $this->dispatch('issues-updated');
        session()->flash('message', 'Issue dismissed.');
    }

    /**
     * Add a single issue's word to dictionary.
     */
    public function addIssueToDictionary(int $issueId): void
    {
        $this->authorize('update', $this->project);

        $issue = Issue::findOrFail($issueId);

        if (! in_array($issue->category, ['spelling', 'grammar'])) {
            return;
        }

        $word = $this->extractWordFromIssue($issue);

        if (! $word) {
            session()->flash('error', 'Could not extract word from issue.');

            return;
        }

        $dictionary = $this->project->dictionary ?? [];

        if (! in_array(strtolower($word), array_map('strtolower', $dictionary))) {
            $dictionary[] = $word;
            $this->project->update(['dictionary' => $dictionary]);

            // Also dismiss the issue
            DismissedIssue::firstOrCreate([
                'issue_id' => $issue->id,
                'project_id' => $this->project->id,
            ], [
                'dismissed_by_user_id' => Auth::id(),
                'reason' => 'added_to_dictionary',
                'scope' => 'project',
            ]);

            $this->dispatch('dictionary-updated');
            session()->flash('message', "'{$word}' added to dictionary.");
        } else {
            session()->flash('message', "'{$word}' is already in the dictionary.");
        }

        $this->dispatch('issues-updated');
    }

    /**
     * Resolve an architecture issue.
     */
    public function resolveArchitectureIssue(string $issueId): void
    {
        $this->authorize('update', $this->project);

        $issue = ArchitectureIssue::findOrFail($issueId);

        // Verify the issue belongs to this project's architecture
        $architecture = $this->project->fresh()->latestSiteArchitecture;
        if (! $architecture || $issue->site_architecture_id !== $architecture->id) {
            session()->flash('error', 'Architecture issue not found.');

            return;
        }

        $issue->update(['is_resolved' => true]);

        $this->dispatch('issues-updated');
        session()->flash('message', 'Architecture issue resolved.');
    }

    /**
     * Ignore/dismiss an architecture issue (same as resolve for now).
     */
    public function ignoreArchitectureIssue(string $issueId): void
    {
        $this->authorize('update', $this->project);

        $issue = ArchitectureIssue::findOrFail($issueId);

        // Verify the issue belongs to this project's architecture
        $architecture = $this->project->fresh()->latestSiteArchitecture;
        if (! $architecture || $issue->site_architecture_id !== $architecture->id) {
            session()->flash('error', 'Architecture issue not found.');

            return;
        }

        $issue->update(['is_resolved' => true]);

        $this->dispatch('issues-updated');
        session()->flash('message', 'Architecture issue dismissed.');
    }

    /**
     * Add a new URL to the project.
     */
    public function addUrl(): void
    {
        $this->validate([
            'newUrl' => ['required', 'url', 'max:2048'],
        ]);

        $this->project->urls()->create([
            'url' => $this->newUrl,
            'status' => 'pending',
        ]);

        $this->newUrl = '';
        $this->dispatch('url-added');
    }

    /**
     * Scan a specific URL.
     */
    public function scanUrl(int $urlId): void
    {
        $url = Url::findOrFail($urlId);
        $this->authorize('update', $url->project);

        $url->markAsScanning();

        $scan = Scan::create([
            'url_id' => $url->id,
            'triggered_by_user_id' => Auth::id(),
            'scan_type' => $this->scanType,
            'status' => 'pending',
        ]);

        ScanUrlJob::dispatch($scan);

        $this->dispatch('scan-started', scanId: $scan->id);
    }

    /**
     * Scan selected URLs.
     *
     * @param  array<int>  $urlIds
     */
    public function scanSelected(array $urlIds): void
    {
        $this->authorize('update', $this->project);

        $urls = $this->project->urls()
            ->whereIn('id', $urlIds)
            ->where('status', '!=', 'scanning')
            ->get();

        foreach ($urls as $url) {
            $url->markAsScanning();

            $scan = Scan::create([
                'url_id' => $url->id,
                'triggered_by_user_id' => Auth::id(),
                'scan_type' => $this->scanType,
                'status' => 'pending',
            ]);

            ScanUrlJob::dispatch($scan);
        }

        $this->dispatch('scans-started', count: $urls->count());
    }

    #[On('set-scan-type')]
    public function setScanType(string $type): void
    {
        $this->scanType = $type;
    }

    #[On('trigger-scan-all')]
    public function scanAllUrls(): void
    {
        $this->authorize('update', $this->project);

        $urls = $this->project->urls()->where('status', '!=', 'scanning')->get();

        foreach ($urls as $url) {
            $url->markAsScanning();

            $scan = Scan::create([
                'url_id' => $url->id,
                'triggered_by_user_id' => Auth::id(),
                'scan_type' => $this->scanType,
                'status' => 'pending',
            ]);

            ScanUrlJob::dispatch($scan);
        }

        $this->dispatch('scans-started', count: $urls->count());
    }

    /**
     * Delete a URL.
     */
    public function deleteUrl(int $urlId): void
    {
        $url = Url::findOrFail($urlId);
        $this->authorize('update', $url->project);

        $url->delete();
        $this->dispatch('url-deleted');
    }

    #[On('echo-private:organizations.{project.organization_id},scan.completed')]
    public function handleScanCompleted(array $data): void
    {
        $this->project->refresh();
    }

    /**
     * Get dashboard statistics.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function stats(): array
    {
        $urls = $this->project->urls()->with(['latestScan.result.issues'])->get();
        $totalUrls = $urls->count();
        $completedUrls = $urls->where('status', 'completed')->count();
        $scanningUrls = $urls->whereIn('status', ['scanning', 'pending'])->count();

        $totalIssues = 0;
        $errorCount = 0;
        $warningCount = 0;
        $lastScanAt = null;

        foreach ($urls as $url) {
            if ($url->latestScan?->result) {
                $issues = $url->latestScan->result->issues;
                $totalIssues += $issues->count();
                $errorCount += $issues->where('severity', 'error')->count();
                $warningCount += $issues->where('severity', 'warning')->count();
            }

            if ($url->last_scanned_at && (! $lastScanAt || $url->last_scanned_at->gt($lastScanAt))) {
                $lastScanAt = $url->last_scanned_at;
            }
        }

        // Calculate score (100 - weighted issues percentage)
        $score = $totalUrls > 0
            ? max(0, 100 - (($errorCount * 5 + $warningCount * 2) / max(1, $totalUrls)))
            : 100;

        // Count pages with issues vs clean pages
        $pagesWithIssues = 0;
        $pagesLookGood = 0;

        foreach ($urls as $url) {
            if ($url->latestScan?->result) {
                if ($url->latestScan->result->issues->count() > 0) {
                    $pagesWithIssues++;
                } else {
                    $pagesLookGood++;
                }
            }
        }

        return [
            'score' => round($score),
            'totalUrls' => $totalUrls,
            'completedUrls' => $completedUrls,
            'scanningUrls' => $scanningUrls,
            'totalIssues' => $totalIssues,
            'errorCount' => $errorCount,
            'warningCount' => $warningCount,
            'lastScanAt' => $lastScanAt,
            'totalScans' => Scan::whereIn('url_id', $urls->pluck('id'))->count(),
            'pagesWithIssues' => $pagesWithIssues,
            'pagesLookGood' => $pagesLookGood,
        ];
    }

    /**
     * Get pending scans in queue.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Scan>
     */
    #[Computed]
    public function pendingScans(): \Illuminate\Database\Eloquent\Collection
    {
        return Scan::whereIn('url_id', $this->project->urls()->pluck('id'))
            ->whereIn('status', ['pending', 'processing'])
            ->with('url')
            ->latest()
            ->get();
    }

    /**
     * Get filtered findings/issues for the findings table.
     */
    #[Computed]
    public function findings(): \Illuminate\Support\Collection
    {
        $urlIds = $this->project->urls()->pluck('id');

        // If page filter is active, limit to those URLs
        if (! empty($this->pageFilter)) {
            $urlIds = collect($this->pageFilter);
        }

        // Get regular content issues
        $issues = \App\Models\Issue::query()
            ->whereHas('result.scan', function ($query) use ($urlIds) {
                $query->whereIn('url_id', $urlIds);
            })
            ->with(['result.scan.url'])
            ->latest()
            ->get();

        // Get architecture issues if not filtering to non-architecture categories
        $architectureIssues = collect();
        if ($this->findingsFilter === 'all' || $this->findingsFilter === 'architecture') {
            $architectureIssues = $this->getArchitectureIssues();
        }

        // Filter by category if not 'all'
        if ($this->findingsFilter !== 'all') {
            $categoryMap = [
                'content' => ['spelling', 'grammar', 'readability'],
                'accessibility' => ['accessibility'],
                'meta' => ['seo', 'meta'],
                'links' => ['links', 'broken-link'],
                'architecture' => ['architecture'],
            ];

            $categories = $categoryMap[$this->findingsFilter] ?? [$this->findingsFilter];

            if ($this->findingsFilter === 'architecture') {
                // Return only architecture issues
                return $architectureIssues;
            }

            $issues = $issues->filter(fn ($issue) => in_array($issue->category, $categories));
        }

        // Merge architecture issues with regular issues (use concat to avoid Eloquent's getKey requirement)
        return collect($issues)->concat($architectureIssues)->sortByDesc('created_at')->values();
    }

    /**
     * Get architecture issues for this project as a normalized collection.
     */
    protected function getArchitectureIssues(): \Illuminate\Support\Collection
    {
        $architecture = $this->project->latestSiteArchitecture;

        if (! $architecture) {
            return collect();
        }

        return $architecture->issues()
            ->unresolved()
            ->with('node')
            ->latest()
            ->get()
            ->map(function (ArchitectureIssue $issue) {
                // Create a synthetic object that matches Issue structure for display
                return (object) [
                    'id' => 'arch_'.$issue->id,
                    'architecture_issue_id' => $issue->id,
                    'category' => 'architecture',
                    'subcategory' => $issue->issue_type->category(),
                    'message' => $issue->message,
                    'suggestion' => $issue->recommendation,
                    'context' => $issue->node?->url,
                    'severity' => $issue->severity->value,
                    'created_at' => $issue->created_at,
                    'is_architecture_issue' => true,
                    'issue_type' => $issue->issue_type,
                    'node' => $issue->node,
                    'result' => null,
                ];
            });
    }

    /**
     * Get findings count by category for filter chips.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function findingsCounts(): array
    {
        $urlIds = $this->project->urls()->pluck('id');

        $issues = \App\Models\Issue::query()
            ->whereHas('result.scan', function ($query) use ($urlIds) {
                $query->whereIn('url_id', $urlIds);
            })
            ->get();

        // Get architecture issues count
        $architectureCount = 0;
        if ($architecture = $this->project->latestSiteArchitecture) {
            $architectureCount = $architecture->issues()->unresolved()->count();
        }

        return [
            'all' => $issues->count() + $architectureCount,
            'content' => $issues->whereIn('category', ['spelling', 'grammar', 'readability'])->count(),
            'accessibility' => $issues->where('category', 'accessibility')->count(),
            'meta' => $issues->whereIn('category', ['seo', 'meta'])->count(),
            'links' => $issues->whereIn('category', ['links', 'broken-link'])->count(),
            'architecture' => $architectureCount,
        ];
    }

    /**
     * Get URLs for the page filter dropdown.
     */
    #[Computed]
    public function filterableUrls(): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->project->urls();

        if ($this->pageSearch) {
            $query->where('url', 'like', '%'.$this->pageSearch.'%');
        }

        return $query->orderBy('url')->get();
    }

    public function render(): View
    {
        $urls = $this->project->urls()
            ->with(['latestScan.result.issues', 'group'])
            ->latest()
            ->get();

        return view('livewire.projects.project-dashboard', [
            'urls' => $urls,
            'urlGroups' => $this->project->urlGroups,
        ]);
    }
}
