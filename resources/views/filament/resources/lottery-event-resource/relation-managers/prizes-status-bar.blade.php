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
    $procBgClass = match ($proc) {
        'switching' => 'bg-amber-50 ring-amber-200 dark:bg-amber-950/30 dark:ring-amber-800',
        'drawing'   => 'bg-red-50 ring-red-200 dark:bg-red-950/30 dark:ring-red-800',
        'offline'   => 'bg-gray-50 ring-gray-200 dark:bg-gray-800/50 dark:ring-gray-700',
        'standby'   => 'bg-emerald-50 ring-emerald-200 dark:bg-emerald-950/30 dark:ring-emerald-800',
    };
@endphp

<div class="rounded-xl shadow-sm ring-1 {{ $procBgClass }}" style="min-height: 5.5rem;">
    <div class="px-5 py-4 space-y-3">
        {{-- Row 1: connection + process badge + current view + progress --}}
        <div class="flex items-center gap-5 flex-wrap">
            {{-- Connection dot --}}
            <span class="inline-flex items-center gap-2">
                @if ($conn === 'online')
                    <span class="relative flex h-3.5 w-3.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-green-500"></span>
                    </span>
                    <span class="text-base font-medium text-green-700 dark:text-green-400">在線</span>
                @else
                    <span class="relative flex h-3.5 w-3.5">
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-gray-400"></span>
                    </span>
                    <span class="text-base font-medium text-gray-500 dark:text-gray-400">離線</span>
                @endif
            </span>

            <span class="text-gray-300 dark:text-gray-600 text-lg">|</span>

            {{-- Process badge --}}
            <x-filament::badge :color="$procColor" size="lg">
                {{ $procLabel }}
            </x-filament::badge>

            @if ($view)
                <span class="text-gray-300 dark:text-gray-600 text-lg">|</span>
                <span class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ $view }}</span>
            @endif

            @if ($progress)
                <span class="text-gray-300 dark:text-gray-600 text-lg">|</span>
                <span class="text-base text-gray-700 dark:text-gray-300">
                    已抽
                    <span class="font-bold text-lg text-gray-900 dark:text-white">{{ $progress['drawn'] }}</span>
                    <span class="text-gray-500">/ {{ $progress['target'] }}</span>
                    <span class="ml-1 text-sm font-semibold {{ $progress['percentage'] >= 100 ? 'text-green-600 dark:text-green-400' : 'text-primary-600 dark:text-primary-400' }}">
                        {{ $progress['percentage'] }}%
                    </span>
                </span>
            @endif
        </div>

        {{-- Row 2: prize summary + suggestion --}}
        <div class="flex items-center gap-4 flex-wrap text-sm">
            @if ($summary)
                <span class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <x-filament::badge color="gray" size="sm">{{ $summary['draw_mode'] }}</x-filament::badge>
                    <x-filament::badge color="gray" size="sm">{{ $summary['animation_style'] }}</x-filament::badge>
                    <x-filament::badge color="gray" size="sm">{{ $summary['lotto_hold_seconds'] }}秒</x-filament::badge>
                </span>
                <span class="text-gray-300 dark:text-gray-600">|</span>
            @endif
            <span class="text-gray-500 dark:text-gray-400 italic text-sm">{{ $suggestion }}</span>
        </div>

        {{-- Progress bar --}}
        @if ($progress)
            <div class="w-full bg-white/60 dark:bg-gray-700/60 rounded-full h-2.5 overflow-hidden">
                <div class="h-2.5 rounded-full transition-all duration-500 {{ $progress['percentage'] >= 100 ? 'bg-green-500' : 'bg-primary-500' }}"
                     style="width: {{ $progress['percentage'] }}%"></div>
            </div>
        @endif
    </div>
</div>
