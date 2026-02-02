<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 篩選表單 --}}
        <x-filament::section>
            <x-slot name="heading">
                篩選條件
            </x-slot>

            <form wire:submit.prevent class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">開始日期</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="startDate"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">結束日期</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="endDate"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">日誌類型</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="logType">
                            <option value="">全部類型</option>
                            <option value="checkout">發起付款</option>
                            <option value="notify">背景通知</option>
                            <option value="notify_result">通知處理結果</option>
                            <option value="result">前台返回</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        共 {{ $totalCount }} 筆記錄
                    </span>
                </div>
            </form>
        </x-filament::section>

        {{-- 日誌列表 --}}
        <x-filament::section>
            <x-slot name="heading">
                日誌記錄
            </x-slot>

            @if($logs->isEmpty())
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-document-magnifying-glass class="mx-auto h-12 w-12 mb-4" />
                    <p>沒有找到符合條件的日誌記錄</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($logs as $index => $log)
                        <div
                            x-data="{ expanded: false }"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                        >
                            {{-- 摘要列 --}}
                            <div
                                @click="expanded = !expanded"
                                class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                <div class="flex items-center gap-4 flex-1 min-w-0">
                                    {{-- 時間 --}}
                                    <div class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($log['time'])->format('Y-m-d H:i:s') }}
                                    </div>

                                    {{-- 類型標籤 --}}
                                    @php
                                        $typeConfig = match($log['type']) {
                                            'checkout' => ['label' => '發起付款', 'color' => 'info'],
                                            'notify' => ['label' => '背景通知', 'color' => 'warning'],
                                            'notify_result' => ['label' => '通知處理', 'color' => 'gray'],
                                            'result' => ['label' => '前台返回', 'color' => 'success'],
                                            default => ['label' => $log['type'], 'color' => 'gray'],
                                        };
                                    @endphp
                                    <x-filament::badge :color="$typeConfig['color']">
                                        {{ $typeConfig['label'] }}
                                    </x-filament::badge>

                                    {{-- 訂單編號 --}}
                                    @if($log['merchant_trade_no'])
                                        <div class="font-mono text-sm truncate">
                                            {{ $log['merchant_trade_no'] }}
                                        </div>
                                    @endif

                                    {{-- 金額 --}}
                                    @if($log['amount'])
                                        <div class="text-sm font-medium">
                                            NT$ {{ number_format($log['amount']) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-3">
                                    {{-- 狀態 --}}
                                    @php
                                        $statusConfig = match($log['status']) {
                                            'pending' => ['label' => '等待中', 'color' => 'warning'],
                                            'success' => ['label' => '成功', 'color' => 'success'],
                                            'failed' => ['label' => '失敗', 'color' => 'danger'],
                                            default => ['label' => '未知', 'color' => 'gray'],
                                        };
                                    @endphp
                                    <x-filament::badge :color="$statusConfig['color']">
                                        {{ $statusConfig['label'] }}
                                    </x-filament::badge>

                                    {{-- 展開圖示 --}}
                                    <x-heroicon-m-chevron-down
                                        class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                        x-bind:class="{ 'rotate-180': expanded }"
                                    />
                                </div>
                            </div>

                            {{-- 詳細資料 --}}
                            <div
                                x-show="expanded"
                                x-collapse
                                class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
                            >
                                <div class="p-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        詳細資料
                                    </h4>
                                    <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded-lg overflow-x-auto max-h-96">{{ json_encode($log['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- 分頁 --}}
                @if($lastPage > 1)
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            第 {{ $currentPage }} 頁，共 {{ $lastPage }} 頁
                        </div>
                        <div class="flex items-center gap-2">
                            <x-filament::button
                                wire:click="previousPage"
                                :disabled="$currentPage <= 1"
                                size="sm"
                                color="gray"
                            >
                                上一頁
                            </x-filament::button>

                            @php
                                // 計算要顯示的頁碼範圍
                                $start = max(1, $currentPage - 2);
                                $end = min($lastPage, $currentPage + 2);
                            @endphp

                            @for($i = $start; $i <= $end; $i++)
                                <x-filament::button
                                    wire:click="goToPage({{ $i }})"
                                    size="sm"
                                    :color="$i === $currentPage ? 'primary' : 'gray'"
                                >
                                    {{ $i }}
                                </x-filament::button>
                            @endfor

                            <x-filament::button
                                wire:click="nextPage"
                                :disabled="$currentPage >= $lastPage"
                                size="sm"
                                color="gray"
                            >
                                下一頁
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
