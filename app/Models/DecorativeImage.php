<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecorativeImage extends Model
{
    /** @use HasFactory<\Database\Factories\DecorativeImageFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'image_url',
        'marked_by_user_id',
    ];

    /**
     * Get the project this decorative image belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who marked this image as decorative.
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }
}
