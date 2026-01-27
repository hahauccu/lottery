<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $currentPrize?->name ?? $event->name }} - 抽獎</title>
    <script>
        window.LotteryConfig = {!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
    </script>

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
            background-image: linear-gradient(180deg, rgba(3, 7, 18, 0.30), rgba(0, 0, 0, 0.45)), var(--lottery-bg-url);
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

        .lotto-canvas-wrap {
            width: 100%;
            height: 100%;
            min-height: 70vh;
            flex: 1;
        }

        .lotto-canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        .slot-hint {
            opacity: 0.85;
        }

        .lottery-winners-hidden {
            opacity: 0;
            visibility: hidden;
        }
    </style>
</head>
<body class="h-[100svh] overflow-hidden bg-black text-white">
    <div
        id="lottery-root"
        class="lottery-bg relative {{ $bgUrl ? 'has-image' : '' }}"
        @if ($bgUrl) style="--lottery-bg-url: url('{{ $bgUrl }}');" @endif
    >
        <div class="absolute inset-0 bg-gradient-to-b from-black/10 via-black/5 to-black/35"></div>

        <div id="lottery-stage" class="relative flex min-h-[100svh] flex-col items-center justify-center gap-6 px-6 text-center">
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

            <h1 class="text-4xl font-semibold tracking-wider text-white/95 sm:text-6xl" id="event-name">
                {{ $currentPrize?->name ?? $event->name }}
            </h1>

            <div id="slot-display" class="slot-reel hidden text-3xl font-semibold tracking-widest text-white/90 sm:text-5xl">
                <span class="text-white/85">抽獎中…</span>
            </div>
            <div id="lotto-canvas-wrap" class="lotto-canvas-wrap hidden">
                <canvas id="lotto-canvas" class="lotto-canvas"></canvas>
            </div>

            <div id="winners-container" class="w-full">
                <div class="flex items-center justify-between text-xs text-slate-200/70">
                    <span>中獎名單</span>
                    <span id="draw-progress"></span>
                </div>
                <ul id="winner-list" class="mt-3 grid max-h-[55vh] grid-cols-1 gap-3 overflow-hidden sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @forelse ($currentWinners as $winner)
                        <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                            <span class="font-semibold">{{ $winner->employee?->name }}</span>
                            <span class="text-sm text-slate-200/70">
                                {{ $winner->employee?->email }}
                                @if($winner->employee?->phone)
                                    · {{ $winner->employee?->phone }}
                                @endif
                            </span>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-white/10 bg-black/20 px-4 py-6 text-sm text-slate-200/50">尚未抽出中獎者</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- 結果展示模式 -->
        <div id="result-mode" class="hidden fixed inset-0 z-40 flex flex-col items-center justify-center">
            <div class="absolute inset-0 bg-black/95"></div>
            <div class="relative z-10 w-full flex flex-col items-center justify-center px-6">
                <!-- 獎項標題 -->
                <div class="mb-8 text-center">
                    <div class="text-lg text-amber-400/80 tracking-widest">恭喜中獎</div>
                    <h2 id="result-prize-name" class="text-5xl font-bold text-white mt-2"></h2>
                    <div id="result-winner-count" class="text-xl text-white/70 mt-3"></div>
                </div>

                <!-- 中獎者輪播區 -->
                <div class="w-full max-w-6xl">
                    <div id="result-winners-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>
                    <div id="result-page-info" class="text-center mt-6 text-white/60"></div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
