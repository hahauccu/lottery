@php
    $feature = \App\Support\LotteryFeatures::all()[$featureSlug];
    $title = $feature['seoTitle'];
    $description = $feature['description'];
    $canonical = route('features.'.$featureSlug);
    $schemas = [
        ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => $title, 'description' => $description, 'url' => $canonical, 'inLanguage' => 'zh-Hant'],
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => '功能介紹', 'item' => route('features.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $feature['title'], 'item' => $canonical],
        ]],
    ];
@endphp
<!DOCTYPE html>
<html lang="zh-Hant">
<head>@include('guides.partials.head', ['loadGuideScript' => false])@include('features.styles')</head>
<body>
    <a class="skip" href="#feature-content">跳至功能說明</a>
    <main class="guide-wrap">
        @include('guides.partials.nav')
        <article>
            <header class="guide-hero">
                <span class="eyebrow">{{ $feature['stage'] }} ／ {{ $feature['title'] }}</span>
                <h1>{{ $feature['headline'] }}</h1><p class="lead">{{ $feature['summary'] }}</p>
                <div class="actions"><a class="guide-btn primary" href="{{ route('register-account') }}">建立抽獎活動 ↗</a><a class="guide-btn" href="{{ route('demo.lottery.landing') }}">免費試玩動畫</a></div>
                <p class="byline">此為正式活動功能，員工人數與每場獎項額度依方案提供。動畫可先免登入試玩。</p>
            </header>
            <div class="guide-layout">
                <nav class="guide-toc" aria-label="本篇目錄"><h2>本頁內容</h2><ol>@foreach ($feature['sections'] as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach</ol><a class="small" href="{{ route('features.index') }}">← 所有功能介紹</a></nav>
                <div class="guide-content" id="feature-content">
                    @yield('content')
                    <aside class="guide-callout"><div><h2>建立你的抽獎活動</h2><p>從員工名單、獎項與參加資格開始設定。活動前，建議使用現場電腦與投影設備完成測試。</p></div><a class="guide-btn primary" href="{{ route('register-account') }}">註冊並開始設定 ↗</a></aside>
                    <nav class="guide-related" aria-label="相關功能與教學">
                        @foreach (\App\Support\LotteryFeatures::all() as $slug => $related)
                            @if ($slug !== $featureSlug)<a href="{{ route('features.'.$slug) }}"><strong>{{ $related['title'] }} ↗</strong><span>{{ $related['summary'] }}</span></a>@endif
                        @endforeach
                        <a href="{{ route('guides.'.$feature['guide']) }}"><strong>{{ \App\Support\LotteryGuides::all()[$feature['guide']]['title'] }} ↗</strong><span>延伸操作教學</span></a>
                    </nav>
                </div>
            </div>
        </article>
        @include('guides.partials.footer')
    </main>
</body>
</html>
