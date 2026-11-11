<style>
    :root { color-scheme: dark; --bg:#0a0b10; --panel:#12141b; --text:#eef0f5; --muted:#acb2c0; --gold:#f0cb83; --line:#ffffff14; }
    * { box-sizing:border-box; }
    [hidden] { display:none !important; }
    html { scroll-behavior:smooth; }
    body { margin:0; background:radial-gradient(900px 600px at 90% 0%,#65462620,transparent),var(--bg); color:var(--text); font-family:system-ui,"Noto Sans TC",sans-serif; }
    a { color:var(--gold); text-underline-offset:.25em; }
    a:hover { color:#fff; }
    :focus-visible { outline:2px solid var(--gold); outline-offset:5px; }
    .skip { position:absolute; top:-5rem; left:1rem; padding:.7rem; background:var(--panel); z-index:5; }
    .skip:focus { top:1rem; }
    .guide-wrap { width:min(100% - 3rem,72rem); margin:auto; }
    .guide-nav { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; padding:1.5rem 0; border-bottom:1px solid var(--line); font-size:.85rem; }
    .guide-nav a { text-decoration:none; }
    .guide-brand { color:#fff; font-weight:700; letter-spacing:.08em; }
    .guide-nav-links { display:flex; gap:1.4rem; flex-wrap:wrap; }
    .guide-hero { padding:4.5rem 0 3rem; }
    .eyebrow { display:flex; align-items:center; gap:.7rem; color:var(--gold); font-size:.78rem; font-weight:700; letter-spacing:.16em; }
    .eyebrow::before { content:""; height:1px; width:2rem; background:var(--gold); }
    h1 { max-width:52rem; font-size:clamp(2rem,4.8vw,3.6rem); font-weight:700; letter-spacing:-.035em; line-height:1.32; margin:1rem 0 1.25rem; text-wrap:balance; }
    h2 { font-size:clamp(1.4rem,3vw,1.9rem); letter-spacing:-.02em; line-height:1.45; margin:0 0 1rem; }
    h3 { font-size:1.1rem; line-height:1.6; margin:1.6rem 0 .6rem; }
    p,li { color:var(--muted); line-height:1.9; }
    p { margin:.7rem 0 1rem; }
    strong { color:var(--text); }
    .lead { max-width:48rem; font-size:1.08rem; }
    .byline,.small { font-size:.83rem; color:var(--muted); }
    .actions { display:flex; flex-wrap:wrap; gap:.75rem; margin-top:1.5rem; }
    .guide-btn { display:inline-flex; align-items:center; justify-content:center; gap:.6rem; min-height:2.8rem; padding:.75rem 1.1rem; border:1px solid #f0cb8344; border-radius:.6rem; color:var(--gold); background:transparent; font:inherit; font-size:.9rem; font-weight:650; text-decoration:none; cursor:pointer; }
    .guide-btn.primary { background:var(--gold); color:#17130d; border-color:var(--gold); }
    .guide-btn:hover { border-color:var(--gold); color:#fff; }
    .guide-btn.primary:hover { background:#ffe1a6; color:#17130d; }
    .guide-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin-bottom:3rem; }
    .guide-card { display:flex; flex-direction:column; min-width:0; padding:1.7rem; border:1px solid var(--line); border-radius:1rem; background:var(--panel); text-decoration:none; transition:border-color .2s; }
    .guide-card:hover { border-color:#f0cb8377; }
    .guide-card h2 { color:#fff; font-size:1.35rem; margin:1.25rem 0 .7rem; }
    .card-top { display:flex; justify-content:space-between; gap:1rem; align-items:center; }
    .card-number { font-size:2.7rem; font-weight:300; color:#f0cb8355; line-height:1; }
    .card-label { font-size:.75rem; color:var(--gold); }
    .card-detail { margin-top:auto; padding-top:1.1rem; border-top:1px solid var(--line); font-size:.78rem; color:var(--muted); line-height:1.8; }
    .card-link { display:flex; justify-content:space-between; margin-top:1.2rem; font-size:.85rem; color:var(--gold); }
    .guide-feature { display:grid; grid-template-columns:minmax(0,1.3fr) minmax(0,1fr); gap:2rem; padding:2.5rem; margin-bottom:1.3rem; border:1px solid #f0cb8333; border-radius:1rem; background:linear-gradient(125deg,#4c382833,var(--panel) 65%); }
    .guide-feature h2 { font-size:clamp(1.65rem,3vw,2.3rem); margin:1.3rem 0 .8rem; }
    .feature-steps { margin:0; padding:0; list-style:none; }
    .feature-steps li { display:flex; gap:1rem; align-items:baseline; border-bottom:1px solid var(--line); padding:.85rem 0; }
    .feature-steps span { color:var(--gold); font: .8rem ui-monospace,monospace; }
    .section-title { display:flex; flex-wrap:wrap; justify-content:space-between; gap:.5rem; align-items:baseline; margin:2rem 0 1.2rem; }
    .section-title p { margin:0; font-size:.85rem; }
    .guide-callout { display:flex; justify-content:space-between; align-items:center; gap:1.5rem; padding:2rem; border:1px solid var(--line); border-radius:1rem; margin:1.5rem 0; background:#ffffff03; }
    .guide-callout p { margin-bottom:0; max-width:42rem; }
    .guide-callout .guide-btn { flex-shrink:0; }
    .guide-layout { display:grid; grid-template-columns:13rem minmax(0,1fr); gap:2.5rem; align-items:start; }
    .guide-toc { position:sticky; top:1.5rem; padding:1.25rem 0; border-top:1px solid var(--gold); }
    .guide-toc h2 { font-size:.85rem; letter-spacing:.06em; }
    .guide-toc ol { padding-left:1.25rem; }
    .guide-toc li { padding:.35rem 0; font-size:.85rem; }
    .guide-toc a { color:var(--muted); text-decoration:none; }
    .guide-toc a:hover { color:var(--gold); }
    .guide-content { min-width:0; }
    .guide-section { padding:2rem; margin-bottom:1.25rem; border:1px solid var(--line); border-radius:1rem; background:#ffffff02; scroll-margin-top:1.5rem; }
    .guide-section > :last-child { margin-bottom:0; }
    .guide-section ol,.guide-section ul { padding-left:1.3rem; }
    .guide-section li { padding:.2rem 0; }
    .guide-note { padding:1rem 1.2rem; border-left:2px solid var(--gold); background:#f0cb8308; border-radius:0 .5rem .5rem 0; }
    .table-scroll { overflow-x:auto; border:1px solid var(--line); border-radius:.65rem; margin:1rem 0; }
    table { width:100%; border-collapse:collapse; min-width:34rem; text-align:left; font-size:.88rem; }
    caption { text-align:left; color:var(--gold); font-weight:650; padding:1rem; }
    th,td { padding:.9rem; border-top:1px solid var(--line); line-height:1.8; vertical-align:top; }
    td { color:var(--muted); }
    th { color:var(--text); }
    th[scope="row"] { position:sticky; left:0; min-width:7rem; background:var(--panel); }
    blockquote { margin:1rem 0; padding:1rem 1.25rem; border-left:2px solid var(--gold); background:#ffffff04; }
    blockquote p { margin:0; color:var(--text); }
    code { color:var(--gold); font-size:.9em; overflow-wrap:anywhere; }
    .copy-message { min-height:1.8rem; margin:.5rem 0 0; font-size:.85rem; color:var(--gold); }
    .guide-related { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; margin:2rem 0; }
    .guide-related a { display:block; padding:1.3rem; border:1px solid var(--line); border-radius:.8rem; text-decoration:none; }
    .guide-related strong { display:block; margin-bottom:.4rem; }
    .guide-related span { color:var(--muted); font-size:.85rem; line-height:1.7; }
    .guide-footer { display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; padding:2rem 0; border-top:1px solid var(--line); margin-top:3rem; font-size:.8rem; color:var(--muted); }
    @media(max-width:850px) { .guide-layout { grid-template-columns:1fr; gap:1rem; } .guide-toc { position:static; } .guide-toc ol { display:flex; flex-wrap:wrap; gap:.3rem 1.7rem; margin-bottom:0; } .guide-grid { grid-template-columns:1fr; } .guide-card { padding:1.5rem; } .guide-card h2 { margin-top:.6rem; } .guide-feature { grid-template-columns:1fr; gap:1.5rem; } }
    @media(max-width:560px) { .guide-wrap { width:calc(100% - 2rem); } .guide-hero { padding:3rem 0 2rem; } h1 { font-size:2rem; } .guide-feature,.guide-section { padding:1.25rem; } .guide-callout { flex-direction:column; align-items:flex-start; padding:1.3rem; } .guide-related { grid-template-columns:1fr; } .guide-nav-links { gap:1rem; } }
    @media(prefers-reduced-motion:reduce) { html { scroll-behavior:auto; } .guide-card { transition:none; } }
    @media print { :root { color-scheme:light; --text:#111; --muted:#333; --gold:#111; --line:#ccc; } body { background:#fff; } .guide-wrap { width:100%; } .guide-nav,.guide-toc,.actions,.guide-related,.guide-callout,.guide-footer,.copy-message { display:none; } .guide-layout { display:block; } .guide-section { padding:1rem 0; border:0; } th[scope="row"] { position:static; background:#fff; } table { min-width:0; } blockquote { break-inside:avoid; } }
</style>
