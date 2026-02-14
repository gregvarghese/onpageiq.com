<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueNote extends Model
{
    /** @use HasFactory<\Database\Factories\IssueNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'user_id',
        'note',
    ];

    /**
     * Get the issue this note belongs to.
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Get the user who created this note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
