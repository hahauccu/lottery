<?php

namespace App\Services;

use App\Models\Prize;
use App\Support\LotteryBroadcaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PrizeRedrawService
{
    public function releaseWinners(Prize $prize, array $winnerIds, int $userId, string $reason = 'absent'): Collection
    {
        return DB::transaction(function () use ($prize, $winnerIds, $userId, $reason) {
            $prize = Prize::where('id', $prize->id)->lockForUpdate()->first();

            $brandCode = $prize->lotteryEvent->brand_code;
            if (Cache::has("lottery-drawing:{$brandCode}")) {
                throw new \RuntimeException('前台正在抽獎中，無法釋出中獎者');
            }

            $now = now();

            $winners = $prize->allWinnerRecords()
                ->whereIn('id', $winnerIds)
                ->whereNull('released_at')
                ->whereNull('claimed_at')
                ->get();

            if ($winners->isEmpty()) {
                return collect();
            }

            foreach ($winners as $winner) {
                $winner->update([
                    'released_at' => $now,
                    'release_reason' => $reason,
                    'released_by_user_id' => $userId,
                ]);
            }

            app(SystemGroupService::class)->syncPrizeWinnersGroup($prize);

            LotteryBroadcaster::dispatchUpdate($prize->lotteryEvent->refresh());

            return $winners;
        });
    }
}
