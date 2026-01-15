const config = window.LotteryConfig;

if (config) {
    const statusEl = document.getElementById('lottery-status');
    const prizeNameEl = document.getElementById('current-prize-name');
    const prizeModeEl = document.getElementById('current-prize-mode');
    const winnerListEl = document.getElementById('winner-list');

    let state = {
        isOpen: config.isOpen,
        currentPrize: config.currentPrize,
        winners: config.winners ?? [],
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

        if (winnerListEl) {
            if (state.winners.length === 0) {
                winnerListEl.innerHTML = '<li class="px-4 py-3 text-sm text-gray-500">尚未抽出中獎者</li>';
                return;
            }

            winnerListEl.innerHTML = state.winners
                .map((winner) => {
                    const details = [winner.employee_email, winner.employee_phone].filter(Boolean).join(' · ');
                    return `
                        <li class="flex items-center justify-between px-4 py-3">
                            <span class="font-semibold">${winner.employee_name ?? ''}</span>
                            <span class="text-sm text-gray-400">${details}</span>
                        </li>
                    `;
                })
                .join('');
        }
    };

    render();

    const draw = async () => {
        if (!state.isOpen || !state.currentPrize || !config.drawUrl) {
            return;
        }

        if (draw.isDrawing) {
            return;
        }

        draw.isDrawing = true;

        try {
            const response = await fetch(config.drawUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                draw.isDrawing = false;
                return;
            }

            const data = await response.json();
            if (data?.winners) {
                state.winners = [...state.winners, ...data.winners];
                render();
            }
        } catch (error) {
            console.error(error);
        } finally {
            draw.isDrawing = false;
        }
    };

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            draw();
        }
    });

    document.addEventListener('mousedown', (event) => {
        if (event.button === 0) {
            draw();
        }
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
