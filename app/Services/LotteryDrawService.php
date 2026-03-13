<?php

namespace App\Services;

use App\Models\LotteryEvent;
use App\Models\Prize;
use App\Models\PrizeWinner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LotteryDrawService
{
    public function drawCurrentPrize(LotteryEvent $event): Collection
    {
        return DB::transaction(function () use ($event) {
            $prize = Prize::where('id', $event->current_prize_id)->lockForUpdate()->first();

            if (! $prize) {
                return collect();
            }

            $eligible = app(EligibleEmployeesService::class)->eligibleForStoredPrize($prize);
            $alreadyDrawn = $prize->winners()->count();
            $remaining = max(0, $prize->winners_count - $alreadyDrawn);

            if ($remaining <= 0 || $eligible->isEmpty()) {
                return collect();
            }

            $drawCount = $prize->draw_mode === Prize::DRAW_MODE_ONE_BY_ONE ? 1 : $remaining;
            $now = now();
            $created = collect();

            // 收集空出的 sequence（已釋出且尚無替補者）
            $vacantSequences = $prize->allWinnerRecords()
                ->whereNotNull('released_at')
                ->whereDoesntHave('replacement')
                ->orderBy('sequence')
                ->pluck('sequence', 'id');

            $vacantList = $vacantSequences->values()->all();
            $vacantWinnerIds = $vacantSequences->keys()->all();
            $vacantIndex = 0;

            // 下一個新 sequence（用於空位用完後）
            $maxSequence = $prize->allWinnerRecords()->max('sequence') ?? 0;
            $nextSequence = $maxSequence + 1;

            if ($prize->allow_repeat_within_prize) {
                for ($index = 0; $index < $drawCount; $index++) {
                    $winner = $eligible->random();

                    if ($vacantIndex < count($vacantList)) {
                        $seq = $vacantList[$vacantIndex];
                        $replacementForId = $vacantWinnerIds[$vacantIndex];
                        $vacantIndex++;
                    } else {
                        $seq = $nextSequence++;
                        $replacementForId = null;
                    }

                    $created->push(PrizeWinner::create([
                        'prize_id' => $prize->id,
                        'employee_id' => $winner->id,
                        'sequence' => $seq,
                        'won_at' => $now,
                        'replacement_for_winner_id' => $replacementForId,
                    ]));
                }

                $this->syncPrizeWinnersGroupIfCompleted($prize);

                return PrizeWinner::query()
                    ->whereKey($created->pluck('id')->all())
                    ->with('employee')
                    ->get();
            }

            $selected = $eligible->shuffle()->take(min($drawCount, $eligible->count()));

            foreach ($selected as $winner) {
                if ($vacantIndex < count($vacantList)) {
                    $seq = $vacantList[$vacantIndex];
                    $replacementForId = $vacantWinnerIds[$vacantIndex];
                    $vacantIndex++;
                } else {
                    $seq = $nextSequence++;
                    $replacementForId = null;
                }

                $created->push(PrizeWinner::create([
                    'prize_id' => $prize->id,
                    'employee_id' => $winner->id,
                    'sequence' => $seq,
                    'won_at' => $now,
                    'replacement_for_winner_id' => $replacementForId,
                ]));
            }

            $this->syncPrizeWinnersGroupIfCompleted($prize);

            return PrizeWinner::query()
                ->whereKey($created->pluck('id')->all())
                ->with('employee')
                ->get();
        });
    }

    private function syncPrizeWinnersGroupIfCompleted(Prize $prize): void
    {
        if ($prize->winners()->count() >= $prize->winners_count) {
            app(SystemGroupService::class)->syncPrizeWinnersGroup($prize);
        }
    }
}
