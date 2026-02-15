<?php

namespace App\Events;

use App\Models\SiteArchitecture;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArchitectureCrawlProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public SiteArchitecture $architecture,
        public int $crawledPages,
        public int $totalDiscovered,
        public string $currentUrl
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
            'crawled_pages' => $this->crawledPages,
            'total_discovered' => $this->totalDiscovered,
            'current_url' => $this->currentUrl,
            'progress_percent' => $this->totalDiscovered > 0
                ? round(($this->crawledPages / $this->totalDiscovered) * 100)
                : 0,
        ];
    }
}
