<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>抽獎動畫 Demo｜7 種風格免費體驗</title>
    <meta name="description" content="免費試玩 7 種線上抽獎動畫風格：樂透氣流機、紅包雨、刮刮樂、寶箱、大寶箱、圓球賽跑、戰鬥陀螺。即時體驗企業尾牙抽獎的趣味互動效果。">
    <link rel="canonical" href="{{ url('/demo/lottery') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="抽獎動畫 Demo｜7 種風格免費體驗">
    <meta property="og:description" content="免費試玩 7 種線上抽獎動畫風格，即時體驗企業尾牙抽獎的趣味互動效果。">
    <meta property="og:url" content="{{ url('/demo/lottery') }}">
    <meta property="og:image" content="{{ url('/images/og-demo.svg') }}">
    <meta property="og:site_name" content="抽獎系統">
    <meta property="og:locale" content="zh_TW">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="抽獎動畫 Demo｜7 種風格免費體驗">
    <meta name="twitter:description" content="免費試玩 7 種線上抽獎動畫風格，即時體驗企業尾牙抽獎的趣味互動效果。">
    <meta name="twitter:image" content="{{ url('/images/og-demo.svg') }}">

    @php
        $demoItemList = collect($styles)
            ->values()
            ->map(function ($style, $index) {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => url('/demo/lottery/'.$style['slug']),
                    'name' => $style['label'],
                    'description' => $style['desc'],
                ];
            })
            ->all();
    @endphp

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "CollectionPage",
        "name": "抽獎動畫 Demo｜7 種風格免費體驗",
        "description": "免費試玩 7 種線上抽獎動畫風格，即時體驗企業尾牙抽獎的趣味互動效果。",
        "url": "{{ url('/demo/lottery') }}",
        "image": "{{ url('/images/og-demo.svg') }}",
        "mainEntity": {
            "@@type": "ItemList",
            "itemListElement": {!! json_encode($demoItemList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        }
    }
    </script>

    @vite(['resources/css/app.css'])

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&display=swap');

        :root {
            --gold: #f5a623;
            --gold-light: #ffd175;
            --gold-dim: rgba(245, 166, 35, 0.15);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Noto Sans TC', system-ui, sans-serif;
            background: #050508;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .landing-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 900px 600px at 15% 10%, rgba(245, 166, 35, 0.12), transparent),
                radial-gradient(ellipse 700px 500px at 85% 80%, rgba(99, 102, 241, 0.08), transparent),
                radial-gradient(ellipse 500px 400px at 50% 50%, rgba(245, 166, 35, 0.04), transparent);
        }

        /* 裝飾性網格 */
        .landing-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 80px 80px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, black, transparent);
        }

        .landing-container {
            position: relative;
            z-index: 1;
            max-width: 1100px;
            margin: 0 auto;
            padding: 4rem 1.5rem 3rem;
        }

        .landing-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .landing-badge {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 999px;
            border: 1px solid rgba(245, 166, 35, 0.3);
            background: rgba(245, 166, 35, 0.08);
            color: var(--gold-light);
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            margin-bottom: 1.5rem;
        }

        .landing-title {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 900;
            letter-spacing: 0.04em;
            line-height: 1.3;
            background: linear-gradient(135deg, #fff 0%, var(--gold-light) 50%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .landing-subtitle {
            margin-top: 1rem;
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 400;
        }

        /* 卡片 grid */
        .styles-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 1.25rem;
        }
        @media (min-width: 640px) {
            .styles-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .styles-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .style-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2rem 1.75rem;
            min-height: 200px;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.07);
            background: linear-gradient(160deg, rgba(255,255,255,0.04) 0%, rgba(0,0,0,0.3) 100%);
            backdrop-filter: blur(8px);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .style-card:hover {
            transform: translateY(-4px);
            border-color: rgba(245, 166, 35, 0.35);
            box-shadow: 0 12px 40px rgba(245, 166, 35, 0.1), 0 0 0 1px rgba(245, 166, 35, 0.15);
        }

        /* 卡片頂部裝飾光暈 */
        .style-card::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -20px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--card-accent, rgba(245, 166, 35, 0.1));
            filter: blur(40px);
            transition: opacity 0.3s ease;
            opacity: 0.6;
        }
        .style-card:hover::before {
            opacity: 1;
        }

        .style-card__icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            line-height: 1;
        }

        .style-card__name {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .style-card__desc {
            font-size: 0.88rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.45);
        }

        .style-card__arrow {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }
        .style-card:hover .style-card__arrow {
            border-color: var(--gold);
            color: var(--gold);
            background: rgba(245, 166, 35, 0.1);
        }

        /* 每張卡片不同光暈色 */
        .style-card:nth-child(1) { --card-accent: rgba(245, 166, 35, 0.12); }
        .style-card:nth-child(2) { --card-accent: rgba(239, 68, 68, 0.12); }
        .style-card:nth-child(3) { --card-accent: rgba(168, 162, 158, 0.12); }
        .style-card:nth-child(4) { --card-accent: rgba(59, 130, 246, 0.12); }
        .style-card:nth-child(5) { --card-accent: rgba(147, 51, 234, 0.12); }
        .style-card:nth-child(6) { --card-accent: rgba(34, 197, 94, 0.12); }
        .style-card:nth-child(7) { --card-accent: rgba(236, 72, 153, 0.12); }

        .landing-footer {
            text-align: center;
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .landing-footer a {
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.2s;
        }
        .landing-footer a:hover {
            color: var(--gold-light);
        }

        /* 入場動畫 */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim-fade-up {
            animation: fadeUp 0.6s ease forwards;
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="landing-bg"></div>

    <div class="landing-container">
        <header class="landing-header anim-fade-up" style="animation-delay: 0.1s;">
            <div class="landing-badge">DEMO 體驗</div>
            <h1 class="landing-title">抽獎動畫 Demo</h1>
            <p class="landing-subtitle">選擇一種動畫風格，開始體驗互動抽獎</p>
        </header>

        <div class="styles-grid">
            @php
                $icons = ['🎰', '🧧', '🎫', '📦', '🎁', '🏁', '🌀'];
            @endphp
            @foreach ($styles as $i => $s)
                <a href="{{ url('/demo/lottery/' . $s['slug']) }}"
                   class="style-card anim-fade-up"
                   style="animation-delay: {{ 0.15 + $i * 0.07 }}s;">
                    <div class="style-card__arrow">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 17L17 7M17 7H7M17 7v10"/>
                        </svg>
                    </div>
                    <div class="style-card__icon">{{ $icons[$i] }}</div>
                    <div class="style-card__name">{{ $s['label'] }}</div>
                    <div class="style-card__desc">{{ $s['desc'] }}</div>
                </a>
            @endforeach
        </div>

        <footer class="landing-footer anim-fade-up" style="animation-delay: 0.8s;">
            <a href="{{ url('/') }}">← 返回首頁</a>
        </footer>
    </div>
</body>
</html>
