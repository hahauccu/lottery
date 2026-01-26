<?php

namespace App\Support;

use App\Events\LotteryEventUpdated;
use App\Models\LotteryEvent;
use Throwable;

class LotteryBroadcaster
{
    public static function dispatchUpdate(LotteryEvent $event): void
    {
        try {
            event(new LotteryEventUpdated($event));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
