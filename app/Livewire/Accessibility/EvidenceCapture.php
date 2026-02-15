<?php

namespace App\Livewire\Accessibility;

use App\Enums\EvidenceType;
use App\Models\AuditCheck;
use App\Models\AuditEvidence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class EvidenceCapture extends Component
{
    use WithFileUploads;

    public AuditCheck $check;

    public bool $showAddModal = false;

    public string $evidenceType = 'note';

    #[Validate('nullable|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:5000')]
    public string $notes = '';

    #[Validate('nullable|url|max:2000')]
    public string $externalUrl = '';

    #[Validate('nullable|file|max:10240')]
    public $file;

    public ?string $editingId = null;

    public function mount(AuditCheck $check): void
    {
        $this->check = $check;
    }

    /**
     * Open the add evidence modal.
     */
    public function openAddModal(string $type = 'note'): void
    {
        $this->resetForm();
        $this->evidenceType = $type;
        $this->showAddModal = true;
    }

    /**
     * Close the modal and reset form.
     */
    public function closeModal(): void
    {
        $this->showAddModal = false;
        $this->resetForm();
    }

    /**
     * Reset the form fields.
     */
    public function resetForm(): void
    {
        $this->title = '';
        $this->notes = '';
        $this->externalUrl = '';
        $this->file = null;
        $this->editingId = null;
        $this->resetValidation();
    }

    /**
     * Save a new evidence or update existing.
     */
    public function saveEvidence(): void
    {
        $type = EvidenceType::from($this->evidenceType);

        // Validate based on type
        $rules = [
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
        ];

        if ($type === EvidenceType::Link) {
            $rules['externalUrl'] = 'required|url|max:2000';
        } elseif ($type->requiresFile() && ! $this->editingId) {
            $rules['file'] = 'required|file|max:10240';
        }

        $this->validate($rules);

        $data = [
            'audit_check_id' => $this->check->id,
            'captured_by_user_id' => Auth::id(),
            'type' => $type,
            'title' => $this->title ?: null,
            'notes' => $this->notes ?: null,
            'captured_at' => now(),
        ];

        // Handle external URL for links
        if ($type === EvidenceType::Link) {
            $data['external_url'] = $this->externalUrl;
        }

        // Handle file upload
        if ($this->file && $type->requiresFile()) {
            $path = $this->file->store('evidence', 'public');
            $data['file_path'] = $path;
            $data['mime_type'] = $this->file->getMimeType();
            $data['file_size'] = $this->file->getSize();
        }

        if ($this->editingId) {
            $evidence = AuditEvidence::findOrFail($this->editingId);
            $evidence->update($data);
        } else {
            AuditEvidence::create($data);
        }

        $this->closeModal();
        $this->dispatch('evidence-saved');
    }

    /**
     * Edit existing evidence.
     */
    public function editEvidence(string $evidenceId): void
    {
        $evidence = AuditEvidence::findOrFail($evidenceId);

        $this->editingId = $evidenceId;
        $this->evidenceType = $evidence->type->value;
        $this->title = $evidence->title ?? '';
        $this->notes = $evidence->notes ?? '';
        $this->externalUrl = $evidence->external_url ?? '';
        $this->showAddModal = true;
    }

    /**
     * Delete evidence.
     */
    public function deleteEvidence(string $evidenceId): void
    {
        $evidence = AuditEvidence::findOrFail($evidenceId);

        // Delete file if exists
        if ($evidence->hasFile()) {
            Storage::disk('public')->delete($evidence->file_path);
        }

        $evidence->delete();

        $this->dispatch('evidence-deleted');
    }

    /**
     * Get all evidence for this check.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AuditEvidence>
     */
    #[Computed]
    public function evidence(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->check->evidence()
            ->with('capturedBy')
            ->orderByDesc('captured_at')
            ->get();
    }

    /**
     * Get evidence grouped by type.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Database\Eloquent\Collection<int, AuditEvidence>>
     */
    #[Computed]
    public function evidenceByType(): \Illuminate\Support\Collection
    {
        return $this->evidence->groupBy(fn ($e) => $e->type->value);
    }

    /**
     * Get the count of evidence items.
     */
    #[Computed]
    public function evidenceCount(): int
    {
        return $this->evidence->count();
    }

    /**
     * Check if current evidence type requires file upload.
     */
    #[Computed]
    public function requiresFile(): bool
    {
        return EvidenceType::from($this->evidenceType)->requiresFile();
    }

    public function render(): View
    {
        return view('livewire.accessibility.evidence-capture', [
            'evidenceTypes' => EvidenceType::cases(),
        ]);
    }
}
