<?php

namespace App\Livewire\Accessibility;

use App\Models\AuditCheck;
use App\Models\AuditEvidence;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AuditCheckDetail extends Component
{
    public AuditCheck $check;

    public bool $showAddNoteModal = false;

    public string $noteContent = '';

    public function mount(AuditCheck $check): void
    {
        $this->check = $check;
    }

    /**
     * Add a note to this check.
     */
    public function addNote(): void
    {
        $this->validate([
            'noteContent' => ['required', 'string', 'max:2000'],
        ]);

        AuditEvidence::create([
            'audit_check_id' => $this->check->id,
            'type' => 'note',
            'notes' => $this->noteContent,
            'captured_by_user_id' => Auth::id(),
            'captured_at' => now(),
        ]);

        $this->noteContent = '';
        $this->showAddNoteModal = false;

        $this->dispatch('note-added');
    }

    /**
     * Delete an evidence item.
     */
    public function deleteEvidence(string $evidenceId): void
    {
        $evidence = AuditEvidence::findOrFail($evidenceId);

        // Only allow deletion of own notes
        if ($evidence->captured_by_user_id === Auth::id()) {
            $evidence->delete();
            $this->dispatch('evidence-deleted');
        }
    }

    /**
     * Get WCAG documentation URL for this criterion.
     */
    #[Computed]
    public function wcagDocUrl(): ?string
    {
        $criteria = config('wcag.criteria', []);
        $criterion = collect($criteria)->firstWhere('id', $this->check->criterion_id);

        return $criterion['documentation_url'] ?? null;
    }

    /**
     * Get related checks (same criterion, different elements).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AuditCheck>
     */
    #[Computed]
    public function relatedChecks(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->check->audit->checks()
            ->where('criterion_id', $this->check->criterion_id)
            ->where('id', '!=', $this->check->id)
            ->limit(5)
            ->get();
    }

    /**
     * Get evidence items for this check.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AuditEvidence>
     */
    #[Computed]
    public function evidence(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->check->evidence()->with('capturedBy')->latest()->get();
    }

    public function render(): View
    {
        return view('livewire.accessibility.audit-check-detail');
    }
}
