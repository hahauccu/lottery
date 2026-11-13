// The full walkthrough remains readable when JavaScript is unavailable.
document.querySelectorAll('[data-guide-tour]').forEach((tour) => {
    const panels = [...tour.querySelectorAll('[data-tour-step]')];
    const links = [...tour.querySelectorAll('[data-tour-link]')];
    const controls = tour.querySelector('[data-tour-controls]');
    const previous = tour.querySelector('[data-tour-prev]');
    const next = tour.querySelector('[data-tour-next]');
    const status = tour.querySelector('[data-tour-status]');
    if (!panels.length || panels.length !== links.length || !controls || !previous || !next || !status) return;
    let current = 0;
    const show = (index) => {
        current = Math.max(0, Math.min(index, panels.length - 1));
        panels.forEach((panel, position) => { panel.hidden = position !== current; });
        links.forEach((link, position) => {
            if (position === current) link.setAttribute('aria-current', 'step');
            else link.removeAttribute('aria-current');
        });
        previous.disabled = current === 0;
        next.textContent = current === panels.length - 1 ? '從頭看一次 ↺' : '下一步 →';
        status.textContent = `步驟 ${current + 1} / ${panels.length}`;
    };
    const showHash = () => {
        const index = panels.findIndex((panel) => `#${panel.id}` === window.location.hash);
        if (index >= 0) show(index);
    };
    links.forEach((link, index) => link.addEventListener('click', (event) => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        show(index);
        history.replaceState(null, '', link.hash);
    }));
    previous.addEventListener('click', () => { show(current - 1); history.replaceState(null, '', links[current].hash); });
    next.addEventListener('click', () => { show((current + 1) % panels.length); history.replaceState(null, '', links[current].hash); });
    window.addEventListener('hashchange', showHash);
    show(0);
    showHash();
    controls.hidden = false;
});

const dialog = document.querySelector('[data-guide-image-dialog]');
if (dialog && typeof dialog.showModal === 'function') {
    const fullImage = dialog.querySelector('[data-guide-image-full]');
    const caption = dialog.querySelector('[data-guide-image-caption]');
    let opener;
    document.querySelectorAll('[data-guide-image]').forEach((link) => {
        link.setAttribute('aria-haspopup', 'dialog');
        link.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            opener = link;
            fullImage.src = link.href;
            fullImage.alt = link.querySelector('img').alt;
            caption.textContent = link.dataset.imageCaption;
            dialog.showModal();
        });
    });
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => opener?.focus());
}
