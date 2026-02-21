@php
    $conn = $statusData['connectionStatus'];
    $proc = $statusData['processStatus'];
    $view = $statusData['currentView'];
    $summary = $statusData['prizeSummary'];
    $progress = $statusData['progress'];
    $suggestion = $statusData['suggestion'];

    $procLabel = match ($proc) {
        'switching' => '切換中',
        'drawing'   => '抽獎中',
        'offline'   => '前台離線',
        'standby'   => '待命',
    };
    $procColor = match ($proc) {
        'switching' => 'warning',
        'drawing'   => 'danger',
        'offline'   => 'gray',
        'standby'   => 'success',
    };
@endphp

<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
     style="min-height: 5rem;">
    <div class="px-4 py-3 space-y-2">
        {{-- Row 1: connection + process + view + progress --}}
        <div class="flex items-center gap-4 flex-wrap text-sm">
            {{-- Connection dot --}}
            <span class="inline-flex items-center gap-1.5">
                @if ($conn === 'online')
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">在線</span>
                @else
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-gray-400"></span>
                    </span>
                    <span class="text-gray-500 dark:text-gray-500">離線</span>
                @endif
            </span>

            <span class="text-gray-300 dark:text-gray-600">|</span>

            {{-- Process badge --}}
            <x-filament::badge :color="$procColor" size="sm">
                {{ $procLabel }}
            </x-filament::badge>

            @if ($view)
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $view }}</span>
            @endif

            @if ($progress)
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <span class="text-gray-600 dark:text-gray-400">
                    已抽 <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $progress['drawn'] }}</span>
                    / {{ $progress['target'] }}
                    <span class="text-xs text-gray-500">({{ $progress['percentage'] }}%)</span>
                </span>
            @endif
        </div>

        {{-- Row 2: prize summary + suggestion --}}
        <div class="flex items-center gap-4 flex-wrap text-sm">
            @if ($summary)
                <span class="text-gray-500 dark:text-gray-400">
                    {{ $summary['draw_mode'] }} · {{ $summary['animation_style'] }} · {{ $summary['lotto_hold_seconds'] }}秒
                </span>
                <span class="text-gray-300 dark:text-gray-600">|</span>
            @endif
            <span class="text-gray-500 dark:text-gray-400 italic">{{ $suggestion }}</span>
        </div>

        {{-- Progress bar --}}
        @if ($progress)
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                <div class="h-1.5 rounded-full transition-all duration-500 {{ $progress['percentage'] >= 100 ? 'bg-green-500' : 'bg-primary-500' }}"
                     style="width: {{ $progress['percentage'] }}%"></div>
            </div>
        @endif
    </div>
</div>
