<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->name }} - 中獎清單</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-950 text-white">
    <div class="mx-auto max-w-5xl space-y-6 px-6 py-10">
        <header>
            <p class="text-sm text-gray-400">活動代碼：{{ $event->brand_code }}</p>
            <h1 class="text-3xl font-semibold">{{ $event->name }} - 中獎清單</h1>
        </header>

        @forelse ($prizes as $prize)
            <section class="rounded-2xl bg-gray-900/80 p-6 shadow">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ $prize->name }}</h2>
                    <span class="text-sm text-gray-400">中獎 {{ $prize->winners->count() }} 人</span>
                </div>

                <div class="mt-4 divide-y divide-gray-800 rounded-xl border border-gray-800">
                    @forelse ($prize->winners as $winner)
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="font-semibold">{{ $winner->employee?->name }}</span>
                            <span class="text-sm text-gray-400">
                                {{ $winner->employee?->email }}
                                @if($winner->employee?->phone)
                                    · {{ $winner->employee?->phone }}
                                @endif
                            </span>
                        </div>
                    @empty
                        <div class="px-4 py-3 text-sm text-gray-500">尚未抽出中獎者</div>
                    @endforelse
                </div>
            </section>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-700 p-6 text-sm text-gray-400">
                尚未建立獎項
            </div>
        @endforelse
    </div>
</body>
</html>
