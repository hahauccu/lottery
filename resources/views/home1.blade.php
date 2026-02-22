<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>尾牙抽獎｜線上抽獎系統｜即時切換、彈幕互動、QRCode 兌獎</title>
    <meta name="description" content="專為企業活動打造的尾牙抽獎與線上抽獎系統。支援後台即切前台即換、員工分群、即時彈幕、抽獎分析報表、QRCode 快速兌獎，讓每場抽獎更公平、更熱鬧、更有效率。">
    <link rel="canonical" href="{{ url('/home1') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="尾牙抽獎｜線上抽獎系統｜即時切換、彈幕互動、QRCode 兌獎">
    <meta property="og:description" content="專為企業活動打造的尾牙抽獎與線上抽獎系統。支援後台即切前台即換、員工分群、即時彈幕、抽獎分析報表、QRCode 快速兌獎，讓每場抽獎更公平、更熱鬧、更有效率。">
    <meta property="og:url" content="{{ url('/home1') }}">
    <meta property="og:image" content="{{ url('/images/previews/lotto_air.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&family=Sora:wght@500;700;800&display=swap" rel="stylesheet">

    @verbatim
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "這套系統適合尾牙抽獎嗎？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "非常適合，專為企業尾牙抽獎與大型活動設計，具備即時切換與多種抽獎效果。"
          }
        },
        {
          "@type": "Question",
          "name": "線上抽獎可以即時切換獎項嗎？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "可以，後台操作後前台可快速同步更新，活動流程不中斷。"
          }
        },
        {
          "@type": "Question",
          "name": "可以分析抽獎結果嗎？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "可以，系統支援抽獎報表與中獎機率模擬分析，方便管理層參考。"
          }
        },
        {
          "@type": "Question",
          "name": "是否支援互動功能？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "支援，提供即時彈幕讓員工參與，提升活動氣氛。"
          }
        },
        {
          "@type": "Question",
          "name": "中獎後怎麼快速領獎？",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "可使用 QRCode 掃描進行兌獎驗證，流程更快更準確。"
          }
        }
      ]
    }
    </script>
    @endverbatim

    @vite(['resources/css/home1.css', 'resources/js/home1.js'])
</head>
<body class="home1-body">
    <header class="home1-hero" id="top">
        <div class="home1-hero-glow" aria-hidden="true"></div>
        <div class="home1-container home1-hero-layout">
            <div class="home1-hero-copy">
                <p class="home1-kicker">企業活動專用平台</p>
                <h1>尾牙抽獎、線上抽獎，一套系統全搞定</h1>
                <p class="home1-subtitle">從獎項設定、即時抽獎、現場互動到兌獎管理，完整支援企業活動流程，讓每場抽獎更即時、更公平、更有氣氛。</p>

                <div class="home1-cta-row">
                    <a href="/admin" class="home1-btn home1-btn-primary" data-track="home_cta_click_start">立即開始抽獎</a>
                    <a href="#features" class="home1-btn home1-btn-outline">觀看六大特色</a>
                    <a href="/admin/login" class="home1-btn home1-btn-ghost" data-track="home_cta_click_login">登入後台 / 建立帳號</a>
                </div>

                <form class="home1-code-form" data-home1-brand-form novalidate>
                    <label for="home1-brand-code">輸入活動代碼，直接前往抽獎頁</label>
                    <div class="home1-code-row">
                        <input id="home1-brand-code" type="text" inputmode="latin" maxlength="20" autocomplete="off" placeholder="例如：DEMO01" data-home1-brand-input>
                        <button type="submit" class="home1-btn home1-btn-primary">前往抽獎</button>
                    </div>
                    <p class="home1-form-note" data-home1-brand-note>代碼格式：英數字、底線或連字號（3-20 碼）</p>
                </form>
            </div>

            <aside class="home1-hero-panel home1-reveal" data-home1-reveal data-home1-delay="120">
                <h2>你會在現場感受到的優勢</h2>
                <ul>
                    <li>後台即切前台即換，主持流程不中斷</li>
                    <li>即時彈幕互動，員工參與熱度拉滿</li>
                    <li>抽獎報表可下載，主管決策有數據</li>
                    <li>QRCode 掃描兌獎，快速又準確</li>
                </ul>
                <div class="home1-panel-stats">
                    <div>
                        <strong>6</strong>
                        <span>種核心能力</span>
                    </div>
                    <div>
                        <strong>5</strong>
                        <span>種抽獎動畫</span>
                    </div>
                    <div>
                        <strong>Realtime</strong>
                        <span>即時同步</span>
                    </div>
                </div>
            </aside>
        </div>
    </header>

    <main>
        <section class="home1-section" id="features">
            <div class="home1-container">
                <div class="home1-title-group home1-reveal" data-home1-reveal>
                    <p class="home1-section-kicker">六大核心特色</p>
                    <h2>六大特色，讓抽獎更即時更好玩</h2>
                </div>

                <div class="home1-feature-grid">
                    <a href="#preview" class="home1-feature-card home1-reveal" data-home1-reveal data-home1-delay="0" data-track="home_feature_card_click" data-track-label="即時切換">
                        <h3>即時切換，後台即切 前台即換</h3>
                        <p>活動進行中可即時切換獎項與顯示模式，主持流程不中斷，現場節奏更順暢。</p>
                    </a>
                    <a href="#features" class="home1-feature-card home1-reveal" data-home1-reveal data-home1-delay="60" data-track="home_feature_card_click" data-track-label="設定簡單">
                        <h3>設定簡單，員工分群管理</h3>
                        <p>支援員工資料與群組管理，活動臨時加碼獎項也能快速上線，不怕手忙腳亂。</p>
                    </a>
                    <a href="#report" class="home1-feature-card home1-reveal" data-home1-reveal data-home1-delay="120" data-track="home_feature_card_click" data-track-label="報表分析">
                        <h3>報表分析，一鍵下載好簡單</h3>
                        <p>提供模擬多次抽獎中獎機率報表，可下載給管理部門做決策參考。</p>
                    </a>
                    <a href="#features" class="home1-feature-card home1-reveal" data-home1-reveal data-home1-delay="180" data-track="home_feature_card_click" data-track-label="即時彈幕">
                        <h3>即時彈幕，員工參與好開心</h3>
                        <p>提供即時彈幕互動，提升員工參與感與現場熱度，讓抽獎更有臨場感。</p>
                    </a>
                    <a href="#features" class="home1-feature-card home1-reveal" data-home1-reveal data-home1-delay="240" data-track="home_feature_card_click" data-track-label="QRCode 兌獎">
                        <h3>即時兌獎，QRCode 快速領獎</h3>
                        <p>中獎後透過 QRCode 快速驗證與領獎，流程清楚、效率高、降低人工錯誤。</p>
                    </a>
                    <a href="#preview" class="home1-feature-card home1-reveal" data-home1-reveal data-home1-delay="300" data-track="home_feature_card_click" data-track-label="多種抽獎動畫">
                        <h3>多種抽獎，喜氣效果樂翻天</h3>
                        <p>提供多種抽獎動畫與視覺效果，營造年節與尾牙氛圍，讓活動更有記憶點。</p>
                    </a>
                </div>
            </div>
        </section>

        <section class="home1-section home1-section-alt" id="preview">
            <div class="home1-container">
                <div class="home1-title-group home1-reveal" data-home1-reveal>
                    <p class="home1-section-kicker">互動重點區</p>
                    <h2>抽獎動畫即時預覽（可切換）</h2>
                </div>

                <div class="home1-preview" data-home1-preview>
                    <div class="home1-preview-tabs" role="tablist" aria-label="抽獎動畫選擇">
                        <button type="button" class="home1-preview-tab is-active" role="tab" aria-selected="true" data-preview-index="0" data-preview-label="樂透氣流機" data-preview-desc="適合正式抽獎流程，節奏穩定。" data-preview-scene="正式流程" data-preview-image="/images/previews/lotto_air.svg">lotto_air</button>
                        <button type="button" class="home1-preview-tab" role="tab" aria-selected="false" data-preview-index="1" data-preview-label="紅包雨" data-preview-desc="適合加碼與高潮段落，氣氛最熱。" data-preview-scene="加碼高潮" data-preview-image="/images/previews/red_packet.svg">red_packet</button>
                        <button type="button" class="home1-preview-tab" role="tab" aria-selected="false" data-preview-index="2" data-preview-label="刮刮樂" data-preview-desc="適合逐步揭曉，提高期待感。" data-preview-scene="慢節奏揭曉" data-preview-image="/images/previews/scratch_card.svg">scratch_card</button>
                        <button type="button" class="home1-preview-tab" role="tab" aria-selected="false" data-preview-index="3" data-preview-label="寶箱" data-preview-desc="適合大獎揭曉，儀式感強。" data-preview-scene="大獎時刻" data-preview-image="/images/previews/treasure_chest.svg">treasure_chest</button>
                        <button type="button" class="home1-preview-tab" role="tab" aria-selected="false" data-preview-index="4" data-preview-label="大寶箱" data-preview-desc="適合壓軸時刻，聚焦視覺效果。" data-preview-scene="壓軸收尾" data-preview-image="/images/previews/big_treasure_chest.svg">big_treasure_chest</button>
                    </div>

                    <div class="home1-preview-stage" data-home1-preview-stage>
                        <img src="/images/previews/lotto_air.svg" alt="樂透氣流機預覽" data-home1-preview-image>
                    </div>

                    <div class="home1-preview-meta">
                        <h3 data-home1-preview-title>樂透氣流機</h3>
                        <p data-home1-preview-desc>適合正式抽獎流程，節奏穩定。</p>
                        <span data-home1-preview-scene>建議場景：正式流程</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="home1-section" id="report">
            <div class="home1-container home1-report-layout">
                <div class="home1-title-group home1-reveal" data-home1-reveal>
                    <p class="home1-section-kicker">管理層視角</p>
                    <h2>不只抽獎，更能提供管理決策參考</h2>
                    <p>提供抽獎結果整理與模擬多次抽獎中獎機率分析，協助管理部門檢視抽獎策略與公平性，讓活動不只熱鬧，也具備可追蹤、可檢核的數據依據。</p>
                </div>
                <div class="home1-report-card home1-reveal" data-home1-reveal data-home1-delay="100">
                    <h3>管理價值一眼看懂</h3>
                    <ul>
                        <li>抽獎結果清單與領獎狀態可追蹤</li>
                        <li>模擬中獎機率報表可直接下載</li>
                        <li>可回頭檢視活動公平性與流程效率</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="home1-section home1-section-alt" id="faq">
            <div class="home1-container home1-faq-wrap">
                <div class="home1-title-group home1-reveal" data-home1-reveal>
                    <p class="home1-section-kicker">常見問題 FAQ</p>
                    <h2>常見問題（FAQ）</h2>
                </div>

                <div class="home1-faq-list" data-home1-faq>
                    <article class="home1-faq-item home1-reveal" data-home1-reveal>
                        <button type="button" class="home1-faq-trigger" aria-expanded="false" data-home1-faq-trigger>這套系統適合尾牙抽獎嗎？</button>
                        <div class="home1-faq-answer" hidden>非常適合，專為企業尾牙抽獎與大型活動設計，具備即時切換與多種抽獎效果。</div>
                    </article>
                    <article class="home1-faq-item home1-reveal" data-home1-reveal data-home1-delay="40">
                        <button type="button" class="home1-faq-trigger" aria-expanded="false" data-home1-faq-trigger>線上抽獎可以即時切換獎項嗎？</button>
                        <div class="home1-faq-answer" hidden>可以，後台操作後前台可快速同步更新，活動流程不中斷。</div>
                    </article>
                    <article class="home1-faq-item home1-reveal" data-home1-reveal data-home1-delay="80">
                        <button type="button" class="home1-faq-trigger" aria-expanded="false" data-home1-faq-trigger>可以分析抽獎結果嗎？</button>
                        <div class="home1-faq-answer" hidden>可以，系統支援抽獎報表與中獎機率模擬分析，方便管理層參考。</div>
                    </article>
                    <article class="home1-faq-item home1-reveal" data-home1-reveal data-home1-delay="120">
                        <button type="button" class="home1-faq-trigger" aria-expanded="false" data-home1-faq-trigger>是否支援互動功能？</button>
                        <div class="home1-faq-answer" hidden>支援，提供即時彈幕讓員工參與，提升活動氣氛。</div>
                    </article>
                    <article class="home1-faq-item home1-reveal" data-home1-reveal data-home1-delay="160">
                        <button type="button" class="home1-faq-trigger" aria-expanded="false" data-home1-faq-trigger>中獎後怎麼快速領獎？</button>
                        <div class="home1-faq-answer" hidden>可使用 QRCode 掃描進行兌獎驗證，流程更快更準確。</div>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <section class="home1-final-cta">
        <div class="home1-container home1-final-inner home1-reveal" data-home1-reveal>
            <h2>立即開始你的下一場抽獎</h2>
            <p>現在就建立活動，讓尾牙抽獎與線上抽獎流程一次到位。</p>
            <div class="home1-cta-row">
                <a href="/admin" class="home1-btn home1-btn-primary" data-track="home_cta_click_start">立即體驗尾牙抽獎</a>
                <a href="/admin/login" class="home1-btn home1-btn-outline" data-track="home_cta_click_login">登入後台管理</a>
            </div>
        </div>
    </section>

    <footer class="home1-footer">
        <div class="home1-container">
            <p>&copy; {{ date('Y') }} 抽獎系統</p>
        </div>
    </footer>
</body>
</html>
