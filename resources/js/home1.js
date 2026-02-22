const trackEvent = (eventName, payload = {}) => {
    if (!eventName) {
        return;
    }

    if (Array.isArray(window.dataLayer)) {
        window.dataLayer.push({
            event: eventName,
            ...payload,
        });
    }

    document.dispatchEvent(
        new CustomEvent("home1:track", {
            detail: {
                event: eventName,
                ...payload,
            },
        }),
    );
};

const initReveal = () => {
    const elements = document.querySelectorAll("[data-home1-reveal]");

    if (!elements.length) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const element = entry.target;
                const delay = Number.parseInt(element.dataset.home1Delay || "0", 10);

                window.setTimeout(() => {
                    element.classList.add("is-visible");
                }, delay);

                observer.unobserve(element);
            });
        },
        { threshold: 0.15 },
    );

    elements.forEach((element) => observer.observe(element));
};

const initTrackedClicks = () => {
    const clickables = document.querySelectorAll("[data-track]");

    clickables.forEach((element) => {
        element.addEventListener("click", () => {
            trackEvent(element.dataset.track, {
                label: element.dataset.trackLabel || "",
            });
        });
    });
};

const initPreview = () => {
    const root = document.querySelector("[data-home1-preview]");

    if (!root) {
        return;
    }

    const tabs = Array.from(root.querySelectorAll(".home1-preview-tab"));
    const stage = root.querySelector("[data-home1-preview-stage]");
    const image = root.querySelector("[data-home1-preview-image]");
    const title = root.querySelector("[data-home1-preview-title]");
    const desc = root.querySelector("[data-home1-preview-desc]");
    const scene = root.querySelector("[data-home1-preview-scene]");

    if (!tabs.length || !stage || !image || !title || !desc || !scene) {
        return;
    }

    const items = tabs.map((tab) => ({
        key: tab.textContent?.trim() || "",
        label: tab.dataset.previewLabel || "",
        description: tab.dataset.previewDesc || "",
        scene: tab.dataset.previewScene || "",
        image: tab.dataset.previewImage || "",
    }));

    let currentIndex = 0;
    let paused = false;
    let timer = null;
    let touchStartX = 0;

    const applyItem = (index, source = "click") => {
        if (!items.length) {
            return;
        }

        currentIndex = (index + items.length) % items.length;
        const item = items[currentIndex];

        tabs.forEach((tab, tabIndex) => {
            const active = tabIndex === currentIndex;
            tab.classList.toggle("is-active", active);
            tab.setAttribute("aria-selected", active ? "true" : "false");
        });

        stage.classList.add("is-switching");
        image.src = item.image;
        image.alt = `${item.label} 抽獎動畫預覽`;
        title.textContent = item.label;
        desc.textContent = item.description;
        scene.textContent = `建議場景：${item.scene}`;

        window.setTimeout(() => {
            stage.classList.remove("is-switching");
        }, 180);

        if (source !== "auto" && source !== "init") {
            trackEvent("home_preview_switch", {
                mode: source,
                preview: item.key,
            });
        }
    };

    const startTimer = () => {
        if (timer) {
            window.clearInterval(timer);
        }

        timer = window.setInterval(() => {
            if (!paused) {
                applyItem(currentIndex + 1, "auto");
            }
        }, 6000);
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener("click", () => {
            applyItem(index, "click");
            startTimer();
        });
    });

    root.addEventListener("mouseenter", () => {
        paused = true;
    });

    root.addEventListener("mouseleave", () => {
        paused = false;
    });

    root.addEventListener("focusin", () => {
        paused = true;
    });

    root.addEventListener("focusout", () => {
        paused = false;
    });

    root.addEventListener(
        "touchstart",
        (event) => {
            touchStartX = event.changedTouches[0]?.clientX || 0;
        },
        { passive: true },
    );

    root.addEventListener("touchend", (event) => {
        const touchEndX = event.changedTouches[0]?.clientX || 0;
        const distance = touchEndX - touchStartX;

        if (Math.abs(distance) < 44) {
            return;
        }

        if (distance < 0) {
            applyItem(currentIndex + 1, "swipe");
        } else {
            applyItem(currentIndex - 1, "swipe");
        }

        startTimer();
    });

    applyItem(0, "init");
    startTimer();

    window.addEventListener("beforeunload", () => {
        if (timer) {
            window.clearInterval(timer);
        }
    });
};

const initBrandCodeForm = () => {
    const form = document.querySelector("[data-home1-brand-form]");

    if (!form) {
        return;
    }

    const input = form.querySelector("[data-home1-brand-input]");
    const note = form.querySelector("[data-home1-brand-note]");

    if (!input || !note) {
        return;
    }

    const validPattern = /^[A-Za-z0-9_-]{3,20}$/;

    form.addEventListener("submit", (event) => {
        event.preventDefault();

        const value = input.value.trim().toUpperCase();
        input.value = value;

        if (!validPattern.test(value)) {
            note.textContent = "請輸入正確活動代碼：英數字、底線或連字號，長度 3-20 碼。";
            note.classList.add("is-error");
            input.focus();
            return;
        }

        note.textContent = "代碼格式：英數字、底線或連字號（3-20 碼）";
        note.classList.remove("is-error");

        trackEvent("home_brandcode_submit", {
            brand_code: value,
        });

        window.location.assign(`/${encodeURIComponent(value)}/lottery`);
    });
};

const initFaq = () => {
    const triggers = Array.from(document.querySelectorAll("[data-home1-faq-trigger]"));

    if (!triggers.length) {
        return;
    }

    const closeTrigger = (trigger) => {
        const answer = trigger.nextElementSibling;
        trigger.setAttribute("aria-expanded", "false");
        if (answer) {
            answer.hidden = true;
        }
    };

    triggers.forEach((trigger, index) => {
        closeTrigger(trigger);

        trigger.addEventListener("click", () => {
            const expanded = trigger.getAttribute("aria-expanded") === "true";

            triggers.forEach((item) => closeTrigger(item));

            if (!expanded) {
                const answer = trigger.nextElementSibling;
                trigger.setAttribute("aria-expanded", "true");
                if (answer) {
                    answer.hidden = false;
                }

                trackEvent("home_faq_expand", {
                    faq_index: index + 1,
                    question: trigger.textContent?.trim() || "",
                });
            }
        });
    });
};

document.addEventListener("DOMContentLoaded", () => {
    initReveal();
    initTrackedClicks();
    initPreview();
    initBrandCodeForm();
    initFaq();
});
