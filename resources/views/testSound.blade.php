<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>testSound 音效試聽台</title>
    <meta name="description" content="抽獎系統音效試聽頁：原有音效說明、播放控制、建議新增音效樣本與寶箱噴金幣音效示範。">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&family=Sora:wght@500;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/testSound.css', 'resources/js/testSound.js'])
</head>
<body class="testsound-body">
    <main class="testsound-shell">
        <header class="testsound-hero">
            <p class="testsound-kicker">Lottery Audio Lab</p>
            <h1>testSound 音效試聽台</h1>
            <p class="testsound-subtitle">這頁會列出目前抽獎流程的原始音效（可直接播放），並加入建議新增的音效樣本。你可以先在這裡試聽，再決定要不要串回正式抽獎流程。</p>
            <div class="testsound-meta">
                <span>原有音效：14 組</span>
                <span>建議新增：7 組</span>
                <span>重點示範：寶箱噴金幣連續音</span>
            </div>
        </header>

        <section class="testsound-panel" aria-label="主控制台">
            <div class="testsound-panel-title">
                <h2>主控制台</h2>
                <p>先按「啟用音效」再試聽。Safari / iOS 需要使用者互動後才能播放。</p>
            </div>

            <div class="testsound-controls">
                <button type="button" class="testsound-btn testsound-btn-primary" data-action="enable-audio">啟用音效</button>
                <button type="button" class="testsound-btn testsound-btn-danger" data-action="stop-all">停止全部音效</button>
                <button type="button" class="testsound-btn" data-action="demo-flow">抽獎節奏 Demo</button>
                <button type="button" class="testsound-btn" data-action="demo-treasure">寶箱噴金幣 Demo</button>
            </div>

            <div class="testsound-sliders">
                <label class="testsound-slider-item">
                    <span>Master 音量</span>
                    <input type="range" min="0" max="100" value="78" data-volume="master">
                    <strong data-volume-label="master">78%</strong>
                </label>
                <label class="testsound-slider-item">
                    <span>SFX 音量</span>
                    <input type="range" min="0" max="100" value="88" data-volume="sfx">
                    <strong data-volume-label="sfx">88%</strong>
                </label>
                <label class="testsound-slider-item">
                    <span>BGM 音量</span>
                    <input type="range" min="0" max="100" value="70" data-volume="bgm">
                    <strong data-volume-label="bgm">70%</strong>
                </label>
            </div>

            <p class="testsound-status" data-role="status">狀態：尚未啟用音效（可先直接按啟用音效）</p>
        </section>

        <section class="testsound-panel" aria-label="抽獎背景音樂測試">
            <div class="testsound-panel-title">
                <h2>抽獎背景音樂（BGM）測試</h2>
                <p>可貼入後台上傳後的音檔 URL（例如 `/storage/lottery/.../file.mp3`）進行測試。</p>
            </div>

            <div class="testsound-bgm-row">
                <input type="text" placeholder="貼上音樂 URL" data-role="bgm-url">
                <button type="button" class="testsound-btn testsound-btn-primary" data-action="bgm-load-play">載入並播放</button>
                <button type="button" class="testsound-btn" data-action="bgm-pause">暫停</button>
                <button type="button" class="testsound-btn" data-action="bgm-stop">停止</button>
                <label class="testsound-loop-toggle">
                    <input type="checkbox" checked data-role="bgm-loop">
                    <span>循環播放</span>
                </label>
            </div>

            <p class="testsound-note">提示：若遇到不能播放，通常是 URL 錯誤、檔案無法存取或瀏覽器自動播放限制。</p>
        </section>

        <section class="testsound-panel" aria-label="原本音效清單">
            <div class="testsound-panel-title">
                <h2>原本音效（目前專案內）</h2>
                <p>以下是 `resources/js/lottery.js` 既有音效，含使用場景與目前是否串接主流程。</p>
            </div>
            <div id="testsound-original-grid" class="testsound-grid"></div>
        </section>

        <section class="testsound-panel" aria-label="建議新增音效清單">
            <div class="testsound-panel-title">
                <h2>建議新增音效（試聽樣本）</h2>
                <p>這些是為了優化節奏與臨場感的提案，先做樣本讓你試聽。</p>
            </div>
            <div id="testsound-suggested-grid" class="testsound-grid"></div>
        </section>
    </main>
</body>
</html>
