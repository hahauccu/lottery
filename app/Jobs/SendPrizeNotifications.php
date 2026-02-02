<?php

namespace App\Jobs;

use App\Mail\PrizeWinnerNotificationMail;
use App\Models\Prize;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        $winners = $prize->winners()
            ->whereNull('notified_at')
            ->with(['employee', 'prize.lotteryEvent'])
            ->get();

        foreach ($winners as $winner) {
            $email = $winner->employee->email;

            if (empty($email)) {
                Log::info("SendPrizeNotifications: Skipping winner {$winner->id} - no email");

                continue;
            }

            try {
                Mail::to($email)->send(new PrizeWinnerNotificationMail($winner));
                $winner->update(['notified_at' => now()]);

                Log::info("SendPrizeNotifications: Sent notification to {$email} for winner {$winner->id}");
            } catch (\Exception $e) {
                Log::error("SendPrizeNotifications: Failed to send notification to {$email}", [
                    'winner_id' => $winner->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
