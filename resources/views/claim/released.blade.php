<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>資格已取消</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-950 text-white">
    <div class="mx-auto max-w-lg px-6 py-10">
        <div class="rounded-2xl bg-gray-900/80 p-8 shadow-xl">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500/20 mb-4">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-red-400">資格已取消</h1>
                <p class="text-gray-400 mt-2">此中獎資格已被管理員取消</p>
            </div>

            <div class="border-t border-gray-800 pt-6">
                <div class="text-center mb-4">
                    <p class="text-sm text-gray-400">{{ $winner->prize->lotteryEvent->name }}</p>
                    <p class="text-xl font-bold text-gray-500">{{ $winner->prize->name }}</p>
                </div>

                <div class="space-y-3 bg-gray-800/50 rounded-xl p-4">
                    <div class="flex justify-between">
                        <span class="text-gray-400">原中獎者</span>
                        <span class="font-semibold text-gray-500">{{ $winner->employee->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">取消時間</span>
                        <span class="text-gray-500">{{ $winner->released_at->format('Y/m/d H:i') }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-800 text-center">
                <p class="text-xs text-gray-500">
                    如有疑問，請聯繫活動管理員
                </p>
            </div>
        </div>
    </div>
</body>
</html>
