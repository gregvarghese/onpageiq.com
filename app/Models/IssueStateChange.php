<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueStateChange extends Model
{
    /** @use HasFactory<\Database\Factories\IssueStateChangeFactory> */
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'user_id',
        'from_state',
        'to_state',
        'note',
    ];

    /**
     * Get the issue this state change belongs to.
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Get the user who made this state change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
