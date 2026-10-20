const events = new Set(['page_view', 'play_click', 'register_click', 'draw_start', 'draw_complete', 'draw_failed']);

// 只傳白名單事件與動畫種類；不讀取網址參數、名單、Email或訪客識別碼。
export function createDemoTracker({ endpoint, csrf, entry, style = 'all' }, { send, optedOut = false }) {
    return (event, animationStyle = style, durationMs = null) => {
        if (optedOut || !events.has(event)) return;
        const payload = { event, animation_style: animationStyle, entry };
        if (durationMs !== null && Number.isFinite(durationMs)) payload.duration_ms = Math.min(600000, Math.max(0, Math.round(durationMs)));
        try {
            Promise.resolve(send(endpoint, {
                method: 'POST', credentials: 'same-origin', keepalive: true,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            })).catch(() => {});
        } catch { /* 統計失敗不得影響導覽或抽獎。 */ }
    };
}

let tracker = () => {};
export const trackDemoEvent = (...args) => tracker(...args);

if (typeof document !== 'undefined') {
    const boot = () => {
        const config = document.getElementById('demo-metrics');
        if (!config) return;
        tracker = createDemoTracker(config.dataset, {
            send: (...args) => fetch(...args),
            optedOut: navigator.doNotTrack === '1' || navigator.globalPrivacyControl === true,
        });
        tracker('page_view');
        document.addEventListener('click', (event) => {
            const link = event.target.closest?.('a[data-demo-event]');
            if (link) tracker(link.dataset.demoEvent, link.dataset.demoStyle || config.dataset.style);
        });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
}
