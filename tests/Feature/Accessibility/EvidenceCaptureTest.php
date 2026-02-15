<?php

use App\Enums\EvidenceType;
use App\Livewire\Accessibility\EvidenceCapture;
use App\Models\AuditCheck;
use App\Models\AuditEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->check = AuditCheck::factory()->create();

    Storage::fake('public');
});

describe('EvidenceCapture Component', function () {
    test('renders with no evidence', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->assertStatus(200)
            ->assertSee('Evidence')
            ->assertSee('No evidence captured yet');
    });

    test('renders with existing evidence', function () {
        AuditEvidence::factory()->create([
            'audit_check_id' => $this->check->id,
            'type' => EvidenceType::Note,
            'notes' => 'Test note content',
        ]);

        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->assertStatus(200)
            ->assertSee('Test note content');
    });

    test('can open add modal for note', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'note')
            ->assertSet('showAddModal', true)
            ->assertSet('evidenceType', 'note');
    });

    test('can open add modal for screenshot', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'screenshot')
            ->assertSet('showAddModal', true)
            ->assertSet('evidenceType', 'screenshot');
    });

    test('can close modal', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'note')
            ->assertSet('showAddModal', true)
            ->call('closeModal')
            ->assertSet('showAddModal', false);
    });

    test('can add note evidence', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'note')
            ->set('title', 'Test Note Title')
            ->set('notes', 'This is a test note')
            ->call('saveEvidence')
            ->assertSet('showAddModal', false)
            ->assertDispatched('evidence-saved');

        $this->assertDatabaseHas('audit_evidence', [
            'audit_check_id' => $this->check->id,
            'type' => EvidenceType::Note->value,
            'title' => 'Test Note Title',
            'notes' => 'This is a test note',
        ]);
    });

    test('can add link evidence', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'link')
            ->set('title', 'WCAG Reference')
            ->set('externalUrl', 'https://www.w3.org/WAI/WCAG21/quickref/')
            ->set('notes', 'WCAG quick reference')
            ->call('saveEvidence')
            ->assertSet('showAddModal', false);

        $this->assertDatabaseHas('audit_evidence', [
            'audit_check_id' => $this->check->id,
            'type' => EvidenceType::Link->value,
            'external_url' => 'https://www.w3.org/WAI/WCAG21/quickref/',
        ]);
    });

    test('validates url for link evidence', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'link')
            ->set('externalUrl', 'not-a-valid-url')
            ->call('saveEvidence')
            ->assertHasErrors(['externalUrl']);
    });

    test('can add screenshot evidence with file', function () {
        $file = UploadedFile::fake()->image('screenshot.png', 800, 600);

        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'screenshot')
            ->set('title', 'Homepage Screenshot')
            ->set('file', $file)
            ->call('saveEvidence')
            ->assertSet('showAddModal', false);

        $evidence = AuditEvidence::where('audit_check_id', $this->check->id)
            ->where('type', EvidenceType::Screenshot->value)
            ->first();

        expect($evidence)->not->toBeNull();
        expect($evidence->file_path)->not->toBeNull();
        Storage::disk('public')->assertExists($evidence->file_path);
    });

    test('requires file for screenshot evidence', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'screenshot')
            ->set('title', 'Missing File Screenshot')
            ->call('saveEvidence')
            ->assertHasErrors(['file']);
    });

    test('can edit existing evidence', function () {
        $evidence = AuditEvidence::factory()->create([
            'audit_check_id' => $this->check->id,
            'type' => EvidenceType::Note,
            'title' => 'Original Title',
            'notes' => 'Original notes',
        ]);

        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('editEvidence', $evidence->id)
            ->assertSet('editingId', $evidence->id)
            ->assertSet('title', 'Original Title')
            ->assertSet('notes', 'Original notes')
            ->set('title', 'Updated Title')
            ->set('notes', 'Updated notes')
            ->call('saveEvidence');

        $evidence->refresh();
        expect($evidence->title)->toBe('Updated Title');
        expect($evidence->notes)->toBe('Updated notes');
    });

    test('can delete evidence', function () {
        $evidence = AuditEvidence::factory()->create([
            'audit_check_id' => $this->check->id,
            'type' => EvidenceType::Note,
        ]);

        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('deleteEvidence', $evidence->id)
            ->assertDispatched('evidence-deleted');

        $this->assertDatabaseMissing('audit_evidence', ['id' => $evidence->id]);
    });

    test('deletes file when deleting file-based evidence', function () {
        $file = UploadedFile::fake()->image('screenshot.png');
        $path = $file->store('evidence', 'public');

        $evidence = AuditEvidence::factory()->create([
            'audit_check_id' => $this->check->id,
            'type' => EvidenceType::Screenshot,
            'file_path' => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('deleteEvidence', $evidence->id);

        Storage::disk('public')->assertMissing($path);
    });

    test('computes evidence count', function () {
        AuditEvidence::factory()->count(3)->create([
            'audit_check_id' => $this->check->id,
        ]);

        $component = Livewire::test(EvidenceCapture::class, ['check' => $this->check]);

        expect($component->get('evidenceCount'))->toBe(3);
    });

    test('computes requires file correctly', function () {
        $component = Livewire::test(EvidenceCapture::class, ['check' => $this->check]);

        $component->set('evidenceType', 'note');
        expect($component->get('requiresFile'))->toBeFalse();

        $component->set('evidenceType', 'link');
        expect($component->get('requiresFile'))->toBeFalse();

        $component->set('evidenceType', 'screenshot');
        expect($component->get('requiresFile'))->toBeTrue();

        $component->set('evidenceType', 'recording');
        expect($component->get('requiresFile'))->toBeTrue();
    });

    test('resets form after closing modal', function () {
        Livewire::test(EvidenceCapture::class, ['check' => $this->check])
            ->call('openAddModal', 'note')
            ->set('title', 'Some Title')
            ->set('notes', 'Some Notes')
            ->call('closeModal')
            ->assertSet('title', '')
            ->assertSet('notes', '');
    });

    test('shows evidence grouped by type', function () {
        AuditEvidence::factory()->create([
            'audit_check_id' => $this->check->id,
            'type' => EvidenceType::Note,
        ]);
        AuditEvidence::factory()->create([
            'audit_check_id' => $this->check->id,
            'type' => EvidenceType::Link,
            'external_url' => 'https://example.com',
        ]);

        $component = Livewire::test(EvidenceCapture::class, ['check' => $this->check]);
        $byType = $component->get('evidenceByType');

        expect($byType)->toHaveKey('note');
        expect($byType)->toHaveKey('link');
    });
});
