<?php

namespace App\Http\Controllers;

use App\Events\DanmakuSent;
use App\Events\PrizeWinnersUpdated;
use App\Models\Employee;
use App\Models\LotteryEvent;
use App\Models\Prize;
use App\Services\EligibleEmployeesService;
use App\Services\LotteryDrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LotteryFrontendController extends Controller
{
    public function show(Request $request, string $brandCode): View|JsonResponse
    {
        $event = LotteryEvent::query()
            ->where('brand_code', $brandCode)
            ->with(['currentPrize.winners.employee'])
            ->firstOrFail();

        $currentPrize = $event->currentPrize;

        $winners = $currentPrize?->winners ?? collect();

        $bgPath = $currentPrize?->bg_image_path ?: $event->default_bg_image_path;
        $bgUrl = $bgPath ? asset('storage/'.$bgPath) : null;
        $musicUrl = $currentPrize?->music_path
            ? asset('storage/'.$currentPrize->music_path)
            : null;

        $eligibleNames = $currentPrize
            ? app(EligibleEmployeesService::class)
                ->eligibleForStoredPrize($currentPrize)
                ->pluck('name')
                ->values()
                ->all()
            : [];

        $allPrizes = $event->prizes()
            ->withCount(['winners as drawn_count'])
            ->get()
            ->map(fn ($prize) => [
                'id' => $prize->id,
                'name' => $prize->name,
                'winnersCount' => $prize->winners_count,
                'drawnCount' => $prize->drawn_count,
            ])->all();

        // 檢查可抽人數是否用盡（名額未滿但沒有人可抽）
        $drawnCount = $currentPrize?->winners()->count() ?? 0;
        $isExhausted = $currentPrize && $drawnCount < $currentPrize->winners_count && empty($eligibleNames);

        $isTestMode = $event->organization->isTestMode();

        $payload = [
            'brandCode' => $event->brand_code,
            'eventId' => $event->id,
            'eventName' => $event->name,
            'isOpen' => $event->is_lottery_open,
            'isTestMode' => $isTestMode,
            'showPrizesPreview' => $event->show_prizes_preview,
            'allPrizes' => $allPrizes,
            'currentPrize' => $currentPrize ? [
                'id' => $currentPrize->id,
                'name' => $currentPrize->name,
                'drawMode' => $currentPrize->draw_mode,
                'animationStyle' => $currentPrize->animation_style,
                'lottoHoldSeconds' => $currentPrize->lotto_hold_seconds,
                'soundEnabled' => $currentPrize->sound_enabled,
                'musicUrl' => $musicUrl,
                'winnersCount' => $currentPrize->winners_count,
                'isCompleted' => $drawnCount >= $currentPrize->winners_count,
                'isExhausted' => $isExhausted,
            ] : null,
            'winners' => $winners->map(fn ($winner) => [
                'id' => $winner->id,
                'employee_name' => $winner->employee?->name,
                'employee_email' => $winner->employee?->email,
                'employee_phone' => $winner->employee?->phone,
                'sequence' => $winner->sequence,
                'won_at' => optional($winner->won_at)->toDateTimeString(),
            ])->values()->all(),
            'eligibleNames' => $eligibleNames,
            'danmakuEnabled' => $event->danmaku_enabled,
            'csrfToken' => csrf_token(),
            'drawUrl' => route('lottery.draw', ['brandCode' => $event->brand_code]),
            'winnersUrl' => route('lottery.winners', ['brandCode' => $event->brand_code]),
        ];

        if ($request->boolean('payload')) {
            return response()->json([
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'brand_code' => $event->brand_code,
                    'is_lottery_open' => $event->is_lottery_open,
                    'is_test_mode' => $isTestMode,
                    'show_prizes_preview' => $event->show_prizes_preview,
                    'current_prize_id' => $event->current_prize_id,
                ],
                'all_prizes' => $allPrizes,
                'current_prize' => $currentPrize ? [
                    'id' => $currentPrize->id,
                    'name' => $currentPrize->name,
                    'draw_mode' => $currentPrize->draw_mode,
                    'animation_style' => $currentPrize->animation_style,
                    'lotto_hold_seconds' => $currentPrize->lotto_hold_seconds,
                    'sound_enabled' => $currentPrize->sound_enabled,
                    'music_url' => $musicUrl,
                    'winners_count' => $currentPrize->winners_count,
                    'is_completed' => $drawnCount >= $currentPrize->winners_count,
                    'is_exhausted' => $isExhausted,
                ] : null,
                'winners' => $payload['winners'],
                'eligible_names' => $eligibleNames,
            ]);
        }

        return view('lottery.show', [
            'event' => $event,
            'currentPrize' => $currentPrize,
            'currentWinners' => $winners,
            'bgUrl' => $bgUrl,
            'payload' => $payload,
        ]);
    }

    public function winners(string $brandCode): Response
    {
        $cacheKey = "winners:{$brandCode}";

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($brandCode) {
            $event = LotteryEvent::query()
                ->where('brand_code', $brandCode)
                ->firstOrFail();

            $prizes = Prize::query()
                ->where('lottery_event_id', $event->id)
                ->with(['winners.employee'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return ['event' => $event, 'prizes' => $prizes];
        });

        return response()
            ->view('lottery.winners', $data)
            ->header('Cache-Control', 'public, max-age=60');
    }

    public function draw(Request $request, string $brandCode): JsonResponse
    {
        $event = LotteryEvent::query()
            ->where('brand_code', $brandCode)
            ->with('currentPrize')
            ->firstOrFail();

        if (! $event->is_lottery_open) {
            return response()->json(['message' => 'lottery_closed'], 403);
        }

        if (! $event->currentPrize) {
            return response()->json(['message' => 'no_prize_selected'], 422);
        }

        $winners = app(LotteryDrawService::class)->drawCurrentPrize($event);

        if ($winners->isEmpty()) {
            return response()->json(['message' => 'no_winners', 'winners' => []]);
        }

        event(new PrizeWinnersUpdated($event->brand_code, $event->currentPrize, $winners));

        // 清除 winners 頁面快取
        Cache::forget("winners:{$brandCode}");

        return response()->json([
            'prize_id' => $event->currentPrize->id,
            'prize_name' => $event->currentPrize->name,
            'winners' => $winners->map(fn ($winner) => [
                'id' => $winner->id,
                'employee_name' => $winner->employee?->name,
                'employee_email' => $winner->employee?->email,
                'employee_phone' => $winner->employee?->phone,
                'sequence' => $winner->sequence,
                'won_at' => optional($winner->won_at)->toDateTimeString(),
            ])->values()->all(),
        ]);
    }

    public function sendDanmaku(Request $request, string $brandCode): JsonResponse
    {
        $event = LotteryEvent::where('brand_code', $brandCode)->firstOrFail();

        if (! $event->danmaku_enabled) {
            return response()->json(['message' => 'danmaku_disabled'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'required|string|min:1|max:100',
        ]);

        $employee = Employee::query()
            ->where('organization_id', $event->organization_id)
            ->where('email', $validated['email'])
            ->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'email' => '找不到對應的員工資料，請確認您的 Email 正確',
            ]);
        }

        $rateLimitKey = 'danmaku:'.$brandCode.':'.$validated['email'];

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            throw ValidationException::withMessages([
                'message' => "發送太頻繁，請等待 {$seconds} 秒後再試",
            ]);
        }

        RateLimiter::hit($rateLimitKey, 10);

        $cleanMessage = strip_tags($validated['message']);
        $cleanMessage = Str::limit($cleanMessage, 100, '');

        event(new DanmakuSent(
            $brandCode,
            $employee->name,
            $cleanMessage,
            Str::uuid()->toString()
        ));

        return response()->json([
            'success' => true,
            'cooldown_seconds' => 10,
        ]);
    }
}
