<?php

namespace App\Http\Controllers;

use App\Support\AnimationStyles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DemoLotteryController extends Controller
{
    private const DEMO_NAMES = [
        '王大明',
        '陳小芳',
        '李美美',
        '張志偉',
        '林佳慧',
        '黃建宏',
        '吳淑芬',
        '劉德華',
        '蔡依琳',
        '楊宗緯',
        '鄭秀文',
        '周杰倫',
        '許志安',
        '趙小棠',
        '孫燕姿',
        '何炅明',
        '蕭敬騰',
        '田馥甄',
        '韓庚義',
        '馬如龍',
        '高以翔',
        '方大同',
        '潘瑋柏',
        '侯佩岑',
        '徐若瑄',
        '郭富城',
        '梁靜茹',
        '陶喆宏',
        '丁噹兒',
        '盧廣仲',
    ];

    private function resolveStyle(string $slug): string
    {
        $style = AnimationStyles::slugToKey()[$slug] ?? null;
        if (! $style) {
            abort(404);
        }

        return $style;
    }

    private function sessionKey(string $style): string
    {
        return "demo_lottery_{$style}";
    }

    private function ensureSession(string $style): array
    {
        $key = $this->sessionKey($style);
        $data = session($key);
        if (! $data) {
            $data = [
                'animation_style' => $style,
                'prize_id' => 1,
                'winners' => [],
                'eligible_names' => self::DEMO_NAMES,
                'draw_count' => 5,
                'draw_mode' => 'all_at_once',
                'is_custom' => false,
            ];
            session([$key => $data]);
        }

        return $data;
    }

    private function buildPayload(array $data, string $slug): array
    {
        $totalNames = $data['is_custom']
            ? count($data['winners']) + count($data['eligible_names'])
            : count(self::DEMO_NAMES);

        return [
            'brandCode' => 'DEMO',
            'eventId' => 0,
            'eventName' => '範例抽獎',
            'event' => [
                'is_lottery_open' => true,
                'is_test_mode' => false,
                'show_prizes_preview' => false,
                'is_prize_switching' => false,
                'danmaku_enabled' => false,
            ],
            'current_prize' => [
                'id' => $data['prize_id'],
                'name' => '範例抽獎',
                'draw_mode' => $data['draw_mode'] ?? 'all_at_once',
                'animation_style' => $data['animation_style'],
                'lotto_hold_seconds' => 10,
                'sound_enabled' => true,
                'music_url' => null,
                'winners_count' => $totalNames,
                'is_completed' => count($data['winners']) >= $totalNames,
                'is_exhausted' => empty($data['eligible_names']),
            ],
            'winners' => $data['winners'],
            'eligible_names' => $data['eligible_names'],
            'all_prizes' => [
                [
                    'id' => 1,
                    'name' => '範例抽獎',
                    'winnersCount' => $totalNames,
                    'drawnCount' => count($data['winners']),
                ],
            ],
            'csrfToken' => csrf_token(),
            'drawUrl' => "/demo/lottery/{$slug}/draw",
            'readyUrl' => "/demo/lottery/{$slug}/ready",
            'drawingStateUrl' => "/demo/lottery/{$slug}/drawing-state",
            'switchAckUrl' => "/demo/lottery/{$slug}/switch-ack",
            'winnersUrl' => "/demo/lottery/{$slug}",
            'bgUrl' => null,
            'danmakuEnabled' => false,
        ];
    }

    public function landing()
    {
        return view('demo.landing', [
            'styles' => AnimationStyles::all(),
        ]);
    }

    public function showStyle(Request $request, string $slug)
    {
        $isEmbeddedPreview = $request->boolean('embedded');
        $style = $this->resolveStyle($slug);
        $data = $this->ensureSession($style);
        $payload = $this->buildPayload($data, $slug);
        $styles = AnimationStyles::all();

        if ($request->has('payload') || $request->expectsJson()) {
            return response()->json($payload);
        }

        $event = (object) ['name' => '範例抽獎'];
        $currentPrize = (object) ['name' => '範例抽獎'];
        $allStyles = array_column($styles, null, 'key');
        $currentStyle = $allStyles[$style] ?? [
            'key' => $style,
            'slug' => $slug,
            'label' => '範例抽獎',
            'desc' => '免費體驗抽獎動畫風格。',
        ];
        $label = $currentStyle['label'];
        $previewImage = public_path("images/previews/{$style}.svg");
        $seoImage = file_exists($previewImage)
            ? url("/images/previews/{$style}.svg")
            : url('/images/og-demo.svg');
        $relatedStyles = collect($styles)
            ->reject(fn (array $item) => $item['key'] === $style)
            ->values()
            ->all();
        $breadcrumbItems = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => '首頁',
                'item' => url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => '抽獎動畫 Demo',
                'item' => url('/demo/lottery'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $label,
                'item' => url("/demo/lottery/{$slug}"),
            ],
        ];

        return view('lottery.show', [
            'payload' => $payload,
            'event' => $event,
            'currentPrize' => $currentPrize,
            'currentWinners' => collect(),
            'bgUrl' => null,
            'isDemo' => true,
            'isEmbeddedPreview' => $isEmbeddedPreview,
            'demoSlug' => $slug,
            'demoStyleLabel' => $label,
            'demoSetupMode' => true,
            'title' => "{$label} — 線上抽獎動畫 Demo",
            'seoTitle' => "{$label} — 線上抽獎動畫 Demo｜即時體驗",
            'seoDescription' => "免費試玩「{$label}」抽獎動畫，即時體驗企業尾牙、活動抽獎的趣味與互動效果。",
            'seoCanonical' => url("/demo/lottery/{$slug}"),
            'seoImage' => $seoImage,
            'demoCurrentStyle' => $currentStyle,
            'demoRelatedStyles' => $relatedStyles,
            'demoBreadcrumbItems' => $breadcrumbItems,
        ]);
    }

    public function configure(Request $request, string $slug): JsonResponse
    {
        $style = $this->resolveStyle($slug);

        $request->validate([
            'names' => 'nullable|string|max:10000',
            'draw_count' => 'nullable|integer|min:1|max:50',
            'draw_mode' => 'nullable|in:all_at_once,one_by_one',
        ]);

        $rawNames = $request->input('names', '');
        $names = collect(preg_split('/[\n,]+/', $rawNames))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $isCustom = ! empty($names);
        if (empty($names)) {
            $names = self::DEMO_NAMES;
        }

        $drawCount = $request->input('draw_count', 5);
        $drawMode = $request->input('draw_mode', 'all_at_once');

        $key = $this->sessionKey($style);
        $data = session($key, []);
        $newPrizeId = ($data['prize_id'] ?? 0) + 1;

        $data = [
            'animation_style' => $style,
            'prize_id' => $newPrizeId,
            'winners' => [],
            'eligible_names' => $names,
            'draw_count' => (int) $drawCount,
            'draw_mode' => $drawMode,
            'is_custom' => $isCustom,
        ];
        session([$key => $data]);

        return response()->json($this->buildPayload($data, $slug));
    }

    public function draw(Request $request, string $slug): JsonResponse
    {
        $style = $this->resolveStyle($slug);
        $key = $this->sessionKey($style);
        $data = $this->ensureSession($style);
        $eligible = $data['eligible_names'];

        if (empty($eligible)) {
            return response()->json([
                'message' => 'no_winners',
                'winners' => [],
            ]);
        }

        $drawCount = min($data['draw_count'] ?? 5, count($eligible));
        $keys = array_rand($eligible, $drawCount);
        if (! is_array($keys)) {
            $keys = [$keys];
        }

        $now = Carbon::now()->toDateTimeString();
        $existingCount = count($data['winners']);
        $newWinners = [];

        foreach ($keys as $i => $key2) {
            $name = $eligible[$key2];
            $winnerId = $existingCount + $i + 1;
            $newWinners[] = [
                'id' => $winnerId,
                'employee_name' => $name,
                'employee_email' => '',
                'employee_phone' => null,
                'sequence' => $winnerId,
                'won_at' => $now,
            ];
        }

        foreach ($keys as $key2) {
            unset($eligible[$key2]);
        }
        $data['eligible_names'] = array_values($eligible);
        $data['winners'] = array_merge($data['winners'], $newWinners);
        session([$key => $data]);

        return response()->json([
            'prize_id' => $data['prize_id'],
            'prize_name' => '範例抽獎',
            'winners' => $newWinners,
        ]);
    }

    public function reset(Request $request, string $slug): JsonResponse
    {
        $style = $this->resolveStyle($slug);
        $key = $this->sessionKey($style);
        $data = $this->ensureSession($style);
        $newPrizeId = $data['prize_id'] + 1;

        $eligibleNames = ($data['is_custom'] ?? false)
            ? array_merge($data['winners'] ? array_map(fn ($w) => $w['employee_name'], $data['winners']) : [], $data['eligible_names'])
            : self::DEMO_NAMES;

        $data = [
            'animation_style' => $style,
            'prize_id' => $newPrizeId,
            'winners' => [],
            'eligible_names' => array_values(array_unique($eligibleNames)),
            'draw_count' => $data['draw_count'] ?? 5,
            'draw_mode' => $data['draw_mode'] ?? 'all_at_once',
            'is_custom' => $data['is_custom'] ?? false,
        ];
        session([$key => $data]);

        return response()->json($this->buildPayload($data, $slug));
    }

    public function ready(Request $request, string $slug): JsonResponse
    {
        $this->resolveStyle($slug);

        return response()->json(['ok' => true, 'ts' => now()->timestamp]);
    }

    public function drawingState(Request $request, string $slug): JsonResponse
    {
        $this->resolveStyle($slug);

        return response()->json(['ok' => true]);
    }

    public function switchAck(Request $request, string $slug): JsonResponse
    {
        $this->resolveStyle($slug);

        return response()->json(['message' => 'ack_ok']);
    }
}
