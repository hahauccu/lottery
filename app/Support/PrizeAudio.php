<?php

namespace App\Support;

use App\Models\Prize;
use Illuminate\Support\Arr;

class PrizeAudio
{
    public const MODE_DEFAULT = 'default';

    public const MODE_CUSTOM = 'custom';

    public const MODE_OFF = 'off';

    public static function modeOptions(): array
    {
        return [
            self::MODE_DEFAULT => '使用預設音效',
            self::MODE_CUSTOM => '上傳自訂音效',
            self::MODE_OFF => '關閉此音效',
        ];
    }

    public static function sounds(): array
    {
        return [
            ['key' => 'draw_bgm', 'label' => '抽獎背景音樂', 'helper' => '抽獎進行中的循環背景音樂，預設會沿用舊版抽獎音樂欄位。', 'loop' => true],
            ['key' => 'button_click', 'label' => '按鈕點擊', 'helper' => '按下抽獎時的短促確認音。', 'loop' => false],
            ['key' => 'error', 'label' => '錯誤提示', 'helper' => '不可抽獎、無可抽名單或流程錯誤時播放。', 'loop' => false],
            ['key' => 'ball_rumble', 'label' => '樂透滾球底噪', 'helper' => '樂透氣流機抽獎中的持續滾球聲。', 'loop' => true],
            ['key' => 'ball_pick', 'label' => '樂透抽球命中', 'helper' => '樂透球被抽出時的命中聲。', 'loop' => false],
            ['key' => 'machine_stop', 'label' => '機器減速停機', 'helper' => '樂透氣流機收尾減速時播放。', 'loop' => false],
            ['key' => 'paper_tear', 'label' => '紅包撕開', 'helper' => '紅包雨揭曉時的撕開音效。', 'loop' => false],
            ['key' => 'coin_drop', 'label' => '金幣落下', 'helper' => '紅包、寶箱或大寶箱揭曉時的金幣聲。', 'loop' => false],
            ['key' => 'reveal', 'label' => '中獎揭曉音效', 'helper' => '揭曉中獎者時的閃光提示音。', 'loop' => false],
            ['key' => 'chest_open', 'label' => '寶箱開啟', 'helper' => '寶箱或大寶箱開啟時播放。', 'loop' => false],
            ['key' => 'victory', 'label' => '抽完勝利音效', 'helper' => '本輪抽完進入結果頁前播放。', 'loop' => false],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::sounds(), 'key');
    }

    public static function modeField(string $key): string
    {
        return "audio_{$key}_mode";
    }

    public static function pathField(string $key): string
    {
        return "audio_{$key}_path";
    }

    public static function formStateFromPrize(Prize $prize): array
    {
        $prize->loadMissing('audioSettings');

        $settings = $prize->audioSettings->keyBy('sound_key');
        $state = [];

        foreach (self::sounds() as $sound) {
            $key = $sound['key'];
            $setting = $settings->get($key);

            $state[self::modeField($key)] = $setting?->mode ?? self::MODE_DEFAULT;
            $state[self::pathField($key)] = $setting?->file_path;
        }

        return $state;
    }

    public static function stripFormFields(array $data): array
    {
        $fields = [];

        foreach (self::keys() as $key) {
            $fields[] = self::modeField($key);
            $fields[] = self::pathField($key);
        }

        return Arr::except($data, $fields);
    }

    public static function syncSettings(Prize $prize, array $state): void
    {
        foreach (self::sounds() as $sound) {
            $key = $sound['key'];
            $mode = $state[self::modeField($key)] ?? self::MODE_DEFAULT;
            $path = self::normalizePath($state[self::pathField($key)] ?? null);

            if (! in_array($mode, [self::MODE_DEFAULT, self::MODE_CUSTOM, self::MODE_OFF], true)) {
                $mode = self::MODE_DEFAULT;
            }

            if ($mode === self::MODE_DEFAULT && blank($path)) {
                $prize->audioSettings()->where('sound_key', $key)->delete();

                continue;
            }

            $prize->audioSettings()->updateOrCreate(
                ['sound_key' => $key],
                [
                    'mode' => $mode,
                    'file_path' => $mode === self::MODE_CUSTOM ? $path : null,
                ],
            );
        }
    }

    public static function payloadForPrize(?Prize $prize, ?string $legacyMusicUrl = null): array
    {
        if (! $prize) {
            return ['enabled' => true, 'sounds' => []];
        }

        $prize->loadMissing('audioSettings');

        $settings = $prize->audioSettings->keyBy('sound_key');
        $sounds = [];

        foreach (self::sounds() as $sound) {
            $key = $sound['key'];
            $setting = $settings->get($key);
            $mode = $setting?->mode ?? self::MODE_DEFAULT;
            $url = null;

            if ($mode === self::MODE_CUSTOM && filled($setting?->file_path)) {
                $url = asset('storage/'.$setting->file_path);
            } elseif ($key === 'draw_bgm' && $mode === self::MODE_DEFAULT) {
                $url = $legacyMusicUrl;
            }

            $sounds[$key] = [
                'mode' => $mode,
                'url' => $url,
                'loop' => (bool) $sound['loop'],
            ];
        }

        return [
            'enabled' => (bool) $prize->sound_enabled,
            'sounds' => $sounds,
        ];
    }

    private static function normalizePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = Arr::first($path);
        }

        return filled($path) ? (string) $path : null;
    }
}
