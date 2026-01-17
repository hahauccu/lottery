<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->name }} - 抽獎</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/lottery.js'])


    <style>
        #lottery-stage {
            min-height: 100svh;
        }

        .lottery-bg {
            background: radial-gradient(1200px circle at 20% 10%, rgba(245, 158, 11, 0.25), transparent 55%),
                radial-gradient(800px circle at 90% 30%, rgba(99, 102, 241, 0.18), transparent 55%),
                linear-gradient(180deg, rgba(3, 7, 18, 1), rgba(0, 0, 0, 1));
        }

        .lottery-bg.has-image {
            background-image: linear-gradient(180deg, rgba(3, 7, 18, 0.70), rgba(0, 0, 0, 0.85)), var(--lottery-bg-url);
            background-size: cover;
            background-position: center;
        }

        .slot-window {
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(148, 163, 184, 0.20);
            box-shadow: 0 18px 60px rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .slot-reel {
            font-variant-numeric: tabular-nums;
            text-shadow: 0 10px 30px rgba(0, 0, 0, 0.55);
        }

        .slot-hint {
            opacity: 0.85;
        }
    </style>
</head>
<body class="h-[100svh] overflow-hidden bg-black text-white">
    <div
        id="lottery-root"
        data-lottery-config="{{ base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
        data-lottery-config-encoding="base64"
        class="lottery-bg relative {{ $bgUrl ? 'has-image' : '' }}"
        @if ($bgUrl) style="--lottery-bg-url: url('{{ $bgUrl }}');" @endif
    >
        <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-black/10 to-black/70"></div>

        <div id="lottery-stage" class="relative flex min-h-[100svh] flex-col">
            <header class="flex items-start justify-between gap-4 px-6 py-6 sm:px-10">
                <div class="space-y-1">
                    <p class="text-xs text-slate-300/80">活動代碼：{{ $event->brand_code }}</p>
                    <h1 class="text-2xl font-semibold tracking-wide sm:text-3xl" id="event-name">{{ $event->name }}</h1>
                    <div class="text-sm text-slate-200/90" id="lottery-status">
                        狀態：<span class="font-semibold">{{ $event->is_lottery_open ? '可抽獎' : '尚未開放' }}</span>
                    </div>
                </div>

                <a
                    href="{{ route('lottery.winners', ['brandCode' => $event->brand_code]) }}"
                    class="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm text-white/90 transition hover:bg-white/10"
                >
                    前台中獎清單
                </a>
            </header>

            <main class="flex flex-1 items-center justify-center px-6 pb-10 sm:px-10">
                <div id="test-overlay" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center">
                    <div class="absolute inset-0 bg-black/70"></div>
                    <div class="relative mx-auto max-w-4xl px-6 text-center">
                        <div class="text-xs font-semibold tracking-[0.4em] text-white/80">TEST MODE</div>
                        <div id="test-overlay-text" class="mt-4 text-5xl font-black tracking-widest text-white sm:text-7xl">
                            DRAWING
                        </div>
                        <div class="mt-5 text-sm text-white/70">（這個效果應該一定看得出來有不同）</div>
                    </div>
                </div>
                <div class="slot-window w-full max-w-3xl rounded-[28px] px-6 py-8 sm:px-10 sm:py-10">
                    <div class="flex flex-col gap-5">
                        <div class="flex flex-col items-center gap-1 text-center">
                            <div class="text-sm text-slate-200/80">目前獎項</div>
                            <div class="text-3xl font-semibold tracking-wide sm:text-4xl" id="current-prize-name">
                                {{ $currentPrize?->name ?? '尚未設定' }}
                            </div>
                            <div class="text-sm text-slate-200/70" id="current-prize-mode">
                                {{ $currentPrize ? ($currentPrize->draw_mode === \App\Models\Prize::DRAW_MODE_ONE_BY_ONE ? '逐一抽出' : '一次全抽') : '' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-black/25 px-5 py-8">
                            <div class="text-center">
                                <div class="text-sm text-slate-200/75">抽獎區</div>
                                <div
                                    id="slot-display"
                                    class="slot-reel mt-4 text-4xl font-semibold tracking-wider sm:text-6xl"
                                    aria-live="polite"
                                >
                                    <span class="text-white/90">準備就緒</span>
                                </div>
                                <div id="slot-subtitle" class="slot-hint mt-3 text-sm text-slate-200/70">
                                    按 Enter 或 Space，或點擊「開始抽獎」。
                                </div>
                            </div>

                            <div class="mt-7 flex flex-col items-center gap-3">
                                <button
                                    type="button"
                                    id="draw-button"
                                    class="inline-flex items-center justify-center rounded-full bg-amber-400 px-8 py-3 text-base font-semibold text-slate-950 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:bg-slate-300"
                                >
                                    開始抽獎
                                </button>

                                <div class="text-xs text-slate-200/65">
                                    小提示：抽獎結果以伺服器回傳為準。
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-slate-200/80">本獎項中獎名單</div>
                                <div id="draw-progress" class="text-xs text-slate-200/60"></div>
                            </div>

                            <ul
                                id="winner-list"
                                class="max-h-56 divide-y divide-white/10 overflow-auto rounded-2xl border border-white/10 bg-black/20"
                            >
                                @forelse ($currentWinners as $winner)
                                    <li class="flex items-center justify-between gap-3 px-4 py-3">
                                        <span class="font-semibold">{{ $winner->employee?->name }}</span>
                                        <span class="text-sm text-slate-200/70">
                                            {{ $winner->employee?->email }}
                                            @if($winner->employee?->phone)
                                                · {{ $winner->employee?->phone }}
                                            @endif
                                        </span>
                                    </li>
                                @empty
                                    <li class="px-4 py-3 text-sm text-slate-200/50">尚未抽出中獎者</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

</body>
</html>
