<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?string $userId,
        public string $architectureId,
        public string $format,
        public string $filename,
        public string $downloadUrl
    ) {}

    public function broadcastOn(): array
    {
        if (! $this->userId) {
            return [];
        }

        return [
            new PrivateChannel('user.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'export.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'architecture_id' => $this->architectureId,
            'format' => $this->format,
            'filename' => $this->filename,
            'download_url' => $this->downloadUrl,
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
