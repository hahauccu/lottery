<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO --}}
    <title>常見問題 FAQ｜尾牙抽獎、線上抽獎、企業抽獎系統</title>
    <meta name="description" content="尾牙抽獎、企業活動、線上抽獎常見問題集 — 涵蓋抽獎流程、公平性與費用、彈幕與 QRCode 領獎、瀏覽器與裝置相容性等 20 題實戰問答。">
    <meta name="keywords" content="尾牙抽獎,線上抽獎,抽獎系統,企業抽獎,QRCode 領獎,彈幕抽獎,抽獎FAQ,常見問題">
    <link rel="canonical" href="{{ route('faq') }}">
    <meta name="robots" content="index,follow,max-image-preview:large">

    {{-- OG --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="常見問題 FAQ｜尾牙抽獎、線上抽獎、企業抽獎系統">
    <meta property="og:description" content="20 題實戰問答 — 涵蓋抽獎流程、公平性與費用、彈幕與 QRCode 領獎、瀏覽器與裝置相容性。">
    <meta property="og:url" content="{{ route('faq') }}">
    <meta property="og:image" content="{{ url('/images/og-home.svg') }}">
    <meta property="og:site_name" content="抽獎系統">
    <meta property="og:locale" content="zh_TW">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="常見問題 FAQ｜尾牙抽獎、線上抽獎、企業抽獎系統">
    <meta name="twitter:description" content="20 題實戰問答 — 抽獎流程、公平性、彈幕與 QRCode 領獎、相容性全收錄。">
    <meta name="twitter:image" content="{{ url('/images/og-home.svg') }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ url('/images/og-home.svg') }}">
    <link rel="icon" href="{{ url('/favicon.ico') }}" sizes="any">
    <meta name="theme-color" content="#0a0a0f">

    @php
        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => '常見問題 FAQ', 'item' => route('faq')],
            ],
        ];

        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($flatFaqs)->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $f['a'],
                ],
            ])->all(),
        ];

        $webPage = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => '常見問題 FAQ｜尾牙抽獎、線上抽獎、企業抽獎系統',
            'url' => route('faq'),
            'description' => '尾牙抽獎、企業活動、線上抽獎常見問題集，共 20 題涵蓋流程、公平性、功能與相容性。',
            'inLanguage' => 'zh-Hant',
        ];
    @endphp

    <script type="application/ld+json">{!! json_encode($webPage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --gold-light: #fbbf24;
            --gold-dim: #b45309;
        }
        body {
            background:
                radial-gradient(1200px circle at 20% 10%, rgba(245, 158, 11, 0.18), transparent 55%),
                radial-gradient(800px circle at 90% 30%, rgba(99, 102, 241, 0.12), transparent 55%),
                linear-gradient(180deg, #030712, #000);
            color: #fff;
            min-height: 100svh;
        }
        .faq-page {
            max-width: 56rem;
            margin: 0 auto;
            padding: 4rem 1.5rem 5rem;
        }
        .faq-breadcrumb {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.55);
            margin-bottom: 1.5rem;
        }
        .faq-breadcrumb a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
        }
        .faq-breadcrumb a:hover {
            color: var(--gold-light);
        }
        .faq-page h1 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 800;
            margin: 0 0 0.75rem;
            line-height: 1.3;
        }
        .faq-page h1 .accent {
            color: var(--gold-light);
        }
        .faq-page__lead {
            color: rgba(255,255,255,0.7);
            line-height: 1.8;
            margin: 0 0 2rem;
            font-size: 1rem;
        }

        .faq-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0 0 2.5rem;
            padding: 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 1rem;
        }
        .faq-nav a {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 0.95rem;
            border-radius: 999px;
            font-size: 0.88rem;
            font-weight: 600;
            color: rgba(255,255,255,0.75);
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .faq-nav a:hover {
            color: var(--gold-light);
            border-color: rgba(245, 158, 11, 0.4);
        }

        .faq-category {
            margin-bottom: 3rem;
            scroll-margin-top: 1rem;
        }
        .faq-category h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 0 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .faq-category h2 .icon {
            font-size: 1.5rem;
        }

        .faq-list {
            display: grid;
            gap: 0.7rem;
        }
        .faq-list details {
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 1rem;
            padding: 1rem 1.2rem;
            background: rgba(0,0,0,0.3);
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .faq-list details[open] {
            border-color: rgba(245, 158, 11, 0.35);
            background: rgba(0,0,0,0.45);
        }
        .faq-list summary {
            cursor: pointer;
            list-style: none;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            line-height: 1.55;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }
        .faq-list summary::-webkit-details-marker { display: none; }
        .faq-list summary::after {
            content: '+';
            color: var(--gold-light);
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .faq-list details[open] summary::after {
            content: '−';
        }
        .faq-list .faq-answer {
            margin: 0.85rem 0 0;
            color: rgba(255,255,255,0.78);
            line-height: 1.85;
            font-size: 0.95rem;
            white-space: pre-wrap;
        }

        .faq-cta {
            margin-top: 4rem;
            padding: 2.5rem 1.5rem;
            text-align: center;
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 1.25rem;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.08), rgba(245, 158, 11, 0.06));
        }
        .faq-cta h2 {
            font-size: 1.4rem;
            font-weight: 800;
            margin: 0 0 0.75rem;
        }
        .faq-cta p {
            color: rgba(255,255,255,0.7);
            margin: 0 0 1.5rem;
        }
        .faq-cta-buttons {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .faq-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.6rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .faq-btn-primary {
            background: linear-gradient(135deg, #dc2626, #f59e0b);
            color: #fff;
        }
        .faq-btn-secondary {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.18);
        }
        .faq-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .faq-footer {
            margin-top: 3rem;
            padding-top: 1.5rem;
            text-align: center;
            color: rgba(255,255,255,0.45);
            font-size: 0.85rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .faq-footer a {
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            margin: 0 0.5rem;
        }
        .faq-footer a:hover {
            color: var(--gold-light);
        }

        @media (max-width: 640px) {
            .faq-page { padding: 2.5rem 1rem 3.5rem; }
            .faq-nav { padding: 0.75rem; }
            .faq-nav a { padding: 0.4rem 0.75rem; font-size: 0.82rem; }
        }
    </style>
</head>
<body>
    <main class="faq-page">
        <nav class="faq-breadcrumb" aria-label="麵包屑導覽">
            <a href="{{ url('/') }}">首頁</a>
            <span> / </span>
            <span>常見問題 FAQ</span>
        </nav>

        <h1>抽獎系統<span class="accent">常見問題 FAQ</span></h1>
        <p class="faq-page__lead">尾牙抽獎、企業活動、線上抽獎最常被問到的 20 題實戰問答 — 涵蓋活動流程、抽獎公平性、彈幕與 QRCode 領獎，以及瀏覽器與裝置相容性。</p>

        <nav class="faq-nav" aria-label="FAQ 分類導航">
            @foreach ($categories as $key => $cat)
                <a href="#{{ $key }}">
                    <span>{{ $cat['icon'] }}</span>
                    <span>{{ $cat['title'] }}</span>
                </a>
            @endforeach
        </nav>

        @foreach ($categories as $key => $cat)
            <section class="faq-category" id="{{ $key }}">
                <h2><span class="icon">{{ $cat['icon'] }}</span>{{ $cat['title'] }}</h2>
                <div class="faq-list">
                    @foreach ($cat['items'] as $item)
                        <details>
                            <summary>{{ $item['q'] }}</summary>
                            <div class="faq-answer">{{ $item['a'] }}</div>
                        </details>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="faq-cta">
            <h2>還有問題沒解答？</h2>
            <p>免費試玩 7 種抽獎動畫，或直接登入後台建立活動。</p>
            <div class="faq-cta-buttons">
                <a href="{{ url('/demo/lottery') }}" class="faq-btn faq-btn-primary">免費試玩 Demo</a>
                <a href="/admin" class="faq-btn faq-btn-secondary">登入後台</a>
            </div>
        </section>

        <footer class="faq-footer">
            <p>
                <a href="{{ url('/') }}">首頁</a> ·
                <a href="{{ url('/demo/lottery') }}">抽獎動畫 Demo</a> ·
                <a href="/admin">登入後台</a>
            </p>
            <p>&copy; {{ date('Y') }} 抽獎系統</p>
        </footer>
    </main>
</body>
</html>
