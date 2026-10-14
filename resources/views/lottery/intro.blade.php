<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:site_name" content="抽獎系統">
    <meta property="og:locale" content="zh_TW">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <title>{{ $seoTitle }}</title>

    @php
        $introFaqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($demoCurrentStyle['faqs'] ?? [])->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['q'] ?? '',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a'] ?? ''],
            ])->all(),
        ];
        $introPageSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $seoTitle,
            'description' => $seoDescription,
            'url' => $seoCanonical,
            'image' => $seoImage,
        ];
        $introProductSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $demoCurrentStyle['label'].'抽獎動畫免費 Demo',
            'description' => $demoCurrentStyle['longDesc'] ?? $demoCurrentStyle['desc'] ?? '',
            'image' => $seoImage,
            'category' => '抽獎動畫',
            'url' => $seoCanonical,
            'brand' => [
                '@type' => 'Brand',
                'name' => '抽獎系統',
                'url' => url('/'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'name' => '免登入免費 Demo',
                'price' => '0',
                'priceCurrency' => 'TWD',
                'availability' => 'https://schema.org/InStock',
                'url' => $demoPlayUrl,
            ],
        ];
    @endphp

    <script type="application/ld+json">{!! json_encode($introPageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    <script type="application/ld+json">{!! json_encode($introProductSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @if (!empty($introFaqSchema['mainEntity']))
        <script type="application/ld+json">{!! json_encode($introFaqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endif
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $demoBreadcrumbItems], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>

    <link rel="icon" type="image/svg+xml" href="{{ url('/images/og-home.svg') }}">
    <link rel="icon" href="{{ url('/favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ url('/images/og-home.svg') }}">
    <meta name="theme-color" content="#08080d">
    <style>
        :root { color-scheme: dark; --bg: #08080d; --card: #12121a; --muted: #94a3b8; --gold: #fbbf24; --red: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; color: #e2e8f0; background: radial-gradient(900px circle at 15% 10%, rgba(220,38,38,.18), transparent 55%), radial-gradient(700px circle at 85% 25%, rgba(245,158,11,.12), transparent 55%), var(--bg); font-family: "Noto Sans TC", system-ui, sans-serif; }
        a { color: inherit; }
        .intro-wrap { width: min(100% - 2rem, 72rem); margin: 0 auto; padding: 1.25rem 0 5rem; }
        .intro-nav { display: flex; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: 3rem; color: var(--muted); font-size: .9rem; }
        .intro-nav a { text-decoration: none; }
        .intro-nav a:hover { color: var(--gold); }
        .crumbs { display: flex; gap: .5rem; flex-wrap: wrap; }
        .hero { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(18rem, .95fr); gap: 3rem; align-items: center; padding: 2rem 0 4.5rem; }
        .eyebrow { display: inline-flex; border: 1px solid rgba(251,191,36,.3); border-radius: 999px; padding: .35rem .8rem; color: #fde68a; background: rgba(245,158,11,.08); font-size: .8rem; }
        h1 { margin: 1rem 0; color: #fff; font-size: clamp(2.2rem, 6vw, 4.5rem); line-height: 1.15; letter-spacing: -.04em; }
        .lead { max-width: 42rem; color: var(--muted); font-size: 1.1rem; line-height: 1.9; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border-radius: .8rem; padding: .8rem 1.2rem; font-weight: 700; text-decoration: none; }
        .btn-primary { color: #111827; background: linear-gradient(135deg, #fbbf24, #dc2626); }
        .btn-secondary { color: #fde68a; border: 1px solid rgba(251,191,36,.3); background: rgba(255,255,255,.04); }
        .visual { min-height: 20rem; display: grid; place-items: center; padding: 1.5rem; border: 1px solid rgba(255,255,255,.1); border-radius: 1.5rem; background: linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.02)); box-shadow: 0 2rem 5rem rgba(0,0,0,.25); }
        .visual img { display: block; width: min(100%, 25rem); max-height: 20rem; object-fit: contain; }
        .visual-fallback { color: #fde68a; font-size: 1.2rem; font-weight: 800; text-align: center; }
        .section { margin-top: 1.5rem; padding: clamp(1.5rem, 4vw, 3rem); border: 1px solid rgba(255,255,255,.08); border-radius: 1.25rem; background: rgba(255,255,255,.035); }
        h2 { margin: 0 0 1rem; color: #fff; font-size: clamp(1.4rem, 3vw, 2rem); }
        h3 { margin: 2rem 0 .75rem; color: #fff; font-size: 1.1rem; }
        p, li { color: var(--muted); line-height: 1.85; }
        ul { margin: 0; padding-left: 1.25rem; }
        .feature-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .feature { padding: 1rem; border: 1px solid rgba(255,255,255,.08); border-radius: .8rem; color: #e2e8f0; background: rgba(0,0,0,.15); }
        details { border-top: 1px solid rgba(255,255,255,.1); padding: 1rem 0; }
        details summary { cursor: pointer; color: #fff; font-weight: 700; }
        details p { margin-bottom: 0; }
        .related { display: flex; flex-wrap: wrap; gap: .6rem; }
        .related a { padding: .5rem .8rem; border: 1px solid rgba(255,255,255,.12); border-radius: 999px; color: #fde68a; text-decoration: none; }
        .related a:hover { border-color: rgba(251,191,36,.5); background: rgba(245,158,11,.08); }
        @media (max-width: 760px) { .hero { grid-template-columns: 1fr; gap: 1.5rem; padding-top: 0; } .intro-nav { align-items: flex-start; flex-direction: column; margin-bottom: 2rem; } .feature-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <main class="intro-wrap">
        <nav class="intro-nav" aria-label="頁面導覽">
            <div class="crumbs">
                <a href="{{ url('/') }}">首頁</a><span>/</span>
                <a href="{{ route('demo.lottery.landing') }}">抽獎動畫</a><span>/</span>
                <span>{{ $demoStyleLabel }}</span>
            </div>
            <a href="{{ route('demo.lottery.templates.index') }}">瀏覽分享範本</a>
        </nav>

        <section class="hero">
            <div>
                <span class="eyebrow">抽獎動畫介紹</span>
                <h1>{{ $demoStyleLabel }}抽獎動畫</h1>
                <p class="lead">{{ $demoCurrentStyle['longDesc'] ?? $demoCurrentStyle['desc'] }}</p>
                <div class="actions">
                    <a class="btn btn-primary" href="{{ $demoPlayUrl }}">開始設定參數試玩</a>
                    <a class="btn btn-secondary" href="{{ route('demo.lottery.landing') }}">查看全部動畫</a>
                </div>
            </div>
            <div class="visual">
                @if (file_exists(public_path('images/previews/'.$demoCurrentStyle['key'].'.svg')))
                    <img src="{{ url('/images/previews/'.$demoCurrentStyle['key'].'.svg') }}" alt="{{ $demoStyleLabel }}抽獎動畫示意圖" width="800" height="450" fetchpriority="high">
                @else
                    <div class="visual-fallback">{{ $demoStyleLabel }}<br>抽獎動畫</div>
                @endif
            </div>
        </section>

        <section class="section" aria-labelledby="demo-steps">
            <h2 id="demo-steps">{{ $demoStyleLabel }}抽獎怎麼玩？</h2>
            <ol>
                <li><strong>選擇名單：</strong>進入試玩頁，直接使用 30 位預設名單，或選擇「設定抽獎」輸入自己的名單，每行一個名字，也可用逗號分隔。</li>
                <li><strong>設定人數與模式：</strong>選擇中獎人數，以及「一次全抽」或「逐一抽出」，再開始抽獎。</li>
                <li><strong>觀看動畫與結果：</strong>由動畫揭曉中獎者；想比較其他呈現方式，可返回動畫總覽切換風格。</li>
            </ol>
            <p>中獎結果由伺服器抽選，動畫負責呈現結果。{{ $demoCurrentStyle['key'] === 'battle_top' ? '陀螺的碰撞與淘汰是揭曉效果，不是另一套決定得主的抽選機制。' : ($demoCurrentStyle['key'] === 'marble_race' ? '圓球競速是揭曉效果，不是另一套決定得主的抽選機制。' : '') }}</p>
            @if (in_array($demoCurrentStyle['key'], ['treasure_chest', 'big_treasure_chest'], true))
                <p>小寶箱以多個箱子逐一開啟呈現結果；大寶箱以單一巨型寶箱營造壓軸揭曉效果，可依主持節奏與獎項安排挑選。</p>
            @endif
        </section>

        <section class="section">
            <h2>{{ $demoStyleLabel }}適合哪些活動？</h2>
            <p>{{ $demoCurrentStyle['bestFitFor'] ?? '適合企業活動、尾牙、春酒與各式線上抽獎。' }}</p>
            @if (!empty($demoCurrentStyle['useCases']))
                <ul>
                    @foreach ($demoCurrentStyle['useCases'] as $useCase)
                        <li>{{ $useCase }}</li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="section" aria-labelledby="demo-scope">
            <h2 id="demo-scope">免費 Demo 與正式活動有什麼不同？</h2>
            <p><strong>此動畫 Demo 免費（NT$0），不需登入。</strong>可使用預設名單，或自訂參加者、中獎人數與抽獎模式，體驗動畫與結果呈現。</p>
            <p>正式活動的組織後台、員工名單匯入、獎項管理與 QRCode 兌獎需註冊登入，使用額度依方案而定；免費 Demo 不代表所有正式活動功能皆免費。</p>
            <div class="actions">
                <a class="btn btn-primary" href="{{ $demoPlayUrl }}">免費試玩{{ $demoStyleLabel }}抽獎</a>
                <a class="btn btn-secondary" href="{{ route('register-account') }}">註冊並設定正式活動</a>
            </div>
        </section>

        <section class="section">
            <h2>功能亮點</h2>
            <div class="feature-grid">
                @foreach (($demoCurrentStyle['features'] ?? []) as $feature)
                    <div class="feature">{{ $feature }}</div>
                @endforeach
            </div>
        </section>

        @if (!empty($demoCurrentStyle['faqs']))
            <section class="section">
                <h2>{{ $demoStyleLabel }}常見問題</h2>
                @foreach ($demoCurrentStyle['faqs'] as $faq)
                    <details>
                        <summary>{{ $faq['q'] }}</summary>
                        <p>{{ $faq['a'] }}</p>
                    </details>
                @endforeach
                <p>想了解活動流程、公平性與 QRCode 領獎？請查看 <a href="{{ route('faq') }}" style="color:#fde68a;">完整 FAQ</a>。</p>
            </section>
        @endif

        <section class="section">
            <h2>其他抽獎動畫</h2>
            <div class="related">
                @foreach ($demoRelatedStyles as $related)
                    <a href="{{ route('demo.lottery.style', ['slug' => $related['slug']]) }}">{{ $related['label'] }}介紹</a>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
