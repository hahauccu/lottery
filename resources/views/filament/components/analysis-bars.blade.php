@php
    // Render with the result so Livewire polling updates the bars without a JS lifecycle or CDN.
    $values = collect($rows)->flatMap(fn ($row) => collect($series)->map(fn ($s) => (float) ($row[$s['key']] ?? 0) * $s['factor']));
    $minimum = min(0, $values->min() ?? 0);
    $maximum = max(0, $values->max() ?? 0);
    $range = max($maximum - $minimum, 1);
    $origin = 180 + (0 - $minimum) / $range * 440;
    $rowHeight = count($series) * 28 + 24;
    $height = count($rows) * $rowHeight + 32;
@endphp
<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" data-analysis-chart>
    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $title }}</h2>
    <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-300">
        @foreach ($series as $s)
            <span><span style="color:{{ $s['color'] }}" aria-hidden="true">●</span> {{ $s['label'] }}</span>
        @endforeach
    </div>
    <svg viewBox="0 0 720 {{ $height }}" role="img" aria-label="{{ $title }}" style="display:block;width:100%;height:auto;margin-top:16px" class="text-gray-700 dark:text-gray-200">
        <line x1="{{ $origin }}" x2="{{ $origin }}" y1="0" y2="{{ $height - 20 }}" stroke="currentColor" opacity="0.25" />
        <text x="{{ $origin }}" y="{{ $height - 3 }}" fill="currentColor" font-size="13" text-anchor="middle">0</text>
        @foreach ($rows as $row)
            @php
                $top = $loop->index * $rowHeight;
            @endphp
            <text x="0" y="{{ $top + 20 }}" fill="currentColor" font-size="15">{{ mb_strimwidth($row[$labelKey] ?? '', 0, 20, '…') }}</text>
            @foreach ($series as $s)
                @php
                    $value = (float) ($row[$s['key']] ?? 0) * $s['factor'];
                    $end = 180 + ($value - $minimum) / $range * 440;
                    $y = $top + $loop->index * 28 + 5;
                    $color = $value < 0 ? '#f87171' : $s['color'];
                @endphp
                <rect x="{{ min($origin, $end) }}" y="{{ $y }}" width="{{ abs($end - $origin) }}" height="20" rx="3" fill="{{ $color }}">
                    <title>{{ $row[$labelKey] ?? '' }} · {{ $s['label'] }}：{{ number_format($value, 1) }}{{ $unit }}</title>
                </rect>
                <text x="{{ $value < 0 ? $end - 6 : $end + 6 }}" y="{{ $y + 15 }}" text-anchor="{{ $value < 0 ? 'end' : 'start' }}" fill="currentColor" font-size="14">{{ number_format($value, 1) }}{{ $unit }}</text>
            @endforeach
        @endforeach
    </svg>
</section>
