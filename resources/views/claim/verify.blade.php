<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>領獎確認 - {{ $winner->prize->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-950 text-white">
    <div class="mx-auto max-w-lg px-6 py-10">
        <div class="rounded-2xl bg-gray-900/80 p-8 shadow-xl">
            <div class="text-center mb-6">
                @if($winner->isClaimed())
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-500/20 mb-4">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-green-400">已領獎</h1>
                    <p class="text-gray-400 mt-2">領獎時間：{{ $winner->claimed_at->format('Y/m/d H:i') }}</p>
                @else
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-500/20 mb-4">
                        <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-yellow-400">待領獎</h1>
                    <p class="text-gray-400 mt-2">請向工作人員出示此畫面</p>
                @endif
            </div>

            <div class="border-t border-gray-800 pt-6">
                <div class="text-center mb-4">
                    <p class="text-sm text-gray-400">{{ $winner->prize->lotteryEvent->name }}</p>
                    <p class="text-xl font-bold text-red-400">{{ $winner->prize->name }}</p>
                </div>

                <div class="space-y-3 bg-gray-800/50 rounded-xl p-4">
                    <div class="flex justify-between">
                        <span class="text-gray-400">中獎者</span>
                        <span class="font-semibold">{{ $winner->employee->name }}</span>
                    </div>
                    @if($winner->employee->department)
                    <div class="flex justify-between">
                        <span class="text-gray-400">部門</span>
                        <span>{{ $winner->employee->department }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-400">中獎序號</span>
                        <span class="font-mono">#{{ $winner->sequence }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">中獎時間</span>
                        <span>{{ $winner->won_at->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-800 text-center">
                <p class="text-xs text-gray-500">
                    活動代碼：{{ $winner->prize->lotteryEvent->brand_code }}
                </p>
            </div>
        </div>
    </div>
</body>
</html>
