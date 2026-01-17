const rootEl = document.getElementById('lottery-root');

const parseConfig = () => {
    const raw = rootEl?.getAttribute('data-lottery-config');
    if (!raw) return null;

    try {
        const json = atob(raw);
        return JSON.parse(json);
    } catch {
        return null;
    }
};

const config = window.LotteryConfig ?? parseConfig();

if (config) {
    const statusEl = document.getElementById('lottery-status');
    const prizeNameEl = document.getElementById('current-prize-name');
    const prizeModeEl = document.getElementById('current-prize-mode');
    const winnerListEl = document.getElementById('winner-list');
    const slotDisplayEl = document.getElementById('slot-display');
    const slotSubtitleEl = document.getElementById('slot-subtitle');
    const drawButtonEl = document.getElementById('draw-button');
    const drawProgressEl = document.getElementById('draw-progress');

    let state = {
        isOpen: config.isOpen,
        currentPrize: config.currentPrize,
        winners: config.winners ?? [],
        isDrawing: false,
    };

    const modeLabel = (mode) => (mode === 'one_by_one' ? '逐一抽出' : '一次全抽');

    const render = () => {
        if (statusEl) {
            statusEl.innerHTML = `狀態：<span class="font-semibold">${state.isOpen ? '可抽獎' : '尚未開放'}</span>`;
        }

        if (prizeNameEl) {
            prizeNameEl.textContent = state.currentPrize?.name ?? '尚未設定';
        }

        if (prizeModeEl) {
            prizeModeEl.textContent = state.currentPrize ? modeLabel(state.currentPrize.drawMode) : '';
        }

        if (slotSubtitleEl) {
            if (!state.isOpen) {
                slotSubtitleEl.textContent = '目前尚未開放抽獎';
            } else if (!state.currentPrize) {
                slotSubtitleEl.textContent = '尚未設定目前獎項';
            } else if (state.isDrawing) {
                slotSubtitleEl.textContent = '抽獎進行中…';
            } else {
                slotSubtitleEl.textContent = '按 Enter 或 Space，或點擊「開始抽獎」。';
            }
        }

        if (drawButtonEl) {
            drawButtonEl.disabled = !state.isOpen || !state.currentPrize || state.isDrawing;
        }

        if (drawProgressEl) {
            drawProgressEl.textContent = state.isDrawing ? '抽獎中…' : '';
        }

        if (winnerListEl) {
            if (state.winners.length === 0) {
                winnerListEl.innerHTML = '<li class="px-4 py-3 text-sm text-slate-200/50">尚未抽出中獎者</li>';
                return;
            }

            winnerListEl.innerHTML = state.winners
                .map((winner) => {
                    const details = [winner.employee_email, winner.employee_phone].filter(Boolean).join(' · ');
                    return `
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="font-semibold">${winner.employee_name ?? ''}</span>
                            <span class="text-sm text-slate-200/70">${details}</span>
                        </li>
                    `;
                })
                .join('');
        }
    };

    render();

    const randomLabel = () => {
        const pool = ['Emp', 'Lucky', 'Winner', '抽獎', '中獎'];
        const left = pool[Math.floor(Math.random() * pool.length)];
        const right = String(Math.floor(Math.random() * 90) + 10);
        return `${left} ${right}`;
    };

    const animateFlash = (element) => {
        if (!element || !element.animate) return;

        element.animate(
            [
                { transform: 'scale(1)', filter: 'brightness(1)' },
                { transform: 'scale(1.03)', filter: 'brightness(1.35)' },
                { transform: 'scale(1)', filter: 'brightness(1)' },
            ],
            { duration: 750, easing: 'ease' }
        );
    };

    const animateListItem = (element) => {
        if (!element || !element.animate) return;

        element.animate(
            [
                { transform: 'translateY(10px)', opacity: 0 },
                { transform: 'translateY(0)', opacity: 1 },
            ],
            { duration: 520, easing: 'cubic-bezier(.2,.8,.2,1)' }
        );
    };

    const revealWithTypewriter = async (text) => {
        if (!slotDisplayEl) return;

        slotDisplayEl.textContent = '';
        for (const char of text) {
            slotDisplayEl.textContent += char;
            await new Promise((r) => setTimeout(r, 42));
        }
    };

    const revealWithScramble = async (text) => {
        if (!slotDisplayEl) return;

        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789抽獎中獎';
        const maxSteps = 18;

        for (let step = 0; step <= maxSteps; step += 1) {
            const ratio = step / maxSteps;
            const keep = Math.floor(text.length * ratio);
            const head = text.slice(0, keep);
            const tail = Array.from({ length: text.length - keep })
                .map(() => chars[Math.floor(Math.random() * chars.length)])
                .join('');

            slotDisplayEl.textContent = head + tail;
            await new Promise((r) => setTimeout(r, 36));
        }

        slotDisplayEl.textContent = text;
    };

    const revealWithFlip = async (text) => {
        if (!slotDisplayEl || !slotDisplayEl.animate) {
            if (slotDisplayEl) slotDisplayEl.textContent = text;
            return;
        }

        await slotDisplayEl.animate(
            [{ transform: 'rotateX(0deg)' }, { transform: 'rotateX(90deg)' }],
            { duration: 240, easing: 'ease-in' }
        ).finished;

        slotDisplayEl.textContent = text;

        await slotDisplayEl.animate(
            [{ transform: 'rotateX(90deg)' }, { transform: 'rotateX(0deg)' }],
            { duration: 260, easing: 'ease-out' }
        ).finished;
    };

    const runSpin = async (durationMs = 900) => {
        if (!slotDisplayEl) return;

        const startedAt = Date.now();
        while (Date.now() - startedAt < durationMs) {
            slotDisplayEl.textContent = randomLabel();
            // eslint-disable-next-line no-await-in-loop
            await new Promise((r) => setTimeout(r, 40));
        }
    };

    const testOverlayEl = document.getElementById('test-overlay');
    const testOverlayTextEl = document.getElementById('test-overlay-text');

    const runTestOverlay = async (finalText) => {
        if (!testOverlayEl || !testOverlayTextEl || !testOverlayEl.animate) {
            return;
        }

        testOverlayEl.classList.remove('hidden');
        testOverlayEl.classList.add('flex');

        testOverlayTextEl.textContent = 'DRAWING';

        const hue = testOverlayEl.animate(
            [
                { filter: 'hue-rotate(0deg) saturate(1.0)', transform: 'scale(1)' },
                { filter: 'hue-rotate(180deg) saturate(1.4)', transform: 'scale(1.02)' },
                { filter: 'hue-rotate(360deg) saturate(1.0)', transform: 'scale(1)' },
            ],
            { duration: 900, iterations: 1, easing: 'linear' }
        );

        const shake = testOverlayTextEl.animate(
            [
                { transform: 'translateX(0px) scale(1)', letterSpacing: '0.25em' },
                { transform: 'translateX(-10px) scale(1.03)', letterSpacing: '0.35em' },
                { transform: 'translateX(10px) scale(1.03)', letterSpacing: '0.35em' },
                { transform: 'translateX(-8px) scale(1.02)', letterSpacing: '0.30em' },
                { transform: 'translateX(8px) scale(1.02)', letterSpacing: '0.30em' },
                { transform: 'translateX(0px) scale(1)', letterSpacing: '0.25em' },
            ],
            { duration: 900, iterations: 1, easing: 'cubic-bezier(.2,.8,.2,1)' }
        );

        const ticker = setInterval(() => {
            testOverlayTextEl.textContent = randomLabel().replace(' ', '');
        }, 50);

        await Promise.allSettled([hue.finished, shake.finished]);
        clearInterval(ticker);

        testOverlayTextEl.textContent = finalText;

        await testOverlayTextEl.animate(
            [
                { transform: 'scale(1)', filter: 'brightness(1)' },
                { transform: 'scale(1.12)', filter: 'brightness(1.5)' },
                { transform: 'scale(1)', filter: 'brightness(1)' },
            ],
            { duration: 520, easing: 'ease' }
        ).finished;

        await new Promise((r) => setTimeout(r, 250));

        testOverlayEl.classList.add('hidden');
        testOverlayEl.classList.remove('flex');
    };

    const revealWithRoulette = async (text) => {
        if (!slotDisplayEl) return;

        const totalDuration = 1200;
        const startedAt = Date.now();
        let interval = 80;

        while (Date.now() - startedAt < totalDuration) {
            slotDisplayEl.textContent = randomLabel();
            // eslint-disable-next-line no-await-in-loop
            await new Promise((r) => setTimeout(r, interval));

            const progress = (Date.now() - startedAt) / totalDuration;
            interval = 80 + progress * 180;
        }

        slotDisplayEl.textContent = text;
    };

    const revealWinner = async (winnerName) => {
        if (!slotDisplayEl) return;

        const style = state.currentPrize?.animationStyle ?? 'slot';
        const name = winnerName || '（未知）';

        if (style === 'test') {
            await runTestOverlay(name);
            slotDisplayEl.textContent = name;
        } else if (style === 'typewriter') {
            await revealWithTypewriter(name);
        } else if (style === 'scramble') {
            await revealWithScramble(name);
        } else if (style === 'flip') {
            await revealWithFlip(name);
        } else if (style === 'roulette') {
            await revealWithRoulette(name);
        } else {
            await runSpin(950);
            slotDisplayEl.textContent = name;
        }

        animateFlash(slotDisplayEl);
    };

    const draw = async () => {
        if (!state.isOpen || !state.currentPrize || !config.drawUrl) {
            return;
        }

        if (state.isDrawing) {
            return;
        }

        state.isDrawing = true;
        render();

        try {
            const minSpin = runSpin(700);

            const response = await fetch(config.drawUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify({}),
            });

            await minSpin;

            if (!response.ok) {
                state.isDrawing = false;
                render();
                return;
            }

            const data = await response.json();
            const winners = data?.winners ?? [];

            if (winners.length === 0) {
                if (slotDisplayEl) slotDisplayEl.textContent = '沒有更多中獎者';
                animateFlash(slotDisplayEl);
                state.isDrawing = false;
                render();
                return;
            }

            for (const winner of winners) {
                // eslint-disable-next-line no-await-in-loop
                await revealWinner(winner.employee_name);

                state.winners = [...state.winners, winner];
                render();

                const lastItem = winnerListEl?.lastElementChild;
                if (lastItem) {
                    animateListItem(lastItem);
                }

                // eslint-disable-next-line no-await-in-loop
                await new Promise((r) => setTimeout(r, 220));
            }
    } catch {
        return null;
    }

    };

    const handleKeydown = (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            draw();
        }
    };

    document.addEventListener('keydown', handleKeydown);

    drawButtonEl?.addEventListener('click', () => {
        draw();
    });

    if (window.Echo && config.brandCode) {
        window.Echo.channel(`lottery.${config.brandCode}`)
            .listen('.lottery.updated', (payload) => {
                state.isOpen = payload.event?.is_lottery_open ?? state.isOpen;
                state.currentPrize = payload.current_prize
                    ? {
                        id: payload.current_prize.id,
                        name: payload.current_prize.name,
                        drawMode: payload.current_prize.draw_mode,
                        animationStyle: payload.current_prize.animation_style ?? state.currentPrize?.animationStyle,
                    }
                    : null;
                state.winners = payload.winners ?? [];
                render();
            })
            .listen('.winners.updated', (payload) => {
                if (!state.currentPrize || payload.prize_id !== state.currentPrize.id) {
                    return;
                }

                state.winners = [...state.winners, ...(payload.winners ?? [])];
                render();
            });
    }
}
