<?php

namespace App\Support;

class AnimationStyles
{
    /**
     * 所有支援的抽獎動畫風格
     * 單一資料來源，後端所有地方統一引用此 class
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            ['key' => 'lotto_air', 'slug' => 'lotto-air', 'label' => '樂透氣流機', 'desc' => '彩球在氣流中翻滾飛舞，逐一揭曉幸運得主'],
            ['key' => 'red_packet', 'slug' => 'red-packet', 'label' => '紅包雨', 'desc' => '紅包從天而降，點擊拆開揭曉驚喜'],
            ['key' => 'scratch_card', 'slug' => 'scratch-card', 'label' => '刮刮樂', 'desc' => '刮開銀色塗層，發現隱藏的中獎名單'],
            ['key' => 'treasure_chest', 'slug' => 'treasure-chest', 'label' => '寶箱', 'desc' => '開啟神秘寶箱，獲得專屬獎品'],
            ['key' => 'big_treasure_chest', 'slug' => 'big-treasure-chest', 'label' => '大寶箱', 'desc' => '巨型寶箱隆重登場，大獎即刻揭曉'],
            ['key' => 'marble_race', 'slug' => 'marble-race', 'label' => '圓球賽跑', 'desc' => '彩色圓球競速衝刺，率先抵達者獲獎'],
            ['key' => 'battle_top', 'slug' => 'battle-top', 'label' => '戰鬥陀螺', 'desc' => '戰鬥陀螺激烈對決，最後站立者勝出'],
        ];
    }

    /**
     * key => label mapping
     * 供 Filament Select 使用
     *
     * @return array
     */
    public static function labels(): array
    {
        return array_column(self::all(), 'label', 'key');
    }

    /**
     * slug => key mapping
     * 供 DemoLotteryController 使用
     *
     * @return array
     */
    public static function slugToKey(): array
    {
        return array_column(self::all(), 'key', 'slug');
    }
}
