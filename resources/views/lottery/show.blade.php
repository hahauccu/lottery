<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->name }} - 抽獎</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/lottery.js'])
</head>
<body class="min-h-screen bg-gray-950 text-white">
    <div class="mx-auto flex min-h-screen max-w-5xl flex-col gap-6 px-6 py-10" id="lottery-root">
        <header class="flex flex-col gap-2">
            <p class="text-sm text-gray-400">活動代碼：{{ $event->brand_code }}</p>
            <h1 class="text-3xl font-semibold" id="event-name">{{ $event->name }}</h1>
            <div class="text-sm text-gray-300" id="lottery-status">
                狀態：<span class="font-semibold">{{ $event->is_lottery_open ? '可抽獎' : '尚未開放' }}</span>
            </div>
        </header>

        <main class="flex flex-col gap-6 rounded-2xl bg-gray-900/80 p-6 shadow">
            <div class="flex flex-col gap-2">
                <div class="text-sm text-gray-400">目前獎項</div>
                <div class="text-2xl font-semibold" id="current-prize-name">
                    {{ $currentPrize?->name ?? '尚未設定' }}
                </div>
                <div class="text-sm text-gray-400" id="current-prize-mode">
                    {{ $currentPrize ? ($currentPrize->draw_mode === \App\Models\Prize::DRAW_MODE_ONE_BY_ONE ? '逐一抽出' : '一次全抽') : '' }}
                </div>
            </div>

            <div class="rounded-xl border border-dashed border-gray-700 px-4 py-3 text-sm text-gray-300">
                進行抽獎：按 Enter 或點擊畫面左鍵。
            </div>

            <div class="space-y-3">
                <div class="text-sm text-gray-400">中獎名單</div>
                <ul id="winner-list" class="divide-y divide-gray-800 rounded-xl border border-gray-800">
                    @forelse ($currentWinners as $winner)
                        <li class="flex items-center justify-between px-4 py-3">
                            <span class="font-semibold">{{ $winner->employee?->name }}</span>
                            <span class="text-sm text-gray-400">
                                {{ $winner->employee?->email }}
                                @if($winner->employee?->phone)
                                    · {{ $winner->employee?->phone }}
                                @endif
                            </span>
                        </li>
                    @empty
                        <li class="px-4 py-3 text-sm text-gray-500">尚未抽出中獎者</li>
                    @endforelse
                </ul>
            </div>
        </main>
    </div>

    <script>
        window.LotteryConfig = @json($payload);
    </script>
</body>
</html>
