@php
    $features = \App\Support\LotteryFeatures::all();
    $title = '抽獎系統功能介紹｜模擬分析、後台主控與不在場重抽';
    $description = '適合公司尾牙、春酒的抽獎系統。支援模擬分析與 Excel 報表、後台切換獎項與投影畫面，以及不在場釋出名額後補抽，保留中獎與替補紀錄。';
    $canonical = route('features.index');
    $schemas = [
        ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $title, 'description' => $description, 'url' => $canonical,
            'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => collect($features)->map(fn ($feature, $slug) => ['@type' => 'ListItem', 'position' => (int) $feature['number'], 'name' => $feature['title'], 'url' => route('features.'.$slug)])->values()->all()]],
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => '功能介紹', 'item' => $canonical],
        ]],
    ];
@endphp
<!DOCTYPE html>
<html lang="zh-Hant">
<head>@include('guides.partials.head', ['ogType' => 'website', 'loadGuideScript' => false])@include('features.styles')</head>
<body>
    <a class="skip" href="#feature-library">跳至功能列表</a>
    <main class="guide-wrap">
        @include('guides.partials.nav')
        <header class="guide-hero">
            <span class="eyebrow">功能介紹</span>
            <h1>為尾牙、春酒準備的<br>線上抽獎系統</h1>
            <p class="lead">設定名單與獎項，從後台切換投影畫面。中獎者不在場，也能釋出名額再補抽，保留每次中獎與替補紀錄。</p>
            <div class="actions"><a class="guide-btn primary" href="{{ route('demo.lottery.landing') }}">免費試玩動畫 ↗</a><a class="guide-btn" href="{{ route('register-account') }}">建立抽獎活動</a></div>
        </header>
        <section class="feature-journey" aria-label="功能使用情境">
            <div class="journey-stop"><span class="eyebrow">01 ／ 活動準備</span><h2>名單與資格設定</h2><p>整理參加者名單，設定每個獎項的名額、參加資格與重複中獎規則。</p></div>
            <div class="journey-stop"><span class="eyebrow">02 ／ 現場抽獎</span><h2>後台管理，前台開抽</h2><p>後台選擇目前獎項，現場操作人員確認畫面後，從前台開始抽獎。</p></div>
            <div class="journey-stop"><span class="eyebrow">03 ／ 活動紀錄</span><h2>中獎與補抽紀錄</h2><p>保留原中獎、釋出與替補紀錄，活動後可匯出名單核對。</p></div>
        </section>
        <section id="feature-library" aria-labelledby="feature-heading">
            <div class="section-title"><h2 id="feature-heading">主要功能</h2><p>查看功能介紹與操作方式。</p></div>
            <div class="guide-grid">
                @foreach ($features as $slug => $item)
                    <a class="guide-card" href="{{ route('features.'.$slug) }}"><div class="card-top"><span class="card-label">{{ $item['stage'] }}</span><span class="card-number" aria-hidden="true">{{ $item['number'] }}</span></div><h2>{{ $item['title'] }}</h2><p>{{ $item['summary'] }}</p><div class="card-detail">{{ $item['detail'] }}</div><div class="card-link">查看功能介紹 <span aria-hidden="true">↗</span></div></a>
                @endforeach
            </div>
        </section>
        <aside class="guide-callout"><div><h2>第一次籌辦公司抽獎？</h2><p>查看 CSV 名單匯入、參加資格與重複中獎設定教學。主持稿與流程表可依公司規則修改使用。</p></div><a class="guide-btn" href="{{ route('guides.index') }}">閱讀操作指南 ↗</a></aside>
        <aside class="guide-callout"><div><h2>先試玩，再建立活動</h2><p>7 種抽獎動畫可免登入試玩。正式活動可管理名單、設定獎項及處理補抽，員工人數與每場獎項額度依方案提供。</p></div><a class="guide-btn primary" href="{{ route('register-account') }}">建立抽獎活動 ↗</a></aside>
        @include('guides.partials.footer')
    </main>
</body>
</html>
