<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-center gap-4">
            <label for="metric-days">統計期間（台灣時間）</label>
            <select id="metric-days" wire:model.live="days" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                <option value="7">近 7 天</option><option value="28">近 28 天</option>
            </select>
            <span class="text-sm text-gray-500">{{ $start }} ～ {{ $end }}</span>
        </div>
        <p class="mt-3 text-sm text-gray-500">以下為事件次數，不是獨立訪客數或同一批訪客的轉換率。瀏覽器拒絕追蹤、網路失敗及跨日操作可能影響統計。無事件時顯示 0，需開始收集資料後才能建立基準。</p>
    </x-filament::section>
    <x-filament::section heading="各動畫的介紹與試玩">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-3">動畫</th><th class="p-3">介紹瀏覽</th><th class="p-3">介紹→試玩點擊</th><th class="p-3">抽獎開始</th><th class="p-3">動畫完成</th><th class="p-3">未完成／錯誤</th><th class="p-3">註冊入口點擊</th></tr></thead>
                <tbody>@foreach ($styles as $style)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        @foreach ($style as $value)<td class="p-3">{{ $value }}</td>@endforeach
                    </tr>
                @endforeach</tbody>
            </table>
        </div>
        <p class="mt-3 text-sm text-gray-500">「註冊入口點擊」不代表註冊成功。抽獎結果保護性收尾會記為未完成／錯誤，仍會呈現伺服器結果。</p>
    </x-filament::section>
    <x-filament::section heading="全部入口事件">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr class="border-b"><th class="p-3">入口</th><th class="p-3">動畫</th><th class="p-3">事件</th><th class="p-3">次數</th></tr></thead>
            <tbody>@forelse ($rows as $row)<tr class="border-b border-gray-100 dark:border-gray-800">
                <td class="p-3">{{ \App\Models\DemoMetric::ENTRIES[$row->entry] }}</td>
                <td class="p-3">{{ \App\Support\AnimationStyles::labels()[$row->animation_style] ?? '全部動畫' }}</td>
                <td class="p-3">{{ \App\Models\DemoMetric::EVENTS[$row->event] }}</td><td class="p-3">{{ $row->total }}</td>
            </tr>@empty<tr><td class="p-3" colspan="4">尚無事件資料</td></tr>@endforelse</tbody>
        </table></div>
    </x-filament::section>
</x-filament-panels::page>
