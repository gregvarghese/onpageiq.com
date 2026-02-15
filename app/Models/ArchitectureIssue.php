<?php

namespace App\Models;

use App\Enums\ArchitectureIssueType;
use App\Enums\ImpactLevel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectureIssue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'site_architecture_id',
        'node_id',
        'issue_type',
        'severity',
        'message',
        'recommendation',
        'is_resolved',
    ];

    protected function casts(): array
    {
        return [
            'issue_type' => ArchitectureIssueType::class,
            'severity' => ImpactLevel::class,
            'is_resolved' => 'boolean',
        ];
    }

    public function siteArchitecture(): BelongsTo
    {
        return $this->belongsTo(SiteArchitecture::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(ArchitectureNode::class, 'node_id');
    }

    public function resolve(): void
    {
        $this->update(['is_resolved' => true]);
    }

    public function reopen(): void
    {
        $this->update(['is_resolved' => false]);
    }

    public function isCritical(): bool
    {
        return $this->severity === ImpactLevel::Critical;
    }

    public function isSerious(): bool
    {
        return $this->severity === ImpactLevel::Serious;
    }

    public function getCategory(): string
    {
        return $this->issue_type->category();
    }

    public static function createForNode(
        ArchitectureNode $node,
        ArchitectureIssueType $type,
        ?string $recommendation = null
    ): self {
        return self::create([
            'site_architecture_id' => $node->site_architecture_id,
            'node_id' => $node->id,
            'issue_type' => $type,
            'severity' => $type->severity(),
            'message' => $type->description(),
            'recommendation' => $recommendation,
        ]);
    }

    public static function createOrphanIssue(ArchitectureNode $node): self
    {
        return self::createForNode(
            $node,
            ArchitectureIssueType::OrphanPage,
            'Add internal links from other relevant pages to this page to improve discoverability.'
        );
    }

    public static function createDeepPageIssue(ArchitectureNode $node): self
    {
        return self::createForNode(
            $node,
            ArchitectureIssueType::DeepPage,
            "This page is {$node->depth} clicks from the homepage. Consider adding direct links from higher-level pages."
        );
    }

    public static function createBrokenLinkIssue(ArchitectureNode $node, string $brokenUrl): self
    {
        return self::create([
            'site_architecture_id' => $node->site_architecture_id,
            'node_id' => $node->id,
            'issue_type' => ArchitectureIssueType::BrokenLink,
            'severity' => ImpactLevel::Critical,
            'message' => "Page contains a broken link to: {$brokenUrl}",
            'recommendation' => 'Remove or update the broken link to point to a valid URL.',
        ]);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeOfType($query, ArchitectureIssueType $type)
    {
        return $query->where('issue_type', $type);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', ImpactLevel::Critical);
    }

    public function scopeSerious($query)
    {
        return $query->where('severity', ImpactLevel::Serious);
    }
}
