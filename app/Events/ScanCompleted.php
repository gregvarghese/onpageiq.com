<?php

namespace App\Events;

use App\Models\Scan;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScanCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Scan $scan
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('scans.'.$this->scan->id),
            new PrivateChannel('organizations.'.$this->scan->url->project->organization_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $result = $this->scan->result;

        return [
            'scan_id' => $this->scan->id,
            'url_id' => $this->scan->url_id,
            'url' => $this->scan->url->url,
            'status' => 'completed',
            'credits_charged' => $this->scan->credits_charged,
            'scores' => $result?->scores ?? [],
            'issue_count' => $result?->issues()->count() ?? 0,
            'completed_at' => $this->scan->completed_at?->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'scan.completed';
    }
}
