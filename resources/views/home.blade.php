<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO --}}
    <title>尾牙抽獎｜線上抽獎系統｜即時切換、彈幕互動、QRCode 兌獎</title>
    <meta name="description" content="專為企業活動打造的尾牙抽獎與線上抽獎系統。支援後台即切前台即換、員工分群、即時彈幕、抽獎分析報表、QRCode 快速兌獎，讓每場抽獎更公平、更熱鬧、更有效率。">
    <link rel="canonical" href="{{ url('/') }}">

    {{-- OG --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="尾牙抽獎｜線上抽獎系統｜即時切換、彈幕互動、QRCode 兌獎">
    <meta property="og:description" content="專為企業活動打造的尾牙抽獎與線上抽獎系統。支援後台即切前台即換、員工分群、即時彈幕、抽獎分析報表、QRCode 快速兌獎，讓每場抽獎更公平、更熱鬧、更有效率。">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ url('/images/og-home.jpg') }}">

    {{-- FAQ JSON-LD --}}
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

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/home.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --c-bg: #0a0a0f;
            --c-bg-card: #12121a;
            --c-bg-card-hover: #1a1a26;
            --c-red: #dc2626;
            --c-red-glow: #ef4444;
            --c-gold: #f59e0b;
            --c-gold-light: #fbbf24;
            --c-gold-dim: #b45309;
            --c-slate: #1e293b;
            --c-text: #e2e8f0;
            --c-text-dim: #94a3b8;
            --c-border: #1e293b;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Noto Sans TC', 'Figtree', system-ui, sans-serif;
            background: var(--c-bg);
            color: var(--c-text);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* ── Scroll Reveal ── */
        [data-reveal] {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.7s cubic-bezier(.16,1,.3,1), transform 0.7s cubic-bezier(.16,1,.3,1);
        }
        [data-reveal].revealed {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── Hero ── */
        .hero {
            position: relative;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 6rem 1.5rem 4rem;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(900px circle at 30% 20%, rgba(220, 38, 38, 0.18), transparent 60%),
                radial-gradient(700px circle at 75% 60%, rgba(245, 158, 11, 0.14), transparent 55%),
                radial-gradient(400px circle at 50% 90%, rgba(245, 158, 11, 0.08), transparent 50%),
                linear-gradient(180deg, #0a0a0f 0%, #0d0d16 100%);
            z-index: 0;
        }

        /* Floating gold particles */
        .hero-particles {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .hero-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--c-gold);
            opacity: 0;
            animation: particleFloat 8s ease-in-out infinite;
        }
        @keyframes particleFloat {
            0% { opacity: 0; transform: translateY(0) scale(0.5); }
            20% { opacity: 0.7; }
            80% { opacity: 0.3; }
            100% { opacity: 0; transform: translateY(-120px) scale(0); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 52rem;
        }
        .hero h1 {
            font-size: clamp(2rem, 5.5vw, 3.5rem);
            font-weight: 900;
            line-height: 1.25;
            margin: 0 0 1.25rem;
            letter-spacing: -0.01em;
        }
        .hero h1 .text-gradient {
            background: linear-gradient(135deg, var(--c-gold-light) 0%, var(--c-gold) 40%, var(--c-red) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero .subtitle {
            font-size: clamp(1rem, 2.2vw, 1.25rem);
            color: var(--c-text-dim);
            line-height: 1.8;
            margin: 0 0 2.5rem;
            max-width: 36rem;
            margin-left: auto;
            margin-right: auto;
        }

        /* CTA buttons */
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--c-red) 0%, #b91c1c 100%);
            color: #fff;
            box-shadow: 0 0 30px rgba(220, 38, 38, 0.3), 0 4px 12px rgba(0,0,0,0.3);
        }
        .btn-primary:hover {
            box-shadow: 0 0 40px rgba(220, 38, 38, 0.5), 0 6px 20px rgba(0,0,0,0.4);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: var(--c-gold-light);
            border: 1px solid rgba(245, 158, 11, 0.25);
        }
        .btn-secondary:hover {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.5);
            transform: translateY(-2px);
        }

        /* ── Section common ── */
        .section {
            padding: 5rem 1.5rem;
            max-width: 72rem;
            margin: 0 auto;
        }
        .section-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }
        .section-header h2 {
            font-size: clamp(1.5rem, 3.5vw, 2.25rem);
            font-weight: 900;
            margin: 0 0 1rem;
            letter-spacing: -0.01em;
        }
        .section-header .accent {
            color: var(--c-gold);
        }
        .section-header p {
            color: var(--c-text-dim);
            font-size: 1.05rem;
            max-width: 32rem;
            margin: 0 auto;
            line-height: 1.7;
        }
        .section-divider {
            width: 48px;
            height: 3px;
            background: linear-gradient(90deg, var(--c-red), var(--c-gold));
            border-radius: 2px;
            margin: 0 auto 1.5rem;
        }

        /* ── Features grid ── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        @media (max-width: 768px) {
            .features-grid { grid-template-columns: 1fr; }
        }
        .feature-card {
            background: var(--c-bg-card);
            border: 1px solid var(--c-border);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.35s ease;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--c-gold), transparent);
            opacity: 0;
            transition: opacity 0.35s;
        }
        .feature-card:hover {
            background: var(--c-bg-card-hover);
            border-color: rgba(245, 158, 11, 0.2);
            transform: translateY(-4px);
        }
        .feature-card:hover::before {
            opacity: 1;
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.15), rgba(245, 158, 11, 0.1));
            border: 1px solid rgba(245, 158, 11, 0.12);
        }
        .feature-icon svg {
            width: 24px;
            height: 24px;
            color: var(--c-gold);
        }
        .feature-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 0.6rem;
        }
        .feature-card p {
            color: var(--c-text-dim);
            font-size: 0.92rem;
            line-height: 1.65;
            margin: 0;
        }

        /* ── Animation Preview ── */
        .preview-section {
            background:
                radial-gradient(600px circle at 50% 30%, rgba(245, 158, 11, 0.06), transparent 60%),
                linear-gradient(180deg, rgba(30, 41, 59, 0.15) 0%, transparent 100%);
            border-top: 1px solid var(--c-border);
            border-bottom: 1px solid var(--c-border);
            padding: 5rem 1.5rem;
        }
        .preview-inner {
            max-width: 60rem;
            margin: 0 auto;
        }
        .preview-tabs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .preview-tab {
            padding: 0.55rem 1.25rem;
            border-radius: 100px;
            font-size: 0.88rem;
            font-weight: 600;
            border: 1px solid var(--c-border);
            background: transparent;
            color: var(--c-text-dim);
            cursor: pointer;
            transition: all 0.25s;
            font-family: inherit;
        }
        .preview-tab:hover {
            border-color: rgba(245, 158, 11, 0.3);
            color: var(--c-text);
        }
        .preview-tab.active {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(245, 158, 11, 0.15));
            border-color: var(--c-gold-dim);
            color: var(--c-gold-light);
        }
        .preview-window {
            position: relative;
            aspect-ratio: 16 / 9;
            max-width: 48rem;
            margin: 0 auto 1.5rem;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--c-border);
            background: var(--c-bg-card);
        }
        .preview-window img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.35s ease;
        }
        .preview-desc {
            text-align: center;
            color: var(--c-text-dim);
            font-size: 1rem;
            max-width: 28rem;
            margin: 0 auto;
            line-height: 1.6;
            min-height: 3.2rem;
            transition: opacity 0.3s;
        }

        /* ── Report section ── */
        .report-section {
            padding: 5rem 1.5rem;
        }
        .report-inner {
            max-width: 60rem;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        @media (max-width: 768px) {
            .report-inner {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
        }
        .report-text h2 {
            font-size: clamp(1.5rem, 3.5vw, 2rem);
            font-weight: 900;
            margin: 0 0 1.25rem;
            line-height: 1.3;
        }
        .report-text p {
            color: var(--c-text-dim);
            font-size: 1rem;
            line-height: 1.75;
            margin: 0;
        }
        .report-visual {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .report-icon-box {
            width: 220px;
            height: 180px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--c-bg-card) 0%, rgba(30, 41, 59, 0.4) 100%);
            border: 1px solid var(--c-border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .report-icon-box svg {
            width: 56px;
            height: 56px;
            color: var(--c-gold);
            opacity: 0.85;
        }
        .report-icon-box span {
            font-size: 0.88rem;
            color: var(--c-text-dim);
        }

        /* ── FAQ ── */
        .faq-section {
            background:
                radial-gradient(500px circle at 70% 50%, rgba(220, 38, 38, 0.04), transparent 60%);
            padding: 5rem 1.5rem;
        }
        .faq-inner {
            max-width: 44rem;
            margin: 0 auto;
        }
        .faq-item {
            border-bottom: 1px solid var(--c-border);
        }
        .faq-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            background: none;
            border: none;
            padding: 1.25rem 0;
            color: var(--c-text);
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            font-family: inherit;
            transition: color 0.2s;
        }
        .faq-trigger:hover {
            color: var(--c-gold-light);
        }
        .faq-trigger svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            transition: transform 0.3s ease;
            color: var(--c-text-dim);
        }
        .faq-answer {
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.4s ease, padding 0.3s ease;
            padding: 0 0;
        }
        .faq-answer.open {
            max-height: 10rem;
            padding: 0 0 1.25rem;
        }
        .faq-answer p {
            color: var(--c-text-dim);
            font-size: 0.95rem;
            line-height: 1.7;
            margin: 0;
        }

        /* ── Final CTA ── */
        .final-cta {
            position: relative;
            padding: 5rem 1.5rem;
            text-align: center;
            overflow: hidden;
        }
        .final-cta::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(600px circle at 50% 50%, rgba(220, 38, 38, 0.12), transparent 60%),
                radial-gradient(400px circle at 50% 80%, rgba(245, 158, 11, 0.08), transparent 50%);
            z-index: 0;
        }
        .final-cta-content {
            position: relative;
            z-index: 1;
            max-width: 36rem;
            margin: 0 auto;
        }
        .final-cta h2 {
            font-size: clamp(1.5rem, 3.5vw, 2.25rem);
            font-weight: 900;
            margin: 0 0 1.5rem;
        }

        /* ── Footer ── */
        .site-footer {
            border-top: 1px solid var(--c-border);
            padding: 2rem 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--c-text-dim);
        }
        .site-footer a {
            color: var(--c-gold);
            text-decoration: none;
        }
        .site-footer a:hover {
            text-decoration: underline;
        }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .hero { padding: 4rem 1rem 3rem; }
            .btn { padding: 0.75rem 1.5rem; font-size: 0.92rem; }
            .section { padding: 3.5rem 1rem; }
            .preview-section, .faq-section, .report-section { padding: 3.5rem 1rem; }
            .feature-card { padding: 1.5rem; }
            .preview-tabs { gap: 0.35rem; }
            .preview-tab { padding: 0.45rem 0.9rem; font-size: 0.82rem; }
        }
    </style>
</head>
<body>
    {{-- ════════════════════════════════════════════
         1. HERO
    ════════════════════════════════════════════ --}}
    <section class="hero">
        <div class="hero-particles" aria-hidden="true">
            @for ($i = 0; $i < 18; $i++)
                <span class="hero-particle" style="
                    left: {{ rand(5, 95) }}%;
                    top: {{ rand(20, 95) }}%;
                    animation-delay: {{ $i * 0.45 }}s;
                    animation-duration: {{ rand(60, 100) / 10 }}s;
                    width: {{ rand(2, 5) }}px;
                    height: {{ rand(2, 5) }}px;
                "></span>
            @endfor
        </div>

        <div class="hero-content">
            <h1 data-reveal>
                <span class="text-gradient">尾牙抽獎、線上抽獎</span><br>一站搞定
            </h1>
            <p class="subtitle" data-reveal data-reveal-delay="120">
                不用再 Excel 手動抽、不怕現場出包。<br>
                從獎項設定到兌獎管理，10 分鐘搞定一場專業抽獎。
            </p>

            <div class="btn-group" data-reveal data-reveal-delay="240">
                <a href="/admin" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    馬上註冊試用
                </a>
                <a href="#features" class="btn btn-secondary">觀看六大特色</a>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         2. FEATURES
    ════════════════════════════════════════════ --}}
    <section class="section" id="features">
        <div class="section-header" data-reveal>
            <div class="section-divider"></div>
            <h2>六大特色，讓<span class="accent">抽獎</span>更即時更好玩</h2>
        </div>

        <div class="features-grid">
            {{-- Feature 1: 即時切換 --}}
            <div class="feature-card" data-reveal data-reveal-delay="0">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </div>
                <h3>即時切換</h3>
                <p>活動進行中可即時切換獎項與顯示模式，主持流程不中斷，現場節奏更順暢。</p>
            </div>

            {{-- Feature 2: 設定簡單 --}}
            <div class="feature-card" data-reveal data-reveal-delay="80">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>設定簡單</h3>
                <p>支援員工資料與群組管理，面對活動臨時加碼也能快速上線，不怕手忙腳亂。</p>
            </div>

            {{-- Feature 3: 報表分析 --}}
            <div class="feature-card" data-reveal data-reveal-delay="160">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <h3>報表分析</h3>
                <p>系統提供模擬多次抽獎中獎機率報表，可下載提供給老闆或管理部門做決策參考。</p>
            </div>

            {{-- Feature 4: 即時彈幕 --}}
            <div class="feature-card" data-reveal data-reveal-delay="240">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h3>即時彈幕</h3>
                <p>提供即時彈幕互動，提升員工參與感與現場熱度，讓抽獎更有臨場感。</p>
            </div>

            {{-- Feature 5: QRCode 兌獎 --}}
            <div class="feature-card" data-reveal data-reveal-delay="320">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="8" height="8" rx="1"/><rect x="14" y="2" width="8" height="8" rx="1"/><rect x="2" y="14" width="8" height="8" rx="1"/><rect x="14" y="14" width="4" height="4" rx="0.5"/><line x1="22" y1="14" x2="22" y2="14.01"/><line x1="22" y1="18" x2="22" y2="22"/><line x1="18" y1="22" x2="18" y2="22.01"/></svg>
                </div>
                <h3>QRCode 兌獎</h3>
                <p>中獎後可透過 QRCode 快速驗證與領獎，流程清楚、效率高、降低人工錯誤。</p>
            </div>

            {{-- Feature 6: 多種動畫 --}}
            <div class="feature-card" data-reveal data-reveal-delay="400">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
                </div>
                <h3>多種動畫</h3>
                <p>提供多種抽獎動畫與視覺效果，營造年節與尾牙氛圍，讓活動更有記憶點。</p>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         3. ANIMATION PREVIEW
    ════════════════════════════════════════════ --}}
    <section class="preview-section" id="preview">
        <div class="preview-inner" x-data="animationPreview"
             @mouseenter="paused = true" @mouseleave="paused = false"
             @touchstart.passive="handleTouchStart($event)"
             @touchend="handleTouchEnd($event)">

            <div class="section-header" data-reveal>
                <div class="section-divider"></div>
                <h2><span class="accent">抽獎動畫</span>即時預覽</h2>
                <p>五種風格任你切換，點擊預覽實際效果</p>
            </div>

            <div class="preview-tabs" data-reveal data-reveal-delay="100">
                <template x-for="(item, i) in items" :key="item.key">
                    <button class="preview-tab"
                            :class="{ 'active': active === i }"
                            @click="switchTo(i)"
                            x-text="item.label"></button>
                </template>
            </div>

            <div class="preview-window" data-reveal data-reveal-delay="180">
                <template x-for="(item, i) in items" :key="item.key">
                    <img :src="item.img"
                         :alt="item.label + ' 動畫預覽'"
                         :style="{ opacity: active === i ? 1 : 0, zIndex: active === i ? 1 : 0 }">
                </template>
            </div>

            <div class="preview-desc" data-reveal data-reveal-delay="260" x-text="current.desc"></div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         4. REPORT VALUE
    ════════════════════════════════════════════ --}}
    <section class="report-section">
        <div class="report-inner">
            <div class="report-text" data-reveal>
                <div class="section-divider" style="margin: 0 0 1.5rem;"></div>
                <h2>不只抽獎，更能提供<span class="accent">管理決策</span>參考</h2>
                <p>提供抽獎結果整理與模擬多次抽獎中獎機率分析，協助管理部門檢視抽獎策略與公平性，讓活動不只熱鬧，也具備可追蹤、可檢核的數據依據。</p>
            </div>
            <div class="report-visual" data-reveal data-reveal-delay="150">
                <div class="report-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    <span>一鍵下載分析報表</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         5. FAQ
    ════════════════════════════════════════════ --}}
    <section class="faq-section" id="faq">
        <div class="faq-inner" x-data="faqAccordion">
            <div class="section-header" data-reveal>
                <div class="section-divider"></div>
                <h2>常見問題</h2>
            </div>

            @php
                $faqs = [
                    ['q' => '這套系統適合尾牙抽獎嗎？', 'a' => '非常適合，專為企業尾牙抽獎與大型活動設計，具備即時切換與多種抽獎效果。'],
                    ['q' => '線上抽獎可以即時切換獎項嗎？', 'a' => '可以，後台操作後前台可快速同步更新，活動流程不中斷。'],
                    ['q' => '可以分析抽獎結果嗎？', 'a' => '可以，系統支援抽獎報表與中獎機率模擬分析，方便管理層參考。'],
                    ['q' => '是否支援互動功能？', 'a' => '支援，提供即時彈幕讓員工參與，提升活動氣氛。'],
                    ['q' => '中獎後怎麼快速領獎？', 'a' => '可使用 QRCode 掃描進行兌獎驗證，流程更快更準確。'],
                ];
            @endphp

            @foreach ($faqs as $i => $faq)
                <div class="faq-item" data-reveal data-reveal-delay="{{ $i * 60 }}">
                    <button class="faq-trigger" @click="toggle({{ $i }})">
                        <span>{{ $faq['q'] }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             :style="{ transform: isOpen({{ $i }}) ? 'rotate(180deg)' : 'rotate(0)' }">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="faq-answer" :class="{ 'open': isOpen({{ $i }}) }">
                        <p>{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         6. FINAL CTA
    ════════════════════════════════════════════ --}}
    <section class="final-cta">
        <div class="final-cta-content">
            <h2 data-reveal>立即開始你的下一場<span class="accent">抽獎</span></h2>

            <div class="btn-group" data-reveal data-reveal-delay="100">
                <a href="/admin" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    馬上註冊試用
                </a>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════
         7. FOOTER
    ════════════════════════════════════════════ --}}
    <footer class="site-footer">
        <p>&copy; {{ date('Y') }} 抽獎系統 &mdash; <a href="/admin">登入後台</a></p>
    </footer>
</body>
</html>
