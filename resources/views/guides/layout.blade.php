@php
    $guide = \App\Support\LotteryGuides::all()[$guideSlug];
    $title = $guide['seoTitle'];
    $description = $guide['description'];
    $canonical = route('guides.'.$guideSlug);
    $schemas = [
        ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $title, 'description' => $description, 'url' => $canonical, 'mainEntityOfPage' => $canonical, 'inLanguage' => 'zh-Hant',
            'author' => ['@type' => 'Organization', 'name' => '抽獎系統', 'url' => url('/')]],
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => '活動指南', 'item' => route('guides.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $guide['title'], 'item' => $canonical],
        ]],
    ];
@endphp
<!DOCTYPE html>
<html lang="zh-Hant">
<head>@include('guides.partials.head') @stack('guide-styles')</head>
<body class="@yield('guide-body-class')">
    @include('lottery.partials.demo-metrics', ['metricEntry' => $guide['entry']])
    <a class="skip" href="#guide-content">跳至教學內容</a>
    <main class="guide-wrap">
        @include('guides.partials.nav')
        <article>
            <header class="guide-hero"><span class="eyebrow">{{ $guide['eyebrow'] }} ／ {{ array_key_exists($guideSlug, \App\Support\LotteryWalkthroughs::series()) ? str_pad((string) (array_search($guideSlug, array_keys(\App\Support\LotteryWalkthroughs::series()), true) + 1), 2, '0', STR_PAD_LEFT) : $guide['step'] }}</span><h1>{{ $guide['title'] }}</h1><p class="lead">{{ $guide['summary'] }}</p><p class="byline">{{ $guide['byline'] ?? '內容整理：抽獎系統 · 依本站現有功能與操作流程整理，範例請依活動規則調整。' }}</p>@yield('actions')</header>
            @if (array_key_exists($guideSlug, \App\Support\LotteryWalkthroughs::series()))
                @include('guides.partials.series')
            @endif
            <div class="guide-layout">
                <nav class="guide-toc" aria-label="本篇目錄"><h2>本篇準備重點</h2><ol>@foreach ($guide['sections'] as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach</ol><a class="small" href="{{ route('guides.index') }}">← 回到指南總覽</a></nav>
                <div class="guide-content" id="guide-content">@yield('content')
                    <aside class="guide-callout"><div><h2>設定你的尾牙抽獎活動</h2><p>用免費 Demo 確認喜歡的動畫；管理員工名單、獎項資格及正式兌獎，請註冊並設定正式活動，員工人數與每場獎項額度依方案而定。</p></div><a class="guide-btn primary" href="{{ route('register-account') }}" data-demo-event="register_click">設定正式活動 ↗</a></aside>
                    @if (array_key_exists($guideSlug, \App\Support\LotteryWalkthroughs::series()))
                        @php
                            $seriesSlugs = array_keys(\App\Support\LotteryWalkthroughs::series());
                            $seriesIndex = array_search($guideSlug, $seriesSlugs, true);
                        @endphp
                        <nav class="guide-related" aria-label="流程上一篇與下一篇">
                            @foreach ([-1 => '上一篇', 1 => '下一篇'] as $offset => $direction)
                                @if (isset($seriesSlugs[$seriesIndex + $offset]))
                                    @php($adjacent = $seriesSlugs[$seriesIndex + $offset])
                                    <a href="{{ route('guides.'.$adjacent) }}"><span>{{ $direction }}</span><strong>{{ \App\Support\LotteryWalkthroughs::series()[$adjacent] }} →</strong></a>
                                @endif
                            @endforeach
                        </nav>
                    @endif
                    <nav class="guide-related" aria-label="接著閱讀">@foreach (\App\Support\LotteryGuides::all() as $slug => $related)@if ($slug !== $guideSlug && (!array_key_exists($guideSlug, \App\Support\LotteryWalkthroughs::series()) || !array_key_exists($slug, \App\Support\LotteryWalkthroughs::series())))<a href="{{ route('guides.'.$slug) }}"><strong>{{ $related['title'] }} ↗</strong><span>{{ $related['eyebrow'] }}</span></a>@endif @endforeach</nav>
                    <nav class="guide-related" aria-label="相關系統功能">@foreach (\App\Support\LotteryFeatures::all() as $slug => $related)@if ($related['guide'] === $guideSlug)<a href="{{ route('features.'.$slug) }}"><strong>{{ $related['title'] }} ↗</strong><span>了解本站如何處理這個情境</span></a>@endif @endforeach</nav>
                </div>
            </div>
        </article>
        @include('guides.partials.footer')
    </main>
</body>
</html>
