@php
    $title = '尾牙抽獎流程指南｜名單準備、動畫選擇與主持彩排';
    $description = '尾牙抽獎怎麼安排？從參加名單、重複中獎規則、獎項順序，到一次全抽與逐一抽出、7 種動畫選擇、主持台詞與現場彩排清單，整理成可照著準備的活動指南。';
    $canonical = route('guides.year-end-lottery');
    $sections = ['preparation' => '名單與資格', 'rules' => '抽獎規則', 'run-of-show' => '獎項與主持流程', 'animations' => '動畫怎麼選', 'rehearsal' => '彩排與時間', 'checklist' => '當天檢查清單', 'demo-or-event' => '試玩或正式活動'];
    $animationChoices = [
        ['slug' => 'lotto-air', 'label' => '樂透氣流機', 'fit' => '一般獎、連續多輪揭獎', 'note' => '彩球逐一出現，適合搭配唱名；多人時先試完整批次的節奏。'],
        ['slug' => 'red-packet', 'label' => '紅包雨', 'fit' => '尾牙暖場、春酒與年節活動', 'note' => '紅金畫面容易建立喜氣氛圍，主持人可在紅包開啟前說明獎項。'],
        ['slug' => 'scratch-card', 'label' => '刮刮樂', 'fit' => '聚會、門市活動的卡片式揭獎', 'note' => '塗層刮除營造懸念；多人會逐張揭曉，彩排要量整批時間。'],
        ['slug' => 'treasure-chest', 'label' => '寶箱', 'fit' => '團隊獎勵、答謝活動', 'note' => '多個寶箱逐一開啟，適合在同一獎項中連續唱名。'],
        ['slug' => 'big-treasure-chest', 'label' => '大寶箱', 'fit' => '特別獎、壓軸大獎', 'note' => '單一大箱聚焦視線；若有多位得主，先確認連續開箱所需時間。'],
        ['slug' => 'marble-race', 'label' => '圓球賽跑', 'fit' => '團隊暖場、希望觀眾一起看競賽的段落', 'note' => '彩球競速與排名增加懸念；先用實際名單量測倒數到結果展示。'],
        ['slug' => 'battle-top', 'label' => '戰鬥陀螺', 'fit' => '競技主題、氣氛熱烈的活動', 'note' => '碰撞淘汰有戲劇張力；戰鬥時間之外還有倒數、入場與揭曉。'],
    ];
    $schemas = [
        ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $title,
            'description' => $description, 'url' => $canonical, 'mainEntityOfPage' => $canonical,
            'inLanguage' => 'zh-Hant', 'image' => url('/images/og-home.svg'),
            'author' => ['@type' => 'Organization', 'name' => '抽獎系統', 'url' => url('/')]],
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => '首頁', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => '尾牙抽獎流程指南', 'item' => $canonical],
        ]],
    ];
@endphp
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ url('/images/og-home.svg') }}">
    <meta property="og:site_name" content="抽獎系統">
    <meta property="og:locale" content="zh_TW">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ url('/images/og-home.svg') }}">
    <meta name="theme-color" content="#08080d">
    <link rel="icon" type="image/svg+xml" href="{{ url('/images/og-home.svg') }}">
    @foreach ($schemas as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endforeach
    <style>
        :root { color-scheme: dark; --bg: #08080d; --text: #e2e8f0; --muted: #adb7c7; --gold: #fde68a; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: radial-gradient(900px circle at 15% 0%, #6b241d45, transparent 65%), var(--bg); color: var(--text); font-family: system-ui, "Noto Sans TC", sans-serif; }
        a { color: var(--gold); text-underline-offset: .25em; }
        a:hover { color: #fff; }
        a:focus-visible, summary:focus-visible { outline: 2px solid var(--gold); outline-offset: 5px; }
        .skip { position: absolute; left: 1rem; top: -5rem; padding: .75rem; background: #222; z-index: 2; }
        .skip:focus { top: 1rem; }
        .wrap { width: min(100% - 2rem, 72rem); margin: auto; padding: 1.5rem 0 4rem; }
        .nav { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; font-size: .9rem; }
        .crumbs { display: flex; flex-wrap: wrap; gap: .5rem; }
        .hero { padding: clamp(2.5rem, 7vw, 5rem) 0 2rem; max-width: 54rem; }
        .eyebrow { color: var(--gold); letter-spacing: .12em; font-size: .85rem; }
        h1 { font-size: clamp(2rem, 5vw, 3.5rem); line-height: 1.35; margin: .75rem 0 1rem; color: #fff; }
        h2 { font-size: clamp(1.4rem, 3vw, 1.9rem); color: #fff; margin: 0 0 1rem; }
        h3 { font-size: 1.1rem; color: #fff; margin: 1.6rem 0 .5rem; }
        p, li { line-height: 1.9; color: var(--muted); }
        strong { color: var(--text); }
        .lead { font-size: 1.13rem; }
        .byline { font-size: .85rem; }
        .actions { display: flex; gap: .75rem; flex-wrap: wrap; margin-top: 1.5rem; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: .8rem 1.15rem; border-radius: .75rem; text-decoration: none; font-weight: 700; border: 1px solid #fde68a55; }
        .primary { background: #fbbf24; color: #17120a; }
        .primary:hover { background: #fde68a; color: #17120a; }
        .layout { display: grid; grid-template-columns: 14rem minmax(0, 1fr); gap: 2rem; align-items: start; }
        .toc { position: sticky; top: 1.5rem; padding: 1.25rem; border: 1px solid #ffffff14; border-radius: 1rem; background: #14141b; }
        .toc h2 { font-size: 1rem; }
        .toc ol { padding-left: 1.3rem; margin-bottom: 0; }
        .toc a { font-size: .9rem; }
        .content { min-width: 0; }
        .section { scroll-margin-top: 1.5rem; padding: clamp(1.25rem, 3vw, 2rem); margin-bottom: 1.25rem; border: 1px solid #ffffff14; background: #ffffff04; border-radius: 1rem; }
        .section > :last-child { margin-bottom: 0; }
        .table-wrap { overflow-x: auto; border: 1px solid #ffffff1a; border-radius: .75rem; }
        .table-wrap:focus-visible { outline: 2px solid var(--gold); outline-offset: 3px; }
        table { width: 100%; border-collapse: collapse; min-width: 33rem; text-align: left; font-size: .93rem; }
        caption { text-align: left; padding: 1rem; color: var(--gold); font-weight: 700; }
        th, td { padding: .9rem; border-top: 1px solid #ffffff14; line-height: 1.7; vertical-align: top; }
        th { color: #fff; background: #ffffff05; }
        td { color: var(--muted); }
        th[scope="row"] { position: sticky; left: 0; min-width: 7.5rem; background: #14141b; }
        .note { padding: 1rem 1.2rem; border-left: 3px solid #fbbf24; background: #fbbf240a; border-radius: 0 .6rem .6rem 0; }
        .checklist { padding-left: 1.3rem; }
        .checklist li { padding: .3rem 0; }
        blockquote { margin: 1rem 0; padding: .5rem 1.25rem; border-left: 3px solid #9ca3af; background: #ffffff05; }
        .footer { margin-top: 2rem; font-size: .9rem; }
        @media (max-width: 800px) { .layout { grid-template-columns: 1fr; gap: 1.25rem; } .toc { position: static; } .toc ol { columns: 2; column-gap: 2rem; } }
        @media (max-width: 440px) { h1 { font-size: 1.8rem; } .toc ol { columns: 1; } }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
        @media print { :root { color-scheme: light; --text: #111; --muted: #333; --gold: #111; } body { background: #fff; } .wrap { width: 100%; } .layout { display: block; } .toc, .nav, .actions, .footer { display: none; } .section { border-color: #ccc; } h1, h2, h3, th { color: #111; } table { min-width: 0; } th, td { border-color: #ccc; } th[scope="row"] { position: static; background: transparent; } }
    </style>
</head>
<body>
    <a class="skip" href="#guide-content">跳至指南內容</a>
    <main class="wrap">
        <nav class="nav" aria-label="頁面導覽">
            <div class="crumbs"><a href="{{ url('/') }}">首頁</a><span>/</span><span>尾牙抽獎流程指南</span></div>
            <a href="{{ route('demo.lottery.landing') }}">查看 7 種抽獎動畫</a>
        </nav>
        <article>
            <header class="hero">
                <span class="eyebrow">活動準備指南</span>
                <h1>尾牙抽獎流程怎麼安排？<br>從名單準備到主持彩排</h1>
                <p class="lead">先確認誰能抽、可以中幾次與獎項順序，再挑選動畫、安排唱名與領獎。這份指南把尾牙抽獎拆成可逐項核對的步驟，也適用於春酒、年會與團隊聚會。</p>
                <p class="byline">內容整理：<a href="{{ url('/') }}">抽獎系統</a>。依本站功能與活動操作流程整理；資格、缺席與補抽方式請由主辦單位事先決定。</p>
                <div class="actions"><a class="button primary" href="#checklist">查看當天檢查清單</a><a class="button" href="{{ route('demo.lottery.landing') }}">先免費試玩動畫</a></div>
            </header>
            <div class="layout">
                <nav class="toc" aria-label="指南目錄">
                    <h2>這篇指南會幫你準備</h2>
                    <ol>@foreach ($sections as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach</ol>
                </nav>
                <div class="content" id="guide-content">
                    <section class="section" id="preparation">
                        <h2>1. 尾牙抽獎名單先整理哪些資料？</h2>
                        <p>先把「出席名單」與「有抽獎資格的名單」分開核對。員工、工作人員、來賓或主管是否同時參加，應先有明確規則，再依規則設定各獎項範圍。名單匯入成功後，仍要確認實際可抽人數。</p>
                        <ul>
                            <li><strong>辨識同名者：</strong>使用員工編號或主辦單位的識別資料核對，避免只看姓名就把兩人當成同一人。</li>
                            <li><strong>確認群組：</strong>若獎項只給特定部門或組別，檢查包含與排除條件；不能只看全活動總人數。</li>
                            <li><strong>核對通知資料：</strong>正式活動若使用 Email 通知與領獎連結，先確認收件資料與通知設定；免費 Demo 只需試玩名單。</li>
                            <li><strong>保留核對版本：</strong>活動開始前確認最後名單，臨時增減由固定窗口處理，讓主持人與操作人員看到相同版本。</li>
                        </ul>
                        <p class="note">第一次測試建議使用「測試甲、測試乙」等合成名單，先熟悉操作，再處理正式參加者資料。</p>
                    </section>
                    <section class="section" id="rules">
                        <h2>2. 抽獎前先說清楚四項規則</h2>
                        <h3>誰可以參加、各獎項是否同一批人？</h3>
                        <p>一般獎與特定團隊獎不一定共用資格。在後台設定後，逐一查看每個獎項的可抽人數；若是 0，先檢查名單與群組條件，避免到現場才發現抽不出人。</p>
                        <h3>同一人可以重複中獎嗎？</h3>
                        <p>「同一獎項能否重複中獎」與「中過前面獎項的人是否還能抽後面的獎」是兩件事。前者在獎項設定控制，後者要搭配資格規則檢查。彩排時實際抽兩輪，比單看設定值更容易發現與預期不同的地方。</p>
                        <h3>一次全抽與逐一抽出怎麼選？</h3>
                        <p><strong>一次全抽</strong>會在一次操作中抽選該獎項剩餘名額，動畫仍可能逐位呈現；<strong>逐一抽出</strong>則每次操作抽一位。一般獎可選一次全抽，方便連續唱名；需要主管頒獎或讓每位得主上台時，可考慮逐一抽出。</p>
                        <h3>缺席、放棄與補抽如何處理？</h3>
                        <p>先決定是否必須在場、唱名幾次、多久後確認缺席，以及由誰決定釋出名額。若需要替補，先在後台核對原中獎紀錄並依活動流程處理；保留原紀錄與替補關係，避免只因畫面暫停就再按一次抽獎。</p>
                    </section>
                    <section class="section" id="run-of-show">
                        <h2>3. 獎項順序與主持流程範例</h2>
                        <p>可以先用一般獎建立操作節奏，再穿插特別獎，最後安排壓軸。下表以「一般獎 5 名、團隊獎 3 名、特別獎 1 名、壓軸獎 1 名」為示例，方便把十個名額與主持動作對起來；實際順序依活動規則調整。</p>
                        <div class="table-wrap" tabindex="0" role="region" aria-label="可橫向捲動的尾牙抽獎流程範例">
                            <table>
                                <caption>十個中獎名額的流程配置範例</caption>
                                <thead><tr><th scope="col">段落</th><th scope="col">操作安排</th><th scope="col">主持與工作人員確認</th></tr></thead>
                                <tbody>
                                    <tr><th scope="row">開場說明</th><td>顯示活動與第一個獎項</td><td>說明資格、重複中獎及缺席處理方式，確認台下已看清畫面。</td></tr>
                                    <tr><th scope="row">一般獎 5 名</th><td>一次全抽；樂透或紅包雨</td><td>操作人員開始抽獎，動畫結束後依名單唱名，工作人員處理領獎。</td></tr>
                                    <tr><th scope="row">團隊獎 3 名</th><td>先核對限定群組，再一次全抽</td><td>切換獎項後核對獎名與人數，確認完成再開始下一輪。</td></tr>
                                    <tr><th scope="row">特別獎 1 名</th><td>逐一抽出；寶箱或競賽動畫</td><td>預留來賓致詞、上台與拍照時間，避免動畫一結束就切獎。</td></tr>
                                    <tr><th scope="row">壓軸獎 1 名</th><td>確認最後資格；大寶箱或偏好的動畫</td><td>先講清楚獎品，再開始揭曉，最後核對得主與剩餘領獎事項。</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <h3>主持人口播可以這樣準備</h3>
                        <blockquote><p>「接下來是＿＿獎，本輪共有＿＿個名額，參加資格是＿＿。中獎後請到＿＿核對領獎；若唱名後未在場，將依事先公告的＿＿方式處理。確認畫面上的獎項後，我們開始抽獎。」</p></blockquote>
                        <p>把獎項名稱、名額與領獎位置填進同一份流程表，交給主持、後台操作與領獎窗口。流程表也要寫出誰負責確認得主，避免多人同時發出補抽指令。</p>
                    </section>
                    <section class="section" id="animations">
                        <h2>4. 七種尾牙抽獎動畫怎麼選？</h2>
                        <p>先決定每輪要抽幾人、是否需要上台，再選畫面風格。動畫的用途是呈現抽獎結果，<strong>中獎者由伺服器抽選</strong>；圓球跑到終點、陀螺留在場上，是揭曉效果。</p>
                        <div class="table-wrap" tabindex="0" role="region" aria-label="可橫向捲動的抽獎動畫選擇表">
                            <table>
                                <caption>依揭獎方式與主持需求挑選動畫</caption>
                                <thead><tr><th scope="col">動畫介紹</th><th scope="col">可考慮的場合</th><th scope="col">彩排時觀察什麼</th></tr></thead>
                                <tbody>@foreach ($animationChoices as $choice)
                                    <tr><th scope="row"><a href="{{ route('demo.lottery.style', ['slug' => $choice['slug']]) }}">{{ $choice['label'] }}抽獎</a></th><td>{{ $choice['fit'] }}</td><td>{{ $choice['note'] }}</td></tr>
                                @endforeach</tbody>
                            </table>
                        </div>
                        <p>想先比較畫面，可查看<a href="{{ route('demo.lottery.landing') }}">七種抽獎動畫與免費 Demo</a>。喜歡競賽感可從<a href="{{ route('demo.lottery.style', ['slug' => 'marble-race']) }}">圓球賽跑</a>與<a href="{{ route('demo.lottery.style', ['slug' => 'battle-top']) }}">戰鬥陀螺</a>開始；選好後用相同名單、人數與裝置試跑，較容易比較。</p>
                    </section>
                    <section class="section" id="rehearsal">
                        <h2>5. 彩排要量整輪時間，不能只看動畫秒數</h2>
                        <p>一輪的實際時間包含「介紹獎項 → 開始抽獎 → 動畫揭曉 → 核對名單 → 上台或領獎」。後台秒數對各動畫的影響不同，倒數、入場、多人依序揭曉與得主展示也會增加時間；設定 10 秒不代表現場整輪一定 10 秒結束。</p>
                        <ol>
                            <li><strong>用實際規模測：</strong>一次抽一位與一次抽五位都跑過，記錄從操作到完整名單出現的時間。</li>
                            <li><strong>再量主持段落：</strong>把口播、唱名、走到領獎處與合照算進去，預留切換獎項和核對的緩衝。</li>
                            <li><strong>使用正式設備：</strong>實際投影、瀏覽器縮放與全螢幕都測過，確認後排看得到姓名、音量適合會場。</li>
                            <li><strong>走一次完整流程：</strong>正式活動若使用測試模式，先確認本次為彩排設定，結束後再核對正式活動、目前獎項與中獎紀錄。</li>
                        </ol>
                        <p class="note">若動畫看起來停住，先由操作人員查看後台是否已有中獎紀錄。已抽出結果時應先核對資料，再處理顯示問題，避免把第二次抽選誤當成重播。</p>
                    </section>
                    <section class="section" id="checklist">
                        <h2>6. 活動當天的尾牙抽獎檢查清單</h2>
                        <p>可複製到工作人員流程表，或列印本指南逐項核對。</p>
                        <ul class="checklist">
                            <li>參加名單與群組已確認，每個獎項的可抽人數符合預期。</li>
                            <li>獎項順序、名額、一次全抽或逐一抽出已核對。</li>
                            <li>重複中獎、缺席、放棄與替補規則已交給主持人。</li>
                            <li>前台顯示正確活動與獎項，操作權限與控制裝置可正常使用。</li>
                            <li>在實際投影解析度檢查姓名、結果分頁、音效與切換獎項。</li>
                            <li>現場網路可用；若異常，由固定操作窗口先核對中獎資料。</li>
                            <li>若使用通知與 QRCode 兌獎，已測試收件、開啟連結及工作人員核對流程。</li>
                            <li>活動結束後核對中獎與領獎狀態，保留需要交接的紀錄。</li>
                        </ul>
                    </section>
                    <section class="section" id="demo-or-event">
                        <h2>7. 先免費試玩，還是直接建立正式活動？</h2>
                        <p><strong>只想挑選動畫：</strong>免費 Demo 不需登入，可用預設名單或自訂姓名、中獎人數與模式。適合先確認視覺與操作感受。</p>
                        <p><strong>需要管理完整尾牙抽獎：</strong>註冊登入後在組織後台建立活動、整理參加者與獎項，再設定資格、通知與兌獎流程。正式活動功能與額度依方案而定，免費 Demo 不代表全部正式功能免費。</p>
                        <div class="actions"><a class="button primary" href="{{ route('demo.lottery.landing') }}">免費比較抽獎動畫</a><a class="button" href="{{ route('register-account') }}">註冊並設定正式活動</a></div>
                        <p>想確認重複中獎、通知與裝置問題，可查看<a href="{{ route('faq') }}">抽獎系統常見問題</a>；操作時有建議或異常，可透過<a href="{{ route('feedback.index') }}">意見回饋與聯絡我們</a>提供說明。</p>
                    </section>
                </div>
            </div>
        </article>
        <footer class="footer"><a href="{{ url('/') }}">首頁</a> · <a href="{{ route('demo.lottery.landing') }}">抽獎動畫</a> · <a href="{{ route('faq') }}">常見問題</a></footer>
    </main>
</body>
</html>
