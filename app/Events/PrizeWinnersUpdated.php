<?php

namespace App\Events;

use App\Models\Prize;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PrizeWinnersUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public string $brandCode, public Prize $prize, public Collection $winners)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('lottery.' . $this->brandCode);
    }

    public function broadcastAs(): string
    {
        return 'winners.updated';
    }

    public function broadcastWith(): array
    {
        $this->winners->loadMissing('employee');

        return [
            'prize_id' => $this->prize->id,
            'prize_name' => $this->prize->name,
            'winners' => $this->winners->map(fn ($winner) => [
                'id' => $winner->id,
                'employee_name' => $winner->employee?->name,
                'employee_email' => $winner->employee?->email,
                'employee_phone' => $winner->employee?->phone,
                'sequence' => $winner->sequence,
                'won_at' => optional($winner->won_at)->toDateTimeString(),
            ])->values()->all(),
        ];
    }
}
