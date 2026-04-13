<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless($isDemo ?? false)
    <meta name="robots" content="noindex, nofollow">
    @endunless
    @if($isDemo ?? false)
    <meta name="description" content="{{ $seoDescription ?? '' }}">
    <link rel="canonical" href="{{ $seoCanonical ?? url('/demo/lottery') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle ?? '範例抽獎' }}">
    <meta property="og:description" content="{{ $seoDescription ?? '' }}">
    <meta property="og:url" content="{{ $seoCanonical ?? url('/demo/lottery') }}">
    <meta property="og:image" content="{{ $seoImage ?? url('/images/og-demo.svg') }}">
    <meta property="og:site_name" content="抽獎系統">
    <meta property="og:locale" content="zh_TW">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle ?? '範例抽獎' }}">
    <meta name="twitter:description" content="{{ $seoDescription ?? '' }}">
    <meta name="twitter:image" content="{{ $seoImage ?? url('/images/og-demo.svg') }}">
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebPage",
        "name": {!! json_encode($seoTitle ?? '範例抽獎', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        "description": {!! json_encode($seoDescription ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        "url": {!! json_encode($seoCanonical ?? url('/demo/lottery'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        "image": {!! json_encode($seoImage ?? url('/images/og-demo.svg'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    }
    </script>
    @isset($demoBreadcrumbItems)
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "BreadcrumbList",
        "itemListElement": {!! json_encode($demoBreadcrumbItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    }
    </script>
    @endisset
    @endif
    <title>{{ $title ?? ($currentPrize?->name ?? $event->name) }} - 抽獎</title>
    <script id="lottery-config-data" type="application/json">{!! json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

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
            transition: opacity 300ms ease, visibility 300ms ease;
        }

        #switching-mask {
            position: absolute;
            inset: 0;
            z-index: 55;
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

        .demo-seo-links {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 45;
            width: min(25rem, calc(100vw - 2rem));
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1rem;
            background: rgba(3, 7, 18, 0.78);
            backdrop-filter: blur(16px);
            box-shadow: 0 16px 50px rgba(0, 0, 0, 0.28);
        }

        .demo-seo-links__crumbs,
        .demo-seo-links__nav,
        .demo-seo-links__related {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .demo-seo-links__title {
            margin: 0.75rem 0 0.5rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: rgba(255, 255, 255, 0.92);
        }

        .demo-seo-links__desc {
            margin: 0 0 0.75rem;
            font-size: 0.84rem;
            line-height: 1.6;
            color: rgba(226, 232, 240, 0.7);
        }

        .demo-seo-links a {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
            padding: 0.42rem 0.72rem;
            border-radius: 999px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.04);
            font-size: 0.78rem;
            transition: border-color 0.2s ease, color 0.2s ease, background 0.2s ease;
        }

        .demo-seo-links a:hover {
            border-color: rgba(245, 158, 11, 0.35);
            color: #fde68a;
            background: rgba(245, 158, 11, 0.08);
        }

        @media (max-width: 768px) {
            .demo-seo-links {
                left: 0.75rem;
                right: 0.75rem;
                bottom: 0.75rem;
                width: auto;
                max-height: 42vh;
                overflow-y: auto;
            }
        }

        body.is-embedded-preview #lottery-stage {
            gap: 0.85rem;
            padding: 0.75rem 1rem 1rem;
            justify-content: flex-start;
        }

        body.is-embedded-preview #event-name {
            margin-top: 0.15rem;
            font-size: clamp(1.4rem, 3.4vw, 2.4rem);
            line-height: 1.1;
        }

        body.is-embedded-preview #winners-container {
            width: min(100%, 50rem);
        }

        body.is-embedded-preview #winners-container > div:first-child {
            display: none;
        }

        body.is-embedded-preview #winner-list {
            margin-top: 0;
            max-height: 28vh;
            gap: 0.65rem;
        }

        body.is-embedded-preview #winner-list li {
            border-radius: 1rem;
            padding: 0.7rem 0.9rem;
            font-size: 0.82rem;
        }

        body.is-embedded-preview #prizes-preview-mode .relative,
        body.is-embedded-preview #result-mode .relative {
            transform: scale(0.88);
            transform-origin: center center;
        }
    </style>
</head>
<body class="h-[100svh] overflow-hidden bg-black text-white {{ !empty($isEmbeddedPreview) ? 'is-embedded-preview' : '' }}">
    @if (!empty($isDemo))
        @if (!empty($demoSetupMode))
            @include('lottery.partials.demo-style-toolbar')
        @elseif (empty($isEmbeddedPreview))
            @include('lottery.partials.demo-toolbar')
        @endif
    @endif
    @if (!empty($isDemo) && empty($isEmbeddedPreview))
        <aside class="demo-seo-links">
            <div class="demo-seo-links__crumbs">
                <a href="{{ url('/') }}">首頁</a>
                <a href="{{ url('/demo/lottery') }}">抽獎動畫 Demo</a>
                <a href="{{ url('/demo/lottery/'.$demoCurrentStyle['slug']) }}">{{ $demoCurrentStyle['label'] }}</a>
            </div>

            <p class="demo-seo-links__title">{{ $demoCurrentStyle['label'] }} 抽獎風格</p>
            <p class="demo-seo-links__desc">{{ $demoCurrentStyle['desc'] }} 適合尾牙抽獎、春酒活動與各式互動抽獎情境。</p>

            <div class="demo-seo-links__nav">
                <a href="{{ url('/') }}">回首頁看功能介紹</a>
                <a href="{{ url('/demo/lottery') }}">查看全部 Demo 風格</a>
            </div>

            <p class="demo-seo-links__title">你也可以試試其他抽獎動畫</p>
            <div class="demo-seo-links__related">
                @foreach ($demoRelatedStyles as $relatedStyle)
                    <a href="{{ url('/demo/lottery/'.$relatedStyle['slug']) }}">試玩 {{ $relatedStyle['label'] }} Demo</a>
                @endforeach
            </div>
        </aside>
    @endif
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
                                {{ \App\Support\DataMasker::maskEmail($winner->employee?->email) }}
                            </span>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-white/10 bg-black/20 px-4 py-6 text-sm text-slate-200/50">請按 Enter 開始抽獎</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- 獎項預覽模式 -->
        <div id="prizes-preview-mode" class="fixed inset-0 z-40 flex flex-col items-center justify-center">
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
        <div id="result-mode" class="fixed inset-0 z-40 flex flex-col items-center justify-center">
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
    #prizes-preview-mode,
    #result-mode {
        opacity: 0;
        visibility: hidden;
        transition: opacity 400ms ease, visibility 400ms ease;
    }
    #prizes-preview-mode.is-active,
    #result-mode.is-active {
        opacity: 1;
        visibility: visible;
    }

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
