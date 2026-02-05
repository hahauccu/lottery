<?php

use App\Models\LotteryEvent;
use App\Services\SystemGroupService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $service = app(SystemGroupService::class);

        LotteryEvent::all()->each(function (LotteryEvent $event) use ($service) {
            $service->ensureEventSystemGroups($event);

            // 為已完成的獎項建立中獎者群組
            $event->prizes()
                ->whereRaw('(SELECT COUNT(*) FROM prize_winners WHERE prize_winners.prize_id = prizes.id) >= prizes.winners_count')
                ->get()
                ->each(fn ($prize) => $service->syncPrizeWinnersGroup($prize));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // System groups will be automatically deleted when events are deleted
    }
};
