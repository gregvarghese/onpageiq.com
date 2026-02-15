<?php

namespace App\Livewire\Accessibility;

use App\Enums\VpatConformanceLevel;
use App\Enums\VpatStatus;
use App\Models\AccessibilityAudit;
use App\Models\VpatEvaluation;
use App\Services\Accessibility\VpatGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class VpatWorkflow extends Component
{
    public AccessibilityAudit $audit;

    public ?VpatEvaluation $vpat = null;

    public string $activePrinciple = 'Perceivable';

    public bool $showCreateModal = false;

    public bool $showExportModal = false;

    // Create form fields
    public string $productName = '';

    public string $productVersion = '';

    public string $productDescription = '';

    public string $vendorName = '';

    public string $vendorContact = '';

    /** @var array<string> */
    public array $reportTypes = ['wcag21'];

    // Evaluation form
    public string $currentCriterion = '';

    public string $conformanceLevel = '';

    public string $remarks = '';

    public function mount(AccessibilityAudit $audit): void
    {
        $this->audit = $audit;
        $this->vpat = $audit->vpatEvaluation;

        // Pre-fill form with project info if creating new
        if (! $this->vpat && $audit->project) {
            $this->productName = $audit->project->name;
            $this->vendorName = $audit->project->organization?->name ?? '';
        }
    }

    /**
     * Create a new VPAT evaluation.
     */
    public function createVpat(): void
    {
        $this->validate([
            'productName' => 'required|string|max:255',
            'productVersion' => 'nullable|string|max:50',
            'productDescription' => 'nullable|string|max:5000',
            'vendorName' => 'required|string|max:255',
            'vendorContact' => 'nullable|string|max:255',
            'reportTypes' => 'required|array|min:1',
        ]);

        $this->vpat = VpatEvaluation::create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => Auth::id(),
            'product_name' => $this->productName,
            'product_version' => $this->productVersion,
            'product_description' => $this->productDescription,
            'vendor_name' => $this->vendorName,
            'vendor_contact' => $this->vendorContact,
            'evaluation_date' => now(),
            'vpat_version' => '2.4',
            'report_types' => $this->reportTypes,
            'status' => VpatStatus::Draft,
        ]);

        $this->showCreateModal = false;

        $this->dispatch('vpat-created');
    }

    /**
     * Auto-populate VPAT from audit checks.
     */
    public function populateFromAudit(): void
    {
        if (! $this->vpat || ! $this->vpat->isEditable()) {
            return;
        }

        $service = app(VpatGeneratorService::class);
        $service->populateFromAudit($this->vpat);

        $this->vpat->refresh();

        $this->dispatch('vpat-populated');
    }

    /**
     * Open the evaluation modal for a criterion.
     */
    public function editCriterion(string $criterionId): void
    {
        if (! $this->vpat || ! $this->vpat->isEditable()) {
            return;
        }

        $this->currentCriterion = $criterionId;
        $evaluation = $this->vpat->getWcagEvaluation($criterionId);

        $this->conformanceLevel = $evaluation['level'] ?? VpatConformanceLevel::NotEvaluated->value;
        $this->remarks = $evaluation['remarks'] ?? '';
    }

    /**
     * Save evaluation for current criterion.
     */
    public function saveEvaluation(): void
    {
        if (! $this->vpat || ! $this->vpat->isEditable() || ! $this->currentCriterion) {
            return;
        }

        $this->validate([
            'conformanceLevel' => 'required|string',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $level = VpatConformanceLevel::from($this->conformanceLevel);
        $this->vpat->setWcagEvaluation($this->currentCriterion, $level, $this->remarks);
        $this->vpat->save();

        $this->currentCriterion = '';
        $this->conformanceLevel = '';
        $this->remarks = '';

        $this->dispatch('evaluation-saved');
    }

    /**
     * Cancel editing.
     */
    public function cancelEdit(): void
    {
        $this->currentCriterion = '';
        $this->conformanceLevel = '';
        $this->remarks = '';
    }

    /**
     * Submit VPAT for review.
     */
    public function submitForReview(): void
    {
        if (! $this->vpat || $this->vpat->status !== VpatStatus::Draft) {
            return;
        }

        $this->vpat->submitForReview();
        $this->vpat->refresh();

        $this->dispatch('vpat-submitted');
    }

    /**
     * Approve the VPAT.
     */
    public function approve(): void
    {
        if (! $this->vpat || $this->vpat->status !== VpatStatus::InReview) {
            return;
        }

        $this->vpat->approve(Auth::user());
        $this->vpat->refresh();

        $this->dispatch('vpat-approved');
    }

    /**
     * Publish the VPAT.
     */
    public function publish(): void
    {
        if (! $this->vpat || $this->vpat->status !== VpatStatus::Approved) {
            return;
        }

        $this->vpat->publish();
        $this->vpat->refresh();

        $this->dispatch('vpat-published');
    }

    /**
     * Export VPAT as PDF.
     */
    public function exportPdf(): mixed
    {
        if (! $this->vpat) {
            return null;
        }

        $service = app(VpatGeneratorService::class);

        try {
            $path = $service->generatePdf($this->vpat);

            return response()->download($path);
        } catch (\Exception $e) {
            $this->addError('export', 'Failed to generate PDF: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Get WCAG criteria organized by principle.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    #[Computed]
    public function wcagCriteria(): array
    {
        $service = app(VpatGeneratorService::class);

        return $service->getWcagCriteria();
    }

    /**
     * Get completion percentage.
     */
    #[Computed]
    public function completionPercentage(): float
    {
        return $this->vpat?->getWcagCompletionPercentage() ?? 0;
    }

    /**
     * Get conformance summary.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function conformanceSummary(): array
    {
        return $this->vpat?->getWcagConformanceSummary() ?? [];
    }

    /**
     * Get criteria with evaluations for the active principle.
     *
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function activeCriteria(): array
    {
        $criteria = $this->wcagCriteria[$this->activePrinciple] ?? [];
        $result = [];

        foreach ($criteria as $id => $info) {
            $evaluation = $this->vpat?->getWcagEvaluation($id);
            $level = $evaluation
                ? VpatConformanceLevel::tryFrom($evaluation['level'])
                : VpatConformanceLevel::NotEvaluated;

            $result[$id] = [
                'id' => $id,
                'name' => $info['name'],
                'wcagLevel' => $info['level'],
                'conformanceLevel' => $level,
                'remarks' => $evaluation['remarks'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Get principles list.
     *
     * @return array<string>
     */
    #[Computed]
    public function principles(): array
    {
        return array_keys($this->wcagCriteria);
    }

    public function render(): View
    {
        return view('livewire.accessibility.vpat-workflow', [
            'conformanceLevels' => VpatConformanceLevel::cases(),
        ]);
    }
}
