import Alpine from 'alpinejs';

window.Alpine = Alpine;

/* ── Scroll Reveal (IntersectionObserver) ── */
function initScrollReveal() {
    const els = document.querySelectorAll('[data-reveal]');
    if (!els.length) return;

    const ob = new IntersectionObserver(
        (entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) {
                    const delay = parseInt(e.target.dataset.revealDelay || '0', 10);
                    setTimeout(() => e.target.classList.add('revealed'), delay);
                    ob.unobserve(e.target);
                }
            });
        },
        { threshold: 0.15 },
    );

    els.forEach((el) => ob.observe(el));
}

/* ── Animation Preview Carousel ── */
Alpine.data('animationPreview', () => ({
    // 從後端注入的 PHP 資料讀取，每項加上 iframeSrc: null（懶載入）
    items: (window.__animationStyles__ || []).map((s) => ({ ...s, iframeSrc: null })),
    active: 0,
    paused: false,
    timer: null,
    touchStartX: 0,

    get current() {
        return this.items[this.active];
    },

    loadIframe(i) {
        if (!this.items[i].iframeSrc) {
            this.items[i].iframeSrc = '/demo/lottery/' + this.items[i].slug;
        }
    },

    switchTo(i) {
        this.loadIframe(i);
        this.active = i;
        this.restartTimer();
    },

    next() {
        const i = (this.active + 1) % this.items.length;
        this.loadIframe(i);
        this.active = i;
    },

    prev() {
        const i = (this.active - 1 + this.items.length) % this.items.length;
        this.loadIframe(i);
        this.active = i;
    },

    restartTimer() {
        clearInterval(this.timer);
        this.timer = setInterval(() => {
            if (!this.paused) this.next();
        }, 6000);
    },

    handleTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
    },

    handleTouchEnd(e) {
        const diff = e.changedTouches[0].clientX - this.touchStartX;
        if (Math.abs(diff) > 50) {
            diff > 0 ? this.prev() : this.next();
        }
    },

    init() {
        this.restartTimer();
    },

    destroy() {
        clearInterval(this.timer);
    },
}));

/* ── FAQ Accordion ── */
Alpine.data('faqAccordion', () => ({
    openIndex: null,

    toggle(i) {
        this.openIndex = this.openIndex === i ? null : i;
    },

    isOpen(i) {
        return this.openIndex === i;
    },
}));

/* ── Smooth scroll for anchor links ── */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach((a) => {
        a.addEventListener('click', (e) => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
}

/* ── Boot ── */
document.addEventListener('DOMContentLoaded', () => {
    initScrollReveal();
    initSmoothScroll();
});

Alpine.start();
