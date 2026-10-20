import './demo-metrics.js';

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
