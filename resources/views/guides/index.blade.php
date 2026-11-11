@php
    $guides = \App\Support\LotteryGuides::all();
    $title = '抽獎活動指南｜尾牙流程、主持稿、名單匯入與重抽規則';
    $description = '準備尾牙或春酒抽獎，從活動流程開始，依序整理主持稿、Excel 與 CSV 參加名單、重複中獎和不在場重抽規則。附可下載範例與免費動畫試玩入口。';
    $canonical = route('guides.index');
    $schemas = [
        ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $title, 'description' => $description, 'url' => $canonical,
            'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => collect($guides)->map(fn ($guide, $slug) => ['@type' => 'ListItem', 'position' => (int) $guide['step'], 'name' => $guide['title'], 'url' => route('guides.'.$slug)])->values()->all()]],
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => '活動指南', 'item' => $canonical],
        ]],
    ];
@endphp
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    @include('guides.partials.head', ['ogType' => 'website'])
</head>
<body>
    @include('lottery.partials.demo-metrics', ['metricEntry' => 'guide_hub'])
    <a class="skip" href="#guide-library">跳至指南列表</a>
    <main class="guide-wrap">
        @include('guides.partials.nav')
        <header class="guide-hero">
            <span class="eyebrow">抽獎活動指南</span>
            <h1>尾牙抽獎準備指南</h1>
            <p class="lead">整理參加名單、設定獎項與資格，再安排主持與領獎。這裡提供抽獎流程、台詞範例與可下載名單，依公司的活動規則調整使用。</p>
            <div class="actions"><a class="guide-btn primary" href="{{ route('guides.year-end-lottery') }}">從完整流程開始 <span aria-hidden="true">↗</span></a><a class="guide-btn" href="{{ route('demo.lottery.landing') }}" data-demo-event="play_click">先看看 7 種抽獎動畫</a></div>
        </header>
        <section class="guide-feature" aria-labelledby="start-here">
            <div>
                <span class="eyebrow">01 ／ 完整籌備</span>
                <h2 id="start-here">尾牙抽獎怎麼安排？<br>先走過一次完整流程。</h2>
                <p>名單與資格、獎項順序、七種動畫比較，以及活動當天的檢查清單。第一次籌備，從這篇開始。</p>
                <a class="guide-btn" href="{{ route('guides.year-end-lottery') }}">閱讀尾牙抽獎流程指南 <span aria-hidden="true">→</span></a>
            </div>
            <ol class="feature-steps" aria-label="活動籌備順序"><li><span>01</span> 整理名單，確認參加資格</li><li><span>02</span> 決定名額與重複中獎規則</li><li><span>03</span> 挑選動畫，安排主持口播</li><li><span>04</span> 用正式設備，完成整輪彩排</li></ol>
        </section>
        <section id="guide-library" aria-labelledby="practical-guides">
            <div class="section-title"><h2 id="practical-guides">尾牙抽獎操作教學</h2><p>依你現在卡住的步驟，選一篇開始。</p></div>
            <div class="guide-grid">
                @foreach (array_slice($guides, 1, null, true) as $slug => $item)
                    <a class="guide-card" href="{{ route('guides.'.$slug) }}">
                        <div class="card-top"><span class="card-label">{{ $item['eyebrow'] }}</span><span class="card-number" aria-hidden="true">{{ $item['step'] }}</span></div>
                        <h2>{{ $item['title'] }}</h2><p>{{ $item['summary'] }}</p><div class="card-detail">{{ $item['detail'] }}</div><div class="card-link">閱讀教學 <span aria-hidden="true">↗</span></div>
                    </a>
                @endforeach
            </div>
        </section>
        <aside class="guide-callout"><div><span class="eyebrow">7 種動畫</span><h2>免費試玩抽獎動畫</h2><p>樂透逐球揭獎、陀螺對決、圓球競速……先用合成名單試玩，找到適合主持節奏的呈現方式。</p></div><a class="guide-btn" href="{{ route('demo.lottery.landing') }}" data-demo-event="play_click">免費比較抽獎動畫 ↗</a></aside>
        <aside class="guide-callout"><div><h2>想知道系統怎麼接住現場細節？</h2><p>從模擬分析、後台選獎，到不在場補抽，查看各功能的操作與使用條件。</p></div><a class="guide-btn" href="{{ route('features.index') }}">認識本站功能 ↗</a></aside>
        <p class="small">內容依本站現有操作流程整理。活動規則由主辦單位決定；教學範例與合成名單僅供準備及彩排使用。</p>
        @include('guides.partials.footer')
    </main>
</body>
</html>
