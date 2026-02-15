<?php

use App\Enums\EvidenceType;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\AuditEvidence;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['subscription_tier' => 'enterprise']);
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->project = Project::factory()->create(['organization_id' => $this->organization->id]);
    $this->audit = AccessibilityAudit::factory()->create(['project_id' => $this->project->id]);
    $this->check = AuditCheck::factory()->forAudit($this->audit)->create();
});

describe('AuditEvidence Model', function () {
    it('can be created with factory', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->create();

        expect($evidence)->toBeInstanceOf(AuditEvidence::class)
            ->and($evidence->audit_check_id)->toBe($this->check->id);
    });

    it('belongs to a check', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->create();

        expect($evidence->check)->toBeInstanceOf(AuditCheck::class)
            ->and($evidence->check->id)->toBe($this->check->id);
    });

    it('belongs to captured by user', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->capturedBy($this->user)->create();

        expect($evidence->capturedBy)->toBeInstanceOf(User::class)
            ->and($evidence->capturedBy->id)->toBe($this->user->id);
    });

    it('casts type to EvidenceType enum', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->screenshot()->create();

        expect($evidence->type)->toBe(EvidenceType::Screenshot);
    });

    it('casts captured_at to datetime', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->create([
            'captured_at' => '2026-02-15 12:00:00',
        ]);

        expect($evidence->captured_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });
});

describe('AuditEvidence Types', function () {
    it('creates screenshot evidence', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->screenshot()->create();

        expect($evidence->type)->toBe(EvidenceType::Screenshot)
            ->and($evidence->file_path)->toContain('screenshots')
            ->and($evidence->mime_type)->toBe('image/png')
            ->and($evidence->hasFile())->toBeTrue();
    });

    it('creates recording evidence', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->recording()->create();

        expect($evidence->type)->toBe(EvidenceType::Recording)
            ->and($evidence->file_path)->toContain('recordings')
            ->and($evidence->mime_type)->toBe('video/webm')
            ->and($evidence->hasFile())->toBeTrue();
    });

    it('creates note evidence', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->note()->create();

        expect($evidence->type)->toBe(EvidenceType::Note)
            ->and($evidence->file_path)->toBeNull()
            ->and($evidence->notes)->not->toBeNull()
            ->and($evidence->hasFile())->toBeFalse();
    });

    it('creates link evidence', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->link()->create();

        expect($evidence->type)->toBe(EvidenceType::Link)
            ->and($evidence->external_url)->not->toBeNull()
            ->and($evidence->hasExternalUrl())->toBeTrue()
            ->and($evidence->hasFile())->toBeFalse();
    });

    it('creates document evidence', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->document()->create();

        expect($evidence->type)->toBe(EvidenceType::Document)
            ->and($evidence->file_path)->toContain('documents')
            ->and($evidence->mime_type)->toBe('application/pdf');
    });
});

describe('AuditEvidence Helper Methods', function () {
    it('returns icon for evidence type', function () {
        $screenshot = AuditEvidence::factory()->forCheck($this->check)->screenshot()->create();
        $recording = AuditEvidence::factory()->forCheck($this->check)->recording()->create();
        $note = AuditEvidence::factory()->forCheck($this->check)->note()->create();

        expect($screenshot->getIcon())->toBe('camera')
            ->and($recording->getIcon())->toBe('video-camera')
            ->and($note->getIcon())->toBe('document-text');
    });

    it('returns color for evidence type', function () {
        $screenshot = AuditEvidence::factory()->forCheck($this->check)->screenshot()->create();
        $link = AuditEvidence::factory()->forCheck($this->check)->link()->create();

        expect($screenshot->getColor())->toBe('blue')
            ->and($link->getColor())->toBe('cyan');
    });

    it('formats file size in bytes', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->create([
            'file_size' => 500,
        ]);

        expect($evidence->getFormattedFileSize())->toBe('500 B');
    });

    it('formats file size in KB', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->create([
            'file_size' => 2048,
        ]);

        expect($evidence->getFormattedFileSize())->toBe('2 KB');
    });

    it('formats file size in MB', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->create([
            'file_size' => 5 * 1024 * 1024,
        ]);

        expect($evidence->getFormattedFileSize())->toBe('5 MB');
    });

    it('returns null for null file size', function () {
        $evidence = AuditEvidence::factory()->forCheck($this->check)->note()->create([
            'file_size' => null,
        ]);

        expect($evidence->getFormattedFileSize())->toBeNull();
    });

    it('checks evidence type correctly', function () {
        $screenshot = AuditEvidence::factory()->forCheck($this->check)->screenshot()->create();
        $recording = AuditEvidence::factory()->forCheck($this->check)->recording()->create();
        $note = AuditEvidence::factory()->forCheck($this->check)->note()->create();
        $link = AuditEvidence::factory()->forCheck($this->check)->link()->create();

        expect($screenshot->isScreenshot())->toBeTrue()
            ->and($screenshot->isRecording())->toBeFalse()
            ->and($recording->isRecording())->toBeTrue()
            ->and($note->isNote())->toBeTrue()
            ->and($link->isLink())->toBeTrue();
    });
});
