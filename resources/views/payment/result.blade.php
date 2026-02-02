<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>付款結果 - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4 text-center">
        @if($success)
            {{-- 付款成功 --}}
            <div class="mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <h1 class="text-xl font-semibold text-gray-800 mb-2">付款成功</h1>
            <p class="text-gray-600 mb-6">{{ $message }}</p>

            @if($payment)
                <div class="bg-green-50 rounded-lg p-4 mb-6 text-left">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="text-gray-500">訂單編號</div>
                        <div class="text-gray-800">{{ $payment->merchant_trade_no }}</div>

                        <div class="text-gray-500">方案名稱</div>
                        <div class="text-gray-800">{{ $payment->plan->name ?? '-' }}</div>

                        <div class="text-gray-500">金額</div>
                        <div class="text-gray-800 font-semibold">NT$ {{ number_format($payment->amount) }}</div>

                        @if($payment->paid_at)
                            <div class="text-gray-500">付款時間</div>
                            <div class="text-gray-800">{{ $payment->paid_at->format('Y-m-d H:i:s') }}</div>
                        @endif
                    </div>
                </div>
            @endif
        @else
            {{-- 付款失敗或處理中 --}}
            <div class="mb-6">
                @if($payment && $payment->isFailed())
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                @else
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                @endif
            </div>

            <h1 class="text-xl font-semibold text-gray-800 mb-2">
                @if($payment && $payment->isFailed())
                    付款失敗
                @else
                    付款處理中
                @endif
            </h1>
            <p class="text-gray-600 mb-6">{{ $message }}</p>

            @if($payment)
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="text-gray-500">訂單編號</div>
                        <div class="text-gray-800">{{ $payment->merchant_trade_no }}</div>

                        <div class="text-gray-500">方案名稱</div>
                        <div class="text-gray-800">{{ $payment->plan->name ?? '-' }}</div>

                        <div class="text-gray-500">金額</div>
                        <div class="text-gray-800">NT$ {{ number_format($payment->amount) }}</div>

                        <div class="text-gray-500">狀態</div>
                        <div class="text-gray-800">
                            @if($payment->isPending())
                                <span class="text-yellow-600">處理中</span>
                            @elseif($payment->isFailed())
                                <span class="text-red-600">失敗</span>
                            @else
                                {{ $payment->status }}
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <div class="space-y-3">
            <a href="{{ url('/admin') }}"
               class="block w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                返回管理後台
            </a>

            @if(!$success && $payment && !$payment->isFailed())
                <button onclick="location.reload()"
                        class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-4 rounded-lg transition-colors">
                    重新整理確認狀態
                </button>
            @endif
        </div>

        <p class="text-xs text-gray-400 mt-6">
            如有任何問題，請聯繫客服
        </p>
    </div>
</body>
</html>
