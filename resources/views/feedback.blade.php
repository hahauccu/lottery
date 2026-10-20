<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>意見回饋與聯絡我們｜抽獎系統</title>
    <meta name="description" content="抽獎動畫卡住、畫面顯示異常，或有想要的新功能？免登入留下意見回饋，協助我們改善線上抽獎體驗。">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ route('feedback.index') }}">
    <meta name="referrer" content="same-origin">
    <meta name="theme-color" content="#0a0a0f">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; color:#f5f3ef; background:radial-gradient(ellipse at 10% 0, #302014 0, transparent 50%), #0a0a0f; font-family:system-ui,-apple-system,sans-serif; line-height:1.65; }
        a { color:#eec47f; text-underline-offset:4px; }
        .wrap { max-width:1040px; padding:30px 24px 60px; margin:auto; }
        nav { display:flex; justify-content:space-between; gap:16px; font-size:14px; }
        nav a { text-decoration:none; }
        header { margin:52px 0 32px; }
        .eyebrow { color:#d2a35f; font-size:12px; letter-spacing:.18em; }
        h1 { font-size:clamp(30px,5vw,44px); line-height:1.25; margin:12px 0; }
        header p, .muted { color:#a5a2a5; }
        .layout { display:grid; grid-template-columns:minmax(0,2fr) minmax(220px,1fr); gap:28px; align-items:start; }
        .card { background:rgba(255,255,255,.035); border:1px solid #ffffff16; border-radius:20px; padding:28px; }
        label { display:block; margin:0 0 7px; font-size:14px; font-weight:600; }
        label span { font-weight:400; color:#97939a; }
        .field { margin-bottom:22px; }
        input,textarea,select { width:100%; font:inherit; font-size:16px; color:#f5f3ef; background:#111116; border:1px solid #ffffff26; border-radius:10px; padding:11px 13px; }
        input:focus,textarea:focus,select:focus { outline:2px solid #e9b566; outline-offset:2px; }
        textarea { resize:vertical; min-height:180px; }
        small { display:block; color:#97939a; margin-top:7px; font-size:12px; }
        .submit { width:100%; border:0; border-radius:10px; padding:14px; background:linear-gradient(110deg,#efcb90,#ce994b); color:#211a10; font-size:16px; font-weight:700; cursor:pointer; }
        .submit:disabled { opacity:.65; cursor:wait; }
        .notice { padding:14px 18px; border:1px solid #56885e; border-radius:12px; background:#15251b; margin-bottom:24px; }
        .errors { border-color:#9b5a50; background:#2c1718; }
        .errors ul { margin:4px 0; padding-left:20px; }
        aside h2 { font-size:16px; margin:0 0 10px; }
        aside p { font-size:14px; color:#aaa6aa; }
        .detail { overflow-wrap:anywhere; }
        .trap { position:absolute; left:-10000px; width:1px; height:1px; overflow:hidden; }
        footer { margin-top:36px; color:#77727a; font-size:12px; }
        @media(max-width:700px) { .wrap { padding:22px 18px 40px; } header { margin-top:34px; } .layout { grid-template-columns:1fr; gap:20px; } .card { padding:22px; } }
    </style>
</head>
<body>
<div class="wrap">
    <nav aria-label="主要導覽"><a href="{{ url('/') }}">← 抽獎系統首頁</a><a href="{{ route('demo.lottery.landing') }}">試玩抽獎動畫 ↗</a></nav>
    <header>
        <div class="eyebrow">YOUR FEEDBACK MATTERS</div>
        <h1>讓下一次抽獎，更順暢。</h1>
        <p>遇到問題，或有期待的新功能？告訴我們。免登入就能留下意見。</p>
    </header>
    @if (session('status'))
        <div class="notice" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice errors" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <main class="layout">
        <form class="card" id="feedback-form" method="POST" action="{{ route('feedback.store') }}">
            @csrf
            <div class="field">
                <label for="category">回饋類型</label>
                <select id="category" name="category" required>
                    <option value="">請選擇類型</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="animation_style">相關動畫 <span>／選填</span></label>
                <select id="animation_style" name="animation_style">
                    <option value="">未指定／其他功能</option>
                    @foreach ($styles as $key => $label)
                        <option value="{{ $key }}" @selected(old('animation_style', $animationStyle) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="message">意見或遇到的問題</label>
                <textarea id="message" name="message" rows="7" minlength="10" maxlength="3000" required aria-describedby="message-help" placeholder="例如：使用手機試玩樂透，抽到第三顆球時停止移動。重新整理後可以看到結果。">{{ old('message') }}</textarea>
                <small id="message-help">10–3000 字。描述操作步驟與發生的情況，有助於我們重現問題。</small>
            </div>
            <div class="field">
                <label for="email">回覆信箱 <span>／選填</span></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" placeholder="you@example.com">
                <small>如果希望收到回覆，請留下 Email。</small>
            </div>
            <div class="trap" aria-hidden="true"><label for="website">網站</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
            <input type="hidden" name="source_path" value="{{ old('source_path', $sourcePath) }}">
            <input type="hidden" id="viewport-width" name="viewport_width">
            <input type="hidden" id="viewport-height" name="viewport_height">
            <button class="submit" type="submit">送出回饋</button>
            <small>回饋僅供系統管理員查看與處理，不會公開。請勿填寫密碼或參加者個人資料。</small>
        </form>
        <aside class="card">
            <h2>意見回饋與聯絡我們</h2>
            <p>不論是動畫卡住、文字看不清楚，或想要不同的抽獎玩法，都歡迎告訴我們。</p>
            <h2>方便我們確認的資訊</h2>
            <p class="detail">來源頁面：{{ old('source_path', $sourcePath) ?: '直接開啟回饋頁' }}<br>視窗尺寸：<span id="viewport-label">未提供</span></p>
            <p>送出時會附上上述頁面與視窗尺寸，以及你選擇的動畫，協助確認顯示問題。</p>
            <p>操作問題也可先看看 <a href="{{ route('faq') }}">常見問題</a>。</p>
            <p>直接聯絡：<br><a href="mailto:dindin1688888888@gmail.com">dindin1688888888@gmail.com</a></p>
        </aside>
    </main>
    <footer>© {{ date('Y') }} 抽獎系統 · 每一份回饋，都讓體驗更好。</footer>
</div>
<script>
    const form = document.getElementById('feedback-form');
    const updateViewport = () => {
        const width = Math.min(16384, Math.max(1, window.innerWidth));
        const height = Math.min(16384, Math.max(1, window.innerHeight));
        document.getElementById('viewport-width').value = width;
        document.getElementById('viewport-height').value = height;
        document.getElementById('viewport-label').textContent = `${width} × ${height}`;
    };
    updateViewport();
    window.addEventListener('resize', updateViewport);
    form.addEventListener('submit', () => {
        updateViewport();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = '送出中…';
    });
</script>
</body>
</html>
