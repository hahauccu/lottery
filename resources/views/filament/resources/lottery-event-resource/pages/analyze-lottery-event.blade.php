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
                            下載 Excel
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

        @if (count($prizes) > 0 || count($departments) > 0)
            <div class="grid gap-6 lg:grid-cols-2">
                @if (count($prizes) > 0)
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">獎項中獎率 (%)</div>
                        <div class="mt-4">
                            <canvas id="chartPrizeWinRate" height="260"></canvas>
                        </div>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">可抽人數 vs 中獎人數</div>
                        <div class="mt-4">
                            <canvas id="chartPrizeEligibleWinners" height="260"></canvas>
                        </div>
                    </div>
                @endif
                @if (count($departments) > 0)
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">部門可抽占比 vs 中獎占比 (%)</div>
                        <div class="mt-4">
                            <canvas id="chartDeptShare" height="260"></canvas>
                        </div>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">部門占比差異 (%)</div>
                        <div class="mt-4">
                            <canvas id="chartDeptDelta" height="260"></canvas>
                        </div>
                    </div>
                @endif
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const prizes = @json($prizes);
                    const departments = @json($departments);
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#d1d5db' : '#374151';
                    const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

                    const baseScales = {
                        x: { ticks: { color: textColor }, grid: { color: gridColor } },
                        y: { ticks: { color: textColor }, grid: { color: gridColor } },
                    };

                    if (prizes.length > 0) {
                        const prizeNames = prizes.map(p => p.name ?? '');
                        const winRates = prizes.map(p => +(((p.win_rate ?? 0) * 100).toFixed(2)));
                        const eligibleAvgs = prizes.map(p => Math.round(p.eligible_avg ?? 0));
                        const winnersCounts = prizes.map(p => p.winners_count ?? 0);

                        new Chart(document.getElementById('chartPrizeWinRate'), {
                            type: 'bar',
                            data: {
                                labels: prizeNames,
                                datasets: [{
                                    label: '中獎率 (%)',
                                    data: winRates,
                                    backgroundColor: 'rgba(99,102,241,0.7)',
                                    borderRadius: 4,
                                }],
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { ...baseScales.x, title: { display: true, text: '%', color: textColor } },
                                    y: { ...baseScales.y },
                                },
                            },
                        });

                        new Chart(document.getElementById('chartPrizeEligibleWinners'), {
                            type: 'bar',
                            data: {
                                labels: prizeNames,
                                datasets: [
                                    {
                                        label: '平均可抽人數',
                                        data: eligibleAvgs,
                                        backgroundColor: 'rgba(99,102,241,0.5)',
                                        borderRadius: 4,
                                    },
                                    {
                                        label: '中獎人數',
                                        data: winnersCounts,
                                        backgroundColor: 'rgba(234,88,12,0.7)',
                                        borderRadius: 4,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                plugins: { legend: { labels: { color: textColor } } },
                                scales: baseScales,
                            },
                        });
                    }

                    if (departments.length > 0) {
                        const deptNames = departments.map(d => d.department ?? '');
                        const eligiblePcts = departments.map(d => +(((d.eligible_pct ?? 0) * 100).toFixed(2)));
                        const winPcts = departments.map(d => +(((d.win_pct ?? 0) * 100).toFixed(2)));
                        const deltaPcts = departments.map(d => +(((d.delta_pct ?? 0) * 100).toFixed(2)));

                        new Chart(document.getElementById('chartDeptShare'), {
                            type: 'bar',
                            data: {
                                labels: deptNames,
                                datasets: [
                                    {
                                        label: '可抽占比 (%)',
                                        data: eligiblePcts,
                                        backgroundColor: 'rgba(99,102,241,0.5)',
                                        borderRadius: 4,
                                    },
                                    {
                                        label: '中獎占比 (%)',
                                        data: winPcts,
                                        backgroundColor: 'rgba(234,88,12,0.7)',
                                        borderRadius: 4,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                plugins: { legend: { labels: { color: textColor } } },
                                scales: baseScales,
                            },
                        });

                        new Chart(document.getElementById('chartDeptDelta'), {
                            type: 'bar',
                            data: {
                                labels: deptNames,
                                datasets: [{
                                    label: '占比差異 (%)',
                                    data: deltaPcts,
                                    backgroundColor: deltaPcts.map(v => v >= 0 ? 'rgba(34,197,94,0.7)' : 'rgba(239,68,68,0.7)'),
                                    borderRadius: 4,
                                }],
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                plugins: { legend: { display: false } },
                                scales: {
                                    x: { ...baseScales.x, title: { display: true, text: '%', color: textColor } },
                                    y: { ...baseScales.y },
                                },
                            },
                        });
                    }
                });
            </script>
        @endif
    </div>
</x-filament-panels::page>
