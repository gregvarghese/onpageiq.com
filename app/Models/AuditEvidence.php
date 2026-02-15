<?php

namespace App\Models;

use App\Enums\EvidenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AuditEvidence extends Model
{
    /** @use HasFactory<\Database\Factories\AuditEvidenceFactory> */
    use HasFactory;

    protected $table = 'audit_evidence';

    protected $fillable = [
        'audit_check_id',
        'captured_by_user_id',
        'type',
        'file_path',
        'external_url',
        'notes',
        'title',
        'mime_type',
        'file_size',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => EvidenceType::class,
            'file_size' => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    /**
     * Get the check this evidence belongs to.
     */
    public function check(): BelongsTo
    {
        return $this->belongsTo(AuditCheck::class, 'audit_check_id');
    }

    /**
     * Get the user who captured this evidence.
     */
    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by_user_id');
    }

    /**
     * Check if this evidence has a file.
     */
    public function hasFile(): bool
    {
        return ! empty($this->file_path);
    }

    /**
     * Check if this evidence has an external URL.
     */
    public function hasExternalUrl(): bool
    {
        return ! empty($this->external_url);
    }

    /**
     * Get the URL to access this evidence.
     */
    public function getUrl(): ?string
    {
        if ($this->hasExternalUrl()) {
            return $this->external_url;
        }

        if ($this->hasFile()) {
            return Storage::url($this->file_path);
        }

        return null;
    }

    /**
     * Get the icon for this evidence type.
     */
    public function getIcon(): string
    {
        return $this->type->icon();
    }

    /**
     * Get the color for this evidence type.
     */
    public function getColor(): string
    {
        return $this->type->color();
    }

    /**
     * Get a human-readable file size.
     */
    public function getFormattedFileSize(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }

    /**
     * Check if this is a screenshot.
     */
    public function isScreenshot(): bool
    {
        return $this->type === EvidenceType::Screenshot;
    }

    /**
     * Check if this is a recording.
     */
    public function isRecording(): bool
    {
        return $this->type === EvidenceType::Recording;
    }

    /**
     * Check if this is a note.
     */
    public function isNote(): bool
    {
        return $this->type === EvidenceType::Note;
    }

    /**
     * Check if this is a link.
     */
    public function isLink(): bool
    {
        return $this->type === EvidenceType::Link;
    }

    /**
     * Delete the associated file when the evidence is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (AuditEvidence $evidence) {
            if ($evidence->hasFile()) {
                Storage::delete($evidence->file_path);
            }
        });
    }
}
