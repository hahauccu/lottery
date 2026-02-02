<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>付款處理中 - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4 text-center">
        <div class="mb-6">
            <svg class="animate-spin h-12 w-12 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <h1 class="text-xl font-semibold text-gray-800 mb-2">正在前往付款頁面</h1>
        <p class="text-gray-600 mb-4">請稍候，系統正在將您導向綠界金流付款頁面...</p>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="text-sm text-gray-500 mb-1">購買方案</div>
            <div class="font-semibold text-gray-800">{{ $plan->name }}</div>
            <div class="text-sm text-gray-500 mt-2">金額</div>
            <div class="text-2xl font-bold text-blue-600">NT$ {{ number_format($payment->amount) }}</div>
        </div>

        <p class="text-xs text-gray-400">
            訂單編號：{{ $payment->merchant_trade_no }}
        </p>
    </div>

    {!! $formHtml !!}

    <script>
        // 頁面載入後自動提交表單
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('ecpay-form').submit();
            }, 1000);
        });
    </script>
</body>
</html>
