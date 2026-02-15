<?php

namespace App\Events;

use App\Models\AccessibilityAudit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccessibilityAuditProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AccessibilityAudit $audit,
        public string $message,
        public int $percentage
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
            'status' => $this->audit->status->value,
            'message' => $this->message,
            'percentage' => $this->percentage,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'accessibility-audit.progress';
    }
}
