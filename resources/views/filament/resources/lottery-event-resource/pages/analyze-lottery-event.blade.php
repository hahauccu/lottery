<x-filament-panels::page>
    @php
        $run = $analysisRun;
        $status = $run?->status ?? 'idle';
        $progress = (int) ($run?->progress ?? 0);
        $busy = in_array($status, ['queued', 'running'], true);
        $statusLabel = match ($status) {
            'queued' => '排隊中',
            'running' => '分析中',
            'completed' => '完成',
            'failed' => '失敗',
            default => '尚未開始',
        };
        $message = match ($status) {
            'queued' => '任務已送出，等待處理',
            'running' => '分析進行中，請稍候',
            'completed' => '分析完成，可下載結果',
            'failed' => $run?->error_message ?: '分析失敗，請稍後再試',
            default => '最多可模擬 1000 次',
        };
        $prizes = $analysisResult['prizes'] ?? [];
        $departments = $analysisResult['departments'] ?? [];
    @endphp

    <div class="space-y-6" wire:poll.2000ms="refreshRun">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">抽獎活動</div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $this->record->name }}</div>
                </div>
                <div class="rounded-full border border-gray-200 px-3 py-1 text-xs font-semibold text-gray-600 dark:border-white/10 dark:text-gray-300">
                    {{ $statusLabel }}
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end">
                <label class="flex w-full flex-col gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 sm:w-40">
                    模擬次數
                    <input
                        wire:model.defer="iterations"
                        type="number"
                        min="1"
                        max="1000"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-950 dark:text-white"
                    />
                </label>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="startAnalysis"
                        @disabled($busy)
                        class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        開始分析
                    </button>
                    @if ($this->downloadUrl)
                        <a
                            href="{{ $this->downloadUrl }}"
                            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-primary-400 hover:text-primary-600 dark:border-white/10 dark:bg-gray-900 dark:text-gray-200"
                        >
                            下載 CSV
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <progress
                    class="h-2 w-full overflow-hidden rounded-full bg-gray-200 text-primary-500 dark:bg-white/10"
                    value="{{ $progress }}"
                    max="100"
                ></progress>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $message }}</div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">獎項統計</div>
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-2 text-left">獎項</th>
                                <th class="px-4 py-2 text-right">中獎率</th>
                                <th class="px-4 py-2 text-right">平均可抽</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-700 dark:divide-white/10 dark:text-gray-200">
                            @forelse ($prizes as $item)
                                <tr>
                                    <td class="px-4 py-2">{{ $item['name'] ?? '' }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format(($item['win_rate'] ?? 0) * 100, 1) }}%</td>
                                    <td class="px-4 py-2 text-right">{{ number_format((int) round($item['eligible_avg'] ?? 0)) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-sm text-gray-400" colspan="3">尚無分析結果</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">部門比較</div>
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-2 text-left">部門</th>
                                <th class="px-4 py-2 text-right">可抽占比</th>
                                <th class="px-4 py-2 text-right">中獎占比</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-700 dark:divide-white/10 dark:text-gray-200">
                            @forelse ($departments as $item)
                                <tr>
                                    <td class="px-4 py-2">{{ $item['department'] ?? '' }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format(($item['eligible_pct'] ?? 0) * 100, 1) }}%</td>
                                    <td class="px-4 py-2 text-right">{{ number_format(($item['win_pct'] ?? 0) * 100, 1) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-6 text-center text-sm text-gray-400" colspan="3">尚無分析結果</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
