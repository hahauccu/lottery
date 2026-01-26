<?php

namespace App\Http\Controllers;

use App\Events\PrizeWinnersUpdated;
use App\Models\LotteryEvent;
use App\Models\Prize;
use App\Services\EligibleEmployeesService;
use App\Services\LotteryDrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $payload = [
            'brandCode' => $event->brand_code,
            'eventId' => $event->id,
            'eventName' => $event->name,
            'isOpen' => $event->is_lottery_open,
            'currentPrize' => $currentPrize ? [
                'id' => $currentPrize->id,
                'name' => $currentPrize->name,
                'drawMode' => $currentPrize->draw_mode,
                'animationStyle' => $currentPrize->animation_style,
                'lottoHoldSeconds' => $currentPrize->lotto_hold_seconds,
                'musicUrl' => $musicUrl,
                'winnersCount' => $currentPrize->winners_count,
                'isCompleted' => $currentPrize->winners()->count() >= $currentPrize->winners_count,
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
            'csrfToken' => csrf_token(),
            'drawUrl' => route('lottery.draw', ['brandCode' => $event->brand_code]),
        ];

        if ($request->boolean('payload')) {
            return response()->json([
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'brand_code' => $event->brand_code,
                    'is_lottery_open' => $event->is_lottery_open,
                    'current_prize_id' => $event->current_prize_id,
                ],
                'current_prize' => $currentPrize ? [
                    'id' => $currentPrize->id,
                    'name' => $currentPrize->name,
                    'draw_mode' => $currentPrize->draw_mode,
                    'animation_style' => $currentPrize->animation_style,
                    'lotto_hold_seconds' => $currentPrize->lotto_hold_seconds,
                    'music_url' => $musicUrl,
                    'winners_count' => $currentPrize->winners_count,
                    'is_completed' => $currentPrize->winners()->count() >= $currentPrize->winners_count,
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

    public function winners(string $brandCode): View
    {
        $event = LotteryEvent::query()
            ->where('brand_code', $brandCode)
            ->firstOrFail();

        $prizes = Prize::query()
            ->where('lottery_event_id', $event->id)
            ->with(['winners.employee'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('lottery.winners', [
            'event' => $event,
            'prizes' => $prizes,
        ]);
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
}
