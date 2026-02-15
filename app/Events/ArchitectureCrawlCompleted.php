<?php

namespace App\Events;

use App\Models\SiteArchitecture;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArchitectureCrawlCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public SiteArchitecture $architecture
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.'.$this->architecture->project_id),
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
            'architecture_id' => $this->architecture->id,
            'status' => $this->architecture->status->value,
            'total_nodes' => $this->architecture->total_nodes,
            'total_links' => $this->architecture->total_links,
            'orphan_count' => $this->architecture->orphan_count,
            'error_count' => $this->architecture->error_count,
        ];
    }
}
