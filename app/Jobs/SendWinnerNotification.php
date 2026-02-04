<?php

namespace App\Jobs;

use App\Mail\PrizeWinnerNotificationMail;
use App\Models\PrizeWinner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWinnerNotification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(
        public int $winnerId
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $winner = PrizeWinner::with(['employee', 'prize.lotteryEvent'])
            ->find($this->winnerId);

        if (! $winner) {
            Log::warning("SendWinnerNotification: Winner {$this->winnerId} not found");

            return;
        }

        // 如果已經通知過，跳過
        if ($winner->isNotified()) {
            Log::info("SendWinnerNotification: Winner {$this->winnerId} already notified");

            return;
        }

        $email = $winner->employee->email;

        if (empty($email)) {
            Log::info("SendWinnerNotification: Winner {$this->winnerId} has no email");
            // 標記為已通知，避免重複嘗試
            $winner->update(['notified_at' => now()]);

            return;
        }

        try {
            Mail::to($email)->send(new PrizeWinnerNotificationMail($winner));
            $winner->update(['notified_at' => now()]);

            Log::info("SendWinnerNotification: Sent notification to {$email} for winner {$this->winnerId}");
        } catch (\Exception $e) {
            Log::error("SendWinnerNotification: Failed to send notification to {$email}", [
                'winner_id' => $this->winnerId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e; // 重新拋出以觸發重試機制
        }
    }
}
