<?php

namespace App\Events;

use App\Models\Prize;
use App\Services\EligibleEmployeesService;
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

    public function __construct(public string $brandCode, public Prize $prize, public Collection $winners) {}

    public function broadcastOn(): Channel
    {
        return new Channel('lottery.'.$this->brandCode);
    }

    public function broadcastAs(): string
    {
        return 'winners.updated';
    }

    public function broadcastWith(): array
    {
        $this->winners->loadMissing('employee');

        // 計算抽獎後更新的可抽人員名單
        $eligibleNames = app(EligibleEmployeesService::class)
            ->eligibleForStoredPrize($this->prize)
            ->pluck('name')
            ->values()
            ->all();

        // 計算獎項完成狀態
        $drawnCount = $this->prize->winners()->count();
        $isCompleted = $drawnCount >= $this->prize->winners_count;
        $isExhausted = empty($eligibleNames) && ! $isCompleted;

        // 計算所有獎項的最新狀態（含 drawnCount）
        $allPrizes = $this->prize->lotteryEvent->prizes
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'winnersCount' => $p->winners_count,
                'drawnCount' => $p->winners()->count(),
            ])->all();

        return [
            'prize_id' => $this->prize->id,
            'prize_name' => $this->prize->name,
            'is_completed' => $isCompleted,
            'is_exhausted' => $isExhausted,
            'winners' => $this->winners->map(fn ($winner) => [
                'id' => $winner->id,
                'employee_name' => $winner->employee?->name,
                'employee_email' => $winner->employee?->email,
                'employee_phone' => $winner->employee?->phone,
                'sequence' => $winner->sequence,
                'won_at' => optional($winner->won_at)->toDateTimeString(),
            ])->values()->all(),
            'eligible_names' => $eligibleNames,
            'all_prizes' => $allPrizes,
        ];
    }
}
