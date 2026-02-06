<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DanmakuSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $brandCode,
        public string $employeeName,
        public string $message,
        public string $danmakuId
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('lottery.'.$this->brandCode);
    }

    public function broadcastAs(): string
    {
        return 'danmaku.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->danmakuId,
            'employee_name' => $this->employeeName,
            'message' => $this->message,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
