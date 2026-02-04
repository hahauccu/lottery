<?php

namespace App\Jobs;

use App\Models\Prize;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPrizeNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $prizeId
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $prize = Prize::find($this->prizeId);

        if (! $prize) {
            Log::warning("SendPrizeNotifications: Prize {$this->prizeId} not found");

            return;
        }

        $winnerIds = $prize->winners()
            ->whereNull('notified_at')
            ->pluck('id');

        if ($winnerIds->isEmpty()) {
            Log::info("SendPrizeNotifications: No winners to notify for prize {$this->prizeId}");

            return;
        }

        Log::info("SendPrizeNotifications: Dispatching {$winnerIds->count()} notification jobs for prize {$this->prizeId}");

        // 為每個中獎者分派獨立的通知 job，可以並行處理
        foreach ($winnerIds as $winnerId) {
            SendWinnerNotification::dispatch($winnerId);
        }
    }
}
