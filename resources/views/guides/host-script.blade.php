@extends('guides.layout', ['guideSlug' => 'lottery-host-script'])

@section('actions')
    <div class="actions"><a class="guide-btn primary" href="#scripts">查看四段口播</a><a class="guide-btn" href="{{ asset('examples/lottery-run-of-show.csv') }}" download data-demo-event="download_click">下載流程表 CSV ↓</a></div>
@endsection

@section('content')
    <section class="guide-section" id="before">
        <h2>1. 先填好這六個空格，再上台。</h2>
        <p>主持稿的用途是讓參加者、操作人員與領獎窗口聽到同一套規則。先和主辦單位確認下列資訊，再把範例中的「＿＿」換成活動設定。</p>
        <ul><li><strong>本輪獎項與名額：</strong>獎名、獎品與幾位得主。</li><li><strong>參加資格：</strong>全體員工或限定群組，以及重複中獎的規則。</li><li><strong>揭獎模式：</strong>一次抽選剩餘名額，或每次操作抽一位。</li><li><strong>唱名方式：</strong>用姓名搭配員工編號或主辦單位核對資料，避免同名誤認。</li><li><strong>缺席處理：</strong>唱名次數、確認窗口與補抽決定者。</li><li><strong>領獎地點：</strong>工作人員位置、是否需要上台與合照。</li></ul>
        <p class="guide-note">以下是可改寫的主持範例，不會自動套用後台設定。先核對實際資格與名額，再向觀眾公告。</p>
    </section>
    <section class="guide-section" id="scripts">
        <h2>2. 四段口播，依現場流程改寫。</h2>
        @foreach ([
            ['id' => 'opening-script', 'title' => '開場：先說清楚怎麼參加', 'text' => '各位夥伴，接下來進入抽獎時間！今天的參加資格是＿＿，同一人跨獎項能否再次中獎的規則是＿＿。唱名後請到＿＿核對領獎；若未在場，我們會依事先公告的＿＿方式處理。請操作人員確認畫面上的活動與第一個獎項，我們準備開始。'],
            ['id' => 'regular-script', 'title' => '一般獎：交代名額，留空間唱名', 'text' => '接下來是＿＿獎，本輪共有＿＿個名額，參加資格是＿＿。這一輪使用＿＿方式揭曉。請操作人員確認獎項名稱與名額，確認完成後開始抽獎。恭喜畫面上的得主！我們依名單順序唱名，請到＿＿由工作人員核對。'],
            ['id' => 'final-script', 'title' => '壓軸：介紹獎品，再開始揭曉', 'text' => '接下來是今天的壓軸＿＿獎，獎品是＿＿，這一輪將抽出＿＿位。請大家先確認本獎項的參加資格與重複中獎規則：＿＿。操作人員確認完成後，我們一起迎接得主！恭喜＿＿，請到＿＿核對身分與領獎，並依工作人員指示上台。'],
            ['id' => 'absence-script', 'title' => '缺席處理：由固定窗口確認', 'text' => '我們再次唱名＿＿，請由＿＿窗口確認是否到場。依活動開始前公告的＿＿規則，本名額將由＿＿確認後處理。請操作人員先核對原中獎紀錄；待確認可以補抽，再由主持人宣布開始。'],
        ] as $script)
            <h3>{{ $script['title'] }}</h3><blockquote id="{{ $script['id'] }}"><p>{{ $script['text'] }}</p></blockquote>
            <button type="button" class="guide-btn" data-copy-target="{{ $script['id'] }}" hidden>複製這段主持稿</button><p class="copy-message" id="{{ $script['id'] }}-status" role="status" aria-live="polite"></p>
        @endforeach
    </section>
    <section class="guide-section" id="schedule">
        <h2>3. 把主持、操作與領獎排在同一張表。</h2>
        <p>以下以一般獎五名、特別獎一名、壓軸一名為例。每段的時間由彩排實測後填入，並把介紹、唱名、走動與核對一起算進去。</p>
        <div class="table-scroll" role="region" aria-label="可橫向捲動的抽獎流程表" tabindex="0"><table><caption>主持與工作人員的交接範例</caption><thead><tr><th scope="col">段落</th><th scope="col">主持人</th><th scope="col">操作／工作人員</th></tr></thead><tbody>
            <tr><th scope="row">開場說明</th><td>公告資格、重複中獎與缺席規則。</td><td>顯示正確活動與第一個獎項，確認投影與音量。</td></tr>
            <tr><th scope="row">一般獎五名</th><td>說明獎品，動畫結束後按名單唱名。</td><td>核對設定後一次抽選；領獎窗口逐位核對。</td></tr>
            <tr><th scope="row">特別獎一名</th><td>邀請頒獎來賓，確認人員到位再開始。</td><td>切換獎項並確認完成，再依主持口令抽獎。</td></tr>
            <tr><th scope="row">壓軸一名</th><td>介紹獎品、揭曉、邀請上台。</td><td>確認最後資格；核對得主，保留拍照與交接時間。</td></tr>
            <tr><th scope="row">結束核對</th><td>提醒尚未領獎者及領獎窗口。</td><td>核對中獎與領獎紀錄，處理剩餘事項。</td></tr>
        </tbody></table></div>
        <a class="guide-btn" href="{{ asset('examples/lottery-run-of-show.csv') }}" download data-demo-event="download_click">下載可編輯流程表 CSV ↓</a><p class="small">可用 Excel 開啟；內容是合成活動範例，時間欄位留白供彩排後填寫。</p>
    </section>
    <section class="guide-section" id="rehearsal">
        <h2>4. 彩排時量整輪，現場由固定窗口下指令。</h2>
        <p>先用實際投影解析度跑一次，確認後排看得到姓名。一次抽五位、每次抽一位都測過，再決定唱名、上台與領獎的時間。</p>
        <p><strong>設定 10 秒不等於整輪只有 10 秒。</strong>不同動畫另有倒數、入場、逐位呈現與得主展示；請以完整試跑安排主持節奏。</p>
        <p>如果畫面暫停，主持人先請工作人員核對後台是否已有中獎紀錄。確認資料後再決定處理方式，避免把再次抽選當成重播。</p>
        <div class="actions"><a class="guide-btn" href="{{ route('demo.lottery.play', ['slug' => 'lotto-air']) }}" data-demo-event="play_click" data-demo-style="lotto_air">試玩一般獎的樂透揭曉 ↗</a><a class="guide-btn" href="{{ route('demo.lottery.style', ['slug' => 'big-treasure-chest']) }}">看看壓軸大寶箱</a></div>
    </section>
@endsection
