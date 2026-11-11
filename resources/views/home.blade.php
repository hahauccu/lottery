@php
    $pageTitle = '尾牙抽獎系統｜名單匯入、抽獎動畫與不在場重抽';
    $pageDescription = '為公司尾牙、春酒準備的線上抽獎系統。匯入員工名單、設定獎項與參加資格，從後台切換投影畫面；中獎者不在場時，可釋出名額再補抽。7 種抽獎動畫可免費試玩。';
    $heroPreview = \App\Support\DemoPreviews::forStyle('battle_top');
@endphp
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO --}}
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ url('/') }}">

    {{-- OG --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ url('/images/og-home.svg') }}">
    <meta property="og:site_name" content="抽獎系統">
    <meta property="og:locale" content="zh_TW">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ url('/images/og-home.svg') }}">

    @php
        $demoItemList = collect($animationStyles)
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

        // 首頁精選 FAQ（取流程類 + 公平性類各 3 題，共 6 題作為前導），完整 20 題見 /faq
        $flowItems = \App\Support\SiteFaqs::category('flow')['items'] ?? [];
        $fairnessItems = \App\Support\SiteFaqs::category('fairness')['items'] ?? [];
        $faqs = array_merge(
            array_slice($flowItems, 0, 3),
            array_slice($fairnessItems, 0, 3),
        );

        $faqMainEntity = collect($faqs)->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['a'],
            ],
        ])->all();

        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqMainEntity,
        ];

        $suitableScenes = [
            ['icon' => '🎉', 'title' => '企業尾牙', 'desc' => '年終大型抽獎、多獎項、多場次，主持流程不中斷。'],
            ['icon' => '🍾', 'title' => '春酒 / 年會', 'desc' => '員工聚會暖場、即時切換抽獎節奏更靈活。'],
            ['icon' => '🚀', 'title' => '品牌發表會', 'desc' => '產品發表搭配互動抽獎，提升參與感與停留時間。'],
            ['icon' => '🤝', 'title' => '客戶答謝宴', 'desc' => '名單可分群、資料可匯入匯出，現場更顯專業。'],
            ['icon' => '🎥', 'title' => '直播 / 線上活動', 'desc' => '雲端即時抽獎、遠端觀眾也能參與互動彈幕。'],
            ['icon' => '🏢', 'title' => '社團 / 同學會', 'desc' => '小型聚會也能快速建立活動，簡單上手。'],
        ];

        $setupSteps = [
            ['title' => '建立活動與匯入名單', 'desc' => '名單可用 Excel 整理，另存 CSV 匯入後台，再依部門或群組設定參加資格。'],
            ['title' => '設定獎項與抽獎規則', 'desc' => '新增獎項、設定中獎人數、抽獎模式（一次抽完或逐一抽出）與資格條件。'],
            ['title' => '開放前台抽獎與兌獎', 'desc' => '大屏投影執行抽獎、即時切換獎項；中獎者可透過 QRCode 完成兌獎驗證。'],
        ];

        $howToSetup = [
            '@context' => 'https://schema.org', '@type' => 'HowTo',
            'name' => '如何設定尾牙抽獎活動',
            'description' => '從名單與資格設定，到前台抽獎與工作人員兌獎。',
            'step' => collect($setupSteps)->map(fn ($step, $index) => [
                '@type' => 'HowToStep', 'position' => $index + 1,
                'name' => $step['title'], 'text' => $step['desc'],
            ])->all(),
        ];

        $softwareApplication = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => '抽獎系統',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => url('/'),
            'image' => url('/images/og-home.svg'),
            'description' => $pageDescription,
            'featureList' => [
                '即時切換獎項',
                '員工名單分群管理',
                '即時彈幕互動',
                'QRCode 中獎兌獎',
                '抽獎分析報表',
                '7 種抽獎動畫風格',
                '多組織與多活動並行',
                '後台下載中獎清單',
            ],
            'offers' => [
                '@type' => 'Offer',
                'availability' => 'https://schema.org/InStock',
                'priceSpecification' => [
                    '@type' => 'PriceSpecification',
                    'description' => '提供彈性方案，請聯繫洽談報價',
                ],
            ],
        ];
    @endphp

    {{-- Organization JSON-LD --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "抽獎系統",
        "url": "{{ url('/') }}",
        "logo": "{{ url('/images/og-home.svg') }}"
    }
    </script>

    {{-- WebSite JSON-LD --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "name": "抽獎系統",
        "url": "{{ url('/') }}"
    }
    </script>

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "ItemList",
        "name": "抽獎動畫 Demo 風格總覽",
        "itemListElement": {!! json_encode($demoItemList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    }
    </script>

    {{-- SoftwareApplication JSON-LD --}}
    <script type="application/ld+json">
    {!! json_encode($softwareApplication, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- HowTo JSON-LD：與可見設定步驟共用內容 --}}
    <script type="application/ld+json">
    {!! json_encode($howToSetup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- FAQ JSON-LD --}}
    <script type="application/ld+json">
    {!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ url('/images/og-home.svg') }}">
    <link rel="icon" href="{{ url('/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ url('/images/og-home.svg') }}">
    <meta name="theme-color" content="#0a0a0f">

    {{-- Fonts: 非阻塞載入，避免行動裝置 render-blocking 拖慢 LCP --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700;900&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700;900&display=swap"></noscript>


    @vite(['resources/css/app.css', 'resources/js/home.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --c-bg: #0a0a0f;
            --c-bg-card: #12121a;
            --c-bg-card-hover: #1a1a26;
            --c-red: #dc2626;
            --c-red-glow: #ef4444;
            --c-gold: #f59e0b;
            --c-gold-light: #fbbf24;
            --c-gold-dim: #b45309;
            --c-slate: #1e293b;
            --c-text: #e2e8f0;
            --c-text-dim: #94a3b8;
            --c-border: #1e293b;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Noto Sans TC', 'Figtree', system-ui, sans-serif;
            background: var(--c-bg);
            color: var(--c-text);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* ── Scroll Reveal ── */
        [data-reveal] {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.7s cubic-bezier(.16,1,.3,1), transform 0.7s cubic-bezier(.16,1,.3,1);
        }
        [data-reveal].revealed {
            opacity: 1;
            transform: translateY(0);
        }

        /* Hero 首屏：用 CSS 自動進場，不等 JS。避免 LCP 被 opacity:0 卡住。 */
        .hero [data-reveal] {
            opacity: 1;
            transform: none;
            animation: heroFadeIn 0.6s cubic-bezier(.16,1,.3,1) both;
        }
        .hero [data-reveal][data-reveal-delay="120"] { animation-delay: .12s; }
        .hero [data-reveal][data-reveal-delay="240"] { animation-delay: .24s; }
        .hero [data-reveal][data-reveal-delay="320"] { animation-delay: .32s; }
        @keyframes heroFadeIn {
            from { opacity: .35; transform: translateY(20px); }
            to   { opacity: 1; transform: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .hero [data-reveal] { animation: none; }
        }

        /* ── Hero ── */
        .hero {
            position: relative;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 6rem 1.5rem 4rem;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(900px circle at 30% 20%, rgba(220, 38, 38, 0.18), transparent 60%),
                radial-gradient(700px circle at 75% 60%, rgba(245, 158, 11, 0.14), transparent 55%),
                radial-gradient(400px circle at 50% 90%, rgba(245, 158, 11, 0.08), transparent 50%),
                linear-gradient(180deg, #0a0a0f 0%, #0d0d16 100%);
            z-index: 0;
        }

        /* Floating gold particles */
        .hero-particles {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .hero-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--c-gold);
            opacity: 0;
            animation: particleFloat 8s ease-in-out infinite;
        }
        .hero-particle:nth-child(1) { left: 8%; top: 22%; animation-delay: 0s; animation-duration: 6.4s; width: 2px; height: 2px; }
        .hero-particle:nth-child(2) { left: 14%; top: 68%; animation-delay: 0.45s; animation-duration: 8.2s; width: 3px; height: 3px; }
        .hero-particle:nth-child(3) { left: 21%; top: 35%; animation-delay: 0.9s; animation-duration: 7.1s; width: 4px; height: 4px; }
        .hero-particle:nth-child(4) { left: 27%; top: 82%; animation-delay: 1.35s; animation-duration: 9.4s; width: 2px; height: 2px; }
        .hero-particle:nth-child(5) { left: 33%; top: 28%; animation-delay: 1.8s; animation-duration: 7.8s; width: 5px; height: 5px; }
        .hero-particle:nth-child(6) { left: 39%; top: 58%; animation-delay: 2.25s; animation-duration: 6.9s; width: 3px; height: 3px; }
        .hero-particle:nth-child(7) { left: 46%; top: 18%; animation-delay: 2.7s; animation-duration: 8.8s; width: 4px; height: 4px; }
        .hero-particle:nth-child(8) { left: 52%; top: 74%; animation-delay: 3.15s; animation-duration: 7.3s; width: 2px; height: 2px; }
        .hero-particle:nth-child(9) { left: 58%; top: 41%; animation-delay: 3.6s; animation-duration: 9.1s; width: 5px; height: 5px; }
        .hero-particle:nth-child(10) { left: 63%; top: 24%; animation-delay: 4.05s; animation-duration: 6.6s; width: 3px; height: 3px; }
        .hero-particle:nth-child(11) { left: 69%; top: 63%; animation-delay: 4.5s; animation-duration: 8.5s; width: 4px; height: 4px; }
        .hero-particle:nth-child(12) { left: 74%; top: 33%; animation-delay: 4.95s; animation-duration: 7.6s; width: 2px; height: 2px; }
        .hero-particle:nth-child(13) { left: 79%; top: 84%; animation-delay: 5.4s; animation-duration: 9.7s; width: 4px; height: 4px; }
        .hero-particle:nth-child(14) { left: 84%; top: 46%; animation-delay: 5.85s; animation-duration: 7.2s; width: 3px; height: 3px; }
        .hero-particle:nth-child(15) { left: 88%; top: 19%; animation-delay: 6.3s; animation-duration: 8.1s; width: 5px; height: 5px; }
        .hero-particle:nth-child(16) { left: 91%; top: 57%; animation-delay: 6.75s; animation-duration: 6.8s; width: 2px; height: 2px; }
        .hero-particle:nth-child(17) { left: 76%; top: 71%; animation-delay: 7.2s; animation-duration: 8.9s; width: 3px; height: 3px; }
        .hero-particle:nth-child(18) { left: 17%; top: 88%; animation-delay: 7.65s; animation-duration: 7.4s; width: 4px; height: 4px; }
        @keyframes particleFloat {
            0% { opacity: 0; transform: translateY(0) scale(0.5); }
            20% { opacity: 0.7; }
            80% { opacity: 0.3; }
            100% { opacity: 0; transform: translateY(-120px) scale(0); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 52rem;
        }
        .hero h1 {
            font-size: clamp(2rem, 5.5vw, 3.5rem);
            font-weight: 900;
            line-height: 1.25;
            margin: 0 0 1.25rem;
            letter-spacing: -0.01em;
        }
        .hero h1 .text-gradient {
            background: linear-gradient(135deg, var(--c-gold-light) 0%, var(--c-gold) 40%, var(--c-red) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero .subtitle {
            font-size: clamp(1rem, 2.2vw, 1.25rem);
            color: var(--c-text-dim);
            line-height: 1.8;
            margin: 0 0 2.5rem;
            max-width: 36rem;
            margin-left: auto;
            margin-right: auto;
        }

        /* CTA buttons */
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--c-red) 0%, #b91c1c 100%);
            color: #fff;
            box-shadow: 0 0 30px rgba(220, 38, 38, 0.3), 0 4px 12px rgba(0,0,0,0.3);
        }
        .btn-primary:hover {
            box-shadow: 0 0 40px rgba(220, 38, 38, 0.5), 0 6px 20px rgba(0,0,0,0.4);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: var(--c-gold-light);
            border: 1px solid rgba(245, 158, 11, 0.25);
        }
        .btn-secondary:hover {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.5);
            transform: translateY(-2px);
        }

        /* ── Section common ── */
        .section {
            padding: 5rem 1.5rem;
            max-width: 72rem;
            margin: 0 auto;
        }
        .section-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }
        .section-header h2 {
            font-size: clamp(1.5rem, 3.5vw, 2.25rem);
            font-weight: 900;
            margin: 0 0 1rem;
            letter-spacing: -0.01em;
        }
        .section-header .accent {
            color: var(--c-gold);
        }
        .section-header p {
            color: var(--c-text-dim);
            font-size: 1.05rem;
            max-width: 32rem;
            margin: 0 auto;
            line-height: 1.7;
        }
        .section-divider {
            width: 48px;
            height: 3px;
            background: linear-gradient(90deg, var(--c-red), var(--c-gold));
            border-radius: 2px;
            margin: 0 auto 1.5rem;
        }

        /* ── Features grid ── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        @media (max-width: 768px) {
            .features-grid { grid-template-columns: 1fr; }
        }
        .feature-card {
            background: var(--c-bg-card);
            border: 1px solid var(--c-border);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.35s ease;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--c-gold), transparent);
            opacity: 0;
            transition: opacity 0.35s;
        }
        .feature-card:hover {
            background: var(--c-bg-card-hover);
            border-color: rgba(245, 158, 11, 0.2);
            transform: translateY(-4px);
        }
        .feature-card:hover::before {
            opacity: 1;
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.15), rgba(245, 158, 11, 0.1));
            border: 1px solid rgba(245, 158, 11, 0.12);
        }
        .feature-icon svg {
            width: 24px;
            height: 24px;
            color: var(--c-gold);
        }
        .feature-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 0.6rem;
        }
        .feature-card p {
            color: var(--c-text-dim);
            font-size: 0.92rem;
            line-height: 1.65;
            margin: 0;
        }

        /* ── Animation Preview ── */
        .preview-section {
            background:
                radial-gradient(600px circle at 50% 30%, rgba(245, 158, 11, 0.06), transparent 60%),
                linear-gradient(180deg, rgba(30, 41, 59, 0.15) 0%, transparent 100%);
            border-top: 1px solid var(--c-border);
            border-bottom: 1px solid var(--c-border);
            padding: 5rem 1.5rem;
        }
        .preview-inner {
            max-width: 68rem;
            margin: 0 auto;
        }
        .preview-tabs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .preview-tab {
            display: inline-block;
            padding: 0.55rem 1.25rem;
            border-radius: 100px;
            font-size: 0.88rem;
            font-weight: 600;
            border: 1px solid var(--c-border);
            background: transparent;
            color: var(--c-text-dim);
            cursor: pointer;
            transition: all 0.25s;
            font-family: inherit;
            text-decoration: none;
        }
        .preview-tab:hover {
            border-color: rgba(245, 158, 11, 0.3);
            color: var(--c-text);
        }
        .preview-tab.active {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(245, 158, 11, 0.15));
            border-color: var(--c-gold-dim);
            color: var(--c-gold-light);
        }
        .preview-window {
            position: relative;
            aspect-ratio: 16 / 10;
            max-width: 60rem;
            margin: 0 auto 1.5rem;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--c-border);
            background: var(--c-bg-card);
        }
        .preview-window iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
            transition: opacity 0.35s ease;
        }
        .preview-card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }
        .preview-card {
            display: flex;
            flex-direction: column;
            min-height: 100%;
            overflow: hidden;
            border: 1px solid var(--c-border);
            border-radius: 16px;
            background: var(--c-bg-card);
            text-decoration: none;
            transition: transform 0.25s ease, border-color 0.25s ease, background 0.25s ease;
        }
        .preview-card:hover {
            background: var(--c-bg-card-hover);
            border-color: rgba(245, 158, 11, 0.35);
            transform: translateY(-3px);
        }
        .preview-card-visual {
            display: grid;
            place-items: center;
            min-height: 10rem;
            padding: 1rem;
            background: radial-gradient(circle at 50% 40%, rgba(245, 158, 11, 0.12), transparent 65%);
        }
        .preview-card-visual img {
            display: block;
            width: 100%;
            height: 9rem;
            object-fit: contain;
        }
        .preview-card-visual-fallback {
            color: var(--c-gold-light);
            font-size: 1.15rem;
            font-weight: 800;
            text-align: center;
        }
        .preview-card-content {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 1.15rem 1.25rem 1.25rem;
        }
        .preview-card-content h3 {
            margin: 0 0 0.5rem;
            font-size: 1.05rem;
        }
        .preview-card-content p {
            flex: 1;
            margin: 0;
            color: var(--c-text-dim);
            font-size: 0.9rem;
            line-height: 1.65;
        }
        .preview-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .preview-card-actions a {
            text-decoration: none;
        }
        .preview-card-actions span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.45rem 0.75rem;
            color: var(--c-gold-light);
            border: 1px solid rgba(245, 158, 11, 0.25);
            font-size: 0.78rem;
            font-weight: 700;
        }
        .preview-desc {
            text-align: center;
            color: var(--c-text-dim);
            font-size: 1rem;
            max-width: 28rem;
            margin: 0 auto;
            line-height: 1.6;
            min-height: 3.2rem;
            transition: opacity 0.3s;
        }

        /* ── Scenes grid ── */
        .scene-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }
        @media (max-width: 768px) {
            .scene-grid { grid-template-columns: 1fr; }
        }
        .scene-card {
            background: var(--c-bg-card);
            border: 1px solid var(--c-border);
            border-radius: 14px;
            padding: 1.5rem 1.35rem;
            transition: transform 0.25s ease, border-color 0.25s ease, background 0.25s ease;
        }
        .scene-card:hover {
            background: var(--c-bg-card-hover);
            border-color: rgba(245, 158, 11, 0.25);
            transform: translateY(-3px);
        }
        .scene-icon {
            font-size: 1.85rem;
            line-height: 1;
            margin-bottom: 0.85rem;
        }
        .scene-card h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
        }
        .scene-card p {
            font-size: 0.9rem;
            color: var(--c-text-dim);
            line-height: 1.65;
            margin: 0;
        }

        /* ── Steps grid ── */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            counter-reset: stepnum;
        }
        @media (max-width: 768px) {
            .steps-grid { grid-template-columns: 1fr; }
        }
        .step-card {
            position: relative;
            background: var(--c-bg-card);
            border: 1px solid var(--c-border);
            border-radius: 16px;
            padding: 2rem 1.75rem 1.75rem;
        }
        .step-card::before {
            counter-increment: stepnum;
            content: counter(stepnum, decimal-leading-zero);
            position: absolute;
            top: 1.2rem;
            right: 1.4rem;
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--c-gold-light), var(--c-red));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.7;
            letter-spacing: 0.02em;
        }
        .step-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 0.6rem;
        }
        .step-card p {
            color: var(--c-text-dim);
            font-size: 0.93rem;
            line-height: 1.7;
            margin: 0;
        }

        /* ── Report section ── */
        .report-section {
            padding: 5rem 1.5rem;
        }
        .report-inner {
            max-width: 60rem;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        @media (max-width: 768px) {
            .report-inner {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
        }
        .report-text h2 {
            font-size: clamp(1.5rem, 3.5vw, 2rem);
            font-weight: 900;
            margin: 0 0 1.25rem;
            line-height: 1.3;
        }
        .report-text p {
            color: var(--c-text-dim);
            font-size: 1rem;
            line-height: 1.75;
            margin: 0;
        }
        .report-visual {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .report-icon-box {
            width: 220px;
            height: 180px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--c-bg-card) 0%, rgba(30, 41, 59, 0.4) 100%);
            border: 1px solid var(--c-border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .report-icon-box svg {
            width: 56px;
            height: 56px;
            color: var(--c-gold);
            opacity: 0.85;
        }
        .report-icon-box span {
            font-size: 0.88rem;
            color: var(--c-text-dim);
        }

        /* ── FAQ ── */
        .faq-section {
            background:
                radial-gradient(500px circle at 70% 50%, rgba(220, 38, 38, 0.04), transparent 60%);
            padding: 5rem 1.5rem;
        }
        .faq-inner {
            max-width: 44rem;
            margin: 0 auto;
        }
        .faq-item {
            border-bottom: 1px solid var(--c-border);
        }
        .faq-item--details {
            padding: 0;
        }
        .faq-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            background: none;
            border: none;
            padding: 1.25rem 0;
            color: var(--c-text);
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            font-family: inherit;
            transition: color 0.2s;
            list-style: none;
            gap: 1rem;
        }
        .faq-trigger::-webkit-details-marker { display: none; }
        .faq-trigger:hover {
            color: var(--c-gold-light);
        }
        .faq-toggle-icon {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--c-gold-light);
            line-height: 1;
            flex-shrink: 0;
            transition: transform 0.25s ease;
        }
        details[open] .faq-toggle-icon {
            transform: rotate(45deg);
        }
        .faq-answer {
            padding: 0 0 1.25rem;
        }
        .faq-answer p {
            color: var(--c-text-dim);
            font-size: 0.95rem;
            line-height: 1.85;
            margin: 0;
            white-space: pre-wrap;
        }
        .faq-more-link {
            display: inline-block;
            color: var(--c-gold-light);
            text-decoration: none;
            border: 1px solid var(--c-gold-dim);
            padding: 0.65rem 1.5rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }
        .faq-more-link:hover {
            background: rgba(245, 158, 11, 0.12);
            border-color: var(--c-gold-light);
        }

        /* ── Final CTA ── */
        .final-cta {
            position: relative;
            padding: 5rem 1.5rem;
            text-align: center;
            overflow: hidden;
        }
        .final-cta::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(600px circle at 50% 50%, rgba(220, 38, 38, 0.12), transparent 60%),
                radial-gradient(400px circle at 50% 80%, rgba(245, 158, 11, 0.08), transparent 50%);
            z-index: 0;
        }
        .final-cta-content {
            position: relative;
            z-index: 1;
            max-width: 36rem;
            margin: 0 auto;
        }
        .final-cta h2 {
            font-size: clamp(1.5rem, 3.5vw, 2.25rem);
            font-weight: 900;
            margin: 0 0 1.5rem;
        }

        /* ── Footer ── */
        .site-footer {
            border-top: 1px solid var(--c-border);
            padding: 2rem 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--c-text-dim);
        }
        .site-footer a {
            color: var(--c-gold);
            text-decoration: none;
        }
        .site-footer a:hover {
            text-decoration: underline;
        }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .hero { padding: 4rem 1rem 3rem; }
            .btn { padding: 0.75rem 1.5rem; font-size: 0.92rem; }
            .section { padding: 3.5rem 1rem; }
            .preview-section, .faq-section, .report-section { padding: 3.5rem 1rem; }
            .feature-card { padding: 1.5rem; }
            .preview-tabs { gap: 0.35rem; }
            .preview-tab { padding: 0.45rem 0.9rem; font-size: 0.82rem; }
            .preview-window {
                aspect-ratio: 4 / 3;
            }
            .preview-card-grid { grid-template-columns: 1fr; }
        }

        /* 尾牙產品首頁 */
        .hero { min-height:80svh; padding:5rem 1.5rem 4rem; }
        .hero-layout { position:relative; z-index:1; display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); align-items:center; gap:3rem; width:min(100%,72rem); text-align:left; }
        .hero-layout .hero-content { min-width:0; }
        .hero-eyebrow { margin:0 0 1.5rem; color:var(--c-gold-light); font-size:.82rem; letter-spacing:.13em; }
        .hero-layout h1 { font-size:clamp(2.3rem,4.5vw,3.8rem); margin-bottom:1rem; }
        .hero-tagline { font-size:clamp(1.2rem,2.5vw,1.65rem); font-weight:700; margin:0 0 1.2rem; color:var(--c-text); }
        .hero-layout .subtitle { margin:0 0 1.75rem; font-size:1rem; }
        .hero-layout .btn-group { justify-content:flex-start; margin-bottom:1rem; gap:.7rem; }
        .hero-layout .btn { padding:.9rem 1.25rem; }
        .hero-account { font-size:.77rem; color:var(--c-text-dim); line-height:1.8; }
        .hero-account a { color:var(--c-gold-light); margin-left:.5rem; text-underline-offset:.25em; }
        .hero-shortcuts { display:flex; flex-wrap:wrap; gap:1.25rem; margin-top:1.5rem; }
        .hero-shortcuts a { font-size:.82rem; color:var(--c-text-dim); text-decoration:none; }
        .hero-preview { min-width:0; margin:0; border:1px solid #f59e0b44; border-radius:1rem; overflow:hidden; background:#12121a; box-shadow:0 30px 80px #0006; }
        .hero-preview-bar { display:flex; justify-content:space-between; padding:.8rem 1rem; color:var(--c-text-dim); font-size:.7rem; border-bottom:1px solid var(--c-border); }
        .hero-preview-bar i { display:inline-block; width:6px; height:6px; border-radius:50%; background:#685b47; margin-right:5px; }
        .hero-preview img { display:block; width:100%; height:auto; }
        .hero-preview figcaption { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; padding:1rem; font-size:.72rem; }
        .hero-preview figcaption span { color:var(--c-text-dim); }
        .hero-preview figcaption a { margin-left:auto; color:var(--c-gold-light); text-decoration:none; }
        .event-prep { padding-top:3rem; }
        .prep-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
        .prep-card { display:flex; flex-direction:column; padding:1.75rem; background:var(--c-bg-card); border:1px solid var(--c-border); border-radius:1rem; }
        .prep-number { color:var(--c-gold); font:400 1.5rem ui-monospace,monospace; }
        .prep-card h3 { font-size:1.1rem; margin:1rem 0 .75rem; }
        .prep-card p { color:var(--c-text-dim); line-height:1.9; font-size:.9rem; margin:0 0 1.5rem; }
        .prep-card a { margin-top:auto; color:var(--c-gold-light); font-size:.83rem; text-decoration:none; line-height:1.7; }
        .prep-footer { display:flex; justify-content:center; gap:1.5rem; flex-wrap:wrap; margin-top:1.5rem; }
        .prep-footer a { font-size:.85rem; color:var(--c-gold-light); text-underline-offset:.3em; }
        @media(max-width:900px) { .hero-layout { grid-template-columns:1fr; max-width:44rem; gap:2.5rem; } .hero-layout .hero-content { max-width:none; } .hero { min-height:auto; padding-top:3rem; } .prep-grid { grid-template-columns:1fr; } }
        @media(max-width:480px) { .hero { padding:2.5rem 1rem; } .hero-layout h1 { font-size:2.3rem; } .hero-tagline { font-size:1.2rem; } .hero-preview figcaption span { flex-basis:100%; } .hero-preview figcaption a { margin-left:0; } .prep-card { padding:1.4rem; } }
    </style>
</head>
<body>
    @include('lottery.partials.demo-metrics', ['metricEntry' => 'home'])
    {{-- ════════════════════════════════════════════
         1. HERO
    ════════════════════════════════════════════ --}}
    <section class="hero">
        <div class="hero-particles" aria-hidden="true">
            @for ($i = 0; $i < 18; $i++)
                <span class="hero-particle"></span>
            @endfor
        </div>

        <div class="hero-layout">
            <div class="hero-content">
                <p class="hero-eyebrow">公司尾牙・春酒・員工活動</p>
                <h1 data-reveal><span class="text-gradient">尾牙抽獎系統</span></h1>
                <p class="hero-tagline">名單、獎項與補抽管理</p>
                <p class="subtitle" data-reveal data-reveal-delay="120">匯入員工名單、設定參加資格，從後台切換投影畫面。中獎者不在場時，可釋出名額再補抽，保留中獎與替補紀錄。</p>
                <div class="btn-group" data-reveal data-reveal-delay="240">
                    <a href="{{ route('demo.lottery.landing') }}" class="btn btn-primary" data-demo-event="play_click">免費試玩 7 種動畫 ↗</a>
                    <a href="{{ route('register-account') }}" class="btn btn-secondary" data-demo-event="register_click">建立抽獎活動</a>
                </div>
                <p class="hero-account">動畫 Demo 免登入；正式活動需建立帳號。<a href="/admin">登入後台</a></p>
                <div class="hero-shortcuts"><a href="{{ route('guides.year-end-lottery') }}">尾牙抽獎流程 →</a><a href="{{ route('features.index') }}">查看系統功能 →</a></div>
            </div>
            @if ($heroPreview)
                <figure class="hero-preview">
                    <div class="hero-preview-bar"><span><i></i><i></i><i></i></span><span>抽獎動畫實際畫面</span></div>
                    <a href="{{ route('demo.lottery.style', ['slug' => 'battle-top']) }}"><img src="{{ $heroPreview['action']['url'] }}" width="{{ $heroPreview['action']['width'] }}" height="{{ $heroPreview['action']['height'] }}" alt="戰鬥陀螺抽獎動畫，八位合成參加者在競技場中對戰" fetchpriority="high" decoding="async"></a>
                    <figcaption><strong>戰鬥陀螺</strong><span>合成名單的 Demo 截圖</span><a href="{{ route('demo.lottery.play', ['slug' => 'battle-top']) }}" data-demo-event="play_click" data-demo-style="battle_top">試玩這個動畫 ↗</a></figcaption>
                </figure>
            @endif
        </div>
    </section>

    <section class="section event-prep" id="year-end-planning" aria-labelledby="year-end-heading">
        <div class="section-header"><div class="section-divider"></div><h2 id="year-end-heading">尾牙抽獎，先準備這三件事</h2><p>從名單到唱名，依公司的活動規則安排。</p></div>
        <div class="prep-grid">
            <article class="prep-card"><span class="prep-number">01</span><h3>整理名單與參加資格</h3><p>確認每個獎項誰能抽、能否重複中獎。用 Excel 整理名單，另存 CSV 匯入後台。</p><a href="{{ route('guides.lottery-participant-import') }}">名單匯入教學 →</a></article>
            <article class="prep-card"><span class="prep-number">02</span><h3>安排抽獎流程與台詞</h3><p>決定獎項順序、揭獎方式與領獎安排。主持稿與流程表可依現場分工改寫。</p><a href="{{ route('guides.lottery-host-script') }}">尾牙抽獎主持稿與流程表 →</a></article>
            <article class="prep-card"><span class="prep-number">03</span><h3>先約定缺席與補抽規則</h3><p>唱名後等多久、由誰確認缺席，事前說清楚。需要補抽時，先釋出名額再重新抽獎。</p><a href="{{ route('features.absent-redraw') }}">不在場重抽功能 →</a></article>
        </div>
        <div class="prep-footer"><a href="{{ route('guides.year-end-lottery') }}">閱讀完整尾牙抽獎流程 →</a><a href="{{ route('guides.lottery-rules') }}">重複中獎與資格設定 →</a></div>
    </section>

    {{-- ════════════════════════════════════════════
         2. FEATURES
    ════════════════════════════════════════════ --}}
    <section class="section" id="features">
        <div class="section-header" data-reveal>
            <div class="section-divider"></div>
            <h2>尾牙抽獎的<span class="accent">現場管理功能</span></h2>
        </div>

        <div class="features-grid">
            {{-- Feature 1: 即時切換 --}}
            <div class="feature-card" data-reveal data-reveal-delay="0">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </div>
                <h3>即時切換</h3>
                <p>後台安排目前獎項與預覽，確認前台完成切換，再交給現場開抽。</p>
                <a href="{{ route('features.lottery-control') }}">了解後台與前台如何協作 →</a>
            </div>

            {{-- Feature 2: 設定簡單 --}}
            <div class="feature-card" data-reveal data-reveal-delay="80">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>名單與資格設定</h3>
                <p>管理員工名單與群組，分別設定每個獎項的包含、排除與重複中獎規則。</p>
            </div>

            {{-- Feature 3: 報表分析 --}}
            <div class="feature-card" data-reveal data-reveal-delay="160">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <h3>報表分析</h3>
                <p>單次最多模擬 1,000 次，查看獎項與部門統計，下載分析表供籌備討論。</p>
                <a href="{{ route('features.lottery-analysis') }}">查看分析內容與適用條件 →</a>
            </div>

            {{-- Feature 4: 即時彈幕 --}}
            <div class="feature-card" data-reveal data-reveal-delay="240">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h3>即時彈幕</h3>
                <p>提供即時彈幕互動，提升員工參與感與現場熱度，讓抽獎更有臨場感。</p>
            </div>

            {{-- Feature 5: QRCode 兌獎 --}}
            <div class="feature-card" data-reveal data-reveal-delay="320">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="8" height="8" rx="1"/><rect x="14" y="2" width="8" height="8" rx="1"/><rect x="2" y="14" width="8" height="8" rx="1"/><rect x="14" y="14" width="4" height="4" rx="0.5"/><line x1="22" y1="14" x2="22" y2="14.01"/><line x1="22" y1="18" x2="22" y2="22"/><line x1="18" y1="22" x2="18" y2="22.01"/></svg>
                </div>
                <h3>QRCode 兌獎</h3>
                <p>中獎後可透過 QRCode 快速驗證與領獎，流程清楚、效率高、降低人工錯誤。</p>
            </div>

            {{-- Feature 6: 多種動畫 --}}
            <div class="feature-card" data-reveal data-reveal-delay="400">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
                </div>
                <h3>多種動畫</h3>
                <p>提供多種抽獎動畫與視覺效果，營造年節與尾牙氛圍，讓活動更有記憶點。</p>
            </div>
        </div>
        <p style="text-align:center;margin-top:2rem;"><a href="{{ route('features.index') }}" style="color:var(--c-gold);">深入了解功能：模擬分析、後台主控與不在場重抽 →</a></p>
    </section>

    {{-- ════════════════════════════════════════════
         2.5 適合哪些活動
    ════════════════════════════════════════════ --}}
    <section class="section" id="scenes">
        <div class="section-header" data-reveal>
            <div class="section-divider"></div>
            <h2>適合<span class="accent">哪些活動</span>使用</h2>
            <p>適用尾牙、春酒及團隊活動，依名單、獎項與現場分工設定抽獎。</p>
        </div>

        <div class="scene-grid">
            @foreach ($suitableScenes as $i => $scene)
                <div class="scene-card" data-reveal data-reveal-delay="{{ $i * 60 }}">
                    <div class="scene-icon" aria-hidden="true">{{ $scene['icon'] }}</div>
                    <h3>{{ $scene['title'] }}</h3>
                    <p>{{ $scene['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         2.7 3 步驟完成一場抽獎
    ════════════════════════════════════════════ --}}
    <section class="section" id="setup-steps">
        <div class="section-header" data-reveal>
            <div class="section-divider"></div>
            <h2>3 步驟<span class="accent">完成一場抽獎</span></h2>
            <p>從建立活動到中獎兌獎，照著做就能快速上手。</p>
        </div>

        <p style="text-align:center; margin-bottom:2rem;"><a href="{{ route('guides.year-end-lottery') }}" style="color:var(--c-gold-light);">籌備尾牙抽獎：查看名單、獎項安排與當天彩排清單 →</a></p>
        <div class="steps-grid">
            @foreach ($setupSteps as $i => $step)
                <div class="step-card" data-reveal data-reveal-delay="{{ $i * 80 }}">
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         3. ANIMATION PREVIEW
    ════════════════════════════════════════════ --}}
    <section class="preview-section" id="preview">
        <div class="preview-inner">

            <div class="section-header" data-reveal>
                <div class="section-divider"></div>
                <h2><span class="accent">抽獎動畫</span>風格介紹</h2>
                <p>{{ count($animationStyles) }} 種風格，各自對應不同活動情境；先看介紹，再進入參數試玩。</p>
            </div>

            <div class="preview-card-grid" data-reveal data-reveal-delay="120">
                @foreach ($animationStyles as $style)
                    <article class="preview-card">
                        <a href="{{ route('demo.lottery.style', ['slug' => $style['slug']]) }}" class="preview-card-visual" aria-label="查看{{ $style['label'] }}介紹">
                            @php($previewImages = \App\Support\DemoPreviews::forStyle($style['key']))
                            @if ($previewImages)
                                <img src="{{ $previewImages['action']['url'] }}" alt="{{ $style['label'] }}抽獎動畫實際畫面，使用合成名單" width="{{ $previewImages['action']['width'] }}" height="{{ $previewImages['action']['height'] }}" loading="lazy" decoding="async">
                            @elseif (file_exists(public_path('images/previews/'.$style['key'].'.svg')))
                                <img src="{{ url('/images/previews/'.$style['key'].'.svg') }}" alt="{{ $style['label'] }}抽獎動畫示意圖" width="800" height="450" loading="lazy">
                            @else
                                <span class="preview-card-visual-fallback">{{ $style['label'] }}<br>抽獎動畫</span>
                            @endif
                        </a>
                        <div class="preview-card-content">
                            <h3>{{ $style['label'] }}</h3>
                            <p>{{ $style['desc'] }}</p>
                            <div class="preview-card-actions">
                                <a href="{{ route('demo.lottery.style', ['slug' => $style['slug']]) }}"><span>查看介紹</span></a>
                                <a href="{{ route('demo.lottery.play', ['slug' => $style['slug']]) }}" data-demo-event="play_click" data-demo-style="{{ $style['key'] }}"><span>立即試玩</span></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <p data-reveal data-reveal-delay="300" style="text-align:center;margin-top:1.5rem;">
                <a href="/demo/lottery" style="color:var(--c-gold-light);font-size:0.95rem;text-decoration:underline;text-underline-offset:3px;">查看全部 {{ count($animationStyles) }} 種抽獎動畫，免費試玩 →</a>
            </p>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         4. REPORT VALUE
    ════════════════════════════════════════════ --}}
    <section class="report-section">
        <div class="report-inner">
            <div class="report-text" data-reveal>
                <div class="section-divider" style="margin: 0 0 1.5rem;"></div>
                <h2>活動紀錄與<span class="accent">分析報表</span></h2>
                <p>活動後可匯出中獎名單，核對釋出與替補紀錄。活動前也可執行模擬分析、下載獎項與部門統計，供籌備討論；分析的適用範圍請見功能說明。</p>
            </div>
            <div class="report-visual" data-reveal data-reveal-delay="150">
                <div class="report-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    <span>中獎紀錄・模擬分析報表</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         5. FAQ
    ════════════════════════════════════════════ --}}
    <section class="faq-section" id="faq">
        <div class="faq-inner">
            <div class="section-header" data-reveal>
                <div class="section-divider"></div>
                <h2>常見問題</h2>
                <p>挑了 6 題最常被問到的，完整 20 題請看 <a href="{{ route('faq') }}" style="color:var(--c-gold-light);text-decoration:underline;text-underline-offset:3px;">FAQ 頁面</a>。</p>
            </div>

            @foreach ($faqs as $i => $faq)
                <details class="faq-item faq-item--details" data-reveal data-reveal-delay="{{ $i * 60 }}">
                    <summary class="faq-trigger">
                        <span>{{ $faq['q'] }}</span>
                        <span class="faq-toggle-icon" aria-hidden="true">+</span>
                    </summary>
                    <div class="faq-answer faq-answer--open">
                        <p>{{ $faq['a'] }}</p>
                    </div>
                </details>
            @endforeach

            <div class="faq-more" data-reveal style="text-align:center;margin-top:2rem;">
                <a href="{{ route('faq') }}" class="faq-more-link">查看完整 20 題 FAQ →</a>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         6. FINAL CTA
    ════════════════════════════════════════════ --}}
    <section class="final-cta">
        <div class="final-cta-content">
            <h2 data-reveal>準備你的<span class="accent">尾牙抽獎</span></h2>

            <div class="btn-group" data-reveal data-reveal-delay="100">
                <a href="{{ route('register-account') }}" class="btn btn-primary" data-demo-event="register_click">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    建立抽獎活動
                </a>
                <a href="/demo/lottery" class="btn btn-secondary">試玩抽獎動畫</a>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         7. FOOTER
    ════════════════════════════════════════════ --}}
    <footer class="site-footer">
        <p>
            &copy; {{ date('Y') }} 抽獎系統 &mdash;
            <a href="{{ route('faq') }}">常見問題</a> ·
            <a href="{{ route('features.index') }}">功能介紹</a> ·
            <a href="{{ route('guides.index') }}">活動準備指南</a> ·
            <a href="{{ route('feedback.index', ['from' => '/']) }}">意見回饋／聯絡我們</a> ·
            <a href="/demo/lottery">抽獎動畫 Demo</a> ·
            <a href="/admin">登入後台</a>
        </p>
    </footer>
</body>
</html>
