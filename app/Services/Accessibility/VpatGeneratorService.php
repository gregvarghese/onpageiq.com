<?php

namespace App\Services\Accessibility;

use App\Enums\VpatConformanceLevel;
use App\Enums\WcagLevel;
use App\Models\VpatEvaluation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class VpatGeneratorService
{
    /**
     * WCAG 2.1 criteria organized by principle and level.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    protected array $wcagCriteria = [
        'Perceivable' => [
            '1.1.1' => ['name' => 'Non-text Content', 'level' => 'A'],
            '1.2.1' => ['name' => 'Audio-only and Video-only (Prerecorded)', 'level' => 'A'],
            '1.2.2' => ['name' => 'Captions (Prerecorded)', 'level' => 'A'],
            '1.2.3' => ['name' => 'Audio Description or Media Alternative (Prerecorded)', 'level' => 'A'],
            '1.2.4' => ['name' => 'Captions (Live)', 'level' => 'AA'],
            '1.2.5' => ['name' => 'Audio Description (Prerecorded)', 'level' => 'AA'],
            '1.3.1' => ['name' => 'Info and Relationships', 'level' => 'A'],
            '1.3.2' => ['name' => 'Meaningful Sequence', 'level' => 'A'],
            '1.3.3' => ['name' => 'Sensory Characteristics', 'level' => 'A'],
            '1.3.4' => ['name' => 'Orientation', 'level' => 'AA'],
            '1.3.5' => ['name' => 'Identify Input Purpose', 'level' => 'AA'],
            '1.4.1' => ['name' => 'Use of Color', 'level' => 'A'],
            '1.4.2' => ['name' => 'Audio Control', 'level' => 'A'],
            '1.4.3' => ['name' => 'Contrast (Minimum)', 'level' => 'AA'],
            '1.4.4' => ['name' => 'Resize Text', 'level' => 'AA'],
            '1.4.5' => ['name' => 'Images of Text', 'level' => 'AA'],
            '1.4.10' => ['name' => 'Reflow', 'level' => 'AA'],
            '1.4.11' => ['name' => 'Non-text Contrast', 'level' => 'AA'],
            '1.4.12' => ['name' => 'Text Spacing', 'level' => 'AA'],
            '1.4.13' => ['name' => 'Content on Hover or Focus', 'level' => 'AA'],
        ],
        'Operable' => [
            '2.1.1' => ['name' => 'Keyboard', 'level' => 'A'],
            '2.1.2' => ['name' => 'No Keyboard Trap', 'level' => 'A'],
            '2.1.4' => ['name' => 'Character Key Shortcuts', 'level' => 'A'],
            '2.2.1' => ['name' => 'Timing Adjustable', 'level' => 'A'],
            '2.2.2' => ['name' => 'Pause, Stop, Hide', 'level' => 'A'],
            '2.3.1' => ['name' => 'Three Flashes or Below Threshold', 'level' => 'A'],
            '2.4.1' => ['name' => 'Bypass Blocks', 'level' => 'A'],
            '2.4.2' => ['name' => 'Page Titled', 'level' => 'A'],
            '2.4.3' => ['name' => 'Focus Order', 'level' => 'A'],
            '2.4.4' => ['name' => 'Link Purpose (In Context)', 'level' => 'A'],
            '2.4.5' => ['name' => 'Multiple Ways', 'level' => 'AA'],
            '2.4.6' => ['name' => 'Headings and Labels', 'level' => 'AA'],
            '2.4.7' => ['name' => 'Focus Visible', 'level' => 'AA'],
            '2.5.1' => ['name' => 'Pointer Gestures', 'level' => 'A'],
            '2.5.2' => ['name' => 'Pointer Cancellation', 'level' => 'A'],
            '2.5.3' => ['name' => 'Label in Name', 'level' => 'A'],
            '2.5.4' => ['name' => 'Motion Actuation', 'level' => 'A'],
        ],
        'Understandable' => [
            '3.1.1' => ['name' => 'Language of Page', 'level' => 'A'],
            '3.1.2' => ['name' => 'Language of Parts', 'level' => 'AA'],
            '3.2.1' => ['name' => 'On Focus', 'level' => 'A'],
            '3.2.2' => ['name' => 'On Input', 'level' => 'A'],
            '3.2.3' => ['name' => 'Consistent Navigation', 'level' => 'AA'],
            '3.2.4' => ['name' => 'Consistent Identification', 'level' => 'AA'],
            '3.3.1' => ['name' => 'Error Identification', 'level' => 'A'],
            '3.3.2' => ['name' => 'Labels or Instructions', 'level' => 'A'],
            '3.3.3' => ['name' => 'Error Suggestion', 'level' => 'AA'],
            '3.3.4' => ['name' => 'Error Prevention (Legal, Financial, Data)', 'level' => 'AA'],
        ],
        'Robust' => [
            '4.1.1' => ['name' => 'Parsing', 'level' => 'A'],
            '4.1.2' => ['name' => 'Name, Role, Value', 'level' => 'A'],
            '4.1.3' => ['name' => 'Status Messages', 'level' => 'AA'],
        ],
    ];

    /**
     * Generate VPAT 2.4 PDF document.
     */
    public function generatePdf(VpatEvaluation $vpat): string
    {
        $data = $this->prepareVpatData($vpat);

        $pdf = Pdf::loadView('pdf.vpat', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = sprintf(
            'vpat-%s-%s.pdf',
            str($vpat->product_name)->slug(),
            $vpat->evaluation_date->format('Y-m-d')
        );

        $path = storage_path("app/vpat/{$filename}");

        // Ensure directory exists
        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        return $path;
    }

    /**
     * Generate VPAT HTML for preview.
     */
    public function generateHtml(VpatEvaluation $vpat): string
    {
        $data = $this->prepareVpatData($vpat);

        return view('pdf.vpat', $data)->render();
    }

    /**
     * Prepare data for VPAT template.
     *
     * @return array<string, mixed>
     */
    protected function prepareVpatData(VpatEvaluation $vpat): array
    {
        return [
            'vpat' => $vpat,
            'productInfo' => $this->getProductInfo($vpat),
            'wcagReport' => $this->prepareWcagReport($vpat),
            'summary' => $this->getConformanceSummary($vpat),
            'generatedAt' => now(),
        ];
    }

    /**
     * Get product information section.
     *
     * @return array<string, mixed>
     */
    protected function getProductInfo(VpatEvaluation $vpat): array
    {
        return [
            'name' => $vpat->product_name,
            'version' => $vpat->product_version,
            'description' => $vpat->product_description,
            'vendor' => $vpat->vendor_name,
            'contact' => $vpat->vendor_contact,
            'evaluationDate' => $vpat->evaluation_date->format('F j, Y'),
            'evaluationMethods' => $vpat->evaluation_methods,
            'vpatVersion' => $vpat->vpat_version,
        ];
    }

    /**
     * Prepare WCAG 2.1 report data.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function prepareWcagReport(VpatEvaluation $vpat): array
    {
        $report = [];

        foreach ($this->wcagCriteria as $principle => $criteria) {
            $report[$principle] = [];

            foreach ($criteria as $criterionId => $criterionInfo) {
                $evaluation = $vpat->getWcagEvaluation($criterionId);
                $level = $evaluation
                    ? VpatConformanceLevel::tryFrom($evaluation['level'])
                    : VpatConformanceLevel::NotEvaluated;

                $report[$principle][$criterionId] = [
                    'id' => $criterionId,
                    'name' => $criterionInfo['name'],
                    'wcagLevel' => $criterionInfo['level'],
                    'conformanceLevel' => $level,
                    'conformanceLabel' => $level->label(),
                    'conformanceColor' => $level->color(),
                    'remarks' => $evaluation['remarks'] ?? '',
                ];
            }
        }

        return $report;
    }

    /**
     * Get conformance summary statistics.
     *
     * @return array<string, mixed>
     */
    protected function getConformanceSummary(VpatEvaluation $vpat): array
    {
        $summary = $vpat->getWcagConformanceSummary();
        $total = array_sum($summary);

        $byLevel = [
            'A' => ['supports' => 0, 'partial' => 0, 'does_not' => 0, 'na' => 0, 'total' => 0],
            'AA' => ['supports' => 0, 'partial' => 0, 'does_not' => 0, 'na' => 0, 'total' => 0],
        ];

        foreach ($this->wcagCriteria as $criteria) {
            foreach ($criteria as $criterionId => $info) {
                $wcagLevel = $info['level'];
                $evaluation = $vpat->getWcagEvaluation($criterionId);
                $conformance = $evaluation
                    ? ($evaluation['level'] ?? VpatConformanceLevel::NotEvaluated->value)
                    : VpatConformanceLevel::NotEvaluated->value;

                $byLevel[$wcagLevel]['total']++;

                match ($conformance) {
                    VpatConformanceLevel::Supports->value => $byLevel[$wcagLevel]['supports']++,
                    VpatConformanceLevel::PartiallySupports->value => $byLevel[$wcagLevel]['partial']++,
                    VpatConformanceLevel::DoesNotSupport->value => $byLevel[$wcagLevel]['does_not']++,
                    VpatConformanceLevel::NotApplicable->value => $byLevel[$wcagLevel]['na']++,
                    default => null,
                };
            }
        }

        return [
            'total' => $total,
            'byConformance' => $summary,
            'byLevel' => $byLevel,
            'completionPercentage' => $vpat->getWcagCompletionPercentage(),
        ];
    }

    /**
     * Get all WCAG criteria.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public function getWcagCriteria(): array
    {
        return $this->wcagCriteria;
    }

    /**
     * Get criteria for a specific WCAG level.
     *
     * @return Collection<string, array<string, string>>
     */
    public function getCriteriaByLevel(WcagLevel $level): Collection
    {
        $levelValue = $level->value;
        $criteria = collect();

        foreach ($this->wcagCriteria as $principle => $principleCriteria) {
            foreach ($principleCriteria as $id => $info) {
                if ($info['level'] === $levelValue) {
                    $criteria[$id] = array_merge($info, ['principle' => $principle]);
                }
            }
        }

        return $criteria;
    }

    /**
     * Populate VPAT from audit checks.
     */
    public function populateFromAudit(VpatEvaluation $vpat): void
    {
        $audit = $vpat->accessibilityAudit;

        if (! $audit) {
            return;
        }

        $checks = $audit->checks()->get()->groupBy('criterion_id');

        foreach ($checks as $criterionId => $criterionChecks) {
            $passed = $criterionChecks->where('status', 'pass')->count();
            $failed = $criterionChecks->where('status', 'fail')->count();
            $total = $criterionChecks->count();

            // Determine conformance level based on check results
            $level = $this->determineConformanceFromChecks($passed, $failed, $total);

            // Generate remarks from check messages
            $remarks = $this->generateRemarksFromChecks($criterionChecks);

            $vpat->setWcagEvaluation($criterionId, $level, $remarks);
        }

        $vpat->save();
    }

    /**
     * Determine conformance level from check results.
     */
    protected function determineConformanceFromChecks(int $passed, int $failed, int $total): VpatConformanceLevel
    {
        if ($total === 0) {
            return VpatConformanceLevel::NotEvaluated;
        }

        $passRate = $passed / $total;

        if ($failed === 0) {
            return VpatConformanceLevel::Supports;
        }

        if ($passRate >= 0.5) {
            return VpatConformanceLevel::PartiallySupports;
        }

        return VpatConformanceLevel::DoesNotSupport;
    }

    /**
     * Generate remarks from audit checks.
     *
     * @param  Collection<int, \App\Models\AuditCheck>  $checks
     */
    protected function generateRemarksFromChecks(Collection $checks): string
    {
        $remarks = [];

        $failed = $checks->where('status', 'fail');
        if ($failed->isNotEmpty()) {
            $remarks[] = 'Issues found: '.implode('; ', $failed->pluck('message')->take(3)->toArray());
        }

        $passed = $checks->where('status', 'pass');
        if ($passed->isNotEmpty() && $failed->isEmpty()) {
            $remarks[] = 'All automated checks passed.';
        }

        $manual = $checks->where('status', 'manual_review');
        if ($manual->isNotEmpty()) {
            $remarks[] = sprintf('%d item(s) require manual review.', $manual->count());
        }

        return implode(' ', $remarks);
    }
}
