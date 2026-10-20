<?php

namespace App\Http\Controllers;

use App\Models\DemoMetric;
use App\Support\AnimationStyles;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class DemoMetricController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'event' => ['required', Rule::in(array_keys(DemoMetric::EVENTS))],
            'animation_style' => ['required', Rule::in(['all', ...array_keys(AnimationStyles::labels())])],
            'entry' => ['required', Rule::in(array_keys(DemoMetric::ENTRIES))],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);
        $event = $data['event'];
        $isDraw = str_starts_with($event, 'draw_');
        abort_if($isDraw && ($data['entry'] !== 'play' || $data['animation_style'] === 'all'), 422);
        abort_if(in_array($event, ['play_click', 'register_click'], true) && $data['entry'] === 'play', 422);
        abort_if($data['entry'] === 'intro' && $data['animation_style'] === 'all', 422);

        DemoMetric::record($event, $data['animation_style'], $data['entry'],
            in_array($event, ['draw_complete', 'draw_failed'], true) ? ($data['duration_ms'] ?? 0) : 0);

        return response()->noContent()->header('X-Robots-Tag', 'noindex');
    }
}
