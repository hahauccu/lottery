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
            background-image: linear-gradient(180deg, rgba(3, 7, 18, 0.10), rgba(0, 0, 0, 0.15)), var(--lottery-bg-url);
            background-size: cover;
            background-position: center;
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

        .lottery-winners-hidden {
            opacity: 0;
            visibility: hidden;
        }

        #switching-mask {
            position: absolute;
            inset: 0;
            z-index: 35;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.85);
            opacity: 0;
            pointer-events: none;
            transition: opacity 360ms ease;
        }

        #switching-mask.is-visible {
            opacity: 1;
            pointer-events: auto;
        }

        #switching-mask .switching-mask__text {
            color: rgba(255, 255, 255, 0.78);
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.35em;
        }

        /* 測試模式浮水印 */
        #test-mode-watermark {
            position: fixed;
            inset: 0;
            z-index: 30;
            pointer-events: none;
            overflow: hidden;
        }
        #test-mode-watermark::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 200%;
            height: 4px;
            background: repeating-linear-gradient(
                90deg,
                rgba(239, 68, 68, 0.6) 0px,
                rgba(239, 68, 68, 0.6) 20px,
                transparent 20px,
                transparent 40px
            );
            transform: translate(-50%, -50%) rotate(-35deg);
        }
        #test-mode-watermark .watermark-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            white-space: nowrap;
            font-size: 4rem;
            font-weight: 800;
            color: rgba(239, 68, 68, 0.35);
            letter-spacing: 0.5em;
            text-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
            user-select: none;
        }
        @media (max-width: 640px) {
            #test-mode-watermark .watermark-text {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="h-[100svh] overflow-hidden bg-black text-white">
        <div
            id="lottery-root"
            class="lottery-bg relative min-h-[100svh] {{ $bgUrl ? 'has-image' : '' }}"
            @if ($bgUrl) style="--lottery-bg-url: url('{{ $bgUrl }}');" @endif
        >
            <div class="absolute inset-0 bg-gradient-to-b from-black/10 via-black/5 to-black/35"></div>

            <div id="switching-mask" aria-hidden="true">
                <div class="switching-mask__text">切換中…</div>
            </div>

            <!-- 測試模式浮水印 -->
            <div id="test-mode-watermark" class="hidden">
            <div class="watermark-text">抽獎測試中</div>
        </div>

        <div id="lottery-stage" class="relative flex min-h-[100svh] flex-col items-center justify-center gap-6 px-6 text-center">
            <h1 class="text-4xl font-semibold tracking-wider text-white/95 sm:text-6xl" id="event-name">
                {{ $currentPrize?->name ?? $event->name }}
            </h1>

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

        <!-- 獎項預覽模式 -->
        <div id="prizes-preview-mode" class="hidden fixed inset-0 z-40 flex flex-col items-center justify-center">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative z-10 w-full max-w-5xl px-6">
                <h2 class="text-center text-4xl font-bold text-white mb-2">今日獎項</h2>
                <p class="text-center text-white/60 mb-8">共 <span id="prizes-preview-total">0</span> 個獎項</p>
                <div id="prizes-preview-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>
                <p id="prizes-preview-page-info" class="mt-4 text-center text-white/50"></p>

                <!-- QR Code 區塊 -->
                <div class="mt-8 flex flex-col items-center">
                    <p class="text-white/60 text-sm mb-3">掃描查看中獎名單</p>
                    <div class="rounded-xl bg-white p-3">
                        <img id="winners-qrcode" src="" alt="QR Code" class="h-32 w-32" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 結果展示模式 -->
        <div id="result-mode" class="hidden fixed inset-0 z-40 flex flex-col items-center justify-center">
            <div class="absolute inset-0 bg-black/10"></div>
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

    {{-- 彈幕容器 --}}
    <div id="danmaku-container" class="hidden fixed inset-0 pointer-events-none z-50 overflow-hidden"></div>

    <style>
    .danmaku-item {
        position: absolute;
        white-space: nowrap;
        font-size: 1.125rem;
        font-weight: 600;
        color: white;
        text-shadow:
            -1px -1px 0 rgba(0,0,0,0.8),
            1px -1px 0 rgba(0,0,0,0.8),
            -1px 1px 0 rgba(0,0,0,0.8),
            1px 1px 0 rgba(0,0,0,0.8),
            0 0 8px rgba(0,0,0,0.6);
        animation: danmaku-scroll linear forwards;
        pointer-events: none;
    }

    @keyframes danmaku-scroll {
        from { transform: translateX(0); }
        to { transform: translateX(-120vw); }
    }
    </style>

</body>
</html>
