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
    items: [
        {
            key: 'lotto_air',
            label: '樂透氣流機',
            desc: '適合正式抽獎流程，節奏穩定，彩球翻飛的經典視覺',
            img: '/images/previews/lotto_air.svg',
        },
        {
            key: 'red_packet',
            label: '紅包雨',
            desc: '適合加碼與高潮段落，滿天紅包洋溢喜慶氣氛',
            img: '/images/previews/red_packet.svg',
        },
        {
            key: 'scratch_card',
            label: '刮刮樂',
            desc: '適合逐步揭曉，刮開的瞬間提高期待感',
            img: '/images/previews/scratch_card.svg',
        },
        {
            key: 'treasure_chest',
            label: '寶箱',
            desc: '適合大獎揭曉，開箱儀式感十足',
            img: '/images/previews/treasure_chest.svg',
        },
        {
            key: 'big_treasure_chest',
            label: '大寶箱',
            desc: '適合壓軸時刻，聚焦全場的終極視覺效果',
            img: '/images/previews/big_treasure_chest.svg',
        },
    ],
    active: 0,
    paused: false,
    timer: null,
    touchStartX: 0,

    get current() {
        return this.items[this.active];
    },

    switchTo(i) {
        this.active = i;
        this.restartTimer();
    },

    next() {
        this.active = (this.active + 1) % this.items.length;
    },

    prev() {
        this.active = (this.active - 1 + this.items.length) % this.items.length;
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
