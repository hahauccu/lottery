<?php

namespace App\Http\Controllers;

use App\Events\PrizeWinnersUpdated;
use App\Models\LotteryEvent;
use App\Models\Prize;
use App\Services\LotteryDrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LotteryFrontendController extends Controller
{
    public function show(string $brandCode): View
    {
        $event = LotteryEvent::query()
            ->where('brand_code', $brandCode)
            ->with(['currentPrize.winners.employee'])
            ->firstOrFail();

        $currentPrize = $event->currentPrize;

        $winners = $currentPrize?->winners ?? collect();

        $bgPath = $currentPrize?->bg_image_path ?: $event->default_bg_image_path;
        $bgUrl = $bgPath ? Storage::disk('public')->url($bgPath) : null;

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
            ] : null,
            'winners' => $winners->map(fn ($winner) => [
                'id' => $winner->id,
                'employee_name' => $winner->employee?->name,
                'employee_email' => $winner->employee?->email,
                'employee_phone' => $winner->employee?->phone,
            ])->values()->all(),
            'csrfToken' => csrf_token(),
            'drawUrl' => route('lottery.draw', ['brandCode' => $event->brand_code]),
        ];

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
