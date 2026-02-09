const initLottery = () => {
    const config = window.LotteryConfig;

    if (!config) {
        return;
    }

    const rootEl = document.getElementById('lottery-root');
    const statusEl = document.getElementById('lottery-status');
    const eventNameEl = document.getElementById('event-name');
    const winnerListEl = document.getElementById('winner-list');
    const winnersContainerEl = document.getElementById('winners-container');
    const lottoWrapEl = document.getElementById('lotto-canvas-wrap');
    const lottoCanvasEl = document.getElementById('lotto-canvas');
    const drawButtonEl = document.getElementById('draw-button');
    const drawProgressEl = document.getElementById('draw-progress');
    const stageEl = document.getElementById('lottery-stage');

    // 結果展示模式元素
    const resultModeEl = document.getElementById('result-mode');
    const resultPrizeNameEl = document.getElementById('result-prize-name');
    const resultWinnerCountEl = document.getElementById('result-winner-count');
    const resultGridEl = document.getElementById('result-winners-grid');
    const resultPageInfoEl = document.getElementById('result-page-info');

    // 獎項預覽模式元素
    const prizesPreviewModeEl = document.getElementById('prizes-preview-mode');
    const prizesPreviewListEl = document.getElementById('prizes-preview-list');
    const prizesPreviewTotalEl = document.getElementById('prizes-preview-total');
    const prizesPreviewPageInfoEl = document.getElementById('prizes-preview-page-info');
    const testModeWatermarkEl = document.getElementById('test-mode-watermark');

    let state = {
        isOpen: config.isOpen,
        isTestMode: config.isTestMode ?? false,
        currentPrize: config.currentPrize,
        winners: config.winners ?? [],
        eligibleNames: config.eligibleNames ?? [],
        isDrawing: false,
        isSwitching: false,
        isResultMode: false,
        isPrizesPreviewMode: false,
        showPrizesPreview: config.showPrizesPreview ?? false,
        allPrizes: config.allPrizes ?? [],
    };

    let lastAckPrizeId = null;

    const escapeHtml = (str) => {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    const modeLabel = (mode) => (mode === 'one_by_one' ? '逐一抽出' : '一次全抽');

    let pageIndex = 0;
    let pageTimer = null;

    const getPageSize = () => {
        if (!winnerListEl) {
            return { perPage: 0, totalPages: 0 };
        }

        const width = winnerListEl.clientWidth;
        const height = winnerListEl.clientHeight || 280;
        let columns = 1;

        if (width >= 1280) {
            columns = 4;
        } else if (width >= 1024) {
            columns = 3;
        } else if (width >= 640) {
            columns = 2;
        }

        const rowHeight = 58;
        const rows = Math.max(1, Math.floor(height / rowHeight));
        const perPage = columns * rows;
        const totalPages = perPage > 0 ? Math.ceil(state.winners.length / perPage) : 0;

        return { perPage, totalPages };
    };

    const renderWinnersPage = (resetPage = false) => {
        if (!winnerListEl) {
            return;
        }

        if (pageTimer) {
            clearInterval(pageTimer);
            pageTimer = null;
        }

        if (state.winners.length === 0) {
            winnerListEl.innerHTML = `
                <li class="rounded-2xl border border-white/10 bg-black/20 px-4 py-6 text-sm text-slate-200/50">尚未抽出中獎者</li>
            `;
            if (drawProgressEl) {
                drawProgressEl.textContent = '';
            }
            return;
        }

        const { perPage, totalPages } = getPageSize();
        if (perPage === 0) {
            return;
        }

        if (resetPage || pageIndex >= totalPages) {
            pageIndex = 0;
        }

        const slice = state.winners.slice(pageIndex * perPage, (pageIndex + 1) * perPage);
        winnerListEl.innerHTML = slice
            .map((winner) => {
                const details = [winner.employee_email, winner.employee_phone].filter(Boolean).map(escapeHtml).join(' · ');
                return `
                    <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                        <span class="font-semibold">${escapeHtml(winner.employee_name ?? '')}</span>
                        <span class="text-sm text-slate-200/70">${details}</span>
                    </li>
                `;
            })
            .join('');

        if (drawProgressEl) {
            drawProgressEl.textContent = totalPages > 1 ? `第 ${pageIndex + 1} / ${totalPages} 頁` : '';
        }

        if (totalPages > 1) {
            pageTimer = setInterval(() => {
                pageIndex = (pageIndex + 1) % totalPages;
                renderWinnersPage();
            }, 4000);
        }
    };

    const updateTitle = (name) => {
        const title = name || config.eventName || '抽獎';

        if (eventNameEl) {
            eventNameEl.textContent = title;
        }

        document.title = `${title} - 抽獎`;
    };

    const render = () => {
        if (statusEl) {
            statusEl.innerHTML = `狀態：<span class="font-semibold">${state.isOpen ? '可抽獎' : '尚未開放'}</span>`;
        }

        // 測試模式浮水印控制
        if (testModeWatermarkEl) {
            testModeWatermarkEl.classList.toggle('hidden', !state.isTestMode);
        }

        updateTitle(state.currentPrize?.name ?? config.eventName);

        const currentStyle = state.currentPrize?.animationStyle;
        const isLotto = isLottoStyle(currentStyle);
        const isRedPacket = isRedPacketStyle(currentStyle);
        const isScratchCard = isScratchCardStyle(currentStyle);
        const isTreasureChest = isTreasureChestStyle(currentStyle);
        const isBigTreasureChest = isBigTreasureChestStyle(currentStyle);
        const isCanvas = isCanvasStyle(currentStyle);

        if (lottoWrapEl) {
            lottoWrapEl.classList.toggle('hidden', !isCanvas);
        }

        if (isLotto) {
            lottoAir.ensureReady();
        }

        if (isRedPacket) {
            redPacketRain.ensureReady();
        }

        if (isScratchCard) {
            scratchCard.ensureReady();
            // 一開始就顯示刮刮卡（不等抽獎）
            if (!state.isDrawing) {
                const remaining = Math.max(0, (state.currentPrize?.winnersCount ?? 1) - (state.winners?.length ?? 0));
                // 考慮實際可抽人數（資格限制）
                // eligibleNames 未定義時預設為 0，避免顯示錯誤的卡片數量
                const eligibleCount = state.eligibleNames?.length ?? 0;
                const actualCanDraw = Math.min(remaining, eligibleCount);
                const cardCount = state.currentPrize?.drawMode === 'one_by_one' ? Math.min(1, actualCanDraw) : Math.min(actualCanDraw, 9);
                if (cardCount > 0) {
                    scratchCard.showCards(cardCount);
                }
            }
        }

        if (isTreasureChest) {
            treasureChest.ensureReady();
            // 一開始就顯示寶箱（不等抽獎）
            if (!state.isDrawing) {
                const remaining = Math.max(0, (state.currentPrize?.winnersCount ?? 1) - (state.winners?.length ?? 0));
                // 考慮實際可抽人數（資格限制）
                // eligibleNames 未定義時預設為 0，避免顯示錯誤的寶箱數量
                const eligibleCount = state.eligibleNames?.length ?? 0;
                const actualCanDraw = Math.min(remaining, eligibleCount);
                const chestCount = state.currentPrize?.drawMode === 'one_by_one' ? Math.min(1, actualCanDraw) : Math.min(actualCanDraw, 9);
                if (chestCount > 0) {
                    treasureChest.showChests(chestCount);
                }
            }
        }

        if (isBigTreasureChest) {
            bigTreasureChest.ensureReady();
            if (!state.isDrawing) {
                bigTreasureChest.showChest();
            }
        }

        if (drawButtonEl) {
            drawButtonEl.disabled = !state.isOpen || !state.currentPrize || state.isDrawing || state.isPrizesPreviewMode || state.isSwitching;
        }

        if (drawProgressEl) {
            drawProgressEl.textContent = state.isDrawing ? '抽獎中…' : '';
        }

        if (winnersContainerEl) {
            winnersContainerEl.classList.toggle('lottery-winners-hidden', state.isDrawing);
        }
        if (!state.isDrawing) {
            renderWinnersPage(true);
        }
        // 若在 resultMode，同步更新內容
        if (state.isResultMode) {
            resultMode.renderPage();
        }
    };

    const isPrizeCompleted = () => {
        if (!state.currentPrize) return false;
        const target = state.currentPrize.winnersCount ?? 0;
        const current = state.winners?.length ?? 0;
        return target > 0 && current >= target;
    };

    const resultMode = (() => {
        let pageIndex = 0;
        let pageTimer = null;
        const perPage = 12;
        const interval = 5000;

        const show = () => {
            if (!resultModeEl) return;
            stageEl?.classList.add('hidden');
            resultModeEl.classList.remove('hidden');
            state.isResultMode = true;
            renderPage(true);
        };

        const hide = () => {
            stopTimer();
            if (!resultModeEl) return;
            resultModeEl.classList.add('hidden');
            stageEl?.classList.remove('hidden');
            state.isResultMode = false;
        };

        const renderPage = (reset = false) => {
            if (reset) pageIndex = 0;

            const total = Math.ceil(state.winners.length / perPage);
            const slice = state.winners.slice(pageIndex * perPage, (pageIndex + 1) * perPage);

            if (resultPrizeNameEl) {
                resultPrizeNameEl.textContent = state.currentPrize?.name ?? '';
            }
            if (resultWinnerCountEl) {
                resultWinnerCountEl.textContent = `共 ${state.winners.length} 位中獎者`;
            }

            if (resultGridEl) {
                resultGridEl.innerHTML = slice.map((w) => `
                    <div class="winner-card rounded-xl border border-amber-400/30 bg-amber-900/20 p-4 text-center">
                        <div class="text-xl font-bold text-white">${escapeHtml(w.employee_name ?? '')}</div>
                        <div class="text-sm text-white/60 mt-1">${escapeHtml(w.employee_email || '')}</div>
                    </div>
                `).join('');
            }

            if (resultPageInfoEl) {
                resultPageInfoEl.textContent = total > 1 ? `第 ${pageIndex + 1} / ${total} 頁` : '';
            }

            startTimer(total);
        };

        const startTimer = (total) => {
            stopTimer();
            if (total > 1) {
                pageTimer = setInterval(() => {
                    pageIndex = (pageIndex + 1) % total;
                    renderPage();
                }, interval);
            }
        };

        const stopTimer = () => {
            if (pageTimer) {
                clearInterval(pageTimer);
                pageTimer = null;
            }
        };

        return { show, hide, renderPage };
    })();

    // 獎項預覽模式（輪播）
    const prizesPreviewMode = (() => {
        const perPage = 6;
        const interval = 5000;
        let pageIndex = 0;
        let pageTimer = null;

        const show = () => {
            if (!prizesPreviewModeEl) return;
            stageEl?.classList.add('hidden');
            resultModeEl?.classList.add('hidden');
            prizesPreviewModeEl.classList.remove('hidden');
            state.isPrizesPreviewMode = true;
            state.isResultMode = false;
            renderPage(true);
        };

        const hide = () => {
            if (!prizesPreviewModeEl) return;
            prizesPreviewModeEl.classList.add('hidden');
            stageEl?.classList.remove('hidden');
            state.isPrizesPreviewMode = false;
            stopTimer();
        };

        const stopTimer = () => {
            if (pageTimer) {
                clearInterval(pageTimer);
                pageTimer = null;
            }
        };

        const startTimer = (totalPages) => {
            stopTimer();
            if (totalPages <= 1) return;
            pageTimer = setInterval(() => {
                pageIndex = (pageIndex + 1) % totalPages;
                renderPage(false);
            }, interval);
        };

        const renderPage = (reset = false) => {
            if (!prizesPreviewListEl) return;

            const prizes = state.allPrizes ?? [];
            if (prizesPreviewTotalEl) {
                prizesPreviewTotalEl.textContent = prizes.length;
            }

            const totalPages = Math.max(1, Math.ceil(prizes.length / perPage));
            if (reset) pageIndex = 0;
            if (pageIndex >= totalPages) pageIndex = 0;

            const start = pageIndex * perPage;
            const pagePrizes = prizes.slice(start, start + perPage);

            prizesPreviewListEl.innerHTML = pagePrizes.map((prize) => {
                const isComplete = prize.drawnCount >= prize.winnersCount;
                const statusClass = isComplete
                    ? 'border-green-500/40 bg-green-900/20'
                    : 'border-amber-400/30 bg-amber-900/20';
                const statusText = isComplete
                    ? '<span class="text-green-400">已抽完</span>'
                    : `<span class="text-amber-400">待抽</span>`;
                return `
                    <div class="prize-card rounded-xl border ${statusClass} p-5">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xl font-bold text-white">${escapeHtml(prize.name)}</h3>
                            ${statusText}
                        </div>
                        <div class="text-white/70">
                            名額：${prize.winnersCount} 位
                        </div>
                        <div class="text-white/50 text-sm mt-1">
                            已抽出：${prize.drawnCount} / ${prize.winnersCount}
                        </div>
                    </div>
                `;
            }).join('');

            if (prizesPreviewPageInfoEl) {
                prizesPreviewPageInfoEl.textContent = totalPages > 1
                    ? `第 ${pageIndex + 1} / ${totalPages} 頁`
                    : '';
            }

            if (reset) startTimer(totalPages);
        };

        return { show, hide, render: renderPage };
    })();

    // 初始化獎項預覽頁面的 QR Code
    const winnersQrcodeEl = document.getElementById('winners-qrcode');
    if (winnersQrcodeEl && config.winnersUrl) {
        const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=128x128&data=' + encodeURIComponent(config.winnersUrl);
        winnersQrcodeEl.src = qrApiUrl;
    }

    // 判斷是否應顯示獎項預覽
    const shouldShowPrizesPreview = () => {
        // 後台手動開啟
        if (state.showPrizesPreview) return true;
        // 沒有當前獎項時自動顯示（但需要有獎項資料）
        if (!state.currentPrize && state.allPrizes?.length > 0) return true;
        return false;
    };

    let drawAudio = null;
    let drawAudioUrl = null;
    let lottoTimerId = null;
    let lottoTimerStart = 0;
    let lottoTimerLabel = '';

    const startDrawAudio = () => {
        const url = state.currentPrize?.musicUrl;
        if (!url) return;

        if (!drawAudio || drawAudioUrl !== url) {
            if (drawAudio) {
                drawAudio.pause();
            }

            drawAudio = new Audio(url);
            drawAudio.loop = true;
            drawAudio.preload = 'auto';
            drawAudioUrl = url;
        }

        drawAudio.currentTime = 0;
        drawAudio.play().catch(() => {});
    };

    const stopDrawAudio = () => {
        if (!drawAudio) return;

        drawAudio.pause();
        drawAudio.currentTime = 0;
    };

    const sfx = (() => {
        // 音效設定由後台獎項設定控制，讀取 state.currentPrize?.soundEnabled
        const AudioContextRef = window.AudioContext || window.webkitAudioContext;
        const noop = () => {};
        const noopLooped = () => ({ stop: noop });

        // 檢查音效是否啟用（讀取獎項設定）
        const isEnabled = () => state.currentPrize?.soundEnabled ?? true;

        if (!AudioContextRef) {
            return {
                isEnabled,
                playChestOpen: noop,
                playBallPick: noop,
                playMachineStop: noop,
                playScratch: noopLooped,
                playReveal: noop,
                playSlotTick: noop,
                playCoinDrop: noop,
                playVictory: noop,
                playPaperTear: noop,
                playBallRumble: noopLooped,
                playButtonClick: noop,
                playError: noop,
                playWhoosh: noop,
                playDrumRoll: noopLooped,
            };
        }

        let context = null;
        let noiseBuffer = null;

        const getContext = () => {
            if (!context) {
                context = new AudioContextRef();
            }
            if (context.state === 'suspended') {
                context.resume().catch(() => {});
            }
            return context;
        };

        // 產生白噪音 buffer（用於刮卡、撕紙等音效）
        const getNoiseBuffer = () => {
            const ctx = getContext();
            if (!noiseBuffer) {
                const bufferSize = ctx.sampleRate * 2;
                noiseBuffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                const data = noiseBuffer.getChannelData(0);
                for (let i = 0; i < bufferSize; i++) {
                    data[i] = Math.random() * 2 - 1;
                }
            }
            return noiseBuffer;
        };

        // 寶箱開啟音效（原有）
        const playChestOpen = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playChestOpen: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.2, now);
            master.connect(ctx.destination);

            const thump = ctx.createOscillator();
            const thumpGain = ctx.createGain();
            thump.type = 'triangle';
            thump.frequency.setValueAtTime(140, now);
            thump.frequency.exponentialRampToValueAtTime(70, now + 0.18);
            thumpGain.gain.setValueAtTime(0.0001, now);
            thumpGain.gain.exponentialRampToValueAtTime(0.7, now + 0.02);
            thumpGain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
            thump.connect(thumpGain);
            thumpGain.connect(master);

            const sparkle = ctx.createOscillator();
            const sparkleGain = ctx.createGain();
            sparkle.type = 'sine';
            sparkle.frequency.setValueAtTime(900, now + 0.01);
            sparkle.frequency.exponentialRampToValueAtTime(420, now + 0.13);
            sparkleGain.gain.setValueAtTime(0.0001, now);
            sparkleGain.gain.exponentialRampToValueAtTime(0.22, now + 0.015);
            sparkleGain.gain.exponentialRampToValueAtTime(0.001, now + 0.14);
            sparkle.connect(sparkleGain);
            sparkleGain.connect(master);

            thump.start(now);
            thump.stop(now + 0.22);
            sparkle.start(now + 0.01);
            sparkle.stop(now + 0.16);
            setTimeout(() => console.log('[sfx] playChestOpen: end'), 220);
        };

        // 樂透抽球音效（whoosh + 咔啦聲）
        const playBallPick = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playBallPick: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.25, now);
            master.connect(ctx.destination);

            // Whoosh 音效（頻率下降的噪音）
            const noise = ctx.createBufferSource();
            noise.buffer = getNoiseBuffer();
            const noiseFilter = ctx.createBiquadFilter();
            noiseFilter.type = 'bandpass';
            noiseFilter.frequency.setValueAtTime(2000, now);
            noiseFilter.frequency.exponentialRampToValueAtTime(400, now + 0.15);
            noiseFilter.Q.setValueAtTime(1.5, now);
            const noiseGain = ctx.createGain();
            noiseGain.gain.setValueAtTime(0.0001, now);
            noiseGain.gain.exponentialRampToValueAtTime(0.3, now + 0.03);
            noiseGain.gain.exponentialRampToValueAtTime(0.001, now + 0.18);
            noise.connect(noiseFilter);
            noiseFilter.connect(noiseGain);
            noiseGain.connect(master);

            // 咔啦聲（短促的敲擊）
            const click = ctx.createOscillator();
            const clickGain = ctx.createGain();
            click.type = 'square';
            click.frequency.setValueAtTime(800, now + 0.12);
            click.frequency.exponentialRampToValueAtTime(200, now + 0.18);
            clickGain.gain.setValueAtTime(0.0001, now + 0.12);
            clickGain.gain.exponentialRampToValueAtTime(0.4, now + 0.125);
            clickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
            click.connect(clickGain);
            clickGain.connect(master);

            noise.start(now);
            noise.stop(now + 0.2);
            click.start(now + 0.12);
            click.stop(now + 0.22);
            setTimeout(() => console.log('[sfx] playBallPick: end'), 220);
        };

        // 機器減速音效
        const playMachineStop = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playMachineStop: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.15, now);
            master.connect(ctx.destination);

            // 低頻嗡嗡聲減速
            const hum = ctx.createOscillator();
            const humGain = ctx.createGain();
            hum.type = 'sawtooth';
            hum.frequency.setValueAtTime(120, now);
            hum.frequency.exponentialRampToValueAtTime(40, now + 0.8);
            humGain.gain.setValueAtTime(0.3, now);
            humGain.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
            hum.connect(humGain);
            humGain.connect(master);

            // 機械摩擦聲
            const friction = ctx.createOscillator();
            const frictionGain = ctx.createGain();
            friction.type = 'triangle';
            friction.frequency.setValueAtTime(80, now);
            friction.frequency.exponentialRampToValueAtTime(30, now + 0.7);
            frictionGain.gain.setValueAtTime(0.2, now);
            frictionGain.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
            friction.connect(frictionGain);
            frictionGain.connect(master);

            hum.start(now);
            hum.stop(now + 1);
            friction.start(now);
            friction.stop(now + 0.9);
            setTimeout(() => console.log('[sfx] playMachineStop: end'), 1000);
        };

        // 刮卡沙沙聲（持續播放，返回控制物件）
        const playScratch = () => {
            if (!isEnabled()) return { stop: () => {} };
            console.log('[sfx] playScratch: start');
            const ctx = getContext();
            if (!ctx) return { stop: () => {} };

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.12, now);
            master.connect(ctx.destination);

            const noise = ctx.createBufferSource();
            noise.buffer = getNoiseBuffer();
            noise.loop = true;

            const filter = ctx.createBiquadFilter();
            filter.type = 'highpass';
            filter.frequency.setValueAtTime(3000, now);
            filter.Q.setValueAtTime(0.5, now);

            const lfo = ctx.createOscillator();
            const lfoGain = ctx.createGain();
            lfo.frequency.setValueAtTime(15, now);
            lfoGain.gain.setValueAtTime(0.08, now);
            lfo.connect(lfoGain);
            lfoGain.connect(master.gain);

            noise.connect(filter);
            filter.connect(master);

            noise.start(now);
            lfo.start(now);

            return {
                stop: () => {
                    console.log('[sfx] playScratch: stop called');
                    const t = ctx.currentTime;
                    master.gain.exponentialRampToValueAtTime(0.001, t + 0.1);
                    setTimeout(() => {
                        try {
                            noise.stop();
                            lfo.stop();
                            console.log('[sfx] playScratch: end');
                        } catch {
                            // already stopped
                        }
                    }, 150);
                },
            };
        };

        // 揭曉閃光音效
        const playReveal = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playReveal: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.2, now);
            master.connect(ctx.destination);

            // 閃光上升音
            const shimmer = ctx.createOscillator();
            const shimmerGain = ctx.createGain();
            shimmer.type = 'sine';
            shimmer.frequency.setValueAtTime(800, now);
            shimmer.frequency.exponentialRampToValueAtTime(1600, now + 0.15);
            shimmerGain.gain.setValueAtTime(0.0001, now);
            shimmerGain.gain.exponentialRampToValueAtTime(0.35, now + 0.05);
            shimmerGain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
            shimmer.connect(shimmerGain);
            shimmerGain.connect(master);

            // 叮聲
            const ding = ctx.createOscillator();
            const dingGain = ctx.createGain();
            ding.type = 'sine';
            ding.frequency.setValueAtTime(1200, now + 0.08);
            dingGain.gain.setValueAtTime(0.0001, now + 0.08);
            dingGain.gain.exponentialRampToValueAtTime(0.4, now + 0.1);
            dingGain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
            ding.connect(dingGain);
            dingGain.connect(master);

            shimmer.start(now);
            shimmer.stop(now + 0.35);
            ding.start(now + 0.08);
            ding.stop(now + 0.55);
            setTimeout(() => console.log('[sfx] playReveal: end'), 550);
        };

        // 轉輪滴答聲
        const playSlotTick = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playSlotTick: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.08, now);
            master.connect(ctx.destination);

            const tick = ctx.createOscillator();
            const tickGain = ctx.createGain();
            tick.type = 'square';
            tick.frequency.setValueAtTime(1800, now);
            tick.frequency.exponentialRampToValueAtTime(600, now + 0.02);
            tickGain.gain.setValueAtTime(0.5, now);
            tickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.03);
            tick.connect(tickGain);
            tickGain.connect(master);

            tick.start(now);
            tick.stop(now + 0.04);
            setTimeout(() => console.log('[sfx] playSlotTick: end'), 40);
        };

        // 金幣叮噹聲
        const playCoinDrop = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playCoinDrop: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.15, now);
            master.connect(ctx.destination);

            // 金屬敲擊聲（多個頻率疊加）
            const frequencies = [2400, 3200, 4000];
            frequencies.forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, now);
                osc.frequency.exponentialRampToValueAtTime(freq * 0.7, now + 0.15);
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.25 - i * 0.05, now + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.12 + i * 0.03);
                osc.connect(gain);
                gain.connect(master);
                osc.start(now);
                osc.stop(now + 0.2);
            });

            // 彈跳聲
            const bounce = ctx.createOscillator();
            const bounceGain = ctx.createGain();
            bounce.type = 'triangle';
            bounce.frequency.setValueAtTime(1200, now + 0.08);
            bounce.frequency.exponentialRampToValueAtTime(800, now + 0.12);
            bounceGain.gain.setValueAtTime(0.0001, now + 0.08);
            bounceGain.gain.exponentialRampToValueAtTime(0.15, now + 0.09);
            bounceGain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
            bounce.connect(bounceGain);
            bounceGain.connect(master);
            bounce.start(now + 0.08);
            bounce.stop(now + 0.18);
            setTimeout(() => console.log('[sfx] playCoinDrop: end'), 200);
        };

        // 勝利音效（和弦上升）
        const playVictory = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playVictory: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.18, now);
            master.connect(ctx.destination);

            // C-E-G 和弦依序播放（琶音）
            const notes = [523.25, 659.25, 783.99, 1046.5]; // C5, E5, G5, C6
            notes.forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, now + i * 0.1);

                gain.gain.setValueAtTime(0.0001, now + i * 0.1);
                gain.gain.exponentialRampToValueAtTime(0.3, now + i * 0.1 + 0.05);
                gain.gain.exponentialRampToValueAtTime(0.15, now + i * 0.1 + 0.3);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.8);

                osc.connect(gain);
                gain.connect(master);
                osc.start(now + i * 0.1);
                osc.stop(now + 1);
            });

            // 閃亮層
            const shimmer = ctx.createOscillator();
            const shimmerGain = ctx.createGain();
            shimmer.type = 'sine';
            shimmer.frequency.setValueAtTime(2093, now + 0.35); // C7
            shimmerGain.gain.setValueAtTime(0.0001, now + 0.35);
            shimmerGain.gain.exponentialRampToValueAtTime(0.2, now + 0.4);
            shimmerGain.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
            shimmer.connect(shimmerGain);
            shimmerGain.connect(master);
            shimmer.start(now + 0.35);
            shimmer.stop(now + 1);
            setTimeout(() => console.log('[sfx] playVictory: end'), 1000);
        };

        // 紅包撕開音效
        const playPaperTear = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playPaperTear: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.2, now);
            master.connect(ctx.destination);

            // 撕紙噪音
            const noise = ctx.createBufferSource();
            noise.buffer = getNoiseBuffer();
            const noiseFilter = ctx.createBiquadFilter();
            noiseFilter.type = 'bandpass';
            noiseFilter.frequency.setValueAtTime(4000, now);
            noiseFilter.frequency.exponentialRampToValueAtTime(1500, now + 0.2);
            noiseFilter.Q.setValueAtTime(2, now);
            const noiseGain = ctx.createGain();
            noiseGain.gain.setValueAtTime(0.0001, now);
            noiseGain.gain.exponentialRampToValueAtTime(0.4, now + 0.02);
            noiseGain.gain.setValueAtTime(0.35, now + 0.08);
            noiseGain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
            noise.connect(noiseFilter);
            noiseFilter.connect(noiseGain);
            noiseGain.connect(master);

            // 撕裂的爆破感
            const pop = ctx.createOscillator();
            const popGain = ctx.createGain();
            pop.type = 'sawtooth';
            pop.frequency.setValueAtTime(300, now);
            pop.frequency.exponentialRampToValueAtTime(80, now + 0.1);
            popGain.gain.setValueAtTime(0.0001, now);
            popGain.gain.exponentialRampToValueAtTime(0.25, now + 0.01);
            popGain.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
            pop.connect(popGain);
            popGain.connect(master);

            noise.start(now);
            noise.stop(now + 0.3);
            pop.start(now);
            pop.stop(now + 0.15);
            setTimeout(() => console.log('[sfx] playPaperTear: end'), 300);
        };

        // 樂透球滾動聲（持續播放）
        const playBallRumble = () => {
            if (!isEnabled()) return { stop: () => {} };
            console.log('[sfx] playBallRumble: start');
            const ctx = getContext();
            if (!ctx) return { stop: () => {} };

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.1, now);
            master.connect(ctx.destination);

            // 低頻滾動聲
            const rumble = ctx.createOscillator();
            const rumbleGain = ctx.createGain();
            rumble.type = 'triangle';
            rumble.frequency.setValueAtTime(60, now);
            rumbleGain.gain.setValueAtTime(0.3, now);
            rumble.connect(rumbleGain);
            rumbleGain.connect(master);

            // 碰撞噪音
            const noise = ctx.createBufferSource();
            noise.buffer = getNoiseBuffer();
            noise.loop = true;
            const noiseFilter = ctx.createBiquadFilter();
            noiseFilter.type = 'lowpass';
            noiseFilter.frequency.setValueAtTime(800, now);
            const noiseGain = ctx.createGain();
            noiseGain.gain.setValueAtTime(0.15, now);
            noise.connect(noiseFilter);
            noiseFilter.connect(noiseGain);
            noiseGain.connect(master);

            // LFO 調製頻率模擬滾動
            const lfo = ctx.createOscillator();
            const lfoGain = ctx.createGain();
            lfo.frequency.setValueAtTime(8, now);
            lfoGain.gain.setValueAtTime(20, now);
            lfo.connect(lfoGain);
            lfoGain.connect(rumble.frequency);

            rumble.start(now);
            noise.start(now);
            lfo.start(now);

            return {
                stop: () => {
                    console.log('[sfx] playBallRumble: stop called');
                    const t = ctx.currentTime;
                    master.gain.exponentialRampToValueAtTime(0.001, t + 0.3);
                    setTimeout(() => {
                        try {
                            rumble.stop();
                            noise.stop();
                            lfo.stop();
                            console.log('[sfx] playBallRumble: end');
                        } catch {
                            // already stopped
                        }
                    }, 350);
                },
            };
        };

        // 按鈕點擊確認音
        const playButtonClick = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playButtonClick: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.12, now);
            master.connect(ctx.destination);

            // 短促的點擊聲
            const click = ctx.createOscillator();
            const clickGain = ctx.createGain();
            click.type = 'sine';
            click.frequency.setValueAtTime(1000, now);
            click.frequency.exponentialRampToValueAtTime(600, now + 0.05);
            clickGain.gain.setValueAtTime(0.4, now);
            clickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
            click.connect(clickGain);
            clickGain.connect(master);

            click.start(now);
            click.stop(now + 0.1);
            setTimeout(() => console.log('[sfx] playButtonClick: end'), 100);
        };

        // 錯誤/無法操作提示音
        const playError = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playError: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.15, now);
            master.connect(ctx.destination);

            // 低沉的錯誤音（兩個下降音）
            [0, 0.12].forEach((delay) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(400, now + delay);
                osc.frequency.exponentialRampToValueAtTime(200, now + delay + 0.1);
                gain.gain.setValueAtTime(0.0001, now + delay);
                gain.gain.exponentialRampToValueAtTime(0.3, now + delay + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, now + delay + 0.12);
                osc.connect(gain);
                gain.connect(master);
                osc.start(now + delay);
                osc.stop(now + delay + 0.15);
            });
            setTimeout(() => console.log('[sfx] playError: end'), 270);
        };

        // 通用過渡音效（Whoosh）
        const playWhoosh = () => {
            if (!isEnabled()) return;
            console.log('[sfx] playWhoosh: start');
            const ctx = getContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.15, now);
            master.connect(ctx.destination);

            const noise = ctx.createBufferSource();
            noise.buffer = getNoiseBuffer();
            const filter = ctx.createBiquadFilter();
            filter.type = 'bandpass';
            filter.frequency.setValueAtTime(3000, now);
            filter.frequency.exponentialRampToValueAtTime(500, now + 0.25);
            filter.Q.setValueAtTime(1, now);
            const noiseGain = ctx.createGain();
            noiseGain.gain.setValueAtTime(0.0001, now);
            noiseGain.gain.exponentialRampToValueAtTime(0.35, now + 0.05);
            noiseGain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
            noise.connect(filter);
            filter.connect(noiseGain);
            noiseGain.connect(master);

            noise.start(now);
            noise.stop(now + 0.35);
            setTimeout(() => console.log('[sfx] playWhoosh: end'), 350);
        };

        // 鼓聲滾動（持續播放，用於緊張氛圍）
        const playDrumRoll = () => {
            if (!isEnabled()) return { stop: () => {} };
            console.log('[sfx] playDrumRoll: start');
            const ctx = getContext();
            if (!ctx) return { stop: () => {} };

            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.gain.setValueAtTime(0.12, now);
            master.connect(ctx.destination);

            // 快速重複的鼓點
            const noise = ctx.createBufferSource();
            noise.buffer = getNoiseBuffer();
            noise.loop = true;
            const filter = ctx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(1200, now);
            const noiseGain = ctx.createGain();
            noiseGain.gain.setValueAtTime(0.25, now);
            noise.connect(filter);
            filter.connect(noiseGain);
            noiseGain.connect(master);

            // LFO 調製音量產生滾動感
            const lfo = ctx.createOscillator();
            const lfoGain = ctx.createGain();
            lfo.frequency.setValueAtTime(20, now);
            lfoGain.gain.setValueAtTime(0.15, now);
            lfo.connect(lfoGain);
            lfoGain.connect(noiseGain.gain);

            noise.start(now);
            lfo.start(now);

            return {
                stop: () => {
                    console.log('[sfx] playDrumRoll: stop called');
                    const t = ctx.currentTime;
                    master.gain.exponentialRampToValueAtTime(0.001, t + 0.2);
                    setTimeout(() => {
                        try {
                            noise.stop();
                            lfo.stop();
                            console.log('[sfx] playDrumRoll: end');
                        } catch {
                            // already stopped
                        }
                    }, 250);
                },
            };
        };

        return {
            isEnabled,
            playChestOpen,
            playBallPick,
            playMachineStop,
            playScratch,
            playReveal,
            playSlotTick,
            playCoinDrop,
            playVictory,
            playPaperTear,
            playBallRumble,
            playButtonClick,
            playError,
            playWhoosh,
            playDrumRoll,
        };
    })();

    const randomLabel = () => {
        const pool = ['Emp', 'Lucky', 'Winner', '抽獎', '中獎'];
        const left = pool[Math.floor(Math.random() * pool.length)];
        const right = String(Math.floor(Math.random() * 90) + 10);
        return `${left} ${right}`;
    };

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
    const lerp = (start, end, amount) => start + (end - start) * amount;
    const rand = (min, max) => min + Math.random() * (max - min);
    const isLottoAirStyle = (style) => style === 'lotto_air';
    const isRedPacketStyle = (style) => style === 'red_packet';
    const isScratchCardStyle = (style) => style === 'scratch_card';
    const isTreasureChestStyle = (style) => style === 'treasure_chest';
    const isBigTreasureChestStyle = (style) => style === 'big_treasure_chest';
    const isCanvasStyle = (style) => isLottoAirStyle(style)
        || isRedPacketStyle(style)
        || isScratchCardStyle(style)
        || isTreasureChestStyle(style)
        || isBigTreasureChestStyle(style);
    const isLottoStyle = (style) => isLottoAirStyle(style);
    const mapRange = (value, min, max) => min + (max - min) * ((value - 1) / 9);
    const TAU = Math.PI * 2;

    const hash2 = (i, j) => {
        let value = (i * 374761393 + j * 668265263) | 0;
        value = (value ^ (value >> 13)) | 0;
        value = (value * 1274126177) | 0;
        return ((value ^ (value >> 16)) >>> 0) / 4294967296;
    };

    const smoothstep = (t) => t * t * (3 - 2 * t);

    const noise2 = (x, y) => {
        const xi = Math.floor(x);
        const yi = Math.floor(y);
        const xf = x - xi;
        const yf = y - yi;
        const u = smoothstep(xf);
        const v = smoothstep(yf);
        const a = hash2(xi, yi);
        const b = hash2(xi + 1, yi);
        const c = hash2(xi, yi + 1);
        const d = hash2(xi + 1, yi + 1);
        const x1 = lerp(a, b, u);
        const x2 = lerp(c, d, u);
        return lerp(x1, x2, v);
    };

    const fbm = (x, y) => {
        let total = 0;
        let amp = 0.55;
        let freq = 1;
        for (let i = 0; i < 3; i += 1) {
            total += amp * noise2(x * freq, y * freq);
            amp *= 0.5;
            freq *= 2;
        }
        return clamp(total, 0, 1);
    };

    const shuffle = (arr) => {
        const copy = [...arr];
        for (let index = copy.length - 1; index > 0; index -= 1) {
            const swap = Math.floor(Math.random() * (index + 1));
            [copy[index], copy[swap]] = [copy[swap], copy[index]];
        }
        return copy;
    };

    const lottoAirConfig = {
        count: 60,
        turb: 0.78,
        swirl: 0.72,
        grav: 0.52,
    };

    const lottoAir = (() => {
        const airState = {
            running: false,
            paused: false,
            mode: 'idle',
            t: 0,
            pickCooldown: 0,
            fieldT: 0,
            slowFactor: 1,
            slowStop: false,
            holdSeconds: 5,
            initialPendingCount: 1,
            balls: [],
            pending: [],
            picked: [],
            waiters: [],
            finishPauseId: null,
            drum: { x: 0, y: 0, r: 0 },
            chute: { x: 0, y: 0, w: 0, h: 0, active: false },
            tray: { x: 0, y: 0, w: 0, h: 0 },
            trayPage: 0,
            trayPageTimer: null,
            ctx: null,
            dpr: 1,
            last: 0,
            rafId: null,
        };

        const particles = [];
        const shake = { power: 0 };

        const buildNamePool = () => {
            const base = (state.eligibleNames?.length ? state.eligibleNames : [])
                .concat(state.winners.map((winner) => winner.employee_name).filter(Boolean))
                .filter(Boolean);
            const unique = Array.from(new Set(base));
            if (unique.length === 0) {
                return Array.from({ length: lottoAirConfig.count }, (_, index) => `Ball ${index + 1}`);
            }
            const shuffled = shuffle(unique);
            if (shuffled.length >= lottoAirConfig.count) {
                return shuffled.slice(0, lottoAirConfig.count);
            }
            const names = [];
            for (let i = 0; i < lottoAirConfig.count; i += 1) {
                names.push(shuffled[i % shuffled.length]);
            }
            return names;
        };

        const shortLabel = (name) => {
            if (!name) return '??';
            const chars = Array.from(name);
            if (chars.length <= 4) return name;
            return `${chars.slice(0, 4).join('')}…`;
        };

        const addShake = (value) => {
            shake.power = Math.max(shake.power, value);
        };

        const syncCountFromEligible = (forceSync = false) => {
            const eligibleCount = state.eligibleNames?.length ?? 0;
            // 只有在以下情況同步：
            // 1. 強制同步 (forceSync)
            // 2. 球還沒初始化 (balls.length === 0)
            // 3. 當前沒有已抽出的球 (沒有 out 的球)
            // 避免 one_by_one 模式連續抽獎時因 eligibleNames 減少而重置已抽出的球
            const hasOutBalls = airState.balls.some((b) => b.out);
            if (eligibleCount > 0 && (forceSync || !airState.balls.length || !hasOutBalls)) {
                lottoAirConfig.count = eligibleCount;
            }
        };

        const applyShake = () => {
            if (shake.power <= 0) return;
            const offset = shake.power;
            shake.power = Math.max(0, shake.power - 10);
            airState.ctx.translate(rand(-offset, offset) * 0.12, rand(-offset, offset) * 0.12);
        };

        const burst = (x, y, count = 80, power = 300) => {
            for (let i = 0; i < count; i += 1) {
                const angle = rand(0, TAU);
                const speed = power * rand(0.25, 1);
                particles.push({
                    x,
                    y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed,
                    life: rand(0.45, 0.95),
                    t: 0,
                    r: rand(1.4, 4.1),
                    hue: rand(38, 62),
                    drag: rand(0.9, 0.985),
                });
            }
        };

        const updateParticles = (dt) => {
            for (let i = particles.length - 1; i >= 0; i -= 1) {
                const particle = particles[i];
                particle.t += dt;
                if (particle.t >= particle.life) {
                    particles.splice(i, 1);
                    continue;
                }
                particle.vy += 520 * dt;
                particle.vx *= Math.pow(particle.drag, dt * 60);
                particle.vy *= Math.pow(particle.drag, dt * 60);
                particle.x += particle.vx * dt;
                particle.y += particle.vy * dt;
            }
        };

        const drawParticles = () => {
            const { ctx } = airState;
            if (!ctx || !particles.length) return;
            ctx.save();
            ctx.globalCompositeOperation = 'lighter';
            particles.forEach((particle) => {
                const k = 1 - particle.t / particle.life;
                ctx.globalAlpha = clamp(k, 0, 1) * 0.95;
                ctx.fillStyle = `hsl(${particle.hue} 95% 60%)`;
                ctx.beginPath();
                ctx.arc(particle.x, particle.y, particle.r * lerp(0.9, 2.0, k), 0, TAU);
                ctx.fill();
            });
            ctx.restore();
            ctx.globalAlpha = 1;
        };

        const resizeCanvas = () => {
            if (!lottoCanvasEl) return false;
            const wrapperRect = lottoWrapEl?.getBoundingClientRect();
            const rect = wrapperRect && wrapperRect.width && wrapperRect.height
                ? wrapperRect
                : lottoCanvasEl.getBoundingClientRect();
            if (!rect.width || !rect.height) return false;

            airState.dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
            lottoCanvasEl.width = Math.max(1, Math.floor(rect.width * airState.dpr));
            lottoCanvasEl.height = Math.max(1, Math.floor(rect.height * airState.dpr));
            airState.ctx = lottoCanvasEl.getContext('2d');
            if (airState.ctx) {
                airState.ctx.setTransform(airState.dpr, 0, 0, airState.dpr, 0, 0);
            }

            const baseR = Math.min(rect.width * 0.5, rect.height * 0.45);
            const drumX = rect.width * 0.42;
            const drumY = rect.height * 0.5;
            airState.drum = { x: drumX, y: drumY, r: baseR };
            airState.chute = {
                x: drumX + baseR * 0.62,
                y: rect.height * 0.12,
                w: baseR * 0.44,
                h: baseR * 0.3,
                active: false,
            };
            // 收納盤在右側，往中間靠，球更大
            const trayW = 100;
            const trayH = rect.height * 0.85;
            airState.tray = {
                x: rect.width - trayW - 40,
                y: rect.height * 0.075,
                w: trayW,
                h: trayH,
                ballSize: 80,
                maxVisible: 5,
            };
            return true;
        };

        const startTrayCarousel = () => {
            const outBalls = airState.balls.filter((b) => b.out);
            const maxVisible = airState.tray.maxVisible || 5;
            if (outBalls.length <= maxVisible) {
                airState.trayPage = 0;
                return;
            }
            if (airState.trayPageTimer) return;
            airState.trayPageTimer = setInterval(() => {
                const currentOutBalls = airState.balls.filter((b) => b.out);
                const totalPages = Math.ceil(currentOutBalls.length / maxVisible);
                if (totalPages <= 1) {
                    airState.trayPage = 0;
                    return;
                }
                airState.trayPage = (airState.trayPage + 1) % totalPages;
            }, 3000);
        };

        const stopTrayCarousel = () => {
            if (airState.trayPageTimer) {
                clearInterval(airState.trayPageTimer);
                airState.trayPageTimer = null;
            }
            airState.trayPage = 0;
        };

        const reset = () => {
            particles.length = 0;
            airState.balls.length = 0;
            airState.picked.length = 0;
            airState.pending.length = 0;
            airState.waiters.length = 0;
            stopTrayCarousel();
            if (airState.finishPauseId) {
                clearTimeout(airState.finishPauseId);
                airState.finishPauseId = null;
            }
            airState.mode = 'idle';
            airState.t = 0;
            airState.pickCooldown = 0;
            airState.fieldT = 0;
            airState.slowFactor = 1;
            airState.slowStop = false;
            airState.paused = false;

            syncCountFromEligible();

            if (!resizeCanvas()) return;
            const names = buildNamePool();
            const count = Math.max(1, Math.min(lottoAirConfig.count, names.length || lottoAirConfig.count));
            const baseR = Math.min(lottoCanvasEl.getBoundingClientRect().width, lottoCanvasEl.getBoundingClientRect().height);
            const radius = clamp(Math.floor(baseR * 0.018), 10, 16);
            for (let i = 0; i < count; i += 1) {
                const angle = rand(0, TAU);
                const spread = rand(0, airState.drum.r - radius - 10);
                const name = names[i % names.length];
            airState.balls.push({
                name,
                label: shortLabel(name),
                x: airState.drum.x + Math.cos(angle) * spread,
                y: airState.drum.y + Math.sin(angle) * spread,
                vx: rand(-80, 80),
                vy: rand(-80, 80),
                r: radius,
                hue: (i * 19) % 360,
                spin: rand(-8, 8),
                jitter: rand(0.6, 1.4),
                restitution: rand(0.82, 0.94),
                friction: rand(0.965, 0.988),
                mass: rand(0.85, 1.15),
                grabbed: false,
                out: false,
                phase: rand(0, TAU),
                onOut: null,
                forceOutAt: 0,
            });
        }
        };

        const confine = (ball) => {
            const { x: cx, y: cy, r } = airState.drum;
            const dx = ball.x - cx;
            const dy = ball.y - cy;
            const dist = Math.hypot(dx, dy);
            const limit = r - ball.r - 2;
            if (dist > limit) {
                const nx = dx / dist;
                const ny = dy / dist;
                ball.x = cx + nx * limit;
                ball.y = cy + ny * limit;
                const vn = ball.vx * nx + ball.vy * ny;
                ball.vx -= (1 + ball.restitution) * vn * nx;
                ball.vy -= (1 + ball.restitution) * vn * ny;
                const tx = -ny;
                const ty = nx;
                const kick = rand(-90, 90) * ball.jitter;
                ball.vx += tx * kick * 0.02;
                ball.vy += ty * kick * 0.02;
            }
        };

        const collide = (a, b) => {
            const dx = b.x - a.x;
            const dy = b.y - a.y;
            const dist = Math.hypot(dx, dy);
            const min = a.r + b.r;
            if (dist === 0 || dist >= min) return;

            const nx = dx / dist;
            const ny = dy / dist;
            const overlap = min - dist;
            const ma = a.mass;
            const mb = b.mass;
            const sum = ma + mb;
            a.x -= nx * overlap * (mb / sum);
            a.y -= ny * overlap * (mb / sum);
            b.x += nx * overlap * (ma / sum);
            b.y += ny * overlap * (ma / sum);
            const rvx = b.vx - a.vx;
            const rvy = b.vy - a.vy;
            const vn = rvx * nx + rvy * ny;
            if (vn > 0) return;
            const e = Math.min(a.restitution, b.restitution);
            const j = -(1 + e) * vn / (1 / ma + 1 / mb);
            const ix = j * nx;
            const iy = j * ny;
            a.vx -= ix / ma;
            a.vy -= iy / ma;
            b.vx += ix / mb;
            b.vy += iy / mb;
            const tx = -ny;
            const ty = nx;
            const vt = rvx * tx + rvy * ty;
            const mu = 0.015 + 0.02 * Math.abs(vt) / 400;
            const jt = clamp(-vt * mu, -140, 140);
            a.vx -= jt * tx / ma;
            a.vy -= jt * ty / ma;
            b.vx += jt * tx / mb;
            b.vy += jt * ty / mb;
            a.spin -= jt * 0.004;
            b.spin += jt * 0.004;
            if (Math.abs(vn) > 180 && Math.random() < 0.25) {
                burst((a.x + b.x) / 2, (a.y + b.y) / 2, 5, 140);
            }
        };

        const pickBall = (winnerName) => {
            const candidates = airState.balls.filter((ball) => !ball.grabbed && !ball.out);
            if (!candidates.length) {
                const waiter = airState.waiters.shift();
                waiter?.();
                return;
            }
            let target = candidates.find((ball) => ball.name === winnerName);
            if (!target) {
                // 找不到匹配的球，使用第一個可用的球並強制更新名字
                target = candidates[0];
                console.warn(`[lottery] pickBall: no ball named "${winnerName}", using fallback and renaming`);
                target.name = winnerName;
                target.label = shortLabel(winnerName);
            }
            target.grabbed = true;
            target.onOut = () => {
                const waiter = airState.waiters.shift();
                waiter?.();
            };
            target.forceOutAt = performance.now() + 6000;
            airState.picked.push(target);
            const tx = airState.chute.x - airState.chute.w * 0.15;
            const ty = airState.chute.y + airState.chute.h * 0.55;
            const dx = tx - target.x;
            const dy = ty - target.y;
            const dist = Math.max(1, Math.hypot(dx, dy));
            target.vx += (dx / dist) * 520;
            target.vy += (dy / dist) * 520;
            burst(target.x, target.y, 90, 320);
            addShake(12);
            sfx.playBallPick();
        };

        const update = (dt) => {
            if (airState.paused) {
                updateParticles(dt);
                return;
            }

            const turb = lottoAirConfig.turb;
            const swirl = lottoAirConfig.swirl;
            const grav = lottoAirConfig.grav;

            airState.fieldT += dt * lerp(0.9, 2.4, turb);
            airState.t += dt;

            // 根據 holdSeconds 計算時間分配
            const totalSeconds = Math.max(3, airState.holdSeconds || 5);
            const pendingCount = airState.initialPendingCount || 1;
            // 混合時間：總時間的 30%，最少 1 秒
            const mixTime = Math.max(1, totalSeconds * 0.3);
            // 每顆球的間隔：剩餘時間平均分配
            const pickInterval = Math.max(0.3, (totalSeconds - mixTime) / pendingCount);

            if (airState.mode === 'mixing') {
                if (airState.t > mixTime) {
                    airState.mode = 'auto';
                    airState.t = 0;
                    airState.pickCooldown = 0.18;
                }
            } else if (airState.mode === 'auto') {
                airState.pickCooldown -= dt;
                if (airState.pickCooldown <= 0 && airState.pending.length) {
                    const next = airState.pending.shift();
                    pickBall(next?.name ?? next);
                    airState.pickCooldown = pickInterval + rand(-0.05, 0.08);
                }
                if (!airState.pending.length && airState.pickCooldown <= 0) {
                    airState.mode = 'show';
                    airState.t = 0;
                }
            }

            const { x: cx, y: cy, r } = airState.drum;
            const swirlStrength = lerp(0.6, 4.8, swirl);
            const turbStrength = lerp(50, 720, turb);
            const gravity = lerp(150, 980, grav);
            const jetAngle = airState.fieldT * 1.2 + 1.6 * Math.sin(airState.fieldT * 0.7);
            const jetX = cx + Math.cos(jetAngle) * r * 0.55;
            const jetY = cy + Math.sin(jetAngle * 0.9) * r * 0.45;

            airState.balls.forEach((ball) => {
                if (ball.out) {
                    // 已抽出的球固定在收納盤中，不需要物理模擬
                    return;
                }

                if (ball.grabbed) {
                    const intakeX = airState.chute.x - airState.chute.w * 0.08;
                    const intakeY = airState.chute.y + airState.chute.h * 0.55;
                    const k = 7.0;
                    ball.vx += (intakeX - ball.x) * k * dt;
                    ball.vy += (intakeY - ball.y) * k * dt;
                    ball.vx += rand(-1, 1) * turbStrength * 0.05 * dt;
                    ball.vy += rand(-1, 1) * turbStrength * 0.05 * dt;
                    if (ball.forceOutAt && performance.now() > ball.forceOutAt) {
                        ball.x = airState.chute.x;
                        ball.y = airState.chute.y + airState.chute.h * 0.55;
                    }
                    const inChute = (
                        ball.x > airState.chute.x - airState.chute.w * 0.46
                        && ball.x < airState.chute.x + airState.chute.w * 0.1
                        && ball.y > airState.chute.y + airState.chute.h * 0.18
                        && ball.y < airState.chute.y + airState.chute.h * 0.9
                    );
                    if (inChute) {
                        ball.out = true;
                        ball.forceOutAt = 0;
                        // 計算此球在已抽出球中的索引
                        const outBalls = airState.balls.filter((b) => b.out);
                        const outIndex = outBalls.length;
                        const tray = airState.tray;
                        const ballSize = tray.ballSize || 56;
                        // 固定位置：垂直排列在右側收納盤
                        ball.x = tray.x + tray.w / 2;
                        ball.y = tray.y + ballSize / 2 + outIndex * (ballSize + 8);
                        ball.vx = 0;
                        ball.vy = 0;
                        ball.r = ballSize / 2 - 4;
                        burst(ball.x, ball.y, 80, 260);
                        addShake(10);
                        // 啟動輪播計時器
                        startTrayCarousel();
                        if (ball.onOut) {
                            const cb = ball.onOut;
                            ball.onOut = null;
                            cb();
                        }
                        if (!airState.pending.length) {
                            const hasActive = airState.balls.some((candidate) => candidate.grabbed && !candidate.out);
                            if (!hasActive) {
                                if (airState.finishPauseId) {
                                    clearTimeout(airState.finishPauseId);
                                }
                                airState.finishPauseId = setTimeout(() => {
                                    airState.paused = true;
                                    airState.finishPauseId = null;
                                }, 1200);
                            }
                        }
                    }
                } else {
                    const dx = ball.x - cx;
                    const dy = ball.y - cy;
                    ball.vx += -dy * swirlStrength * dt;
                    ball.vy += dx * swirlStrength * dt;
                    const nx = ball.x * 0.004 + airState.fieldT * 0.22 + ball.phase * 0.02;
                    const ny = ball.y * 0.004 - airState.fieldT * 0.18 + ball.phase * 0.02;
                    const n0 = fbm(nx, ny);
                    const n1 = fbm(nx + 7.13, ny + 3.77);
                    ball.vx += (n0 - 0.5) * turbStrength * ball.jitter * dt;
                    ball.vy += (n1 - 0.5) * turbStrength * ball.jitter * dt;
                    const jdx = ball.x - jetX;
                    const jdy = ball.y - jetY;
                    const jd = Math.max(1, Math.hypot(jdx, jdy));
                    const jet = Math.exp(-(jd * jd) / (2 * (r * 0.18) * (r * 0.18)));
                    const jtx = -jdy / jd;
                    const jty = jdx / jd;
                    ball.vx += jtx * jet * turbStrength * 0.22 * dt;
                    ball.vy += jty * jet * turbStrength * 0.22 * dt;
                    ball.vy += gravity * dt;
                    if (Math.random() < 0.018 * turb * dt * 60) {
                        ball.vx += rand(-220, 220) * turb * ball.jitter * 0.25;
                        ball.vy += rand(-220, 120) * turb * ball.jitter * 0.25;
                        ball.spin += rand(-10, 10);
                    }
                }

                ball.x += ball.vx * dt;
                ball.y += ball.vy * dt;
                ball.vx *= Math.pow(ball.friction, dt * 60);
                ball.vy *= Math.pow(ball.friction, dt * 60);
                confine(ball);
            });

            if (airState.slowStop) {
                airState.slowFactor *= 0.85;
                airState.balls.forEach((ball) => {
                    ball.vx *= airState.slowFactor;
                    ball.vy *= airState.slowFactor;
                    ball.spin *= airState.slowFactor;
                });
                if (airState.slowFactor < 0.05) {
                    airState.slowStop = false;
                    airState.paused = true;
                }
            }

            for (let i = 0; i < airState.balls.length; i += 1) {
                const a = airState.balls[i];
                if (a.out) continue;
                for (let j = i + 1; j < airState.balls.length; j += 1) {
                    const b = airState.balls[j];
                    if (b.out) continue;
                    collide(a, b);
                }
            }

            updateParticles(dt);
        };

        const drawBackground = () => {
            const { ctx } = airState;
            const rect = lottoCanvasEl.getBoundingClientRect();
            const width = rect.width;
            const height = rect.height;
            const g1 = ctx.createRadialGradient(width * 0.5, height * 0.26, 80, width * 0.5, height * 0.26, Math.max(width, height));
            g1.addColorStop(0, 'rgba(120,170,255,0.11)');
            g1.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.fillStyle = g1;
            ctx.fillRect(0, 0, width, height);
            const g2 = ctx.createRadialGradient(width * 0.5, height * 0.92, 80, width * 0.5, height * 0.92, Math.max(width, height));
            g2.addColorStop(0, 'rgba(255,210,120,0.10)');
            g2.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.fillStyle = g2;
            ctx.fillRect(0, 0, width, height);
            const vg = ctx.createRadialGradient(width * 0.5, height * 0.55, Math.min(width, height) * 0.1, width * 0.5, height * 0.55, Math.max(width, height));
            vg.addColorStop(0, 'rgba(0,0,0,0)');
            vg.addColorStop(1, 'rgba(0,0,0,0.55)');
            ctx.fillStyle = vg;
            ctx.fillRect(0, 0, width, height);
        };

        const rr = (x, y, w, h, r) => {
            const { ctx } = airState;
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        };

        const drawMachine = () => {
            const { ctx } = airState;
            const { x, y, r } = airState.drum;
            const ring = ctx.createRadialGradient(x, y, r * 0.2, x, y, r * 1.25);
            ring.addColorStop(0, 'rgba(255,255,255,0.08)');
            ring.addColorStop(1, 'rgba(0,0,0,0.65)');
            ctx.fillStyle = ring;
            ctx.beginPath();
            ctx.arc(x, y, r * 1.08, 0, TAU);
            ctx.fill();
            ctx.beginPath();
            ctx.arc(x, y, r, 0, TAU);
            ctx.strokeStyle = 'rgba(255,255,255,0.18)';
            ctx.lineWidth = 2;
            ctx.stroke();
            ctx.beginPath();
            ctx.arc(x - r * 0.18, y - r * 0.2, r * 0.78, -0.2, Math.PI * 0.65);
            ctx.strokeStyle = 'rgba(200,230,255,0.10)';
            ctx.lineWidth = 10;
            ctx.stroke();
            const c = airState.chute;
            ctx.save();
            ctx.globalAlpha = 0.92;
            ctx.strokeStyle = 'rgba(255,255,255,0.16)';
            ctx.lineWidth = 2;
            rr(c.x - c.w * 0.6, c.y + c.h * 0.18, c.w * 0.55, c.h * 0.62, 18);
            ctx.stroke();
            const pipe = ctx.createLinearGradient(c.x - c.w * 0.6, 0, c.x, 0);
            pipe.addColorStop(0, 'rgba(255,255,255,0.04)');
            pipe.addColorStop(1, 'rgba(255,220,120,0.06)');
            ctx.fillStyle = pipe;
            rr(c.x - c.w * 0.6, c.y + c.h * 0.18, c.w * 0.55, c.h * 0.62, 18);
            ctx.fill();
            ctx.globalAlpha = 0.95;
            ctx.fillStyle = 'rgba(255,220,120,0.10)';
            ctx.beginPath();
            ctx.arc(c.x - c.w * 0.45, c.y + c.h * 0.5, 14, 0, TAU);
            ctx.fill();
            ctx.restore();
            // 右側收納盤
            const tray = airState.tray;
            const outBalls = airState.balls.filter((b) => b.out);
            const maxVisible = tray.maxVisible || 5;
            const totalPages = Math.ceil(outBalls.length / maxVisible);
            ctx.save();
            ctx.fillStyle = 'rgba(0,0,0,0.35)';
            ctx.strokeStyle = 'rgba(255,220,120,0.22)';
            ctx.lineWidth = 2;
            rr(tray.x - 4, tray.y - 4, tray.w + 8, tray.h + 8, 14);
            ctx.fill();
            ctx.stroke();
            // 顯示頁數指示
            if (totalPages > 1) {
                ctx.fillStyle = 'rgba(255,255,255,0.6)';
                ctx.font = '11px ui-sans-serif, system-ui';
                ctx.textAlign = 'center';
                ctx.fillText(`${airState.trayPage + 1}/${totalPages}`, tray.x + tray.w / 2, tray.y + tray.h + 18);
            }
            ctx.restore();
        };

        const drawBall = (ball, isLarge = false) => {
            const { ctx } = airState;
            ctx.save();
            ctx.globalAlpha = 0.3;
            ctx.fillStyle = 'black';
            ctx.beginPath();
            ctx.arc(ball.x + 6, ball.y + 9, ball.r * 1.02, 0, TAU);
            ctx.fill();
            ctx.globalAlpha = 1;
            const g = ctx.createRadialGradient(ball.x - ball.r * 0.35, ball.y - ball.r * 0.35, ball.r * 0.25, ball.x, ball.y, ball.r * 1.15);
            g.addColorStop(0, `hsla(${ball.hue} 95% 72% / 1)`);
            g.addColorStop(1, `hsla(${(ball.hue + 25) % 360} 95% 46% / 1)`);
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(ball.x, ball.y, ball.r, 0, TAU);
            ctx.fill();
            ctx.globalAlpha = 0.26;
            ctx.fillStyle = 'white';
            ctx.beginPath();
            ctx.arc(ball.x - ball.r * 0.32, ball.y - ball.r * 0.36, ball.r * 0.36, 0, TAU);
            ctx.fill();
            ctx.globalAlpha = 1;
            // 大球顯示完整名字（可換行），小球顯示縮寫
            if (isLarge && ball.name) {
                ctx.fillStyle = 'rgba(255,255,255,0.95)';
                ctx.beginPath();
                ctx.arc(ball.x, ball.y, ball.r * 0.72, 0, TAU);
                ctx.fill();
                ctx.fillStyle = 'rgba(0,0,0,0.88)';
                const chars = Array.from(ball.name);
                const fontSize = Math.floor(ball.r * 0.42);
                ctx.font = `bold ${fontSize}px ui-sans-serif, system-ui`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                if (chars.length <= 3) {
                    ctx.fillText(ball.name, ball.x, ball.y);
                } else {
                    // 超過 3 字換行
                    const line1 = chars.slice(0, 3).join('');
                    const line2 = chars.slice(3, 6).join('') + (chars.length > 6 ? '…' : '');
                    const lineHeight = fontSize * 1.1;
                    ctx.fillText(line1, ball.x, ball.y - lineHeight / 2);
                    ctx.fillText(line2, ball.x, ball.y + lineHeight / 2);
                }
            } else {
                ctx.fillStyle = 'rgba(255,255,255,0.92)';
                ctx.beginPath();
                ctx.arc(ball.x, ball.y, ball.r * 0.58, 0, TAU);
                ctx.fill();
                ctx.fillStyle = 'rgba(0,0,0,0.82)';
                ctx.font = `${Math.floor(ball.r * 0.85)}px ui-sans-serif, system-ui`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(ball.label, ball.x, ball.y + 0.5);
            }
            if (ball.grabbed && !ball.out) {
                ctx.globalAlpha = 0.22;
                ctx.strokeStyle = `hsla(${ball.hue} 95% 70% / 1)`;
                ctx.lineWidth = 8;
                ctx.beginPath();
                ctx.arc(ball.x, ball.y, ball.r + 6, 0, TAU);
                ctx.stroke();
                ctx.globalAlpha = 1;
            }
            ctx.restore();
        };

        const drawFrame = () => {
            const { ctx } = airState;
            if (!ctx || !lottoCanvasEl) return;
            const rect = lottoCanvasEl.getBoundingClientRect();
            // 清除畫布以顯示背景
            ctx.clearRect(0, 0, rect.width, rect.height);
            drawBackground();
            ctx.save();
            applyShake();
            drawMachine();
            const { x, y, r } = airState.drum;
            ctx.save();
            ctx.beginPath();
            ctx.arc(x, y, r - 1, 0, TAU);
            ctx.clip();
            airState.balls.forEach((ball) => {
                if (!ball.out) drawBall(ball);
            });
            ctx.restore();
            // 繪製已抽出的球（輪播顯示）
            const outBalls = airState.balls.filter((b) => b.out);
            const tray = airState.tray;
            const maxVisible = tray.maxVisible || 5;
            const ballSize = tray.ballSize || 56;
            const startIdx = airState.trayPage * maxVisible;
            const visibleBalls = outBalls.slice(startIdx, startIdx + maxVisible);
            visibleBalls.forEach((ball, idx) => {
                // 重新計算位置以適應當前頁面
                const drawX = tray.x + tray.w / 2;
                const drawY = tray.y + ballSize / 2 + idx * (ballSize + 12);
                const tempX = ball.x;
                const tempY = ball.y;
                const tempR = ball.r;
                ball.x = drawX;
                ball.y = drawY;
                ball.r = ballSize / 2 - 2;
                drawBall(ball, true);
                ball.x = tempX;
                ball.y = tempY;
                ball.r = tempR;
            });
            drawParticles();
            ctx.restore();
        };

        const tick = (now) => {
            if (!airState.running) return;
            const dt = Math.min(0.033, (now - airState.last) / 1000);
            airState.last = now;
            update(dt);
            drawFrame();
            airState.rafId = requestAnimationFrame(tick);
        };

        const ensureRunning = () => {
            if (!airState.running) {
                airState.running = true;
                airState.last = performance.now();
                airState.rafId = requestAnimationFrame(tick);
            }
        };

        const ensureReady = (forceReset = false) => {
            syncCountFromEligible();
            // 只在以下情況重置：
            // 1. forceReset（切換獎項時強制清除舊狀態）
            // 2. 沒有 canvas context
            // 3. 球還沒初始化
            // 4. 球數不對且沒有已抽出的球（避免 one_by_one 模式重置收納盤）
            const hasOutBalls = airState.balls.some((b) => b.out);
            const needsReset = forceReset
                || !airState.ctx
                || !airState.balls.length
                || (airState.balls.length !== lottoAirConfig.count && !hasOutBalls);
            if (needsReset) {
                reset();
                drawFrame();
            }
        };

        const start = () => {
            ensureReady();
            airState.paused = false;
            airState.mode = 'mixing';
            airState.t = 0;
            airState.pickCooldown = 0.25;
            airState.chute.active = true;
            airState.slowStop = false;
            airState.slowFactor = 1;
            burst(airState.drum.x, airState.chute.y, 80, 260);
            addShake(10);
            ensureRunning();
        };

        const stop = () => {
            airState.running = false;
            if (airState.rafId) {
                cancelAnimationFrame(airState.rafId);
                airState.rafId = null;
            }
        };

        const ensureCount = (count) => {
            // 如果有已抽出的球，不更新 count，避免 one_by_one 模式下的不一致
            const hasOutBalls = airState.balls.some((b) => b.out);
            if (!hasOutBalls && lottoAirConfig.count !== count) {
                lottoAirConfig.count = count;
            }
        };

        const setWinners = (names, options = {}) => {
            const list = (names ?? []).filter(Boolean);
            const { resetBalls = true, resetPicked = true } = options;
            if (lottoAirConfig.count <= 0) {
                lottoAirConfig.count = Math.max(1, list.length);
            }
            if (resetBalls) {
                reset();
            } else {
                airState.pending.length = 0;
                airState.waiters.length = 0;
            }
            airState.pending = list.map((name) => ({ name }));
            airState.initialPendingCount = list.length || 1;
            if (resetPicked) {
                airState.picked.length = 0;
            }
            airState.mode = 'mixing';
            airState.t = 0;
            if (airState.finishPauseId) {
                clearTimeout(airState.finishPauseId);
                airState.finishPauseId = null;
            }
        };

        const waitForNextPick = () => new Promise((resolve) => {
            airState.waiters.push(resolve);
        });

        const pause = () => {
            airState.paused = true;
        };

        const resume = () => {
            airState.paused = false;
            ensureRunning();
        };

        const slowStop = () => {
            airState.slowStop = true;
            sfx.playMachineStop();
        };

        const setHoldSeconds = (seconds) => {
            airState.holdSeconds = Math.max(3, seconds || 5);
        };

        return {
            ensureReady,
            start,
            startDraw: start,
            stop,
            resize: () => {
                if (resizeCanvas()) {
                    drawFrame();
                }
            },
            setWinners,
            ensureCount,
            setHoldSeconds,
            waitForNextPick,
            pauseMachine: pause,
            resumeMachine: resume,
            slowStopMachine: slowStop,
        };
    })();

    // ===========================================
    // 紅包雨動畫模組
    // ===========================================
    const redPacketRain = (() => {
        const packets = [];
        const coins = [];
        let selectedPacket = null;
        let phase = 'idle'; // idle | raining | selecting | opening | reveal
        let ctx = null;
        let dpr = 1;
        let winner = null;
        let rafId = null;
        let last = 0;
        let running = false;
        let holdSeconds = 5;
        let revealResolve = null;
        let canvasRect = { width: 0, height: 0 };
        let animateRafId = null;
        let revealTimeoutId = null;
        let spawnTimer = 0; // 持續生成紅包的計時器

        const resizeCanvas = () => {
            if (!lottoCanvasEl) return false;
            const wrapperRect = lottoWrapEl?.getBoundingClientRect();
            const rect = wrapperRect && wrapperRect.width && wrapperRect.height
                ? wrapperRect
                : lottoCanvasEl.getBoundingClientRect();
            if (!rect.width || !rect.height) return false;

            dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
            lottoCanvasEl.width = Math.max(1, Math.floor(rect.width * dpr));
            lottoCanvasEl.height = Math.max(1, Math.floor(rect.height * dpr));
            ctx = lottoCanvasEl.getContext('2d');
            if (ctx) {
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }
            canvasRect = { width: rect.width, height: rect.height };
            return true;
        };

        const roundRect = (x, y, w, h, r) => {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        };

        const createPacket = (yOffset = 0) => ({
            x: rand(60, canvasRect.width - 60),
            y: rand(-120, -50) + yOffset,
            vx: rand(-15, 15),
            vy: rand(140, 220),
            width: rand(50, 65),
            height: rand(70, 90),
            rotation: rand(-0.2, 0.2),
            wobble: rand(0, TAU),
            wobbleSpeed: rand(2.5, 4),
            scale: 1,
            selected: false,
            opening: false,
            openProgress: 0,
            name: null,
        });

        const drawPacket = (p) => {
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rotation + Math.sin(p.wobble) * 0.15);
            ctx.scale(p.scale, p.scale);

            const w = p.width;
            const h = p.height;

            // 紅包陰影
            ctx.shadowColor = 'rgba(0,0,0,0.3)';
            ctx.shadowBlur = 15;
            ctx.shadowOffsetY = 5;

            // 紅包底色
            const grad = ctx.createLinearGradient(-w / 2, -h / 2, w / 2, h / 2);
            grad.addColorStop(0, 'hsl(0, 85%, 50%)');
            grad.addColorStop(0.5, 'hsl(0, 85%, 45%)');
            grad.addColorStop(1, 'hsl(0, 85%, 38%)');
            ctx.fillStyle = grad;
            roundRect(-w / 2, -h / 2, w, h, 8);
            ctx.fill();

            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
            ctx.shadowOffsetY = 0;

            // 金色封口
            const sealGrad = ctx.createLinearGradient(0, -h / 2, 0, -h / 2 + h * 0.22);
            sealGrad.addColorStop(0, 'hsl(45, 100%, 60%)');
            sealGrad.addColorStop(0.5, 'hsl(45, 100%, 50%)');
            sealGrad.addColorStop(1, 'hsl(45, 90%, 45%)');
            ctx.fillStyle = sealGrad;
            ctx.fillRect(-w / 2 + 4, -h / 2, w - 8, h * 0.22);

            // 金色緞帶裝飾
            ctx.fillStyle = 'hsl(45, 100%, 55%)';
            ctx.fillRect(-w * 0.08, -h / 2, w * 0.16, h * 0.28);

            // 金色圓形裝飾
            const circleGrad = ctx.createRadialGradient(0, h * 0.05, 0, 0, h * 0.05, w * 0.28);
            circleGrad.addColorStop(0, 'hsl(45, 100%, 70%)');
            circleGrad.addColorStop(0.7, 'hsl(45, 100%, 50%)');
            circleGrad.addColorStop(1, 'hsl(45, 90%, 40%)');
            ctx.fillStyle = circleGrad;
            ctx.beginPath();
            ctx.arc(0, h * 0.05, w * 0.28, 0, TAU);
            ctx.fill();

            // 福字
            ctx.fillStyle = 'hsl(0, 85%, 35%)';
            ctx.font = `bold ${w * 0.32}px "Noto Serif TC", "Noto Serif SC", serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('福', 0, h * 0.06);

            // 選中時的光暈
            if (p.selected && !p.opening) {
                ctx.shadowColor = 'gold';
                ctx.shadowBlur = 35;
                ctx.strokeStyle = 'hsl(45, 100%, 60%)';
                ctx.lineWidth = 4;
                roundRect(-w / 2 - 2, -h / 2 - 2, w + 4, h + 4, 10);
                ctx.stroke();
            }

            // 打開動畫
            if (p.opening && p.openProgress > 0) {
                const progress = p.openProgress;

                // 光芒效果
                if (progress > 0.3) {
                    const rayProgress = (progress - 0.3) / 0.7;
                    ctx.save();
                    ctx.globalCompositeOperation = 'lighter';
                    for (let i = 0; i < 12; i++) {
                        const angle = (i / 12) * TAU + progress * 2;
                        const rayLength = w * 1.5 * rayProgress;
                        const rayGrad = ctx.createLinearGradient(0, 0, Math.cos(angle) * rayLength, Math.sin(angle) * rayLength);
                        rayGrad.addColorStop(0, `rgba(255, 215, 0, ${0.8 * rayProgress})`);
                        rayGrad.addColorStop(1, 'rgba(255, 215, 0, 0)');
                        ctx.strokeStyle = rayGrad;
                        ctx.lineWidth = 6;
                        ctx.beginPath();
                        ctx.moveTo(0, 0);
                        ctx.lineTo(Math.cos(angle) * rayLength, Math.sin(angle) * rayLength);
                        ctx.stroke();
                    }
                    ctx.restore();
                }

                // 封口打開（向上翻起）
                if (progress < 0.6) {
                    const flapProgress = progress / 0.6;
                    ctx.save();
                    ctx.translate(0, -h / 2 + h * 0.22);
                    ctx.rotate(-flapProgress * Math.PI * 0.6);
                    ctx.fillStyle = sealGrad;
                    ctx.fillRect(-w / 2 + 4, -h * 0.22, w - 8, h * 0.22);
                    ctx.restore();
                }
            }

            ctx.restore();
        };

        const drawCoin = (coin) => {
            ctx.save();
            ctx.translate(coin.x, coin.y);
            ctx.rotate(coin.rotation);
            ctx.scale(Math.abs(Math.cos(coin.rotation * 2)), 1);

            const size = coin.size;
            const alpha = 1 - coin.t / coin.life;

            // 金幣
            const coinGrad = ctx.createRadialGradient(0, -size * 0.2, 0, 0, 0, size);
            coinGrad.addColorStop(0, `hsla(45, 100%, 70%, ${alpha})`);
            coinGrad.addColorStop(0.5, `hsla(45, 100%, 55%, ${alpha})`);
            coinGrad.addColorStop(1, `hsla(45, 90%, 40%, ${alpha})`);
            ctx.fillStyle = coinGrad;
            ctx.beginPath();
            ctx.arc(0, 0, size / 2, 0, TAU);
            ctx.fill();

            // 金幣邊框
            ctx.strokeStyle = `hsla(45, 80%, 35%, ${alpha})`;
            ctx.lineWidth = 2;
            ctx.stroke();

            // 金幣內圈
            ctx.beginPath();
            ctx.arc(0, 0, size / 2 - 4, 0, TAU);
            ctx.strokeStyle = `hsla(45, 90%, 60%, ${alpha * 0.6})`;
            ctx.lineWidth = 1;
            ctx.stroke();

            ctx.restore();
        };

        const drawWinnerName = () => {
            if (phase !== 'reveal' || !winner) return;

            const cx = canvasRect.width / 2;
            const cy = canvasRect.height / 2;

            // 背景光暈
            ctx.save();
            ctx.globalCompositeOperation = 'lighter';
            const glowGrad = ctx.createRadialGradient(cx, cy, 0, cx, cy, 200);
            glowGrad.addColorStop(0, 'rgba(255, 215, 0, 0.3)');
            glowGrad.addColorStop(0.5, 'rgba(255, 180, 0, 0.1)');
            glowGrad.addColorStop(1, 'rgba(255, 150, 0, 0)');
            ctx.fillStyle = glowGrad;
            ctx.beginPath();
            ctx.arc(cx, cy, 200, 0, TAU);
            ctx.fill();
            ctx.restore();

            // 名字背景框
            const fontSize = Math.min(72, canvasRect.width * 0.08);
            ctx.font = `bold ${fontSize}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
            const textWidth = ctx.measureText(winner).width;
            const boxPadding = 30;
            const boxWidth = textWidth + boxPadding * 2;
            const boxHeight = fontSize + boxPadding * 1.5;

            // 框背景
            ctx.fillStyle = 'rgba(139, 0, 0, 0.9)';
            roundRect(cx - boxWidth / 2, cy - boxHeight / 2, boxWidth, boxHeight, 12);
            ctx.fill();

            // 金色邊框
            ctx.strokeStyle = 'hsl(45, 100%, 50%)';
            ctx.lineWidth = 3;
            roundRect(cx - boxWidth / 2, cy - boxHeight / 2, boxWidth, boxHeight, 12);
            ctx.stroke();

            // 中獎者名字
            ctx.fillStyle = 'hsl(45, 100%, 60%)';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.shadowColor = 'rgba(255, 215, 0, 0.5)';
            ctx.shadowBlur = 10;
            ctx.fillText(winner, cx, cy);
            ctx.shadowBlur = 0;

            // 恭喜文字
            ctx.font = `bold ${fontSize * 0.35}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
            ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
            ctx.fillText('恭喜中獎！', cx, cy - boxHeight / 2 - 25);
        };

        const launchRain = () => {
            phase = 'raining';
            packets.length = 0;
            coins.length = 0;
            selectedPacket = null;
            spawnTimer = 0;

            const packetCount = 55;
            for (let i = 0; i < packetCount; i++) {
                packets.push(createPacket(-i * 15));
            }
        };

        const selectPacket = (winnerName) => {
            // 找一個可用的紅包（未被選中、在畫面中）
            const availablePackets = packets.filter((p) => !p.selected && p.y > 50 && p.y < canvasRect.height - 100);
            if (availablePackets.length === 0) return false;

            phase = 'selecting';
            winner = winnerName;

            const idx = Math.floor(rand(0, availablePackets.length));
            selectedPacket = availablePackets[idx];
            selectedPacket.selected = true;
            selectedPacket.name = winnerName;

            return true;
        };

        const launchCoins = () => {
            const cx = canvasRect.width / 2;
            const cy = canvasRect.height / 2;

            for (let i = 0; i < 60; i++) {
                const angle = rand(0, TAU);
                const speed = rand(250, 550);
                coins.push({
                    x: cx,
                    y: cy,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 250,
                    size: rand(18, 28),
                    rotation: rand(0, TAU),
                    rotationSpeed: rand(-12, 12),
                    life: rand(1.8, 3),
                    t: 0,
                });
            }
        };

        const animateTo = (obj, targets, duration, onComplete) => {
            const startValues = {};
            const startTime = performance.now();

            for (const key in targets) {
                startValues[key] = obj[key];
            }

            const animTick = () => {
                if (!running) return; // 停止時不再執行

                const elapsed = performance.now() - startTime;
                const progress = Math.min(1, elapsed / duration);
                const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic

                for (const key in targets) {
                    obj[key] = lerp(startValues[key], targets[key], eased);
                }

                if (progress < 1) {
                    animateRafId = requestAnimationFrame(animTick);
                } else {
                    animateRafId = null;
                    if (onComplete) onComplete();
                }
            };

            animateRafId = requestAnimationFrame(animTick);
        };

        const update = (dt) => {
            // 持續生成新紅包（保持雨勢）
            spawnTimer += dt;
            const spawnInterval = 0.08; // 每 0.08 秒生成一個
            const maxPackets = 80;
            while (spawnTimer >= spawnInterval && packets.length < maxPackets) {
                spawnTimer -= spawnInterval;
                // 只在 raining 階段或有足夠紅包時才生成
                if (phase === 'raining' || packets.filter((p) => !p.selected).length < 30) {
                    packets.push(createPacket());
                }
            }

            // 更新紅包
            for (let i = packets.length - 1; i >= 0; i--) {
                const p = packets[i];

                if (p === selectedPacket && phase === 'selecting') {
                    // 選中的紅包飛向中央
                    const targetX = canvasRect.width / 2;
                    const targetY = canvasRect.height / 2;
                    const dx = targetX - p.x;
                    const dy = targetY - p.y;
                    const dist = Math.hypot(dx, dy);

                    if (dist > 5) {
                        p.x += dx * 5 * dt;
                        p.y += dy * 5 * dt;
                        p.scale = lerp(p.scale, 2.5, dt * 4);
                        p.rotation = lerp(p.rotation, 0, dt * 6);
                    } else {
                        // 到達中央，開始打開動畫
                        phase = 'opening';
                        p.opening = true;
                        sfx.playPaperTear();
                        animateTo(p, { openProgress: 1, scale: 3 }, 800, () => {
                            if (!running) return;
                            phase = 'reveal';
                            launchCoins();
                            sfx.playCoinDrop();
                            // 在 reveal 後稍等一下再 resolve
                            revealTimeoutId = setTimeout(() => {
                                revealTimeoutId = null;
                                if (revealResolve) {
                                    revealResolve();
                                    revealResolve = null;
                                }
                            }, 700);
                        });
                    }
                } else if (!p.selected) {
                    // 普通紅包飄落
                    p.wobble += p.wobbleSpeed * dt;
                    p.x += p.vx * dt + Math.sin(p.wobble) * 30 * dt;
                    p.y += p.vy * dt;
                    p.rotation = Math.sin(p.wobble * 0.7) * 0.15;

                    // 移除畫面外的紅包
                    if (p.y > canvasRect.height + 100) {
                        packets.splice(i, 1);
                    }
                } else if (p.selected && p !== selectedPacket) {
                    // 之前選中的紅包（已揭曉），淡出移除
                    p.scale *= 0.95;
                    if (p.scale < 0.1) {
                        packets.splice(i, 1);
                    }
                }
            }

            // 雨期檢查：當大部分紅包落到畫面中時開始選取
            if (phase === 'raining' && winner) {
                const visibleCount = packets.filter((p) => p.y > 0 && p.y < canvasRect.height && !p.selected).length;
                if (visibleCount > 8) {
                    selectPacket(winner);
                }
            }

            // 更新金幣
            for (let i = coins.length - 1; i >= 0; i--) {
                const coin = coins[i];
                coin.t += dt;

                if (coin.t >= coin.life) {
                    coins.splice(i, 1);
                    continue;
                }

                coin.vy += 600 * dt; // 重力
                coin.x += coin.vx * dt;
                coin.y += coin.vy * dt;
                coin.rotation += coin.rotationSpeed * dt;
                coin.vx *= 0.99;
                coin.vy *= 0.99;
            }
        };

        const drawBackground = () => {
            const { width, height } = canvasRect;

            // 漸層背景
            const grad = ctx.createRadialGradient(
                width * 0.5,
                height * 0.3,
                0,
                width * 0.5,
                height * 0.5,
                Math.max(width, height)
            );
            grad.addColorStop(0, 'rgba(139, 0, 0, 0.15)');
            grad.addColorStop(0.5, 'rgba(80, 0, 0, 0.1)');
            grad.addColorStop(1, 'rgba(0, 0, 0, 0)');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, width, height);
        };

        const drawFrame = () => {
            if (!ctx) return;

            const { width, height } = canvasRect;
            // 清除畫布以顯示背景
            ctx.clearRect(0, 0, width, height);

            drawBackground();

            // 繪製紅包（未選中的）
            packets.forEach((p) => {
                if (p !== selectedPacket) {
                    drawPacket(p);
                }
            });

            // 選中的紅包最後繪製（在最上層）
            if (selectedPacket) {
                drawPacket(selectedPacket);
            }

            // 繪製金幣
            coins.forEach((coin) => drawCoin(coin));

            // 繪製中獎者名字
            drawWinnerName();
        };

        const tick = (now) => {
            if (!running) return;
            const dt = Math.min(0.033, (now - last) / 1000);
            last = now;
            update(dt);
            drawFrame();
            rafId = requestAnimationFrame(tick);
        };

        const cleanup = () => {
            // 取消所有動畫和計時器
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
            if (animateRafId) {
                cancelAnimationFrame(animateRafId);
                animateRafId = null;
            }
            if (revealTimeoutId) {
                clearTimeout(revealTimeoutId);
                revealTimeoutId = null;
            }
            // 清空陣列
            packets.length = 0;
            coins.length = 0;
            selectedPacket = null;
            spawnTimer = 0;
            // resolve 任何等待中的 promise
            if (revealResolve) {
                revealResolve();
                revealResolve = null;
            }
        };

        const start = () => {
            // 先清理之前的狀態
            cleanup();
            if (!resizeCanvas()) return;
            running = true;
            phase = 'raining';
            last = performance.now();
            winner = null;
            launchRain();
            rafId = requestAnimationFrame(tick);
        };

        const stop = () => {
            running = false;
            phase = 'idle';
            cleanup();
        };

        // 準備下一位中獎者（不停止雨，繼續動畫）
        const prepareNext = () => {
            // 取消之前的動畫計時器
            if (animateRafId) {
                cancelAnimationFrame(animateRafId);
                animateRafId = null;
            }
            if (revealTimeoutId) {
                clearTimeout(revealTimeoutId);
                revealTimeoutId = null;
            }
            // 移除已選中的紅包
            if (selectedPacket) {
                const idx = packets.indexOf(selectedPacket);
                if (idx >= 0) {
                    packets.splice(idx, 1);
                }
            }
            selectedPacket = null;
            winner = null;
            phase = 'raining';
            // 補充一些紅包
            const currentCount = packets.length;
            if (currentCount < 25) {
                for (let i = 0; i < 15; i++) {
                    packets.push(createPacket(-i * 20));
                }
            }
        };

        const setWinner = (name) => {
            winner = name;
            if (phase === 'raining') {
                // 如果已經在下雨，且有足夠紅包可見，立即選取
                const visibleCount = packets.filter((p) => p.y > 0 && p.y < canvasRect.height && !p.selected).length;
                if (visibleCount > 5) {
                    selectPacket(name);
                }
            }
        };

        const waitForReveal = () => {
            return new Promise((resolve) => {
                revealResolve = resolve;
            });
        };

        const setHoldSeconds = (seconds) => {
            holdSeconds = Math.max(3, seconds || 5);
        };

        const ensureReady = () => {
            if (!ctx || canvasRect.width === 0) {
                resizeCanvas();
            }
        };

        return {
            start,
            stop,
            setWinner,
            waitForReveal,
            prepareNext,
            setHoldSeconds,
            ensureReady,
            resize: () => {
                if (resizeCanvas() && running) {
                    drawFrame();
                }
            },
        };
    })();

    // ========== 刮刮樂模組（支援多卡片） ==========
    const scratchCard = (() => {
        // 狀態
        let running = false;
        let phase = 'idle'; // idle → scratching → reveal
        let cards = []; // 多張卡片資料
        let particles = [];
        let sparkles = [];
        let holdSeconds = 5;
        let revealResolve = null;
        let revealedCount = 0;

        // Canvas 相關
        let ctx = null;
        let canvasRect = { width: 0, height: 0 };
        let dpr = 1;
        let rafId = null;
        let last = 0;

        // 佈局設定
        const GAP = 12;
        const MAX_COLS = 3;

        const resizeCanvas = () => {
            if (!lottoCanvasEl) return false;
            const wrapperRect = lottoWrapEl?.getBoundingClientRect();
            const rect = wrapperRect && wrapperRect.width && wrapperRect.height
                ? wrapperRect
                : lottoCanvasEl.getBoundingClientRect();
            if (!rect.width || !rect.height) return false;

            dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
            lottoCanvasEl.width = Math.max(1, Math.floor(rect.width * dpr));
            lottoCanvasEl.height = Math.max(1, Math.floor(rect.height * dpr));
            ctx = lottoCanvasEl.getContext('2d');
            if (ctx) {
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }
            canvasRect = { width: rect.width, height: rect.height };
            return true;
        };

        const roundRect = (context, x, y, w, h, r) => {
            context.beginPath();
            context.moveTo(x + r, y);
            context.arcTo(x + w, y, x + w, y + h, r);
            context.arcTo(x + w, y + h, x, y + h, r);
            context.arcTo(x, y + h, x, y, r);
            context.arcTo(x, y, x + w, y, r);
            context.closePath();
        };

        // 計算網格佈局
        const getGridLayout = (count) => {
            const cols = Math.min(count, MAX_COLS);
            const rows = Math.ceil(count / cols);

            // 計算可用空間
            const availW = canvasRect.width - GAP * 2;
            const availH = canvasRect.height - GAP * 2;

            // 計算卡片尺寸（保持 16:10 比例）
            const cardRatio = 16 / 10;
            let cardW = (availW - GAP * (cols - 1)) / cols;
            let cardH = cardW / cardRatio;

            // 檢查高度是否超出
            const totalH = cardH * rows + GAP * (rows - 1);
            if (totalH > availH) {
                cardH = (availH - GAP * (rows - 1)) / rows;
                cardW = cardH * cardRatio;
            }

            // 計算起始位置（置中）
            const totalW = cardW * cols + GAP * (cols - 1);
            const startX = (canvasRect.width - totalW) / 2;
            const startY = (canvasRect.height - (cardH * rows + GAP * (rows - 1))) / 2;

            return { cols, rows, cardW, cardH, startX, startY };
        };

        // 建立卡片資料
        const createCard = (index, layout) => {
            const { cols, cardW, cardH, startX, startY } = layout;
            const row = Math.floor(index / cols);
            const col = index % cols;
            const x = startX + col * (cardW + GAP);
            const y = startY + row * (cardH + GAP);

            // 建立該卡片的遮罩 canvas
            const maskCanvas = document.createElement('canvas');
            maskCanvas.width = Math.ceil(cardW * dpr);
            maskCanvas.height = Math.ceil(cardH * dpr);
            const maskCtx = maskCanvas.getContext('2d');
            if (maskCtx) {
                maskCtx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }

            // 產生刮除路徑
            const scratchPath = generateCardScratchPath(cardW, cardH);

            return {
                index,
                x, y, w: cardW, h: cardH,
                winner: null,
                phase: 'idle', // idle, scratching, reveal
                maskCanvas,
                maskCtx,
                scratchPath,
                scratchIndex: 0,
                scratchDelay: rand(0, 0.3), // 隨機延遲開始刮
                revealed: false,
            };
        };

        // 產生單張卡片的刮除路徑
        const generateCardScratchPath = (cardW, cardH) => {
            const path = [];
            const padding = 8;
            const rows = 4;
            const cols = 6;

            for (let row = 0; row < rows; row++) {
                const y = padding + (cardH - padding * 2) * (row / (rows - 1));
                const direction = row % 2 === 0 ? 1 : -1;
                for (let col = 0; col < cols; col++) {
                    const actualCol = direction === 1 ? col : cols - 1 - col;
                    const x = padding + (cardW - padding * 2) * (actualCol / (cols - 1));
                    path.push({
                        x: x + rand(-5, 5),
                        y: y + rand(-3, 3),
                    });
                }
            }
            return path;
        };

        // 初始化卡片的遮罩
        const initCardMask = (card) => {
            const { maskCtx, w, h } = card;
            if (!maskCtx) return;

            maskCtx.clearRect(0, 0, w, h);

            // 銀灰色金屬質感
            const silverGrad = maskCtx.createLinearGradient(0, 0, w, h);
            silverGrad.addColorStop(0, 'hsl(220, 5%, 75%)');
            silverGrad.addColorStop(0.3, 'hsl(220, 8%, 82%)');
            silverGrad.addColorStop(0.7, 'hsl(220, 5%, 70%)');
            silverGrad.addColorStop(1, 'hsl(220, 5%, 68%)');

            const r = Math.min(w, h) * 0.06;
            roundRect(maskCtx, 2, 2, w - 4, h - 4, r);
            maskCtx.fillStyle = silverGrad;
            maskCtx.fill();

            // 光澤線條
            maskCtx.save();
            maskCtx.globalAlpha = 0.25;
            for (let i = 1; i < 6; i++) {
                const lineY = h * (i / 6);
                const lineGrad = maskCtx.createLinearGradient(0, lineY, w, lineY);
                lineGrad.addColorStop(0, 'transparent');
                lineGrad.addColorStop(0.4, 'rgba(255,255,255,0.5)');
                lineGrad.addColorStop(0.6, 'rgba(255,255,255,0.5)');
                lineGrad.addColorStop(1, 'transparent');
                maskCtx.fillStyle = lineGrad;
                maskCtx.fillRect(5, lineY - 1, w - 10, 2);
            }
            maskCtx.restore();

            // 「刮開」文字
            const fontSize = Math.min(w * 0.15, 24);
            maskCtx.font = `bold ${fontSize}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
            maskCtx.fillStyle = 'hsl(220, 10%, 45%)';
            maskCtx.textAlign = 'center';
            maskCtx.textBaseline = 'middle';
            maskCtx.fillText('刮開', w / 2, h / 2);
        };

        // 在卡片遮罩上刮除
        const scratchCardAt = (card, localX, localY, radius) => {
            const { maskCtx } = card;
            if (!maskCtx) return;
            maskCtx.save();
            maskCtx.globalCompositeOperation = 'destination-out';
            for (let i = 0; i < 4; i++) {
                const ox = rand(-radius * 0.3, radius * 0.3);
                const oy = rand(-radius * 0.3, radius * 0.3);
                maskCtx.beginPath();
                maskCtx.arc(localX + ox, localY + oy, radius * rand(0.5, 1), 0, TAU);
                maskCtx.fill();
            }
            maskCtx.restore();
        };

        // 產生刮屑粒子
        const spawnScratchParticle = (x, y) => {
            const count = Math.floor(rand(2, 4));
            for (let i = 0; i < count; i++) {
                const angle = rand(0, TAU);
                const speed = rand(30, 80);
                particles.push({
                    x, y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 20,
                    size: rand(2, 5),
                    life: rand(0.3, 0.6),
                    t: 0,
                    hue: rand(0, 20),
                });
            }
        };

        // 產生揭曉閃光
        const spawnCardSparkles = (card) => {
            const cx = card.x + card.w / 2;
            const cy = card.y + card.h / 2;
            const count = Math.min(20, Math.max(10, Math.floor(card.w * 0.15)));
            for (let i = 0; i < count; i++) {
                const angle = rand(0, TAU);
                const speed = rand(50, 180);
                sparkles.push({
                    x: cx, y: cy,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 40,
                    size: rand(3, 8),
                    life: rand(0.8, 1.5),
                    t: 0,
                    hue: rand(40, 55),
                    rotSpeed: rand(-6, 6),
                    rot: rand(0, TAU),
                });
            }
        };

        // 繪製單張卡片底層
        const drawCardBase = (card) => {
            const { x, y, w, h, winner, phase: cardPhase } = card;
            const r = Math.min(w, h) * 0.06;

            // 陰影
            ctx.save();
            ctx.shadowColor = 'rgba(0, 0, 0, 0.35)';
            ctx.shadowBlur = 12;
            ctx.shadowOffsetY = 4;

            // 金色漸層
            const gradient = ctx.createLinearGradient(x, y, x + w, y + h);
            gradient.addColorStop(0, 'hsl(45, 85%, 58%)');
            gradient.addColorStop(0.4, 'hsl(43, 90%, 52%)');
            gradient.addColorStop(1, 'hsl(38, 80%, 42%)');

            roundRect(ctx, x, y, w, h, r);
            ctx.fillStyle = gradient;
            ctx.fill();
            ctx.restore();

            // 邊框
            ctx.strokeStyle = 'hsl(35, 75%, 35%)';
            ctx.lineWidth = 2;
            roundRect(ctx, x, y, w, h, r);
            ctx.stroke();

            // 裝飾圖案
            ctx.save();
            ctx.globalAlpha = 0.12;
            const patSize = Math.min(w, h) * 0.15;
            for (let px = x + patSize; px < x + w - patSize; px += patSize * 1.5) {
                for (let py = y + patSize; py < y + h - patSize; py += patSize * 1.5) {
                    ctx.beginPath();
                    ctx.arc(px, py, patSize * 0.25, 0, TAU);
                    ctx.fillStyle = 'hsl(35, 70%, 30%)';
                    ctx.fill();
                }
            }
            ctx.restore();

            // 中獎者名字
            if (winner && (cardPhase === 'scratching' || cardPhase === 'reveal')) {
                const fontSize = Math.min(w * 0.5, h * 0.55, 80);
                ctx.font = `bold ${fontSize}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.save();
                ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
                ctx.shadowBlur = 6;
                ctx.fillStyle = 'hsl(25, 90%, 22%)';
                ctx.fillText(winner, x + w / 2, y + h / 2);
                ctx.restore();
            }

            // 頂部小標籤
            if (cardPhase === 'idle') {
                const labelSize = Math.min(w * 0.08, 11);
                ctx.font = `bold ${labelSize}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
                ctx.fillStyle = 'hsl(35, 70%, 30%)';
                ctx.textAlign = 'center';
                ctx.fillText('刮開有驚喜', x + w / 2, y + labelSize + 6);
            }
        };

        // 繪製單張卡片的揭曉效果
        const drawCardRevealEffect = (card) => {
            if (card.phase !== 'reveal') return;
            const cx = card.x + card.w / 2;
            const cy = card.y + card.h / 2;

            // 小型金光
            ctx.save();
            ctx.globalCompositeOperation = 'lighter';
            const rayCount = 8;
            const time = performance.now() / 1000;
            for (let i = 0; i < rayCount; i++) {
                const angle = (i / rayCount) * TAU + time * 0.8 + card.index * 0.5;
                const rayLen = card.w * 0.4;
                const rayGrad = ctx.createLinearGradient(
                    cx, cy,
                    cx + Math.cos(angle) * rayLen,
                    cy + Math.sin(angle) * rayLen
                );
                rayGrad.addColorStop(0, 'rgba(255, 215, 0, 0.5)');
                rayGrad.addColorStop(1, 'rgba(255, 180, 0, 0)');
                ctx.strokeStyle = rayGrad;
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(cx, cy);
                ctx.lineTo(cx + Math.cos(angle) * rayLen, cy + Math.sin(angle) * rayLen);
                ctx.stroke();
            }
            ctx.restore();
        };

        // 繪製粒子
        const drawParticles = () => {
            for (const p of particles) {
                const alpha = 1 - p.t / p.life;
                ctx.save();
                ctx.globalAlpha = alpha;
                ctx.fillStyle = `hsl(${p.hue}, 10%, ${70 + p.hue}%)`;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size * (1 - p.t / p.life * 0.4), 0, TAU);
                ctx.fill();
                ctx.restore();
            }
        };

        // 繪製閃光
        const drawSparkles = () => {
            for (const s of sparkles) {
                const alpha = 1 - s.t / s.life;
                ctx.save();
                ctx.translate(s.x, s.y);
                ctx.rotate(s.rot);
                ctx.globalAlpha = alpha;
                const grad = ctx.createRadialGradient(0, 0, 0, 0, 0, s.size);
                grad.addColorStop(0, `hsla(${s.hue}, 100%, 70%, 1)`);
                grad.addColorStop(0.5, `hsla(${s.hue}, 100%, 55%, 0.7)`);
                grad.addColorStop(1, `hsla(${s.hue}, 100%, 50%, 0)`);
                ctx.fillStyle = grad;
                ctx.beginPath();
                for (let i = 0; i < 4; i++) {
                    const ang = (i / 4) * TAU;
                    const len = s.size * (i % 2 === 0 ? 1 : 0.35);
                    if (i === 0) ctx.moveTo(Math.cos(ang) * len, Math.sin(ang) * len);
                    else ctx.lineTo(Math.cos(ang) * len, Math.sin(ang) * len);
                    const midAng = ang + TAU / 8;
                    ctx.lineTo(Math.cos(midAng) * s.size * 0.25, Math.sin(midAng) * s.size * 0.25);
                }
                ctx.closePath();
                ctx.fill();
                ctx.restore();
            }
        };

        // 更新邏輯
        const update = (dt) => {
            // 更新每張卡片
            for (const card of cards) {
                if (card.phase === 'scratching') {
                    // 處理延遲
                    if (card.scratchDelay > 0) {
                        card.scratchDelay -= dt;
                        continue;
                    }

                    const scratchSpeed = card.scratchPath.length / (holdSeconds * 0.65);
                    card.scratchIndex += scratchSpeed * dt;

                    if (card.scratchIndex < card.scratchPath.length) {
                        const idx = Math.floor(card.scratchIndex);
                        const pt = card.scratchPath[idx];
                        if (pt) {
                            const radius = Math.min(card.w, card.h) * 0.12;
                            scratchCardAt(card, pt.x, pt.y, radius);
                            // 產生粒子（轉換為全域座標）
                            spawnScratchParticle(card.x + pt.x, card.y + pt.y);
                        }
                    } else if (!card.revealed) {
                        // 刮除完成
                        card.phase = 'reveal';
                        card.revealed = true;
                        spawnCardSparkles(card);
                        sfx.playReveal();
                        revealedCount++;

                        // 單張卡片刮除完成，resolve 該卡片的 Promise
                        if (card.scratchResolve) {
                            setTimeout(() => {
                                if (card.scratchResolve) {
                                    card.scratchResolve();
                                    card.scratchResolve = null;
                                }
                            }, 600);
                        }

                        // 檢查是否全部揭曉
                        if (revealedCount >= cards.filter(c => c.winner).length && revealResolve) {
                            setTimeout(() => {
                                if (revealResolve) {
                                    revealResolve();
                                    revealResolve = null;
                                }
                            }, 600);
                        }
                    }
                }
            }

            // 更新粒子
            for (let i = particles.length - 1; i >= 0; i--) {
                const p = particles[i];
                p.t += dt;
                if (p.t >= p.life) { particles.splice(i, 1); continue; }
                p.vy += 150 * dt;
                p.x += p.vx * dt;
                p.y += p.vy * dt;
                p.vx *= 0.97;
            }

            // 更新閃光
            for (let i = sparkles.length - 1; i >= 0; i--) {
                const s = sparkles[i];
                s.t += dt;
                if (s.t >= s.life) { sparkles.splice(i, 1); continue; }
                s.vy += 120 * dt;
                s.x += s.vx * dt;
                s.y += s.vy * dt;
                s.rot += s.rotSpeed * dt;
                s.vx *= 0.97;
                s.vy *= 0.97;
            }
        };

        // 繪製背景
        const drawBackground = () => {
            const { width, height } = canvasRect;
            const grad = ctx.createRadialGradient(
                width * 0.5, height * 0.4, 0,
                width * 0.5, height * 0.5, Math.max(width, height) * 0.7
            );
            grad.addColorStop(0, 'rgba(60, 50, 30, 0.15)');
            grad.addColorStop(0.5, 'rgba(40, 35, 20, 0.1)');
            grad.addColorStop(1, 'rgba(0, 0, 0, 0)');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, width, height);
        };

        // 主繪製函式
        const drawFrame = () => {
            if (!ctx) return;
            const { width, height } = canvasRect;

            // 清除畫布以顯示背景
            ctx.clearRect(0, 0, width, height);

            drawBackground();

            // 繪製每張卡片
            for (const card of cards) {
                drawCardBase(card);
                // 繪製遮罩（未完全揭曉時）
                if (card.maskCanvas && card.phase !== 'reveal') {
                    ctx.drawImage(card.maskCanvas, card.x, card.y, card.w, card.h);
                }
                drawCardRevealEffect(card);
            }

            drawParticles();
            drawSparkles();
        };

        const tick = (now) => {
            if (!running) return;
            const dt = Math.min(0.05, (now - last) / 1000);
            last = now;
            update(dt);
            drawFrame();
            rafId = requestAnimationFrame(tick);
        };

        const cleanup = () => {
            if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
            particles.length = 0;
            sparkles.length = 0;
            if (revealResolve) { revealResolve(); revealResolve = null; }
        };

        // 顯示卡片（在選擇動畫時就呼叫，不等抽獎）
        const showCards = (count) => {
            cleanup();
            if (!resizeCanvas()) return;

            const cardCount = Math.min(Math.max(1, count), 9);
            const layout = getGridLayout(cardCount);

            cards = [];
            for (let i = 0; i < cardCount; i++) {
                const card = createCard(i, layout);
                initCardMask(card);
                cards.push(card);
            }

            running = true;
            phase = 'idle';
            revealedCount = 0;
            last = performance.now();
            rafId = requestAnimationFrame(tick);
        };

        // 設定中獎者（可一次設定多個）
        const setWinners = (names) => {
            const nameList = Array.isArray(names) ? names : [names];
            for (let i = 0; i < cards.length && i < nameList.length; i++) {
                cards[i].winner = nameList[i];
            }
        };

        // 開始刮除所有卡片
        const startScratching = () => {
            phase = 'scratching';
            revealedCount = 0;
            for (const card of cards) {
                if (card.winner) {
                    card.phase = 'scratching';
                    card.scratchIndex = 0;
                    card.scratchDelay = rand(0, 0.4); // 隨機延遲
                }
            }
        };

        // 刮除單張卡片（返回 Promise，用於逐一刮開模式）
        const scratchSingleCard = (index) => {
            return new Promise((resolve) => {
                const card = cards[index];
                if (!card || !card.winner) {
                    resolve();
                    return;
                }
                phase = 'scratching';
                card.scratchResolve = resolve;
                card.phase = 'scratching';
                card.scratchIndex = 0;
                card.scratchDelay = 0;
            });
        };

        // 等待所有卡片揭曉
        const waitForAllRevealed = () => {
            return new Promise((resolve) => {
                // 如果已經全部揭曉
                if (revealedCount >= cards.filter(c => c.winner).length) {
                    resolve();
                    return;
                }
                revealResolve = resolve;
            });
        };

        const stop = () => {
            running = false;
            phase = 'idle';
            cards = [];
            cleanup();
        };

        const setHoldSeconds = (seconds) => {
            holdSeconds = Math.max(2, seconds || 5);
        };

        const ensureReady = () => {
            if (!ctx || canvasRect.width === 0) {
                resizeCanvas();
            }
        };

        // 單卡模式的相容 API
        const start = () => showCards(1);
        const setWinner = (name) => {
            setWinners([name]);
            startScratching();
        };
        const waitForReveal = () => waitForAllRevealed();
        const prepareNext = () => {
            // 重置所有卡片
            for (const card of cards) {
                card.winner = null;
                card.phase = 'idle';
                card.scratchIndex = 0;
                card.revealed = false;
                initCardMask(card);
            }
            revealedCount = 0;
            phase = 'idle';
        };

        return {
            // 多卡片 API
            showCards,
            setWinners,
            startScratching,
            scratchSingleCard,
            waitForAllRevealed,
            // 單卡相容 API
            start,
            stop,
            setWinner,
            waitForReveal,
            prepareNext,
            setHoldSeconds,
            ensureReady,
            resize: () => {
                if (!running || cards.length === 0) return;
                if (!resizeCanvas()) return;
                const layout = getGridLayout(cards.length);
                // 重新計算卡片位置
                for (let i = 0; i < cards.length; i++) {
                    const { cols, cardW, cardH, startX, startY } = layout;
                    const row = Math.floor(i / cols);
                    const col = i % cols;
                    cards[i].x = startX + col * (cardW + GAP);
                    cards[i].y = startY + row * (cardH + GAP);
                    cards[i].w = cardW;
                    cards[i].h = cardH;
                    // 重建遮罩
                    cards[i].maskCanvas.width = Math.ceil(cardW * dpr);
                    cards[i].maskCanvas.height = Math.ceil(cardH * dpr);
                    cards[i].maskCtx = cards[i].maskCanvas.getContext('2d');
                    if (cards[i].maskCtx) {
                        cards[i].maskCtx.setTransform(dpr, 0, 0, dpr, 0, 0);
                    }
                    if (cards[i].phase !== 'reveal') {
                        initCardMask(cards[i]);
                    }
                    cards[i].scratchPath = generateCardScratchPath(cardW, cardH);
                }
                drawFrame();
            },
        };
    })();

    // ========== 寶箱開啟模組（支援多寶箱）==========
    const treasureChest = (() => {
        // 狀態
        let running = false;
        let phase = 'idle'; // idle → opening → reveal
        let chests = []; // 多個寶箱資料
        let coins = [];  // 金幣粒子
        let sparkles = []; // 閃光粒子
        let holdSeconds = 5;
        let openResolve = null;
        let openedCount = 0;

        // Canvas 相關
        let ctx = null;
        let canvasRect = { width: 0, height: 0 };
        let dpr = 1;
        let rafId = null;
        let last = 0;

        // 佈局設定
        const GAP = 16;
        const MAX_COLS = 3;

        const resizeCanvas = () => {
            if (!lottoCanvasEl) return false;
            const wrapperRect = lottoWrapEl?.getBoundingClientRect();
            const rect = wrapperRect && wrapperRect.width && wrapperRect.height
                ? wrapperRect
                : lottoCanvasEl.getBoundingClientRect();
            if (!rect.width || !rect.height) return false;

            dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
            lottoCanvasEl.width = Math.max(1, Math.floor(rect.width * dpr));
            lottoCanvasEl.height = Math.max(1, Math.floor(rect.height * dpr));
            ctx = lottoCanvasEl.getContext('2d');
            if (ctx) {
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }
            canvasRect = { width: rect.width, height: rect.height };
            return true;
        };

        const roundRect = (context, x, y, w, h, r) => {
            context.beginPath();
            context.moveTo(x + r, y);
            context.arcTo(x + w, y, x + w, y + h, r);
            context.arcTo(x + w, y + h, x, y + h, r);
            context.arcTo(x, y + h, x, y, r);
            context.arcTo(x, y, x + w, y, r);
            context.closePath();
        };

        // 計算網格佈局
        const getGridLayout = (count) => {
            const cols = Math.min(count, MAX_COLS);
            const rows = Math.ceil(count / cols);

            const availW = canvasRect.width - GAP * 2;
            const availH = canvasRect.height - GAP * 2;

            // 寶箱比例 (寬:高 = 1:0.9)
            const chestRatio = 1 / 0.9;
            let cardW = (availW - GAP * (cols - 1)) / cols;
            let cardH = cardW / chestRatio;

            const totalH = cardH * rows + GAP * (rows - 1);
            if (totalH > availH) {
                cardH = (availH - GAP * (rows - 1)) / rows;
                cardW = cardH * chestRatio;
            }

            const totalW = cardW * cols + GAP * (cols - 1);
            const startX = (canvasRect.width - totalW) / 2;
            const startY = (canvasRect.height - (cardH * rows + GAP * (rows - 1))) / 2;

            return { cols, rows, cardW, cardH, startX, startY };
        };

        // 建立寶箱資料
        const createChest = (index, layout) => {
            const { cols, cardW, cardH, startX, startY } = layout;
            const row = Math.floor(index / cols);
            const col = index % cols;
            const x = startX + col * (cardW + GAP);
            const y = startY + row * (cardH + GAP);

            return {
                index,
                x, y, w: cardW, h: cardH,
                winner: null,
                phase: 'closed', // closed → shaking → opening → open
                lidAngle: 0,      // 蓋子角度 (0 = 關閉, -110 = 打開)
                shakeOffset: 0,   // 震動偏移
                shakeTime: 0,     // 震動計時
                glowIntensity: 0, // 光暈強度
                openDelay: rand(0, 0.3), // 隨機延遲開啟
                opened: false,
            };
        };

        // 繪製單個寶箱
        const drawChest = (chest) => {
            const { x, y, w, h, phase: chestPhase, lidAngle, shakeOffset, glowIntensity, winner } = chest;
            const cx = x + w / 2;
            const cy = y + h / 2;

            ctx.save();
            ctx.translate(cx + shakeOffset, cy);

            // 寶箱尺寸
            const bodyW = w * 0.8;
            const bodyH = h * 0.42;
            const lidH = h * 0.32;

            // 光暈效果（打開時從箱內湧出）
            if (glowIntensity > 0) {
                const glowGrad = ctx.createRadialGradient(0, -bodyH * 0.2, 0, 0, -bodyH * 0.3, w * 0.9);
                glowGrad.addColorStop(0, `rgba(255, 215, 0, ${glowIntensity * 0.7})`);
                glowGrad.addColorStop(0.5, `rgba(255, 180, 0, ${glowIntensity * 0.4})`);
                glowGrad.addColorStop(1, 'rgba(255, 215, 0, 0)');
                ctx.fillStyle = glowGrad;
                ctx.fillRect(-w * 0.9, -h * 0.9, w * 1.8, h * 1.4);
            }

            // 箱體陰影
            ctx.save();
            ctx.shadowColor = 'rgba(0, 0, 0, 0.4)';
            ctx.shadowBlur = 15;
            ctx.shadowOffsetY = 6;

            // 箱體
            const bodyY = bodyH * 0.15;
            const bodyGrad = ctx.createLinearGradient(-bodyW / 2, bodyY, bodyW / 2, bodyY + bodyH);
            bodyGrad.addColorStop(0, '#8B4513');
            bodyGrad.addColorStop(0.3, '#A0522D');
            bodyGrad.addColorStop(0.7, '#8B4513');
            bodyGrad.addColorStop(1, '#654321');
            ctx.fillStyle = bodyGrad;
            roundRect(ctx, -bodyW / 2, bodyY, bodyW, bodyH, 10);
            ctx.fill();
            ctx.restore();

            // 金屬邊框
            ctx.strokeStyle = '#DAA520';
            ctx.lineWidth = 4;
            roundRect(ctx, -bodyW / 2, bodyY, bodyW, bodyH, 10);
            ctx.stroke();

            // 中間金屬條
            const bandGrad = ctx.createLinearGradient(0, bodyY + bodyH * 0.35, 0, bodyY + bodyH * 0.5);
            bandGrad.addColorStop(0, '#DAA520');
            bandGrad.addColorStop(0.5, '#FFD700');
            bandGrad.addColorStop(1, '#B8860B');
            ctx.fillStyle = bandGrad;
            ctx.fillRect(-bodyW / 2 + 6, bodyY + bodyH * 0.38, bodyW - 12, 10);

            // 木紋裝飾
            ctx.save();
            ctx.globalAlpha = 0.15;
            ctx.strokeStyle = '#4A2000';
            ctx.lineWidth = 1;
            for (let i = 1; i < 4; i++) {
                const lineX = -bodyW / 2 + (bodyW / 4) * i;
                ctx.beginPath();
                ctx.moveTo(lineX, bodyY + 8);
                ctx.lineTo(lineX, bodyY + bodyH - 8);
                ctx.stroke();
            }
            ctx.restore();

            // 蓋子（帶旋轉）
            ctx.save();
            ctx.translate(0, bodyY);
            ctx.rotate((lidAngle * Math.PI) / 180);

            // 蓋子陰影
            ctx.save();
            ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
            ctx.shadowBlur = 8;
            ctx.shadowOffsetY = 3;

            const lidGrad = ctx.createLinearGradient(-bodyW / 2, -lidH, bodyW / 2, 0);
            lidGrad.addColorStop(0, '#A0522D');
            lidGrad.addColorStop(0.4, '#8B4513');
            lidGrad.addColorStop(1, '#654321');
            ctx.fillStyle = lidGrad;

            // 弧形蓋子
            ctx.beginPath();
            ctx.moveTo(-bodyW / 2, 0);
            ctx.lineTo(-bodyW / 2, -lidH * 0.5);
            ctx.quadraticCurveTo(-bodyW / 2, -lidH, -bodyW / 4, -lidH);
            ctx.lineTo(bodyW / 4, -lidH);
            ctx.quadraticCurveTo(bodyW / 2, -lidH, bodyW / 2, -lidH * 0.5);
            ctx.lineTo(bodyW / 2, 0);
            ctx.closePath();
            ctx.fill();
            ctx.restore();

            // 蓋子邊框
            ctx.strokeStyle = '#DAA520';
            ctx.lineWidth = 4;
            ctx.beginPath();
            ctx.moveTo(-bodyW / 2, 0);
            ctx.lineTo(-bodyW / 2, -lidH * 0.5);
            ctx.quadraticCurveTo(-bodyW / 2, -lidH, -bodyW / 4, -lidH);
            ctx.lineTo(bodyW / 4, -lidH);
            ctx.quadraticCurveTo(bodyW / 2, -lidH, bodyW / 2, -lidH * 0.5);
            ctx.lineTo(bodyW / 2, 0);
            ctx.stroke();

            // 蓋子金屬條
            const lidBandGrad = ctx.createLinearGradient(0, -lidH * 0.6, 0, -lidH * 0.4);
            lidBandGrad.addColorStop(0, '#B8860B');
            lidBandGrad.addColorStop(0.5, '#FFD700');
            lidBandGrad.addColorStop(1, '#DAA520');
            ctx.fillStyle = lidBandGrad;
            ctx.fillRect(-bodyW / 2 + 8, -lidH * 0.55, bodyW - 16, 8);

            ctx.restore();

            // 鎖頭（關閉和震動時顯示）
            if (chestPhase === 'closed' || chestPhase === 'shaking') {
                // 鎖頭光澤
                const lockGrad = ctx.createRadialGradient(-3, bodyY - 3, 0, 0, bodyY, 14);
                lockGrad.addColorStop(0, '#FFE066');
                lockGrad.addColorStop(0.5, '#FFD700');
                lockGrad.addColorStop(1, '#B8860B');
                ctx.fillStyle = lockGrad;
                ctx.beginPath();
                ctx.arc(0, bodyY + 6, 14, 0, TAU);
                ctx.fill();

                // 鎖頭邊框
                ctx.strokeStyle = '#8B6914';
                ctx.lineWidth = 2;
                ctx.stroke();

                // 鎖扣
                ctx.fillStyle = '#B8860B';
                ctx.fillRect(-5, bodyY + 10, 10, 14);

                // 鑰匙孔
                ctx.fillStyle = '#4A3000';
                ctx.beginPath();
                ctx.arc(0, bodyY + 4, 4, 0, TAU);
                ctx.fill();
                ctx.fillRect(-2, bodyY + 4, 4, 6);
            }

            // 中獎者名字（打開後顯示）
            if (winner && chestPhase === 'open') {
                const fontSize = Math.min(w * 0.14, 32);
                ctx.font = `bold ${fontSize}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                // 文字光暈
                ctx.save();
                ctx.shadowColor = 'rgba(255, 215, 0, 0.8)';
                ctx.shadowBlur = 12;
                ctx.fillStyle = '#FFD700';
                ctx.fillText(winner, 0, -h * 0.22);
                ctx.restore();

                // 主文字
                ctx.fillStyle = '#FFFFFF';
                ctx.fillText(winner, 0, -h * 0.22);
            }

            ctx.restore();
        };

        // 產生金幣
        const spawnCoins = (chest) => {
            const cx = chest.x + chest.w / 2;
            const cy = chest.y + chest.h * 0.35;
            const count = Math.floor(rand(18, 28));
            for (let i = 0; i < count; i++) {
                const angle = rand(-Math.PI * 0.85, -Math.PI * 0.15);
                const speed = rand(180, 400);
                coins.push({
                    x: cx + rand(-20, 20),
                    y: cy,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed,
                    size: rand(10, 18),
                    rotation: rand(0, TAU),
                    rotSpeed: rand(-12, 12),
                    life: rand(1.5, 2.5),
                    t: 0,
                    glint: rand(0, 1) > 0.7, // 部分金幣有閃光
                });
            }
        };

        // 產生閃光
        const spawnSparkles = (chest) => {
            const cx = chest.x + chest.w / 2;
            const cy = chest.y + chest.h * 0.35;
            const count = 25;
            for (let i = 0; i < count; i++) {
                const angle = rand(0, TAU);
                const speed = rand(100, 250);
                sparkles.push({
                    x: cx, y: cy,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 60,
                    size: rand(4, 9),
                    life: rand(0.8, 1.5),
                    t: 0,
                    hue: rand(38, 52),
                });
            }
        };

        // 更新邏輯
        const update = (dt) => {
            // 更新每個寶箱
            for (const chest of chests) {
                if (chest.phase === 'shaking') {
                    chest.shakeTime += dt;
                    chest.shakeOffset = Math.sin(chest.shakeTime * 45) * 5;
                    if (chest.shakeTime > 0.6) {
                        chest.phase = 'opening';
                        chest.shakeOffset = 0;
                    }
                } else if (chest.phase === 'opening') {
                    // 處理延遲
                    if (chest.openDelay > 0) {
                        chest.openDelay -= dt;
                        continue;
                    }
                    // 開啟蓋子
                    chest.lidAngle = Math.max(-110, chest.lidAngle - 280 * dt);
                    chest.glowIntensity = Math.min(1, chest.glowIntensity + dt * 3.5);
                    if (chest.lidAngle <= -100 && !chest.opened) {
                        chest.phase = 'open';
                        chest.opened = true;
                        sfx.playChestOpen();
                        spawnCoins(chest);
                        spawnSparkles(chest);
                        setTimeout(() => sfx.playCoinDrop(), 80);
                        openedCount++;

                        // 單個寶箱開啟完成，resolve 該寶箱的 Promise
                        if (chest.openResolve) {
                            setTimeout(() => {
                                if (chest.openResolve) {
                                    chest.openResolve();
                                    chest.openResolve = null;
                                }
                            }, 600);
                        }

                        // 檢查是否全部開啟（相容舊的 startOpening + waitForAllOpened 流程）
                        if (openedCount >= chests.filter(c => c.winner).length && openResolve) {
                            setTimeout(() => {
                                if (openResolve) {
                                    openResolve();
                                    openResolve = null;
                                }
                            }, 800);
                        }
                    }
                } else if (chest.phase === 'open') {
                    // 維持光暈脈動
                    chest.glowIntensity = 0.5 + Math.sin(performance.now() / 350) * 0.25;
                }
            }

            // 更新金幣
            for (let i = coins.length - 1; i >= 0; i--) {
                const c = coins[i];
                c.t += dt;
                if (c.t >= c.life) { coins.splice(i, 1); continue; }
                c.vy += 450 * dt; // 重力
                c.x += c.vx * dt;
                c.y += c.vy * dt;
                c.rotation += c.rotSpeed * dt;
                c.vx *= 0.99;
            }

            // 更新閃光
            for (let i = sparkles.length - 1; i >= 0; i--) {
                const s = sparkles[i];
                s.t += dt;
                if (s.t >= s.life) { sparkles.splice(i, 1); continue; }
                s.vy += 120 * dt;
                s.x += s.vx * dt;
                s.y += s.vy * dt;
                s.vx *= 0.97;
            }
        };

        // 繪製金幣
        const drawCoins = () => {
            for (const c of coins) {
                const alpha = 1 - (c.t / c.life) * 0.5;
                ctx.save();
                ctx.translate(c.x, c.y);
                ctx.rotate(c.rotation);
                ctx.globalAlpha = alpha;

                // 金幣橢圓（旋轉效果）
                const squeeze = Math.abs(Math.sin(c.rotation * 2));
                const coinW = c.size;
                const coinH = c.size * (0.3 + squeeze * 0.7);

                // 金幣漸層
                const grad = ctx.createRadialGradient(-2, -2, 0, 0, 0, c.size);
                grad.addColorStop(0, '#FFE066');
                grad.addColorStop(0.4, '#FFD700');
                grad.addColorStop(1, '#B8860B');
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.ellipse(0, 0, coinW, coinH, 0, 0, TAU);
                ctx.fill();

                // 幣面高光
                if (c.glint && squeeze > 0.5) {
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
                    ctx.beginPath();
                    ctx.ellipse(-coinW * 0.3, -coinH * 0.3, coinW * 0.25, coinH * 0.2, 0, 0, TAU);
                    ctx.fill();
                }

                // 幣面標記
                if (squeeze > 0.4) {
                    ctx.fillStyle = '#DAA520';
                    ctx.beginPath();
                    ctx.arc(0, 0, c.size * 0.25, 0, TAU);
                    ctx.fill();
                }

                ctx.restore();
            }
        };

        // 繪製閃光
        const drawSparkles = () => {
            for (const s of sparkles) {
                const alpha = 1 - s.t / s.life;
                ctx.save();
                ctx.globalAlpha = alpha;
                const grad = ctx.createRadialGradient(s.x, s.y, 0, s.x, s.y, s.size);
                grad.addColorStop(0, `hsla(${s.hue}, 100%, 75%, 1)`);
                grad.addColorStop(0.5, `hsla(${s.hue}, 100%, 60%, 0.6)`);
                grad.addColorStop(1, `hsla(${s.hue}, 100%, 50%, 0)`);
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.size, 0, TAU);
                ctx.fill();
                ctx.restore();
            }
        };

        // 繪製背景
        const drawBackground = () => {
            const { width, height } = canvasRect;
            const grad = ctx.createRadialGradient(
                width * 0.5, height * 0.4, 0,
                width * 0.5, height * 0.5, Math.max(width, height) * 0.65
            );
            grad.addColorStop(0, 'rgba(80, 60, 20, 0.12)');
            grad.addColorStop(0.5, 'rgba(60, 45, 15, 0.08)');
            grad.addColorStop(1, 'rgba(0, 0, 0, 0)');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, width, height);
        };

        // 主繪製函式
        const drawFrame = () => {
            if (!ctx) return;
            const { width, height } = canvasRect;
            ctx.clearRect(0, 0, width, height);

            drawBackground();

            // 繪製寶箱
            for (const chest of chests) {
                drawChest(chest);
            }

            drawCoins();
            drawSparkles();
        };

        const tick = (now) => {
            if (!running) return;
            const dt = Math.min(0.05, (now - last) / 1000);
            last = now;
            update(dt);
            drawFrame();
            rafId = requestAnimationFrame(tick);
        };

        const cleanup = () => {
            if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
            coins.length = 0;
            sparkles.length = 0;
            if (openResolve) { openResolve(); openResolve = null; }
        };

        // 顯示寶箱
        const showChests = (count) => {
            cleanup();
            if (!resizeCanvas()) return;

            const chestCount = Math.min(Math.max(1, count), 9);
            const layout = getGridLayout(chestCount);

            chests = [];
            for (let i = 0; i < chestCount; i++) {
                chests.push(createChest(i, layout));
            }

            running = true;
            phase = 'idle';
            openedCount = 0;
            last = performance.now();
            rafId = requestAnimationFrame(tick);
        };

        // 設定中獎者（可一次設定多個）
        const setWinners = (names) => {
            const nameList = Array.isArray(names) ? names : [names];
            for (let i = 0; i < chests.length && i < nameList.length; i++) {
                chests[i].winner = nameList[i];
            }
        };

        // 開始開啟所有寶箱
        const startOpening = () => {
            phase = 'opening';
            openedCount = 0;
            for (const chest of chests) {
                if (chest.winner) {
                    chest.phase = 'shaking';
                    chest.shakeTime = 0;
                    chest.openDelay = rand(0, 0.35);
                }
            }
        };

        // 等待所有寶箱開啟
        const waitForAllOpened = () => {
            return new Promise((resolve) => {
                if (openedCount >= chests.filter(c => c.winner).length) {
                    resolve();
                    return;
                }
                openResolve = resolve;
            });
        };

        // 開啟單個寶箱（返回 Promise，用於逐一開啟模式）
        const openSingleChest = (index) => {
            return new Promise((resolve) => {
                const chest = chests[index];
                if (!chest || !chest.winner) {
                    resolve();
                    return;
                }

                // 設定該寶箱的 resolve callback
                chest.openResolve = resolve;
                chest.phase = 'shaking';
                chest.shakeTime = 0;
                chest.openDelay = 0; // 立即開啟，不要隨機延遲
            });
        };

        const stop = () => {
            running = false;
            phase = 'idle';
            chests = [];
            cleanup();
        };

        const setHoldSeconds = (seconds) => {
            holdSeconds = Math.max(2, seconds || 5);
        };

        const ensureReady = () => {
            if (!ctx || canvasRect.width === 0) {
                resizeCanvas();
            }
        };

        // 單寶箱模式相容 API
        const start = () => showChests(1);
        const setWinner = (name) => {
            setWinners([name]);
            startOpening();
        };
        const waitForReveal = () => waitForAllOpened();
        const prepareNext = () => {
            for (const chest of chests) {
                chest.winner = null;
                chest.phase = 'closed';
                chest.lidAngle = 0;
                chest.shakeOffset = 0;
                chest.shakeTime = 0;
                chest.glowIntensity = 0;
                chest.opened = false;
            }
            openedCount = 0;
            phase = 'idle';
            coins.length = 0;
            sparkles.length = 0;
        };

        return {
            // 多寶箱 API
            showChests,
            setWinners,
            startOpening,
            waitForAllOpened,
            openSingleChest,
            // 單寶箱相容 API
            start,
            stop,
            setWinner,
            waitForReveal,
            prepareNext,
            setHoldSeconds,
            ensureReady,
            resize: () => {
                if (!running || chests.length === 0) return;
                if (!resizeCanvas()) return;
                const layout = getGridLayout(chests.length);
                for (let i = 0; i < chests.length; i++) {
                    const { cols, cardW, cardH, startX, startY } = layout;
                    const row = Math.floor(i / cols);
                    const col = i % cols;
                    chests[i].x = startX + col * (cardW + GAP);
                    chests[i].y = startY + row * (cardH + GAP);
                    chests[i].w = cardW;
                    chests[i].h = cardH;
                }
                drawFrame();
            },
        };
    })();

    // ========== 大寶箱模組 ==========
    const bigTreasureChest = (() => {
        let running = false;
        let phase = 'idle';
        let winner = null;
        let holdSeconds = 5;
        let phaseTime = 0;
        let openProgress = 0;
        let burstDone = false;
        let revealResolve = null;
        let spawnTimer = 0;

        const HERO_HOLD_SECONDS = 3.5;

        let ctx = null;
        let canvasRect = { width: 0, height: 0 };
        let dpr = 1;
        let rafId = null;
        let last = 0;

        const coins = [];
        const ingots = [];
        const gourds = [];
        const sparkles = [];
        const heroToken = {
            active: false,
            phase: 'idle',
            t: 0,
            total: 0,
            x: 0,
            y: 0,
            startX: 0,
            startY: 0,
            targetX: 0,
            targetY: 0,
            size: 0,
            scale: 0.7,
            opacity: 0,
            rotation: 0,
            floatPhase: 0,
            name: '',
            variant: 'ingot',
        };

        const chest = {
            x: 0,
            y: 0,
            w: 0,
            h: 0,
        };

        const resizeCanvas = () => {
            if (!lottoCanvasEl) return false;
            const wrapperRect = lottoWrapEl?.getBoundingClientRect();
            const rect = wrapperRect && wrapperRect.width && wrapperRect.height
                ? wrapperRect
                : lottoCanvasEl.getBoundingClientRect();
            if (!rect.width || !rect.height) return false;

            dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
            lottoCanvasEl.width = Math.max(1, Math.floor(rect.width * dpr));
            lottoCanvasEl.height = Math.max(1, Math.floor(rect.height * dpr));
            ctx = lottoCanvasEl.getContext('2d');
            if (ctx) {
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }
            canvasRect = { width: rect.width, height: rect.height };
            updateLayout();
            return true;
        };

        const updateLayout = () => {
            const width = canvasRect.width;
            const height = canvasRect.height;
            const maxW = Math.min(width * 0.82, 680);
            const chestW = clamp(maxW, 260, width * 0.95);
            const chestH = chestW * 0.6;
            chest.w = chestW;
            chest.h = chestH;
            chest.x = (width - chestW) / 2;
            chest.y = height * 0.6 - chestH / 2;
        };

        const roundRect = (context, x, y, w, h, r) => {
            context.beginPath();
            context.moveTo(x + r, y);
            context.arcTo(x + w, y, x + w, y + h, r);
            context.arcTo(x + w, y + h, x, y + h, r);
            context.arcTo(x, y + h, x, y, r);
            context.arcTo(x, y, x + w, y, r);
            context.closePath();
        };

        const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

        const getEmitPoint = () => {
            const cx = chest.x + chest.w / 2;
            const cy = chest.y + chest.h * 0.18;
            const spreadX = chest.w * 0.08;
            const spreadY = chest.h * 0.06;
            return {
                x: cx + rand(-spreadX, spreadX),
                y: cy + rand(-spreadY, spreadY * 0.5),
            };
        };

        const getEmitVelocity = (speedMin, speedMax, boostMin = 700, boostMax = 1100) => {
            const angle = rand(-Math.PI * 0.82, -Math.PI * 0.18);
            const speed = rand(speedMin, speedMax);
            const boost = rand(boostMin, boostMax);
            return {
                angle,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                ax: Math.cos(angle) * boost,
                ay: Math.sin(angle) * boost,
                boostTime: rand(0.12, 0.2),
            };
        };

        const getTiming = () => {
            const total = Math.max(3, holdSeconds || 5);
            const open = clamp(total * 0.32, 0.9, 1.8);
            const fly = clamp(total * 0.2, 0.6, 1.0);
            const fade = 0.6;
            return { open, fly, fade };
        };

        const spawnBurst = () => {
            const coinCount = 60;
            const ingotCount = 14;
            const gourdCount = 4;
            for (let i = 0; i < coinCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(360, 780, 900, 1400);
                coins.push({
                    x,
                    y,
                    vx,
                    vy,
                    size: rand(14, 24),
                    rotation: rand(0, TAU),
                    rotSpeed: rand(-10, 10),
                    life: rand(1.6, 2.4),
                    t: 0,
                    ax,
                    ay,
                    boostTime,
                });
            }
            for (let i = 0; i < ingotCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(300, 640, 800, 1200);
                ingots.push({
                    x,
                    y,
                    vx,
                    vy,
                    w: rand(22, 36),
                    h: rand(14, 24),
                    rotation: rand(-0.6, 0.6),
                    rotSpeed: rand(-6, 6),
                    life: rand(1.7, 2.5),
                    t: 0,
                    ax,
                    ay,
                    boostTime,
                });
            }
            for (let i = 0; i < gourdCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(260, 520, 720, 1100);
                gourds.push({
                    x,
                    y,
                    vx,
                    vy,
                    size: rand(16, 26),
                    rotation: rand(-0.4, 0.4),
                    rotSpeed: rand(-4, 4),
                    life: rand(1.6, 2.4),
                    t: 0,
                    ax,
                    ay,
                    boostTime,
                });
            }
            const sparkleCount = 26;
            for (let i = 0; i < sparkleCount; i++) {
                const { x, y } = getEmitPoint();
                const angle = rand(-Math.PI * 0.9, -Math.PI * 0.1);
                const speed = rand(160, 300);
                sparkles.push({
                    x,
                    y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 40,
                    size: rand(3, 7),
                    life: rand(0.6, 1.1),
                    t: 0,
                });
            }
        };

        const spawnStream = (dt) => {
            spawnTimer += dt;
            const interval = 0.06;
            const maxParticles = 180;

            while (spawnTimer >= interval) {
                spawnTimer -= interval;
                const total = coins.length + ingots.length + gourds.length;
                if (total >= maxParticles) break;

                const roll = Math.random();
                if (roll < 0.72) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(220, 420, 520, 900);
                    coins.push({
                        x,
                        y,
                        vx,
                        vy,
                        size: rand(10, 16),
                        rotation: rand(0, TAU),
                        rotSpeed: rand(-10, 10),
                        life: rand(1.1, 1.9),
                        t: 0,
                        ax,
                        ay,
                        boostTime,
                    });
                } else if (roll < 0.93) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(200, 380, 480, 820);
                    ingots.push({
                        x,
                        y,
                        vx,
                        vy,
                        w: rand(16, 24),
                        h: rand(10, 16),
                        rotation: rand(-0.6, 0.6),
                        rotSpeed: rand(-6, 6),
                        life: rand(1.2, 2.0),
                        t: 0,
                        ax,
                        ay,
                        boostTime,
                    });
                } else {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(200, 360, 460, 760);
                    gourds.push({
                        x,
                        y,
                        vx,
                        vy,
                        size: rand(12, 20),
                        rotation: rand(-0.4, 0.4),
                        rotSpeed: rand(-4, 4),
                        life: rand(1.3, 2.1),
                        t: 0,
                        ax,
                        ay,
                        boostTime,
                    });
                }
            }
        };

        const spawnHeroToken = () => {
            if (!winner) return;
            heroToken.active = true;
            heroToken.phase = 'fly';
            heroToken.t = 0;
            heroToken.total = 0;
            heroToken.name = winner;
            heroToken.variant = Math.random() < 0.65 ? 'ingot' : 'coin';
            heroToken.rotation = rand(-0.12, 0.12);
            heroToken.floatPhase = rand(0, TAU);
            heroToken.size = clamp(chest.w * 0.36, 170, 260);
            heroToken.startX = chest.x + chest.w / 2 + rand(-24, 24);
            heroToken.startY = chest.y + chest.h * 0.12;
            heroToken.targetX = canvasRect.width * 0.5;
            heroToken.targetY = canvasRect.height * 0.42;
            heroToken.x = heroToken.startX;
            heroToken.y = heroToken.startY;
            heroToken.opacity = 0;
            heroToken.scale = 0.65;
        };

        const updateHero = (dt, timing) => {
            if (!heroToken.active) return false;
            heroToken.total += dt;
            heroToken.t += dt;

            if (heroToken.phase === 'fly') {
                const progress = clamp(heroToken.t / timing.fly, 0, 1);
                const eased = easeOutCubic(progress);
                heroToken.x = lerp(heroToken.startX, heroToken.targetX, eased);
                heroToken.y = lerp(heroToken.startY, heroToken.targetY, eased);
                heroToken.scale = lerp(0.65, 1, easeOutCubic(progress));
                heroToken.opacity = Math.min(1, progress * 1.2);
                if (progress >= 1) {
                    heroToken.phase = 'hold';
                    heroToken.t = 0;
                }
            } else if (heroToken.phase === 'hold') {
                heroToken.opacity = 1;
                heroToken.scale = 1 + Math.sin(heroToken.total * 2.2) * 0.02;
                if (heroToken.t >= HERO_HOLD_SECONDS) {
                    heroToken.phase = 'fade';
                    heroToken.t = 0;
                }
            } else if (heroToken.phase === 'fade') {
                const progress = clamp(heroToken.t / timing.fade, 0, 1);
                heroToken.opacity = 1 - progress;
                heroToken.scale = 1 - progress * 0.08;
                if (progress >= 1) {
                    heroToken.active = false;
                    heroToken.phase = 'idle';
                    return true;
                }
            }
            return false;
        };

        const updateParticles = (dt) => {
            for (let i = coins.length - 1; i >= 0; i--) {
                const c = coins[i];
                c.t += dt;
                if (c.boostTime && c.t <= c.boostTime) {
                    c.vx += c.ax * dt;
                    c.vy += c.ay * dt;
                }
                if (c.t >= c.life) { coins.splice(i, 1); continue; }
                c.vy += 520 * dt;
                c.x += c.vx * dt;
                c.y += c.vy * dt;
                c.rotation += c.rotSpeed * dt;
                c.vx *= 0.985;
                c.vy *= 0.985;
            }

            for (let i = ingots.length - 1; i >= 0; i--) {
                const g = ingots[i];
                g.t += dt;
                if (g.boostTime && g.t <= g.boostTime) {
                    g.vx += g.ax * dt;
                    g.vy += g.ay * dt;
                }
                if (g.t >= g.life) { ingots.splice(i, 1); continue; }
                g.vy += 520 * dt;
                g.x += g.vx * dt;
                g.y += g.vy * dt;
                g.rotation += g.rotSpeed * dt;
                g.vx *= 0.985;
                g.vy *= 0.985;
            }

            for (let i = gourds.length - 1; i >= 0; i--) {
                const g = gourds[i];
                g.t += dt;
                if (g.boostTime && g.t <= g.boostTime) {
                    g.vx += g.ax * dt;
                    g.vy += g.ay * dt;
                }
                if (g.t >= g.life) { gourds.splice(i, 1); continue; }
                g.vy += 520 * dt;
                g.x += g.vx * dt;
                g.y += g.vy * dt;
                g.rotation += g.rotSpeed * dt;
                g.vx *= 0.985;
                g.vy *= 0.985;
            }

            for (let i = sparkles.length - 1; i >= 0; i--) {
                const s = sparkles[i];
                s.t += dt;
                if (s.t >= s.life) { sparkles.splice(i, 1); continue; }
                s.vy += 80 * dt;
                s.x += s.vx * dt;
                s.y += s.vy * dt;
                s.vx *= 0.96;
            }
        };

        const drawBackground = () => {
            const { width, height } = canvasRect;
            const grad = ctx.createRadialGradient(width * 0.5, height * 0.35, 0, width * 0.5, height * 0.5, Math.max(width, height) * 0.8);
            grad.addColorStop(0, 'rgba(120, 45, 20, 0.18)');
            grad.addColorStop(0.5, 'rgba(60, 30, 10, 0.08)');
            grad.addColorStop(1, 'rgba(0, 0, 0, 0)');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, width, height);
        };

        const drawChest = () => {
            const { x, y, w, h } = chest;
            const bodyH = h * 0.55;
            const lidH = h * 0.35;
            const lidAngle = -110 * openProgress;

            ctx.save();
            ctx.translate(x, y + h * 0.1);

            if (openProgress > 0.05) {
                const glow = ctx.createRadialGradient(w * 0.5, h * 0.15, 0, w * 0.5, h * 0.15, w * 0.9);
                glow.addColorStop(0, `rgba(255, 215, 0, ${0.6 * openProgress})`);
                glow.addColorStop(0.5, `rgba(255, 170, 0, ${0.35 * openProgress})`);
                glow.addColorStop(1, 'rgba(255, 170, 0, 0)');
                ctx.fillStyle = glow;
                ctx.fillRect(-w * 0.2, -h * 0.2, w * 1.4, h * 1.1);
            }

            const bodyGrad = ctx.createLinearGradient(0, bodyH * 0.2, 0, bodyH * 1.1);
            bodyGrad.addColorStop(0, '#9a4a1f');
            bodyGrad.addColorStop(0.5, '#b5652f');
            bodyGrad.addColorStop(1, '#6b3416');
            ctx.fillStyle = bodyGrad;
            roundRect(ctx, w * 0.08, h * 0.25, w * 0.84, bodyH, 18);
            ctx.fill();

            ctx.strokeStyle = '#f4c430';
            ctx.lineWidth = 5;
            roundRect(ctx, w * 0.08, h * 0.25, w * 0.84, bodyH, 18);
            ctx.stroke();

            const bandGrad = ctx.createLinearGradient(0, h * 0.52, 0, h * 0.6);
            bandGrad.addColorStop(0, '#d4a017');
            bandGrad.addColorStop(0.5, '#ffd24d');
            bandGrad.addColorStop(1, '#b8860b');
            ctx.fillStyle = bandGrad;
            ctx.fillRect(w * 0.12, h * 0.55, w * 0.76, 10);

            ctx.save();
            ctx.translate(w * 0.5, h * 0.25);
            ctx.rotate((lidAngle * Math.PI) / 180);
            const lidGrad = ctx.createLinearGradient(0, -lidH, 0, 0);
            lidGrad.addColorStop(0, '#b5652f');
            lidGrad.addColorStop(0.5, '#8b4513');
            lidGrad.addColorStop(1, '#6b3416');
            ctx.fillStyle = lidGrad;
            ctx.beginPath();
            ctx.moveTo(-w * 0.42, 0);
            ctx.lineTo(-w * 0.42, -lidH * 0.5);
            ctx.quadraticCurveTo(-w * 0.42, -lidH, -w * 0.2, -lidH);
            ctx.lineTo(w * 0.2, -lidH);
            ctx.quadraticCurveTo(w * 0.42, -lidH, w * 0.42, -lidH * 0.5);
            ctx.lineTo(w * 0.42, 0);
            ctx.closePath();
            ctx.fill();

            ctx.strokeStyle = '#f4c430';
            ctx.lineWidth = 5;
            ctx.stroke();
            ctx.restore();

            ctx.restore();
        };

        const drawCoin = (coin) => {
            const alpha = 1 - coin.t / coin.life;
            ctx.save();
            ctx.translate(coin.x, coin.y);
            ctx.rotate(coin.rotation);
            ctx.globalAlpha = alpha;
            const size = coin.size;
            const squeeze = Math.abs(Math.sin(coin.rotation * 2));
            const w = size;
            const h = size * (0.35 + squeeze * 0.65);
            const grad = ctx.createRadialGradient(-2, -2, 0, 0, 0, size);
            grad.addColorStop(0, '#fff1a8');
            grad.addColorStop(0.4, '#ffd24d');
            grad.addColorStop(1, '#b8860b');
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.ellipse(0, 0, w, h, 0, 0, TAU);
            ctx.fill();
            ctx.restore();
        };

        const drawIngot = (ingot) => {
            const alpha = 1 - ingot.t / ingot.life;
            ctx.save();
            ctx.translate(ingot.x, ingot.y);
            ctx.rotate(ingot.rotation);
            ctx.globalAlpha = alpha;
            const grad = ctx.createLinearGradient(-ingot.w / 2, -ingot.h / 2, ingot.w / 2, ingot.h / 2);
            grad.addColorStop(0, '#ffe08a');
            grad.addColorStop(0.5, '#ffd24d');
            grad.addColorStop(1, '#c58b00');
            ctx.fillStyle = grad;
            roundRect(ctx, -ingot.w / 2, -ingot.h / 2, ingot.w, ingot.h, ingot.h * 0.4);
            ctx.fill();
            ctx.strokeStyle = 'rgba(160, 110, 0, 0.6)';
            ctx.lineWidth = 2;
            ctx.stroke();
            ctx.restore();
        };

        const drawGourd = (gourd) => {
            const alpha = 1 - gourd.t / gourd.life;
            ctx.save();
            ctx.translate(gourd.x, gourd.y);
            ctx.rotate(gourd.rotation);
            ctx.globalAlpha = alpha;

            const topR = gourd.size * 0.45;
            const bottomR = gourd.size * 0.6;
            const grad = ctx.createLinearGradient(0, -bottomR, 0, bottomR);
            grad.addColorStop(0, '#fff1a8');
            grad.addColorStop(0.5, '#ffd24d');
            grad.addColorStop(1, '#b8860b');
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.arc(0, -topR * 0.6, topR, 0, TAU);
            ctx.arc(0, bottomR * 0.5, bottomR, 0, TAU);
            ctx.fill();

            ctx.strokeStyle = 'rgba(160, 110, 0, 0.5)';
            ctx.lineWidth = 1.6;
            ctx.stroke();

            ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
            ctx.beginPath();
            ctx.ellipse(-topR * 0.2, -topR * 0.7, topR * 0.25, topR * 0.4, -0.4, 0, TAU);
            ctx.fill();
            ctx.restore();
        };

        const drawSparkles = () => {
            for (const s of sparkles) {
                const alpha = 1 - s.t / s.life;
                ctx.save();
                ctx.globalAlpha = alpha;
                const grad = ctx.createRadialGradient(s.x, s.y, 0, s.x, s.y, s.size);
                grad.addColorStop(0, 'rgba(255, 236, 166, 0.9)');
                grad.addColorStop(0.5, 'rgba(255, 200, 80, 0.6)');
                grad.addColorStop(1, 'rgba(255, 200, 80, 0)');
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.size, 0, TAU);
                ctx.fill();
                ctx.restore();
            }
        };

        const getFittedFontSize = (text, maxWidth, maxSize, minSize = 16) => {
            let size = maxSize;
            while (size > minSize) {
                ctx.font = `700 ${size}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
                if (ctx.measureText(text).width <= maxWidth) break;
                size -= 2;
            }
            return size;
        };

        const drawHeroToken = () => {
            if (!heroToken.active || !ctx) return;
            const displayName = heroToken.name || '???';
            const floatOffset = Math.sin(heroToken.total * 2.2 + heroToken.floatPhase) * Math.min(12, heroToken.size * 0.08);
            const baseX = heroToken.x;
            const baseY = heroToken.y + floatOffset;
            const tokenW = heroToken.size * 1.35;
            const tokenH = heroToken.size * 0.9;

            ctx.save();
            ctx.translate(baseX, baseY);
            ctx.rotate(heroToken.rotation);
            ctx.scale(heroToken.scale, heroToken.scale);
            ctx.globalAlpha = heroToken.opacity;

            if (heroToken.variant === 'coin') {
                const grad = ctx.createRadialGradient(-tokenW * 0.2, -tokenH * 0.2, 0, 0, 0, tokenW * 0.7);
                grad.addColorStop(0, '#fff5b5');
                grad.addColorStop(0.45, '#ffd24d');
                grad.addColorStop(1, '#b8860b');
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.ellipse(0, 0, tokenW * 0.5, tokenH * 0.5, 0, 0, TAU);
                ctx.fill();
                ctx.strokeStyle = '#f7d56b';
                ctx.lineWidth = 6;
                ctx.stroke();
                ctx.strokeStyle = 'rgba(160, 110, 0, 0.6)';
                ctx.lineWidth = 3;
                ctx.stroke();
            } else {
                const grad = ctx.createLinearGradient(0, -tokenH * 0.6, 0, tokenH * 0.6);
                grad.addColorStop(0, '#fff1a8');
                grad.addColorStop(0.45, '#ffd24d');
                grad.addColorStop(1, '#b8860b');
                ctx.fillStyle = grad;
                roundRect(ctx, -tokenW / 2, -tokenH / 2, tokenW, tokenH, tokenH * 0.45);
                ctx.fill();
                ctx.strokeStyle = 'rgba(160, 110, 0, 0.7)';
                ctx.lineWidth = 5;
                ctx.stroke();

                ctx.fillStyle = 'rgba(255, 248, 200, 0.9)';
                ctx.beginPath();
                ctx.ellipse(0, -tokenH * 0.15, tokenW * 0.28, tokenH * 0.18, 0, 0, TAU);
                ctx.fill();
            }

            ctx.restore();

            ctx.save();
            ctx.translate(baseX, baseY);
            ctx.scale(heroToken.scale, heroToken.scale);
            ctx.globalAlpha = heroToken.opacity;
            const scaleGuard = Math.max(0.8, heroToken.scale);
            const maxTextWidth = (tokenW * 0.75) / scaleGuard;
            const fontSize = getFittedFontSize(displayName, maxTextWidth, (tokenH * 0.35) / scaleGuard, 16);
            ctx.font = `700 ${fontSize}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#6b1f06';
            ctx.shadowColor = 'rgba(255, 215, 120, 0.9)';
            ctx.shadowBlur = 14;
            ctx.fillText(displayName, 0, 0);
            ctx.restore();
        };

        const drawFrame = () => {
            if (!ctx) return;
            const { width, height } = canvasRect;
            ctx.clearRect(0, 0, width, height);
            drawBackground();
            drawChest();
            coins.forEach(drawCoin);
            ingots.forEach(drawIngot);
            gourds.forEach(drawGourd);
            drawHeroToken();
            drawSparkles();
        };

        const update = (dt) => {
            const timing = getTiming();
            phaseTime += dt;

            if (phase === 'opening') {
                openProgress = clamp(phaseTime / timing.open, 0, 1);
                spawnStream(dt);
                if (!burstDone && openProgress > 0.35) {
                    spawnBurst();
                    sfx.playCoinDrop();
                    burstDone = true;
                }
                if (phaseTime >= timing.open) {
                    phase = 'hero';
                    phaseTime = 0;
                    spawnHeroToken();
                }
            } else if (phase === 'hero') {
                openProgress = 1;
                spawnStream(dt);
                const done = updateHero(dt, timing);
                if (done) {
                    if (revealResolve) {
                        revealResolve();
                        revealResolve = null;
                    }
                    phase = 'hold';
                    phaseTime = 0;
                }
            } else if (phase === 'idle') {
                openProgress = 0;
            } else if (phase === 'hold') {
                openProgress = 1;
            }

            updateParticles(dt);
        };

        const tick = (now) => {
            if (!running) return;
            const dt = Math.min(0.05, (now - last) / 1000);
            last = now;
            update(dt);
            drawFrame();
            rafId = requestAnimationFrame(tick);
        };

        const cleanup = () => {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
            coins.length = 0;
            ingots.length = 0;
            gourds.length = 0;
            sparkles.length = 0;
            spawnTimer = 0;
            heroToken.active = false;
            heroToken.phase = 'idle';
            if (revealResolve) {
                revealResolve();
                revealResolve = null;
            }
        };

        const showChest = () => {
            if (running) return;
            cleanup();
            if (!resizeCanvas()) return;
            running = true;
            phase = 'idle';
            phaseTime = 0;
            openProgress = 0;
            burstDone = false;
            spawnTimer = 0;
            heroToken.active = false;
            heroToken.phase = 'idle';
            winner = null;
            last = performance.now();
            rafId = requestAnimationFrame(tick);
        };

        const startOpening = () => {
            if (!running) return;
            phase = 'opening';
            phaseTime = 0;
            openProgress = 0;
            burstDone = false;
            spawnTimer = 0;
            heroToken.active = false;
            heroToken.phase = 'idle';
            sfx.playChestOpen();
        };

        const setWinner = (name) => {
            if (!running) {
                showChest();
            }
            winner = name || '???';
            startOpening();
        };

        const waitForReveal = () => new Promise((resolve) => {
            revealResolve = resolve;
        });

        const prepareNext = () => {
            phase = 'idle';
            phaseTime = 0;
            openProgress = 0;
            burstDone = false;
            winner = null;
            coins.length = 0;
            ingots.length = 0;
            gourds.length = 0;
            sparkles.length = 0;
            spawnTimer = 0;
            heroToken.active = false;
            heroToken.phase = 'idle';
        };

        const stop = () => {
            running = false;
            phase = 'idle';
            winner = null;
            cleanup();
        };

        const setHoldSeconds = (seconds) => {
            holdSeconds = Math.max(3, seconds || 5);
        };

        const ensureReady = () => {
            if (!ctx || canvasRect.width === 0) {
                resizeCanvas();
            }
        };

        return {
            showChest,
            start: showChest,
            stop,
            setWinner,
            waitForReveal,
            prepareNext,
            setHoldSeconds,
            ensureReady,
            resize: () => {
                if (!running) return;
                if (resizeCanvas()) {
                    drawFrame();
                }
            },
        };
    })();


    // [已刪除舊版 lotto/lotto3 相關函數]
    // 舊版樂透動畫已整合至 lottoAir 模組

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

    const draw = async () => {
        if (!state.isOpen || !state.currentPrize || !config.drawUrl || state.isPrizesPreviewMode || state.isSwitching) {
            sfx.playError();
            return;
        }

        // 檢查是否有可抽人員
        if (!state.eligibleNames || state.eligibleNames.length === 0) {
            console.warn('[lottery] no eligible employees to draw');
            sfx.playError();
            return;
        }

        if (state.isDrawing) {
            return;
        }

        sfx.playButtonClick();
        state.isDrawing = true;

        // 抽獎開始前清空中獎名單（避免顯示上一次結果）
        // one_by_one 模式且已有中獎者時才保留
        if (state.currentPrize?.drawMode !== 'one_by_one' || !state.winners?.length) {
            state.winners = [];
        }

        render();

        const style = state.currentPrize?.animationStyle ?? 'lotto_air';
        const useLottoAir = isLottoAirStyle(style);
        const useRedPacket = isRedPacketStyle(style);
        const useScratchCard = isScratchCardStyle(style);
        const useTreasureChest = isTreasureChestStyle(style);
        const useBigTreasureChest = isBigTreasureChestStyle(style);

        // 用於追蹤持續播放的音效
        let ballRumbleSound = null;

        try {
            startDrawAudio();
            lottoTimerLabel = '';

            // 紅包雨：先啟動飄落動畫
            if (useRedPacket) {
                redPacketRain.ensureReady();
                redPacketRain.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
                redPacketRain.start();
            }

            // 刮刮樂：卡片已經在 render() 時顯示，這裡只設定秒數
            if (useScratchCard) {
                scratchCard.ensureReady();
                scratchCard.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
            }

            // 寶箱：寶箱已經在 render() 時顯示，這裡只設定秒數
            if (useTreasureChest) {
                treasureChest.ensureReady();
                treasureChest.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
            }

            if (useBigTreasureChest) {
                bigTreasureChest.ensureReady();
                bigTreasureChest.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
            }

            const response = await fetch(config.drawUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                if (useRedPacket) {
                    redPacketRain.stop();
                }
                if (useScratchCard) {
                    scratchCard.stop();
                }
                if (useTreasureChest) {
                    treasureChest.stop();
                }
                if (useBigTreasureChest) {
                    bigTreasureChest.stop();
                }
                state.isDrawing = false;
                render();
                return;
            }

            const data = await response.json();
            const winners = data?.winners ?? [];

            if (winners.length === 0) {
                console.warn('[lottery] no winners returned from /draw');
                if (useRedPacket) {
                    redPacketRain.stop();
                }
                if (useScratchCard) {
                    scratchCard.stop();
                }
                if (useTreasureChest) {
                    treasureChest.stop();
                }
                if (useBigTreasureChest) {
                    bigTreasureChest.stop();
                }
                state.isDrawing = false;
                sfx.playError();
                // 可抽人數已用盡但名額未滿，若有中獎者則進入 resultMode
                if (state.winners?.length > 0 && !isPrizeCompleted()) {
                    setTimeout(() => resultMode.show(), 2000);
                }
                render();
                return;
            }

            if (useLottoAir) {
                lottoAir.ensureReady();
                lottoAir.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
                ballRumbleSound = sfx.playBallRumble();
                if (state.currentPrize?.drawMode === 'one_by_one') {
                    if (state.eligibleNames?.length) {
                        lottoAir.ensureCount(state.eligibleNames.length);
                    }
                    lottoAir.setWinners([winners[0]?.employee_name ?? randomLabel()], {
                        resetBalls: false,
                        resetPicked: false,
                    });
                    lottoAir.startDraw();
                    // eslint-disable-next-line no-await-in-loop
                    await lottoAir.waitForNextPick();
                    state.winners = [...state.winners, winners[0]];
                    render();

                    const lastItem = winnerListEl?.lastElementChild;
                    if (lastItem) {
                        animateListItem(lastItem);
                    }
                } else {
                    lottoAir.setWinners(winners.map((winner) => winner.employee_name ?? randomLabel()));
                    lottoAir.startDraw();
                    for (const winner of winners) {
                        // eslint-disable-next-line no-await-in-loop
                        await lottoAir.waitForNextPick();
                        state.winners = [...state.winners, winner];
                        render();

                        const lastItem = winnerListEl?.lastElementChild;
                        if (lastItem) {
                            animateListItem(lastItem);
                        }

                        // eslint-disable-next-line no-await-in-loop
                        await new Promise((r) => setTimeout(r, 220));
                    }
                }
            } else if (useRedPacket) {
                // 紅包雨：設定中獎者並等待揭曉
                if (state.currentPrize?.drawMode === 'one_by_one') {
                    // 逐一抽出模式
                    redPacketRain.setWinner(winners[0]?.employee_name ?? '???');
                    await redPacketRain.waitForReveal();
                    state.winners = [...state.winners, winners[0]];
                    render();

                    const lastItem = winnerListEl?.lastElementChild;
                    if (lastItem) {
                        animateListItem(lastItem);
                    }

                    // 等待一下再停止動畫
                    await new Promise((r) => setTimeout(r, 1500));
                    redPacketRain.stop();
                } else {
                    // 一次全抽模式：紅包雨持續，依序揭曉每個中獎者
                    for (let i = 0; i < winners.length; i++) {
                        const winner = winners[i];
                        redPacketRain.setWinner(winner.employee_name ?? '???');
                        // eslint-disable-next-line no-await-in-loop
                        await redPacketRain.waitForReveal();
                        state.winners = [...state.winners, winner];
                        render();

                        const lastItem = winnerListEl?.lastElementChild;
                        if (lastItem) {
                            animateListItem(lastItem);
                        }

                        // 如果還有下一位中獎者，準備下一輪（不停止雨）
                        if (i < winners.length - 1) {
                            // eslint-disable-next-line no-await-in-loop
                            await new Promise((r) => setTimeout(r, 800));
                            redPacketRain.prepareNext();
                            // 等待紅包補充
                            // eslint-disable-next-line no-await-in-loop
                            await new Promise((r) => setTimeout(r, 600));
                        }
                    }

                    // 最後一位揭曉後等待一下再停止動畫
                    await new Promise((r) => setTimeout(r, 1500));
                    redPacketRain.stop();
                }
            } else if (useScratchCard) {
                // 刮刮樂：設定中獎者並等待刮除揭曉
                if (state.currentPrize?.drawMode === 'one_by_one') {
                    // 逐一抽出模式：單張卡片
                    scratchCard.setWinner(winners[0]?.employee_name ?? '???');
                    await scratchCard.waitForReveal();
                    state.winners = [...state.winners, winners[0]];
                    render();

                    const lastItem = winnerListEl?.lastElementChild;
                    if (lastItem) {
                        animateListItem(lastItem);
                    }

                    // 等待一下再準備下一張
                    await new Promise((r) => setTimeout(r, 1200));
                    scratchCard.prepareNext();
                } else {
                    // 一次全抽模式：分批顯示，逐一刮開
                    let processedCount = 0;

                    while (processedCount < winners.length) {
                        // 計算本批數量（最多 9 張）
                        const batchStart = processedCount;
                        const batchCount = Math.min(winners.length - processedCount, 9);
                        const batchWinners = winners.slice(batchStart, batchStart + batchCount);

                        // 顯示本批卡片
                        scratchCard.showCards(batchCount);
                        scratchCard.setWinners(batchWinners.map((w) => w.employee_name ?? '???'));

                        // 建立隨機刮開順序 (Fisher-Yates)
                        const scratchOrder = Array.from({ length: batchCount }, (_, i) => i);
                        for (let i = scratchOrder.length - 1; i > 0; i--) {
                            const j = Math.floor(Math.random() * (i + 1));
                            [scratchOrder[i], scratchOrder[j]] = [scratchOrder[j], scratchOrder[i]];
                        }

                        // 逐一刮開每張卡片（隨機順序）
                        for (let i = 0; i < batchCount; i++) {
                            const cardIndex = scratchOrder[i];
                            // eslint-disable-next-line no-await-in-loop
                            await scratchCard.scratchSingleCard(cardIndex);

                            // 加入中獎者列表
                            state.winners = [...state.winners, batchWinners[cardIndex]];
                            render();

                            const lastItem = winnerListEl?.lastElementChild;
                            if (lastItem) {
                                animateListItem(lastItem);
                            }

                            // 間隔 1.5 秒（最後一個不用等）
                            if (i < batchCount - 1) {
                                // eslint-disable-next-line no-await-in-loop
                                await new Promise((r) => setTimeout(r, 1500));
                            }
                        }

                        processedCount += batchCount;

                        // 如果還有下一批，等待後重新顯示
                        if (processedCount < winners.length) {
                            // eslint-disable-next-line no-await-in-loop
                            await new Promise((r) => setTimeout(r, 1500));
                        }
                    }

                    // 最後等待一下再重置
                    await new Promise((r) => setTimeout(r, 1500));
                    scratchCard.prepareNext();
                }
            } else if (useTreasureChest) {
                // 寶箱：設定中獎者並等待開啟揭曉
                if (state.currentPrize?.drawMode === 'one_by_one') {
                    // 逐一抽出模式：單個寶箱
                    treasureChest.setWinner(winners[0]?.employee_name ?? '???');
                    await treasureChest.waitForReveal();
                    state.winners = [...state.winners, winners[0]];
                    render();

                    const lastItem = winnerListEl?.lastElementChild;
                    if (lastItem) {
                        animateListItem(lastItem);
                    }

                    // 等待一下再準備下一個
                    await new Promise((r) => setTimeout(r, 1500));
                    treasureChest.prepareNext();
                } else {
                    // 一次全抽模式：分批顯示，逐一開啟
                    let processedCount = 0;

                    while (processedCount < winners.length) {
                        // 計算本批數量（最多 9 個）
                        const batchStart = processedCount;
                        const batchCount = Math.min(winners.length - processedCount, 9);
                        const batchWinners = winners.slice(batchStart, batchStart + batchCount);

                        // 顯示本批寶箱
                        treasureChest.showChests(batchCount);
                        treasureChest.setWinners(batchWinners.map((w) => w.employee_name ?? '???'));

                        // 建立隨機開啟順序
                        const openOrder = Array.from({ length: batchCount }, (_, i) => i);
                        for (let i = openOrder.length - 1; i > 0; i--) {
                            const j = Math.floor(Math.random() * (i + 1));
                            [openOrder[i], openOrder[j]] = [openOrder[j], openOrder[i]];
                        }

                        // 逐一開啟每個寶箱（隨機順序）
                        for (let i = 0; i < batchCount; i++) {
                            const chestIndex = openOrder[i];
                            // eslint-disable-next-line no-await-in-loop
                            await treasureChest.openSingleChest(chestIndex);

                            // 加入中獎者列表
                            state.winners = [...state.winners, batchWinners[chestIndex]];
                            render();

                            const lastItem = winnerListEl?.lastElementChild;
                            if (lastItem) {
                                animateListItem(lastItem);
                            }

                            // 間隔 1 秒（最後一個不用等）
                            if (i < batchCount - 1) {
                                // eslint-disable-next-line no-await-in-loop
                                await new Promise((r) => setTimeout(r, 1000));
                            }
                        }

                        processedCount += batchCount;

                        // 如果還有下一批，等待後重新顯示
                        if (processedCount < winners.length) {
                            // eslint-disable-next-line no-await-in-loop
                            await new Promise((r) => setTimeout(r, 1500));
                        }
                    }

                    // 最後等待一下再清除
                    await new Promise((r) => setTimeout(r, 1500));
                    treasureChest.prepareNext();
                }
            } else if (useBigTreasureChest) {
                const settleDelay = 380;
                const gapDelay = 480;

                if (state.currentPrize?.drawMode === 'one_by_one') {
                    bigTreasureChest.setWinner(winners[0]?.employee_name ?? '???');
                    await bigTreasureChest.waitForReveal();
                    state.winners = [...state.winners, winners[0]];
                    render();

                    const lastItem = winnerListEl?.lastElementChild;
                    if (lastItem) {
                        animateListItem(lastItem);
                    }

                    await new Promise((r) => setTimeout(r, settleDelay));
                    bigTreasureChest.prepareNext();
                } else {
                    for (let i = 0; i < winners.length; i++) {
                        const winner = winners[i];
                        bigTreasureChest.setWinner(winner.employee_name ?? '???');
                        // eslint-disable-next-line no-await-in-loop
                        await bigTreasureChest.waitForReveal();
                        state.winners = [...state.winners, winner];
                        render();

                        const lastItem = winnerListEl?.lastElementChild;
                        if (lastItem) {
                            animateListItem(lastItem);
                        }

                        if (i < winners.length - 1) {
                            // eslint-disable-next-line no-await-in-loop
                            await new Promise((r) => setTimeout(r, gapDelay));
                            bigTreasureChest.prepareNext();
                            // eslint-disable-next-line no-await-in-loop
                            await new Promise((r) => setTimeout(r, 400));
                        }
                    }

                    await new Promise((r) => setTimeout(r, settleDelay));
                    bigTreasureChest.prepareNext();
                }
            }
        } catch {
            if (useLottoAir) {
                lottoAir.slowStopMachine();
            }
            if (useRedPacket) {
                redPacketRain.stop();
            }
            if (useScratchCard) {
                scratchCard.stop();
            }
            if (useTreasureChest) {
                treasureChest.stop();
            }
            if (useBigTreasureChest) {
                bigTreasureChest.stop();
            }
            return null;
        } finally {
            // 停止持續播放的音效
            ballRumbleSound?.stop();
            stopDrawAudio();
            state.isDrawing = false;
            render();

            // 延遲檢查是否抽完，切換到結果模式
            const exhausted = (state.eligibleNames?.length === 0) && !isPrizeCompleted() && (state.winners?.length > 0);
            if (isPrizeCompleted() || exhausted) {
                sfx.playVictory();
                setTimeout(() => resultMode.show(), 2000);
            }
        }
    };

    let lastKeyDrawAt = 0;

    const shouldTriggerDraw = (event) => {
        const key = event.key;
        const code = event.code;
        return key === 'Enter' || key === ' ' || key === 'Spacebar'
            || code === 'Enter' || code === 'NumpadEnter' || code === 'Space';
    };

    const handleKeydown = (event) => {
        if (!shouldTriggerDraw(event)) return;
        if (state.isResultMode) return;

        event.preventDefault();
        const now = Date.now();
        if (now - lastKeyDrawAt < 300) return;
        lastKeyDrawAt = now;
        draw();
    };

    const handleKeyup = (event) => {
        if (!shouldTriggerDraw(event)) return;
        if (state.isResultMode) return;

        event.preventDefault();
        const now = Date.now();
        if (now - lastKeyDrawAt < 300) return;
        lastKeyDrawAt = now;
        draw();
    };

    document.addEventListener('keydown', handleKeydown);
    document.addEventListener('keyup', handleKeyup);
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('keyup', handleKeyup);

    drawButtonEl?.addEventListener('click', () => {
        if (!state.isResultMode) {
            draw();
        }
    });

    stageEl?.addEventListener('click', () => {
        if (!state.isResultMode) {
            draw();
        }
    });

    window.addEventListener('resize', () => {
        if (!state.isDrawing) {
            renderWinnersPage(true);
        }
        if (isLottoStyle(state.currentPrize?.animationStyle)) {
            lottoAir.resize();
        }
        if (isRedPacketStyle(state.currentPrize?.animationStyle)) {
            redPacketRain.resize();
        }
        if (isScratchCardStyle(state.currentPrize?.animationStyle)) {
            scratchCard.resize();
        }
        if (isTreasureChestStyle(state.currentPrize?.animationStyle)) {
            treasureChest.resize();
        }
        if (isBigTreasureChestStyle(state.currentPrize?.animationStyle)) {
            bigTreasureChest.resize();
        }
    });

    const applyLotteryPayload = (payload, options = {}) => {
        const { skipWinnersUpdate = false } = options;
        const nextPrize = payload.current_prize
            ? {
                id: payload.current_prize.id,
                name: payload.current_prize.name,
                drawMode: payload.current_prize.draw_mode,
                animationStyle: payload.current_prize.animation_style ?? state.currentPrize?.animationStyle,
                lottoHoldSeconds: payload.current_prize.lotto_hold_seconds ?? 5,
                soundEnabled: payload.current_prize.sound_enabled ?? true,
                winnersCount: payload.current_prize.winners_count ?? 0,
            }
            : null;

        state.isOpen = payload.event?.is_lottery_open ?? state.isOpen;
        state.isTestMode = payload.event?.is_test_mode ?? state.isTestMode;
        state.isSwitching = payload.event?.is_prize_switching ?? false;
        state.showPrizesPreview = payload.event?.show_prizes_preview ?? state.showPrizesPreview;
        state.allPrizes = payload.all_prizes ?? state.allPrizes ?? [];

        if (nextPrize) {
            nextPrize.musicUrl = payload.current_prize?.music_url ?? nextPrize.musicUrl;
        }

        // 獎項變更時強制清空 eligibleNames，避免使用舊獎項的資格名單
        const prizeChanged = nextPrize?.id !== state.currentPrize?.id;

        state.currentPrize = nextPrize;
        if (!skipWinnersUpdate) {
            state.winners = payload.winners ?? [];
        }
        state.eligibleNames = prizeChanged
            ? (payload.eligible_names ?? []) // 獎項變更：使用新值或清空
            : (payload.eligible_names ?? state.eligibleNames ?? []); // 同獎項：保留舊值
        pageIndex = 0;

        // 更新背景圖片
        const newBgUrl = payload.bg_url ?? payload.bgUrl ?? null;
        const root = document.getElementById('lottery-root');
        if (root) {
            if (newBgUrl) {
                root.style.setProperty('--lottery-bg-url', `url('${newBgUrl}')`);
                root.classList.add('has-image');
            } else {
                root.style.removeProperty('--lottery-bg-url');
                root.classList.remove('has-image');
            }
        }

        updateTitle(payload.current_prize?.name ?? payload.event?.name);
        render();
        if (isLottoStyle(nextPrize?.animationStyle)) {
            lottoAir.ensureReady(prizeChanged);
        } else {
            lottoAir.stop();
        }
        if (!isRedPacketStyle(nextPrize?.animationStyle)) {
            redPacketRain.stop();
        }
        if (!isScratchCardStyle(nextPrize?.animationStyle)) {
            scratchCard.stop();
        }
        if (!isTreasureChestStyle(nextPrize?.animationStyle)) {
            treasureChest.stop();
        }
        if (!isBigTreasureChestStyle(nextPrize?.animationStyle)) {
            bigTreasureChest.stop();
        }

        // 模式切換邏輯：獎項預覽優先於結果展示
        const isCompleted = payload.current_prize?.is_completed ?? isPrizeCompleted();
        // 可抽人數用盡（名額未滿但沒有人可抽）且有中獎者
        const isExhausted = payload.current_prize?.is_exhausted && state.winners?.length > 0;
        const shouldShowResult = isCompleted || isExhausted;
        const wantPreview = shouldShowPrizesPreview();

        if (wantPreview && !state.isPrizesPreviewMode) {
            // 顯示獎項預覽
            prizesPreviewMode.show();
        } else if (!wantPreview && state.isPrizesPreviewMode) {
            // 隱藏獎項預覽
            prizesPreviewMode.hide();
            // 檢查是否需要顯示結果模式
            if (shouldShowResult) {
                resultMode.show();
            }
        } else if (!wantPreview) {
            // 不在預覽模式時，處理結果展示模式
            if (shouldShowResult && !state.isResultMode) {
                resultMode.show();
            } else if (!shouldShowResult && state.isResultMode) {
                resultMode.hide();
            }
        }

        // 如果在預覽模式，更新內容
        if (state.isPrizesPreviewMode) {
            prizesPreviewMode.render();
        }

        // showPrizesPreview 是一次性信號，處理完後重設
        state.showPrizesPreview = false;

        // 獎項切換中 → 回報前端已載入（防抖：同一獎項只發送一次）
        const ackPrizeId = payload.current_prize?.id;
        if (payload.event?.is_prize_switching && config.switchAckUrl && ackPrizeId && ackPrizeId !== lastAckPrizeId) {
            lastAckPrizeId = ackPrizeId;
            fetch(config.switchAckUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
            }).then(res => {
                console.log('[lottery] switch-ack sent, status:', res.status, 'prizeId:', ackPrizeId);
            }).catch(err => {
                console.error('[lottery] switch-ack failed:', err);
                lastAckPrizeId = null; // 失敗時重設，允許重試
            });
        }
    };

    const pollPayload = async () => {
        // 抽獎期間仍輪詢，但只更新切換狀態（不覆蓋 winners）
        const skipWinnersUpdate = state.isDrawing;
        const url = new URL(window.location.href);
        url.searchParams.set('payload', '1');

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) return;
            const payload = await response.json();
            applyLotteryPayload(payload, { skipWinnersUpdate });
        } catch {
            // ignore
        }
    };

    if (window.Echo && config.brandCode && !window.__lotteryEchoRegistered) {
        window.__lotteryEchoRegistered = true;
        window.Echo.channel(`lottery.${config.brandCode}`)
            .listen('.lottery.updated', (payload) => {
                console.log('[lottery] websocket: lottery.updated', payload);

                // 更新彈幕開關狀態（不受抽獎狀態影響）
                if (payload.event?.danmaku_enabled !== undefined) {
                    updateDanmakuContainer(payload.event.danmaku_enabled);
                }

                // 抽獎進行中：只處理獎項切換信息，不更新 winners
                if (state.isDrawing) {
                    if (payload.event?.is_prize_switching !== undefined) {
                        console.log('[lottery] processing switch update during drawing');
                        applyLotteryPayload(payload, { skipWinnersUpdate: true });
                    } else {
                        console.log('[lottery] ignored lottery.updated during drawing');
                    }
                    return;
                }
                applyLotteryPayload(payload);
            })
            .listen('.winners.updated', (payload) => {
                console.log('[lottery] websocket: winners.updated', payload);
                if (!state.currentPrize || payload.prize_id !== state.currentPrize.id) {
                    return;
                }

                // 更新 eligibleNames（影響刮刮樂卡片數量計算）
                if (payload.eligible_names) {
                    state.eligibleNames = payload.eligible_names;
                }

                // 更新 allPrizes（影響獎項預覽的已抽出數量顯示）
                if (payload.all_prizes) {
                    state.allPrizes = payload.all_prizes;
                }

                // 抽獎進行中，只更新 eligibleNames 和 allPrizes，不更新 winners
                // 因為 draw() 會自己處理 winners 的更新
                if (state.isDrawing) {
                    console.log('[lottery] winners.updated during drawing: only updated eligibleNames/allPrizes');
                    return;
                }

                // 非抽獎狀態：用 id 去重，附加新中獎者
                const existingIds = new Set(state.winners.map((w) => w.id));
                const newWinners = (payload.winners ?? []).filter((w) => !existingIds.has(w.id));
                state.winners = [...state.winners, ...newWinners];

                pageIndex = 0;
                render();

                // 若在 resultMode，立即更新
                if (state.isResultMode) {
                    resultMode.renderPage();
                }

                // 若獎項已完成或可抽人數用盡，進入結果模式
                if (payload.is_completed || (payload.is_exhausted && state.winners?.length > 0)) {
                    if (!state.isResultMode && !shouldShowPrizesPreview()) {
                        setTimeout(() => resultMode.show(), 2000);
                    }
                }
            })
            .listen('.danmaku.sent', (payload) => {
                console.log('[lottery] websocket: danmaku.sent', payload);
                showDanmaku(payload.employee_name, payload.message);
            });
    }

    setInterval(pollPayload, 5000);

    // ========== 彈幕功能 ==========
    const danmakuContainer = document.getElementById('danmaku-container');
    let danmakuLanes = [];
    const laneCount = 5;
    const laneHeight = 60;

    const updateDanmakuContainer = (enabled) => {
        if (!danmakuContainer) return;
        danmakuContainer.classList.toggle('hidden', !enabled);
    };

    const showDanmaku = (employeeName, message) => {
        if (!danmakuContainer || danmakuContainer.classList.contains('hidden')) {
            return;
        }

        const laneIndex = findAvailableLane();
        const top = 100 + laneIndex * laneHeight;

        const danmakuEl = document.createElement('div');
        danmakuEl.className = 'danmaku-item';
        danmakuEl.style.top = `${top}px`;
        danmakuEl.style.right = '0';
        danmakuEl.textContent = `${employeeName}: ${message}`;

        const duration = 8 + message.length * 0.05;
        danmakuEl.style.animationDuration = `${duration}s`;

        danmakuContainer.appendChild(danmakuEl);
        danmakuLanes[laneIndex] = Date.now();

        setTimeout(() => {
            danmakuEl.remove();
        }, duration * 1000);
    };

    const findAvailableLane = () => {
        const now = Date.now();
        const minGap = 2000;

        let bestLane = 0;
        let oldestTime = now;

        for (let i = 0; i < laneCount; i++) {
            const lastUsed = danmakuLanes[i] || 0;
            if (now - lastUsed > minGap && lastUsed < oldestTime) {
                oldestTime = lastUsed;
                bestLane = i;
            }
        }

        return bestLane;
    };

    updateDanmakuContainer(config.danmakuEnabled ?? false);

    render();
    if (isLottoAirStyle(state.currentPrize?.animationStyle)) {
        lottoAir.ensureReady();
    }

    // 初始化時檢查顯示模式
    const initialWantPreview = shouldShowPrizesPreview();
    const initialCompleted = state.currentPrize?.isCompleted ?? isPrizeCompleted();
    // 可抽人數用盡（名額未滿但沒有人可抽）且有中獎者
    const initialExhausted = state.currentPrize?.isExhausted && state.winners?.length > 0;

    if (initialWantPreview) {
        // 優先顯示獎項預覽
        prizesPreviewMode.show();
    } else if (initialCompleted || initialExhausted) {
        // 其次顯示結果模式（名額滿或可抽人數用盡）
        resultMode.show();
    }
};

window.addEventListener('load', () => {
    initLottery();
});
