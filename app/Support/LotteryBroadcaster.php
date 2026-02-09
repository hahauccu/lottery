<?php

namespace App\Support;

use App\Events\LotteryEventUpdated;
use App\Models\LotteryEvent;
use Throwable;

class LotteryBroadcaster
{
    /** @var array<string, float> */
    private static array $lastDispatch = [];

    public static function dispatchUpdate(LotteryEvent $event): void
    {
        $key = "event:{$event->id}";
        $now = microtime(true);

        // 200ms 內重複廣播直接忽略
        if (isset(self::$lastDispatch[$key]) && ($now - self::$lastDispatch[$key]) < 0.2) {
            return;
        }

        self::$lastDispatch[$key] = $now;

        try {
            event(new LotteryEventUpdated($event));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
