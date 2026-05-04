<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>抽什麼區｜線上抽籤、午餐吃什麼、抽獎範本免費套用</title>
    <meta name="description" content="抽什麼區提供午餐吃什麼、飲料喝什麼、聚餐去哪裡、尾牙抽獎等線上抽籤與抽獎範本，免費套用後可直接開始抽。">
    <link rel="canonical" href="{{ route('demo.lottery.templates.index') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="抽什麼區｜線上抽籤、午餐吃什麼、抽獎範本免費套用">
    <meta property="og:description" content="免費套用大家分享的抽什麼卡片，用線上抽籤快速決定午餐、飲料、聚餐與活動抽獎。">
    <meta property="og:url" content="{{ route('demo.lottery.templates.index') }}">
    <meta property="og:image" content="{{ url('/images/og-demo.svg') }}">
    <meta property="og:locale" content="zh_TW">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="抽什麼區｜線上抽籤、午餐吃什麼、抽獎範本免費套用">
    <meta name="twitter:description" content="免費套用大家分享的抽什麼卡片，用線上抽籤快速決定午餐、飲料、聚餐與活動抽獎。">
    <meta name="twitter:image" content="{{ url('/images/og-demo.svg') }}">
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Noto Sans TC", sans-serif; background: #08080d; color: #f8fafc; }
        a { color: inherit; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 48px 20px 72px; }
        .hero { padding: 44px 0 34px; text-align: center; }
        .eyebrow { display: inline-flex; padding: 6px 14px; border: 1px solid rgba(245,158,11,.3); border-radius: 999px; color: #fbbf24; background: rgba(245,158,11,.08); font-size: 13px; }
        h1 { margin: 18px 0 10px; font-size: clamp(34px, 6vw, 60px); letter-spacing: -.04em; }
        .lead { margin: 0 auto; max-width: 680px; color: #94a3b8; line-height: 1.8; font-size: 17px; }
        .nav { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin: 28px 0 36px; }
        .nav a { text-decoration: none; border: 1px solid rgba(255,255,255,.1); color: #cbd5e1; padding: 9px 14px; border-radius: 999px; background: rgba(255,255,255,.04); font-size: 14px; }
        .nav a.active, .nav a:hover { color: #fde68a; border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.09); }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        @media (max-width: 900px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } }
        .card { display: block; min-height: 210px; padding: 22px; border: 1px solid rgba(255,255,255,.09); border-radius: 22px; background: linear-gradient(160deg, rgba(255,255,255,.07), rgba(255,255,255,.025)); text-decoration: none; box-shadow: 0 20px 60px rgba(0,0,0,.22); }
        .card:hover { border-color: rgba(245,158,11,.35); transform: translateY(-2px); transition: .2s ease; }
        .meta { display: flex; gap: 8px; flex-wrap: wrap; color: #fbbf24; font-size: 12px; }
        .pill { border: 1px solid rgba(245,158,11,.22); border-radius: 999px; padding: 4px 9px; background: rgba(245,158,11,.08); }
        .card h2 { margin: 16px 0 8px; font-size: 22px; line-height: 1.25; }
        .desc { min-height: 44px; color: #94a3b8; line-height: 1.6; font-size: 14px; }
        .stats { margin-top: 18px; color: #cbd5e1; font-size: 13px; display: flex; gap: 14px; flex-wrap: wrap; }
        .reports { margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.08); color: #e2e8f0; font-size: 13px; line-height: 1.6; }
        .empty { padding: 42px; text-align: center; border: 1px dashed rgba(255,255,255,.14); border-radius: 22px; color: #94a3b8; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; gap: 12px; color: #94a3b8; }
        .footer a { color: #fbbf24; text-decoration: none; }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="hero">
            <div class="eyebrow">抽什麼區</div>
            <h1>線上抽籤，幫你決定抽什麼</h1>
            <p class="lead">午餐吃什麼、飲料喝什麼、聚餐去哪裡，或公司活動要抽什麼，都可以套用大家分享的抽什麼卡片，直接用抽獎動畫決定。</p>
        </section>

        <nav class="nav" aria-label="抽什麼分類">
            <a href="{{ route('demo.lottery.templates.index') }}" class="{{ $currentCategory ? '' : 'active' }}">全部</a>
            @foreach ($categories as $key => $label)
                <a href="{{ route('demo.lottery.templates.index', ['category' => $key]) }}" class="{{ $currentCategory === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>

        @if ($templates->isEmpty())
            <div class="empty">目前還沒有公開卡片。到任一 Demo 設定抽獎後，可以發佈到抽什麼區。</div>
        @else
            <div class="grid">
                @foreach ($templates as $template)
                    <a class="card" href="{{ route('demo.lottery.templates.show', $template) }}">
                        <div class="meta">
                            <span class="pill">{{ $categories[$template->category] ?? '其他' }}</span>
                            <span class="pill">{{ $template->styleLabel() }}</span>
                        </div>
                        <h2>{{ $template->title }}</h2>
                        <p class="desc">{{ $template->description ?: '套用這張卡片，用線上抽籤快速決定今天要抽什麼。' }}</p>
                        <div class="stats">
                            <span>{{ $template->optionsCount() }} 個選項</span>
                            <span>每次抽 {{ $template->draw_count }} 個</span>
                            <span>套用 {{ $template->uses_count }} 次</span>
                        </div>
                        @if ($template->publicReports->isNotEmpty())
                            <div class="reports">
                                @foreach ($template->publicReports as $report)
                                    <div>有人抽到「{{ $report->result_text }}」</div>
                                @endforeach
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        <div class="footer">
            <a href="{{ url('/demo/lottery') }}">返回抽獎動畫 Demo</a>
            {{ $templates->links() }}
        </div>
    </main>
</body>
</html>
