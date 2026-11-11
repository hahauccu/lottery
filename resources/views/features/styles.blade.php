<style>
    .feature-journey { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); border:1px solid var(--line); border-radius:1rem; overflow:hidden; margin:0 0 3rem; }
    .journey-stop { padding:1.7rem; background:#ffffff03; }
    .journey-stop + .journey-stop { border-left:1px solid var(--line); }
    .journey-stop h2 { font-size:1.15rem; margin:1rem 0 .5rem; }
    .journey-stop p { font-size:.9rem; margin:0; }
    .feature-panel { padding:1.4rem; background:linear-gradient(140deg,#f0cb830b,#12141b); border:1px solid #f0cb832d; border-radius:.8rem; margin:1.25rem 0; }
    .feature-panel-title { display:flex; flex-wrap:wrap; align-items:baseline; justify-content:space-between; gap:.5rem; padding-bottom:1rem; border-bottom:1px solid var(--line); }
    .feature-panel-title strong { font-size:.95rem; }
    .feature-panel-title span { font-size:.75rem; color:var(--muted); }
    .feature-flow { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; list-style:none; padding:0 !important; margin:1.2rem 0; }
    .feature-flow li { padding:1rem; border:1px solid var(--line); border-radius:.65rem; background:var(--panel); font-size:.85rem; }
    .feature-flow strong { display:block; margin-bottom:.5rem; color:var(--gold); }
    .feature-stat { font:500 clamp(2rem,5vw,3.2rem)/1.2 ui-monospace,monospace; color:var(--gold); margin:1.25rem 0 .7rem; }
    .feature-stat small { font:400 .85rem system-ui,sans-serif; color:var(--muted); }
    .feature-example { border-left:2px solid #7dcfb8; padding:1rem 1.2rem; background:#7dcfb808; border-radius:0 .5rem .5rem 0; }
    .feature-example p { margin:0; }
    .feature-faq details { border-top:1px solid var(--line); padding:1rem 0; }
    .feature-faq summary { cursor:pointer; line-height:1.8; font-weight:650; color:var(--text); }
    .feature-faq details p { padding-left:1rem; margin-bottom:0; }
    .feature-record { display:grid; grid-template-columns:5rem 1fr; gap:.5rem 1rem; font-size:.85rem; margin:1.2rem 0 0; }
    .feature-record dt { color:var(--gold); }.feature-record dd { margin:0; line-height:1.8; color:var(--muted); }
    @media(max-width:650px) { .feature-journey,.feature-flow { grid-template-columns:1fr; } .journey-stop + .journey-stop { border-left:0; border-top:1px solid var(--line); } .journey-stop { padding:1.3rem; } .feature-panel { padding:1rem; } }
</style>
