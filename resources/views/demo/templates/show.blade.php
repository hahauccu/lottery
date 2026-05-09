<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless($template->is_indexable)
    <meta name="robots" content="noindex,nofollow">
    @endunless
    <title>{{ $template->title }}｜線上抽籤卡片</title>
    <meta name="description" content="套用「{{ $template->title }}」線上抽籤卡片，用 {{ $template->styleLabel() }} 抽獎動畫快速決定抽什麼。">
    <link rel="canonical" href="{{ route('demo.lottery.templates.show', $template) }}">
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Noto Sans TC", sans-serif; background: #08080d; color: #f8fafc; }
        a { color: #fbbf24; text-decoration: none; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 42px 20px 72px; }
        .top { display: flex; justify-content: space-between; gap: 16px; align-items: center; margin-bottom: 28px; }
        .panel { border: 1px solid rgba(255,255,255,.1); border-radius: 26px; background: linear-gradient(160deg, rgba(255,255,255,.08), rgba(255,255,255,.025)); box-shadow: 0 24px 80px rgba(0,0,0,.28); padding: clamp(24px, 5vw, 42px); }
        .meta { display: flex; gap: 8px; flex-wrap: wrap; color: #fbbf24; font-size: 13px; }
        .pill { border: 1px solid rgba(245,158,11,.22); border-radius: 999px; padding: 5px 10px; background: rgba(245,158,11,.08); }
        h1 { margin: 18px 0 12px; font-size: clamp(32px, 6vw, 54px); line-height: 1.1; letter-spacing: -.04em; }
        .desc { color: #94a3b8; line-height: 1.8; font-size: 17px; max-width: 680px; }
        .stats { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; color: #cbd5e1; }
        .section { margin-top: 24px; }
        .section h2 { font-size: 20px; margin: 0 0 12px; }
        .options { display: flex; flex-wrap: wrap; gap: 8px; }
        .option { padding: 8px 11px; border-radius: 999px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08); color: #e2e8f0; font-size: 14px; }
        .form { display: grid; gap: 12px; margin-top: 14px; }
        textarea, input { width: 100%; box-sizing: border-box; border: 1px solid rgba(255,255,255,.12); border-radius: 14px; background: rgba(0,0,0,.28); color: #fff; padding: 12px 14px; font: inherit; }
        label { color: #cbd5e1; font-size: 14px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 14px; background: linear-gradient(135deg, #f59e0b, #dc2626); color: #111827; padding: 13px 18px; font-weight: 800; cursor: pointer; }
        .btn.secondary { background: rgba(255,255,255,.08); color: #f8fafc; border: 1px solid rgba(255,255,255,.1); }
        .grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 22px; align-items: start; }
        @media (max-width: 760px) { .grid { grid-template-columns: 1fr; } .top { align-items: flex-start; flex-direction: column; } }
        .reports { display: grid; gap: 10px; }
        .report { padding: 14px 16px; border: 1px solid rgba(255,255,255,.08); border-radius: 16px; background: rgba(255,255,255,.04); }
        .report b { color: #fde68a; }
        .report p { margin: 6px 0 0; color: #94a3b8; line-height: 1.6; }
        .notice { color: #94a3b8; font-size: 13px; line-height: 1.7; }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="top">
            <a href="{{ route('demo.lottery.templates.index') }}">← 回大家都抽什麼</a>
            <a href="{{ url('/demo/lottery') }}">查看全部抽獎動畫</a>
        </div>

        <section class="panel">
            <div class="meta">
                <span class="pill">{{ $categories[$template->category] ?? '其他' }}</span>
                <span class="pill">{{ $template->styleLabel() }}</span>
                <span class="pill">{{ $template->draw_mode === 'one_by_one' ? '逐一抽出' : '一次全抽' }}</span>
            </div>
            <h1>{{ $template->title }}</h1>
            <p class="desc">{{ $template->description ?: '這是一張大家分享的大家都抽什麼卡片，可直接套用，也可以改成自己的選項後再抽。' }}</p>
            <div class="stats">
                <span>{{ $template->optionsCount() }} 個選項</span>
                <span>每次抽 {{ $template->draw_count }} 個</span>
                <span>已被套用 {{ $template->uses_count }} 次</span>
            </div>
        </section>

        <div class="grid section">
            <section class="panel">
                <h2>套用這張卡片</h2>
                <p class="notice">可以直接使用卡片內的選項，也可以填入自己的姓名、餐廳或飲料清單。留空就使用原本選項。</p>
                <form class="form" method="POST" action="{{ route('demo.lottery.templates.apply', $template) }}">
                    @csrf
                    <label for="names">自訂選項 / 姓名，可留空</label>
                    <textarea id="names" name="names" rows="6" placeholder="每行一個選項，例如：&#10;牛肉麵&#10;日式便當&#10;韓式料理"></textarea>
                    <button class="btn" type="submit">套用並開始抽</button>
                </form>
            </section>

            <section class="panel">
                <h2>卡片選項</h2>
                <div class="options">
                    @foreach (array_slice($template->options ?? [], 0, 18) as $option)
                        <span class="option">{{ $option }}</span>
                    @endforeach
                    @if ($template->optionsCount() > 18)
                        <span class="option">還有 {{ $template->optionsCount() - 18 }} 個</span>
                    @endif
                </div>
            </section>
        </div>

        <section class="panel section">
            <h2>大家抽到了什麼</h2>
            @if (session('status'))
                <p class="notice">{{ session('status') }}</p>
            @endif
            @if ($template->publicReports->isEmpty())
                <p class="notice">目前還沒有人回報成果。套用抽完後，可以回來寫下抽到了什麼。</p>
            @else
                <div class="reports">
                    @foreach ($template->publicReports as $report)
                        <div class="report">
                            <div>{{ $report->author_name ?: '有人' }} 抽到 <b>「{{ $report->result_text }}」</b></div>
                            @if ($report->comment)
                                <p>{{ $report->comment }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <form class="form" method="POST" action="{{ route('demo.lottery.templates.report', $template) }}">
                @csrf
                <label for="result_text">抽到了什麼</label>
                <input id="result_text" name="result_text" maxlength="120" placeholder="例如：韓式料理、Switch 2、王小明" required>
                <label for="author_name">暱稱，可留空</label>
                <input id="author_name" name="author_name" maxlength="40" placeholder="例如：信義上班族">
                <label for="comment">一句心得，可留空</label>
                <input id="comment" name="comment" maxlength="120" placeholder="例如：今天真的吃這間">
                <button class="btn secondary" type="submit">送出抽獎成果</button>
            </form>
        </section>
    </main>
</body>
</html>
