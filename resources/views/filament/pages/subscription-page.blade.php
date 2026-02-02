<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 當前訂閱狀態 --}}
        <x-filament::section>
            <x-slot name="heading">
                目前狀態
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">員工數</div>
                    <div class="text-2xl font-semibold">{{ number_format($employeeCount) }} 人</div>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">當前方案</div>
                    <div class="text-2xl font-semibold">
                        @if($plan)
                            {{ $plan->name }}
                        @else
                            <span class="text-gray-400">無</span>
                        @endif
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">到期日</div>
                    <div class="text-2xl font-semibold">
                        @if($subscription)
                            <span class="{{ $subscription->expires_at->isPast() ? 'text-danger-500' : '' }}">
                                {{ $subscription->expires_at->format('Y-m-d') }}
                            </span>
                            @if($subscription->expires_at->isFuture())
                                <span class="text-sm text-gray-500">（剩餘 {{ $subscription->daysRemaining() }} 天）</span>
                            @else
                                <span class="text-sm text-danger-500">（已過期）</span>
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($isTestMode)
                <div class="mt-4 p-4 rounded-lg bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800">
                    <div class="flex items-center gap-2 text-warning-600 dark:text-warning-400">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                        <span class="font-medium">目前為測試模式</span>
                    </div>
                    <p class="mt-1 text-sm text-warning-600 dark:text-warning-400">
                        前台抽獎頁面將顯示「抽獎測試中」浮水印。請購買適合的方案以解除限制。
                    </p>
                </div>
            @endif
        </x-filament::section>

        {{-- 可購買方案 --}}
        <x-filament::section>
            <x-slot name="heading">
                可購買方案
            </x-slot>

            @if($availablePlans->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-face-frown class="w-12 h-12 mx-auto mb-2" />
                    <p>目前沒有適合的方案</p>
                    <p class="text-sm">您的員工數量超過所有方案上限，請聯繫客服。</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($availablePlans as $availablePlan)
                        <div class="border rounded-lg p-6 {{ $plan && $plan->id === $availablePlan->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold">{{ $availablePlan->name }}</h3>
                                    <span class="text-sm text-gray-500">{{ $availablePlan->code }}</span>
                                </div>
                                @if($plan && $plan->id === $availablePlan->id)
                                    <span class="px-2 py-1 text-xs rounded-full bg-primary-500 text-white">目前方案</span>
                                @endif
                            </div>

                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">員工上限</span>
                                    <span class="font-medium">{{ number_format($availablePlan->max_employees) }} 人</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">有效天數</span>
                                    <span class="font-medium">{{ $availablePlan->duration_days }} 天</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">價格</span>
                                    <span class="font-semibold text-lg">
                                        @if($availablePlan->price > 0)
                                            NT$ {{ number_format($availablePlan->price) }}
                                        @else
                                            免費
                                        @endif
                                    </span>
                                </div>
                            </div>

                            @if($availablePlan->description)
                                <p class="text-sm text-gray-500 mb-4">{{ $availablePlan->description }}</p>
                            @endif

                            <x-filament::button
                                wire:click="purchasePlan({{ $availablePlan->id }})"
                                wire:loading.attr="disabled"
                                class="w-full"
                                :color="$plan && $plan->id === $availablePlan->id ? 'gray' : 'primary'"
                            >
                                <span wire:loading.remove wire:target="purchasePlan({{ $availablePlan->id }})">
                                    @if($plan && $plan->id === $availablePlan->id)
                                        續訂
                                    @elseif($availablePlan->price > 0)
                                        購買
                                    @else
                                        啟用
                                    @endif
                                </span>
                                <span wire:loading wire:target="purchasePlan({{ $availablePlan->id }})">
                                    處理中...
                                </span>
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- 付款記錄 --}}
        @if($recentPayments->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    最近付款記錄
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-2 text-left text-gray-500 dark:text-gray-400">訂單編號</th>
                                <th class="px-4 py-2 text-left text-gray-500 dark:text-gray-400">方案</th>
                                <th class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">金額</th>
                                <th class="px-4 py-2 text-center text-gray-500 dark:text-gray-400">狀態</th>
                                <th class="px-4 py-2 text-left text-gray-500 dark:text-gray-400">時間</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPayments as $payment)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $payment->merchant_trade_no }}</td>
                                    <td class="px-4 py-3">{{ $payment->plan->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right">NT$ {{ number_format($payment->amount) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($payment->isPaid())
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                已付款
                                            </span>
                                        @elseif($payment->isFailed())
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                失敗
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                處理中
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ $payment->created_at->format('Y-m-d H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>

    {{-- 隱藏的表單容器，用於提交綠界付款 --}}
    <div id="ecpay-form-container" style="display: none;"></div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            // Livewire 3 事件參數以陣列形式傳遞
            Livewire.on('submit-ecpay-form', (params) => {
                const formHtml = params[0]?.formHtml || params.formHtml;
                const container = document.getElementById('ecpay-form-container');
                container.innerHTML = formHtml;

                // 短暫延遲後提交表單
                setTimeout(() => {
                    const form = container.querySelector('form');
                    if (form) {
                        form.submit();
                    }
                }, 100);
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
