<?php

namespace App\Events;

use App\Models\LotteryEvent;
use App\Services\EligibleEmployeesService;
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
        $this->event->loadMissing(['currentPrize.winners.employee', 'prizes']);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('lottery.'.$this->event->brand_code);
    }

    public function broadcastAs(): string
    {
        return 'lottery.updated';
    }

    public function broadcastWith(): array
    {
        $currentPrize = $this->event->currentPrize;

        $eligibleNames = $currentPrize
            ? app(EligibleEmployeesService::class)
                ->eligibleForStoredPrize($currentPrize)
                ->pluck('name')
                ->values()
                ->all()
            : [];

        $musicUrl = $currentPrize?->music_path
            ? asset('storage/'.$currentPrize->music_path)
            : null;

        $allPrizes = $this->event->prizes
            ->map(fn ($prize) => [
                'id' => $prize->id,
                'name' => $prize->name,
                'winnersCount' => $prize->winners_count,
                'drawnCount' => $prize->winners()->count(),
            ])->all();

        $bgPath = $currentPrize?->bg_image_path ?: $this->event->default_bg_image_path;
        $bgUrl = $bgPath ? asset('storage/'.$bgPath) : null;

        return [
            'event' => [
                'id' => $this->event->id,
                'name' => $this->event->name,
                'brand_code' => $this->event->brand_code,
                'is_lottery_open' => $this->event->is_lottery_open,
                'show_prizes_preview' => $this->event->show_prizes_preview,
                'danmaku_enabled' => $this->event->danmaku_enabled,
                'current_prize_id' => $this->event->current_prize_id,
                'is_prize_switching' => $this->event->is_prize_switching,
            ],
            'bg_url' => $bgUrl,
            'all_prizes' => $allPrizes,
            'current_prize' => $currentPrize ? [
                'id' => $currentPrize->id,
                'name' => $currentPrize->name,
                'draw_mode' => $currentPrize->draw_mode,
                'animation_style' => $currentPrize->animation_style,
                'lotto_hold_seconds' => $currentPrize->lotto_hold_seconds,
                'sound_enabled' => $currentPrize->sound_enabled,
                'music_url' => $musicUrl,
                'winners_count' => $currentPrize->winners_count,
                'is_completed' => $currentPrize->winners()->count() >= $currentPrize->winners_count,
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
            'eligible_names' => $eligibleNames,
        ];
    }
}
