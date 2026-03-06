<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

    private const VALID_STYLES = [
        'lotto_air',
        'red_packet',
        'scratch_card',
        'treasure_chest',
        'big_treasure_chest',
        'marble_race',
        'battle_top',
    ];

    private function ensureSession(): array
    {
        $data = session('demo_lottery');
        if (!$data) {
            $data = [
                'animation_style' => 'lotto_air',
                'prize_id' => 1,
                'winners' => [],
                'eligible_names' => self::DEMO_NAMES,
            ];
            session(['demo_lottery' => $data]);
        }

        return $data;
    }

    private function buildPayload(array $data): array
    {
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
                'draw_mode' => 'all_at_once',
                'animation_style' => $data['animation_style'],
                'lotto_hold_seconds' => 10,
                'sound_enabled' => true,
                'music_url' => null,
                'winners_count' => count(self::DEMO_NAMES),
                'is_completed' => count($data['winners']) >= count(self::DEMO_NAMES),
                'is_exhausted' => empty($data['eligible_names']),
            ],
            'winners' => $data['winners'],
            'eligible_names' => $data['eligible_names'],
            'all_prizes' => [
                [
                    'id' => 1,
                    'name' => '範例抽獎',
                    'winnersCount' => count(self::DEMO_NAMES),
                    'drawnCount' => count($data['winners']),
                ]
            ],
            'csrfToken' => csrf_token(),
            'drawUrl' => '/demo/lottery/draw',
            'readyUrl' => '/demo/lottery/ready',
            'drawingStateUrl' => '/demo/lottery/drawing-state',
            'switchAckUrl' => '/demo/lottery/switch-ack',
            'winnersUrl' => '/demo/lottery',
            'bgUrl' => null,
            'danmakuEnabled' => false,
        ];
    }

    public function show(Request $request)
    {
        $data = $this->ensureSession();
        $payload = $this->buildPayload($data);

        if ($request->has('payload') || $request->expectsJson()) {
            return response()->json($payload);
        }

        $event = (object) ['name' => '範例抽獎'];
        $currentPrize = (object) ['name' => '範例抽獎'];

        return view('lottery.show', [
            'payload' => $payload,
            'event' => $event,
            'currentPrize' => $currentPrize,
            'currentWinners' => collect(),
            'bgUrl' => null,
            'isDemo' => true,
            'title' => '範例抽獎',
        ]);
    }

    public function draw(Request $request): JsonResponse
    {
        $data = $this->ensureSession();
        $eligible = $data['eligible_names'];

        if (empty($eligible)) {
            return response()->json([
                'message' => 'no_winners',
                'winners' => [],
            ]);
        }

        $drawCount = min(5, count($eligible));
        $keys = array_rand($eligible, $drawCount);
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $now = Carbon::now()->toDateTimeString();
        $existingCount = count($data['winners']);
        $newWinners = [];

        foreach ($keys as $i => $key) {
            $name = $eligible[$key];
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

        // 移除已抽人員
        foreach ($keys as $key) {
            unset($eligible[$key]);
        }
        $data['eligible_names'] = array_values($eligible);
        $data['winners'] = array_merge($data['winners'], $newWinners);
        session(['demo_lottery' => $data]);

        return response()->json([
            'prize_id' => $data['prize_id'],
            'prize_name' => '範例抽獎',
            'winners' => $newWinners,
        ]);
    }

    public function setStyle(Request $request): JsonResponse
    {
        $request->validate([
            'style' => 'required|in:' . implode(',', self::VALID_STYLES),
        ]);

        $data = $this->ensureSession();
        $data['animation_style'] = $request->input('style');
        $data['prize_id'] += 1;
        $data['winners'] = [];
        $data['eligible_names'] = self::DEMO_NAMES;
        session(['demo_lottery' => $data]);

        return response()->json($this->buildPayload($data));
    }

    public function reset(): JsonResponse
    {
        $data = $this->ensureSession();
        $newPrizeId = $data['prize_id'] + 1;

        $data = [
            'animation_style' => $data['animation_style'],
            'prize_id' => $newPrizeId,
            'winners' => [],
            'eligible_names' => self::DEMO_NAMES,
        ];
        session(['demo_lottery' => $data]);

        return response()->json($this->buildPayload($data));
    }

    public function ready(): JsonResponse
    {
        return response()->json(['ok' => true, 'ts' => now()->timestamp]);
    }

    public function drawingState(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    public function switchAck(): JsonResponse
    {
        return response()->json(['message' => 'ack_ok']);
    }
}
