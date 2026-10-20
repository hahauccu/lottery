<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DemoMetric extends Model
{
    public const EVENTS = [
        'page_view' => '頁面瀏覽', 'play_click' => '試玩入口點擊',
        'draw_start' => '開始抽獎', 'draw_complete' => '完成動畫',
        'draw_failed' => '動畫未完成／錯誤', 'register_click' => '註冊入口點擊',
    ];

    public const ENTRIES = ['home' => '首頁', 'hub' => '動畫總覽', 'intro' => '介紹頁', 'play' => '試玩頁'];

    public static function record(string $event, string $style, string $entry, int $durationMs = 0): void
    {
        $key = ['day' => now('Asia/Taipei')->toDateString(), 'event' => $event, 'animation_style' => $style, 'entry' => $entry];
        static::query()->insertOrIgnore($key + ['total' => 0, 'duration_total_ms' => 0, 'created_at' => now(), 'updated_at' => now()]);
        static::query()->where($key)->update([
            'total' => DB::raw('total + 1'),
            'duration_total_ms' => DB::raw('duration_total_ms + '.(int) $durationMs),
            'updated_at' => now(),
        ]);
    }
}
