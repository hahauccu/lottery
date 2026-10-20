<?php

namespace App\Filament\Pages;

use App\Models\DemoMetric;
use App\Support\AnimationStyles;
use Filament\Pages\Page;

class DemoMetricsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = '試玩成效';

    protected static ?string $title = '試玩成效';

    protected static ?string $navigationGroup = '系統管理';

    protected static ?int $navigationSort = 104;

    protected static string $view = 'filament.pages.demo-metrics-page';

    public string $days = '28';

    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    protected function getViewData(): array
    {
        $days = in_array($this->days, ['7', '28'], true) ? (int) $this->days : 28;
        $start = now('Asia/Taipei')->subDays($days - 1)->toDateString();
        $rows = DemoMetric::query()->whereBetween('day', [$start, now('Asia/Taipei')->toDateString()])
            ->selectRaw('event, animation_style, entry, SUM(total) as total, SUM(duration_total_ms) as duration_total_ms')
            ->groupBy('event', 'animation_style', 'entry')->get();
        $styles = [];
        foreach (AnimationStyles::labels() as $key => $label) {
            $find = fn (string $event, ?string $entry = null) => $rows->where('animation_style', $key)->where('event', $event)
                ->when($entry, fn ($items) => $items->where('entry', $entry))->sum('total');
            $styles[] = ['label' => $label, 'views' => $find('page_view', 'intro'), 'clicks' => $find('play_click', 'intro'),
                'starts' => $find('draw_start'), 'completed' => $find('draw_complete'), 'failed' => $find('draw_failed'),
                'register' => $find('register_click', 'intro')];
        }

        return ['styles' => $styles, 'start' => $start, 'end' => now('Asia/Taipei')->toDateString(), 'rows' => $rows];
    }
}
