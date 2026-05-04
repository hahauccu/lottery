<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>線上抽獎 Demo｜7 種抽獎動畫免費體驗</title>
    <meta name="description" content="免費試玩 7 種抽獎與線上抽獎動畫風格：樂透氣流機、紅包雨、刮刮樂、寶箱、大寶箱、圓球賽跑、戰鬥陀螺。即時體驗企業尾牙抽獎的趣味互動效果。">
    <meta name="keywords" content="抽獎,線上抽獎,抽獎動畫 Demo,線上抽獎 Demo,尾牙抽獎,樂透氣流機,紅包雨,刮刮樂,寶箱抽獎,戰鬥陀螺抽獎">
    <link rel="canonical" href="{{ url('/demo/lottery') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="線上抽獎 Demo｜7 種抽獎動畫免費體驗">
    <meta property="og:description" content="免費試玩 7 種抽獎與線上抽獎動畫風格，即時體驗企業尾牙抽獎的趣味互動效果。">
    <meta property="og:url" content="{{ url('/demo/lottery') }}">
    <meta property="og:image" content="{{ url('/images/og-demo.svg') }}">
    <meta property="og:site_name" content="抽獎系統">
    <meta property="og:locale" content="zh_TW">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="線上抽獎 Demo｜7 種抽獎動畫免費體驗">
    <meta name="twitter:description" content="免費試玩 7 種抽獎與線上抽獎動畫風格，即時體驗企業尾牙抽獎的趣味互動效果。">
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
        "name": "線上抽獎 Demo｜7 種抽獎動畫免費體驗",
        "description": "免費試玩 7 種抽獎與線上抽獎動畫風格，即時體驗企業尾牙抽獎的趣味互動效果。",
        "url": "{{ url('/demo/lottery') }}",
        "image": "{{ url('/images/og-demo.svg') }}",
        "mainEntity": {
            "@@type": "ItemList",
            "itemListElement": {!! json_encode($demoItemList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        }
    }
    </script>

    @php
        $landingBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => '抽獎動畫 Demo', 'item' => url('/demo/lottery')],
            ],
        ];

        $landingFaqs = [
            ['q' => '這 7 種抽獎動畫有什麼不同？', 'a' => '每種動畫在視覺、節奏與互動方式上各有重點，如樂透氣流機走經典搖獎感、紅包雨強調喜氣互動、戰鬥陀螺則偏重激烈對決的臨場感。'],
            ['q' => '這些 Demo 可以在手機上執行嗎？', 'a' => '可以，所有 Demo 都針對桌機與手機調整過，在 390×844 手機直式畫面也能流暢運作。'],
            ['q' => '可以自己輸入名單試玩嗎？', 'a' => '可以，Demo 頁面提供自訂名單、抽獎人數與抽獎模式，僅保存在你的瀏覽器 session 中，不會寫入實際資料庫。'],
            ['q' => '正式活動要用哪一種動畫？', 'a' => '後台新增獎項時可為每個獎項獨立指定動畫風格，活動中也能依節奏切換不同獎項搭配不同動畫。'],
            ['q' => 'Demo 有提供彈幕或領獎 QRCode 嗎？', 'a' => '彈幕與 QRCode 兌獎屬於正式活動功能，Demo 主要展示動畫視覺，可到首頁或後台申請試用實際完整功能。'],
        ];

        $landingFaqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($landingFaqs)->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ])->all(),
        ];

        $landingCompare = [
            ['label' => '樂透氣流機', 'slug' => 'lotto-air', 'scene' => '尾牙 / 春酒 / 大型抽獎', 'feature' => '經典搖獎感、氣勢十足', 'people' => '50 ~ 500 人'],
            ['label' => '紅包雨', 'slug' => 'red-packet', 'scene' => '年節 / 春酒 / 喜慶活動', 'feature' => '紅包雨特效、點擊互動', 'people' => '30 ~ 300 人'],
            ['label' => '刮刮樂', 'slug' => 'scratch-card', 'scene' => '攤位活動 / 小型聚會', 'feature' => '刮開塗層揭曉名單', 'people' => '10 ~ 150 人'],
            ['label' => '寶箱', 'slug' => 'treasure-chest', 'scene' => '獎勵揭曉 / 答謝晚宴', 'feature' => '多寶箱依序開啟', 'people' => '20 ~ 200 人'],
            ['label' => '大寶箱', 'slug' => 'big-treasure-chest', 'scene' => '最終大獎揭曉', 'feature' => '巨型寶箱儀式感', 'people' => '全場適用'],
            ['label' => '圓球賽跑', 'slug' => 'marble-race', 'scene' => '品牌發表會 / 遊戲化活動', 'feature' => '多球競速賽道', 'people' => '20 ~ 150 人'],
            ['label' => '戰鬥陀螺', 'slug' => 'battle-top', 'scene' => '年輕化品牌 / 潮流活動', 'feature' => '陀螺激烈對決、戲劇張力', 'people' => '20 ~ 200 人'],
        ];

        $landingScenes = [
            ['scene' => '企業尾牙', 'pick' => '樂透氣流機、紅包雨、大寶箱', 'why' => '氣勢足、互動高，能撐滿整場多獎項流程。'],
            ['scene' => '春酒 / 年會', 'pick' => '紅包雨、樂透氣流機', 'why' => '喜氣風格強，搭配主持節奏效果最佳。'],
            ['scene' => '品牌發表 / 新品活動', 'pick' => '圓球賽跑、戰鬥陀螺', 'why' => '動態十足、富科技感，呼應品牌調性。'],
            ['scene' => '小型聚會 / 答謝宴', 'pick' => '刮刮樂、寶箱', 'why' => '輕快互動，適合 50 人內的親密場合。'],
            ['scene' => '直播 / 線上活動', 'pick' => '紅包雨、刮刮樂', 'why' => '視覺直觀，遠端觀眾也能快速理解抽獎過程。'],
        ];
    @endphp

    <script type="application/ld+json">
    {!! json_encode($landingBreadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode($landingFaqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ url('/images/og-home.svg') }}">
    <link rel="icon" href="{{ url('/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ url('/images/og-home.svg') }}">
    <meta name="theme-color" content="#050508">

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

        /* ── 比較區塊 ── */
        .landing-section {
            margin-top: 4.5rem;
        }
        .landing-section__title {
            font-size: clamp(1.35rem, 2.6vw, 1.75rem);
            font-weight: 800;
            letter-spacing: 0.03em;
            background: linear-gradient(135deg, #fff 0%, var(--gold-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }
        .landing-section__lead {
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.96rem;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }
        .compare-table-wrap {
            overflow-x: auto;
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(0, 0, 0, 0.25);
        }
        .compare-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 680px;
        }
        .compare-table th,
        .compare-table td {
            padding: 0.85rem 1rem;
            text-align: left;
            font-size: 0.92rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .compare-table th {
            color: var(--gold-light);
            font-weight: 700;
            letter-spacing: 0.05em;
            background: rgba(245, 166, 35, 0.06);
        }
        .compare-table td a {
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px dashed rgba(245, 166, 35, 0.35);
        }
        .compare-table td a:hover {
            color: var(--gold-light);
        }
        .scene-list {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 0.75rem;
            list-style: none;
        }
        @media (min-width: 720px) {
            .scene-list { grid-template-columns: repeat(2, 1fr); }
        }
        .scene-list li {
            padding: 1rem 1.1rem;
            border-radius: 0.9rem;
            border: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.02);
        }
        .scene-list b {
            display: block;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        .scene-list span {
            display: block;
            font-size: 0.85rem;
            color: var(--gold-light);
            margin-bottom: 0.25rem;
        }
        .scene-list p {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            line-height: 1.6;
            margin: 0;
        }
        .landing-faq {
            display: grid;
            gap: 0.6rem;
        }
        .landing-faq details {
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 0.9rem;
            padding: 0.9rem 1.1rem;
            background: rgba(0,0,0,0.25);
            transition: border-color 0.2s ease;
        }
        .landing-faq details[open] {
            border-color: rgba(245, 166, 35, 0.3);
        }
        .landing-faq summary {
            cursor: pointer;
            list-style: none;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .landing-faq summary::-webkit-details-marker { display: none; }
        .landing-faq summary::after {
            content: '+';
            color: var(--gold-light);
            font-size: 1.2rem;
            font-weight: 700;
            transition: transform 0.2s ease;
        }
        .landing-faq details[open] summary::after {
            content: '−';
        }
        .landing-faq p {
            margin: 0.75rem 0 0;
            color: rgba(255,255,255,0.65);
            line-height: 1.7;
            font-size: 0.9rem;
        }

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
            <h1 class="landing-title">線上抽獎 Demo</h1>
            <p class="landing-subtitle">選擇一種抽獎動畫風格，開始體驗互動抽獎與線上抽獎流程</p>
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

        <section class="landing-section anim-fade-up" style="animation-delay: 0.9s;">
            <h2 class="landing-section__title">7 種抽獎動畫怎麼選？</h2>
            <p class="landing-section__lead">每種風格針對不同活動規模與情境設計，參考下表挑選最適合你場合的抽獎動畫。</p>
            <div class="compare-table-wrap">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th>動畫風格</th>
                            <th>適合活動</th>
                            <th>特色</th>
                            <th>建議人數</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($landingCompare as $row)
                            <tr>
                                <td><a href="{{ url('/demo/lottery/'.$row['slug']) }}">{{ $row['label'] }}</a></td>
                                <td>{{ $row['scene'] }}</td>
                                <td>{{ $row['feature'] }}</td>
                                <td>{{ $row['people'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="landing-section anim-fade-up" style="animation-delay: 1.0s;">
            <h2 class="landing-section__title">情境對照：哪個場合該用哪種？</h2>
            <p class="landing-section__lead">以常見企業活動情境為切入點，直接告訴你選哪幾種風格最對味。</p>
            <ul class="scene-list">
                @foreach ($landingScenes as $s)
                    <li>
                        <b>{{ $s['scene'] }}</b>
                        <span>{{ $s['pick'] }}</span>
                        <p>{{ $s['why'] }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="landing-section anim-fade-up" style="animation-delay: 1.1s;">
            <h2 class="landing-section__title">常見問題</h2>
            <div class="landing-faq">
                @foreach ($landingFaqs as $faq)
                    <details>
                        <summary>{{ $faq['q'] }}</summary>
                        <p>{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <footer class="landing-footer anim-fade-up" style="animation-delay: 1.2s;">
            <a href="{{ url('/') }}">← 返回首頁</a>
        </footer>
    </div>
</body>
</html>
