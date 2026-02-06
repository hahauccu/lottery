<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->name }} - 中獎清單</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/danmaku.js'])
</head>
<body class="min-h-screen bg-gray-950 text-white">
    <div class="mx-auto max-w-5xl space-y-6 px-6 py-10">
        <header>
            <p class="text-sm text-gray-400">活動代碼：{{ $event->brand_code }}</p>
            <h1 class="text-3xl font-semibold">{{ $event->name }} - 中獎清單</h1>
        </header>

        {{-- 彈幕發送表單 --}}
        @if($event->danmaku_enabled)
        <section id="danmaku-form-section" class="rounded-2xl bg-gradient-to-br from-indigo-900/40 to-purple-900/30 border border-indigo-500/30 p-6 shadow-lg">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3 1h10v8H5V6z"/>
                </svg>
                發送彈幕到抽獎頁面
            </h2>

            <form id="danmaku-form" class="space-y-4">
                @csrf
                <div>
                    <label for="danmaku-email" class="block text-sm font-medium text-gray-300 mb-1">
                        您的 Email
                    </label>
                    <input
                        type="email"
                        id="danmaku-email"
                        name="email"
                        required
                        maxlength="255"
                        placeholder="example@company.com"
                        class="w-full px-4 py-2.5 bg-gray-900/80 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition"
                    >
                    <p id="email-error" class="mt-1 text-sm text-red-400 hidden"></p>
                </div>

                <div>
                    <label for="danmaku-message" class="block text-sm font-medium text-gray-300 mb-1">
                        彈幕訊息 <span class="text-xs text-gray-400">(最多 100 字)</span>
                    </label>
                    <input
                        type="text"
                        id="danmaku-message"
                        name="message"
                        required
                        maxlength="100"
                        placeholder="輸入您的祝福或訊息..."
                        class="w-full px-4 py-2.5 bg-gray-900/80 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition"
                    >
                    <p id="message-error" class="mt-1 text-sm text-red-400 hidden"></p>
                </div>

                <button
                    type="submit"
                    id="danmaku-submit-btn"
                    class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-lg shadow-lg transition transform hover:scale-[1.02] active:scale-95"
                >
                    <span id="btn-text">發送彈幕</span>
                    <span id="btn-cooldown" class="hidden">等待 <span id="cooldown-seconds">10</span> 秒</span>
                </button>

                <p id="form-success" class="text-sm text-green-400 text-center hidden"></p>
            </form>
        </section>

        <script>
            window.DanmakuConfig = {
                brandCode: @json($event->brand_code),
                danmakuEnabled: @json($event->danmaku_enabled),
                csrfToken: @json(csrf_token()),
                apiUrl: @json(route('lottery.danmaku', ['brandCode' => $event->brand_code])),
            };
        </script>
        @endif

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
