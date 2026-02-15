<?php

namespace App\Models;

use App\Enums\LinkType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectureLink extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'site_architecture_id',
        'source_node_id',
        'target_node_id',
        'target_url',
        'link_type',
        'link_type_override',
        'anchor_text',
        'is_external',
        'external_domain',
        'is_nofollow',
        'position_in_page',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'link_type' => LinkType::class,
            'link_type_override' => LinkType::class,
            'is_external' => 'boolean',
            'is_nofollow' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function siteArchitecture(): BelongsTo
    {
        return $this->belongsTo(SiteArchitecture::class);
    }

    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(ArchitectureNode::class, 'source_node_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(ArchitectureNode::class, 'target_node_id');
    }

    public function getEffectiveLinkType(): LinkType
    {
        return $this->link_type_override ?? $this->link_type;
    }

    public function isInternal(): bool
    {
        return ! $this->is_external;
    }

    public function isBroken(): bool
    {
        if ($this->is_external) {
            return false;
        }

        return $this->target_node_id === null;
    }

    public function overrideLinkType(LinkType $type): void
    {
        $this->update(['link_type_override' => $type]);
    }

    public function clearOverride(): void
    {
        $this->update(['link_type_override' => null]);
    }

    public function getColor(): string
    {
        return $this->getEffectiveLinkType()->color();
    }

    public function toGraphEdge(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source_node_id,
            'target' => $this->target_node_id,
            'type' => $this->getEffectiveLinkType()->value,
            'color' => $this->getColor(),
            'anchor_text' => $this->anchor_text,
            'is_external' => $this->is_external,
            'is_nofollow' => $this->is_nofollow,
            'position' => $this->position_in_page,
        ];
    }
}
