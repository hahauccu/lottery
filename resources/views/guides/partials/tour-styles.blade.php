<style>
    .import-guide .guide-hero { padding:3rem 0 2rem; }
    .import-guide .guide-hero h1 { max-width:none; }
    .import-guide .guide-layout { grid-template-columns:1fr; gap:1.5rem; }
    .import-guide .guide-toc { position:static; display:flex; align-items:center; gap:2rem; border-top:1px solid var(--line); border-bottom:1px solid var(--line); padding:1rem 0; }
    .import-guide .guide-toc h2,.import-guide .guide-toc > .small { display:none; }
    .import-guide .guide-toc ol { display:flex; flex-wrap:wrap; gap:.5rem 2rem; padding:0; margin:0; list-style:none; }
    .import-guide .guide-section { padding:2.2rem; margin-top:1.5rem; }
    .import-tour { scroll-margin-top:1rem; border:1px solid #f0cb8330; border-radius:1.1rem; overflow:hidden; background:linear-gradient(130deg,#221c17 0%,#13151c 50%); }
    .tour-kicker { font-size:.73rem; letter-spacing:.12em; color:var(--gold); }
    .tour-steps { display:grid; grid-template-columns:repeat(auto-fit,minmax(8.5rem,1fr)); margin:1.4rem 2rem 0; border:1px solid var(--line); border-radius:.7rem; overflow:hidden; }
    .tour-steps a { display:flex; align-items:center; gap:.6rem; padding:1rem; text-decoration:none; color:var(--muted); font-size:.85rem; border-right:1px solid var(--line); }
    .tour-steps a:last-child { border-right:0; }
    .tour-steps a[aria-current="step"] { background:var(--gold); color:#201a11; font-weight:700; }
    .tour-steps a:hover { background:#f0cb8320; color:#fff; }
    .tour-steps a[aria-current="step"]:hover { background:#ffe0a5; color:#201a11; }
    .tour-number { font: .72rem ui-monospace,monospace; opacity:.7; }
    .tour-panel { padding:1.5rem 2rem 2rem; scroll-margin-top:1rem; }
    .tour-copy p { font-size:1rem; margin:0 0 1.25rem; }
    .tour-figure { margin:0 auto; min-width:0; width:fit-content; max-width:100%; }
    .tour-image-link { position:relative; display:block; border:1px solid #ffffff24; border-radius:.65rem; overflow:hidden; background:#09090b; cursor:zoom-in; }
    .tour-image-link img { display:block; width:auto; max-width:100%; height:auto; max-height:clamp(10rem,calc(100svh - 20rem),32rem); object-fit:contain; }
    .tour-pin { position:absolute; left:var(--pin-x); top:var(--pin-y); transform:translate(-50%,-50%); display:grid; place-items:center; width:1.7rem; height:1.7rem; border-radius:50%; background:var(--gold); color:#17130d; border:2px solid #fff0cf; box-shadow:0 0 0 5px #f0cb8325; font-size:.8rem; font-weight:800; pointer-events:none; }
    .tour-zoom { display:block; padding:.55rem .75rem; font-size:.72rem; text-align:right; color:var(--gold); background:#191a20; border-top:1px solid var(--line); }
    .tour-controls { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.25rem 2rem; border-top:1px solid var(--line); }
    .tour-controls [role="status"] { font-size:.8rem; color:var(--muted); }
    .tour-controls button:disabled { cursor:default; opacity:.35; }
    .sample-section .section-title { margin:0; }
    .sample-section h2 { margin:.6rem 0; }
    .import-columns { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); margin:1.4rem 0; gap:.6rem; }
    .import-columns > span { border:1px solid var(--line); border-radius:.6rem; padding:1rem .6rem; text-align:center; font-size:.9rem; }
    .import-columns small { display:block; color:var(--muted); margin-top:.4rem; font-size:.7rem; }
    .import-faq { border-top:1px solid var(--line); padding:1rem 0; }
    .import-faq summary { font-size:.95rem; line-height:1.8; cursor:pointer; color:var(--text); }
    .import-faq p { font-size:.9rem; }
    .import-next h2 { margin-top:.7rem; }
    .guide-image-dialog { width:min(96vw,90rem); max-width:none; max-height:94dvh; padding:1rem; background:#111218; border:1px solid #ffffff30; border-radius:1rem; color:var(--text); }
    .guide-image-dialog::backdrop { background:#000c; }
    .guide-image-toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:.7rem; }
    .guide-image-toolbar p { margin:0; font-size:.85rem; }
    .guide-image-toolbar button { white-space:nowrap; }
    .guide-image-scroll { overflow:auto; max-height:calc(94dvh - 7rem); }
    .guide-image-scroll img { display:block; margin:auto; width:auto; max-width:100%; height:auto; max-height:calc(94dvh - 7rem); object-fit:contain; }
    @media(max-width:560px) { .import-guide .guide-hero { padding:2.2rem 0 1.5rem; } .import-guide .guide-toc ol { gap:.25rem 1rem; } .import-guide .guide-section { padding:1.25rem; }    .tour-steps { margin:1rem 1.25rem 0; grid-template-columns:repeat(2,minmax(0,1fr)); } .tour-steps a { padding:.8rem; font-size:.8rem; } .tour-steps a:nth-child(2) { border-right:0; } .tour-steps a:nth-child(-n+2) { border-bottom:1px solid var(--line); } .tour-panel { padding:1.25rem; }    .tour-controls { padding:1rem 1.25rem; gap:.4rem; } .tour-controls .guide-btn { padding:.6rem .75rem; font-size:.8rem; } .tour-controls [role="status"] { font-size:.72rem; }  .import-columns { grid-template-columns:repeat(3,minmax(0,1fr)); } .tour-pin { width:1.25rem; height:1.25rem; font-size:.65rem; border-width:1px; box-shadow:0 0 0 3px #f0cb8325; } }
    @media print { .import-guide .guide-toc,.tour-steps,.tour-controls,.tour-zoom,.tour-pin,.guide-image-dialog { display:none!important; } .tour-panel[hidden] { display:grid!important; } .import-tour { background:#fff; border:0; } .tour-panel { grid-template-columns:1fr; break-inside:avoid; padding:1rem 0; }  }
</style>
