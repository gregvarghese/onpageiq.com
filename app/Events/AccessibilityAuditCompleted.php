<?php

namespace App\Events;

use App\Models\AccessibilityAudit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccessibilityAuditCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AccessibilityAudit $audit
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('accessibility-audits.'.$this->audit->id),
            new PrivateChannel('projects.'.$this->audit->project_id),
            new PrivateChannel('organizations.'.$this->audit->project->organization_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'audit_id' => $this->audit->id,
            'project_id' => $this->audit->project_id,
            'url_id' => $this->audit->url_id,
            'status' => 'completed',
            'overall_score' => $this->audit->overall_score,
            'scores_by_category' => $this->audit->scores_by_category,
            'checks_total' => $this->audit->checks_total,
            'checks_passed' => $this->audit->checks_passed,
            'checks_failed' => $this->audit->checks_failed,
            'wcag_level_target' => $this->audit->wcag_level_target->value,
            'framework' => $this->audit->framework->value,
            'completed_at' => $this->audit->completed_at?->toIso8601String(),
            'duration_seconds' => $this->audit->getDurationInSeconds(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'accessibility-audit.completed';
    }
}
