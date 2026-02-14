<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FalsePositiveReport extends Model
{
    /** @use HasFactory<\Database\Factories\FalsePositiveReportFactory> */
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'user_id',
        'category',
        'context',
    ];

    /**
     * Get the issue this report is for.
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Get the user who reported this false positive.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
