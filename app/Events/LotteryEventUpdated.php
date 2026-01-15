<?php

namespace App\Events;

use App\Models\LotteryEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LotteryEventUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public LotteryEvent $event)
    {
        $this->event->loadMissing(['currentPrize.winners.employee']);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('lottery.' . $this->event->brand_code);
    }

    public function broadcastAs(): string
    {
        return 'lottery.updated';
    }

    public function broadcastWith(): array
    {
        $currentPrize = $this->event->currentPrize;

        return [
            'event' => [
                'id' => $this->event->id,
                'name' => $this->event->name,
                'brand_code' => $this->event->brand_code,
                'is_lottery_open' => $this->event->is_lottery_open,
                'current_prize_id' => $this->event->current_prize_id,
            ],
            'current_prize' => $currentPrize ? [
                'id' => $currentPrize->id,
                'name' => $currentPrize->name,
                'draw_mode' => $currentPrize->draw_mode,
                'winners_count' => $currentPrize->winners_count,
            ] : null,
            'winners' => $currentPrize
                ? $currentPrize->winners
                    ->sortBy('sequence')
                    ->map(fn ($winner) => [
                        'id' => $winner->id,
                        'employee_name' => $winner->employee?->name,
                        'employee_email' => $winner->employee?->email,
                        'employee_phone' => $winner->employee?->phone,
                        'sequence' => $winner->sequence,
                        'won_at' => optional($winner->won_at)->toDateTimeString(),
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }
}
