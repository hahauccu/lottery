const initLottery = () => {
    // 防止重複初始化（Vite HMR 會重新執行模組）
    if (window.__lotteryInitialized) {
        console.warn('[lottery] initLottery() already executed, skipping');
        return;
    }
    window.__lotteryInitialized = true;

    // 清理 URL 中的 _lac 參數（後台自動帶入的存取碼）
    if (new URL(location.href).searchParams.has('_lac')) {
        const u = new URL(location.href);
        u.searchParams.delete('_lac');
        history.replaceState({}, '', u.toString());
    }

    const isEmbeddedPreview = new URL(window.location.href).searchParams.get('embedded') === '1';

    const configEl = document.getElementById('lottery-config-data');
    let config = null;

    if (configEl) {
        try {
            config = JSON.parse(configEl.textContent || 'null');
        } catch (error) {
            console.error('[lottery] failed to parse config', error);
        }
    }

    window.LotteryConfig = config;

    if (!config) {
        window.__lotteryInitialized = false;
        return;
    }

    if (!isEmbeddedPreview) {
        // --- Tab 唯一性管理 ---
        const tabStorageKey = `lottery_active_tab_${config.brandCode}`;
        const tabId = Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
        localStorage.setItem(tabStorageKey, tabId);

        const showTabInvalidatedOverlay = () => {
            clearInterval(window.__lotteryReadyTimer);
            clearInterval(window.__lotteryPollTimer);
            stopDrawingHeartbeat();

            const overlay = document.createElement('div');
            overlay.id = 'tab-invalidated-overlay';
            overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center;';
            overlay.innerHTML = `
                <div style="text-align:center;color:#fff;padding:2rem;">
                    <p style="font-size:1.5rem;font-weight:bold;margin-bottom:0.75rem;">此分頁已失效</p>
                    <p style="color:#9ca3af;margin-bottom:1.5rem;">已在其他分頁開啟前台抽獎，此分頁停止運作。</p>
                    <button onclick="location.reload()" style="padding:0.5rem 1.5rem;background:#f59e0b;color:#000;border-radius:0.5rem;font-weight:600;cursor:pointer;border:none;">重新載入</button>
                </div>
            `;
            document.body.appendChild(overlay);
        };

        window.addEventListener('storage', (event) => {
            if (event.key === tabStorageKey && event.newValue && event.newValue !== tabId) {
                showTabInvalidatedOverlay();
            }
        });
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
    const switchingMaskEl = document.getElementById('switching-mask');

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
        needsAnimationReset: false,
    };

    // --- 存取碼保護機制 ---
    let operatorToken = config.operatorToken ?? null;

    const operatorHeaders = () => {
        const h = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': config.csrfToken,
        };
        if (operatorToken) {
            h['X-Operator-Token'] = operatorToken;
        }
        return h;
    };

    const showAccessCodeModal = () => {
        // 如果已有 modal 則不重複建立
        if (document.getElementById('access-code-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'access-code-modal';
        modal.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.85)';
        modal.innerHTML = `
            <div style="background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:1rem;padding:2rem;max-width:400px;width:90%;text-align:center">
                <h2 style="color:white;font-size:1.5rem;font-weight:700;margin-bottom:0.5rem">請輸入存取碼</h2>
                <p style="color:rgba(255,255,255,0.6);font-size:0.875rem;margin-bottom:1.5rem">請向活動管理員索取今日存取碼</p>
                <input id="access-code-input" type="text" maxlength="8" placeholder="8 碼存取碼"
                    style="width:100%;padding:0.75rem 1rem;background:rgba(0,0,0,0.4);border:1px solid rgba(255,255,255,0.2);border-radius:0.5rem;color:white;font-size:1.25rem;text-align:center;letter-spacing:0.3em;text-transform:uppercase;outline:none"
                >
                <p id="access-code-error" style="color:#f87171;font-size:0.875rem;margin-top:0.5rem;display:none"></p>
                <button id="access-code-submit"
                    style="width:100%;margin-top:1rem;padding:0.75rem;background:linear-gradient(to right,#4f46e5,#7c3aed);color:white;font-weight:600;border:none;border-radius:0.5rem;cursor:pointer;font-size:1rem"
                >驗證</button>
            </div>
        `;
        document.body.appendChild(modal);

        const input = document.getElementById('access-code-input');
        const errorEl = document.getElementById('access-code-error');
        const submitBtn = document.getElementById('access-code-submit');

        const doVerify = async () => {
            const code = input.value.trim();
            if (!code) return;
            submitBtn.disabled = true;
            submitBtn.textContent = '驗證中…';
            errorEl.style.display = 'none';

            try {
                const res = await fetch(config.verifyAccessUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrfToken },
                    body: JSON.stringify({ access_code: code }),
                });
                const data = await res.json();
                if (res.ok && data.operatorToken) {
                    operatorToken = data.operatorToken;
                    modal.remove();
                } else {
                    errorEl.textContent = data.message || '存取碼錯誤';
                    errorEl.style.display = 'block';
                }
            } catch {
                errorEl.textContent = '驗證失敗，請稍後再試';
                errorEl.style.display = 'block';
            }
            submitBtn.disabled = false;
            submitBtn.textContent = '驗證';
        };

        submitBtn.addEventListener('click', doVerify);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') doVerify(); });
        input.focus();
    };

    // 頁面載入時，如果沒有 operatorToken 就顯示 modal
    if (!operatorToken && config.verifyAccessUrl) {
        showAccessCodeModal();
    }

    const switchingMask = (() => {
        let hideTimer = null;
        const show = () => {
            if (!switchingMaskEl) return;
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
            switchingMaskEl.classList.add('is-visible');
        };
        const hide = (delay = 240) => {
            if (!switchingMaskEl) return;
            if (hideTimer) {
                clearTimeout(hideTimer);
            }
            hideTimer = setTimeout(() => {
                switchingMaskEl.classList.remove('is-visible');
            }, delay);
        };
        return { show, hide };
    })();

    const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

    const createDrawClock = (budgetMs) => {
        const start = performance.now();
        const budget = Math.max(3000, budgetMs);
        return {
            budgetMs: budget,
            elapsedMs: () => performance.now() - start,
            remainingMs: () => Math.max(0, budget - (performance.now() - start)),
            waitUntilEnd: () => {
                const rem = budget - (performance.now() - start);
                return rem > 50 ? delay(rem) : Promise.resolve();
            },
            sliceMs: (fraction) => Math.max(50, (budget - (performance.now() - start)) * fraction),
        };
    };

    const clearCanvas = () => {
        if (!lottoCanvasEl) return;
        const ctx = lottoCanvasEl.getContext('2d');
        if (!ctx) return;
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, lottoCanvasEl.width, lottoCanvasEl.height);
        ctx.restore();
    };

    // 使用全域追蹤 ACK 狀態（避免多個閉包各自獨立追蹤）
    if (!window.__lotteryAckState) {
        window.__lotteryAckState = { lastNonce: null, pending: false };
    }
    const ackState = window.__lotteryAckState;

    const escapeHtml = (str) => {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    const modeLabel = (mode) => (mode === 'one_by_one' ? '逐一抽出' : '一次全抽');

    let pageIndex = 0;
    let pageTimer = null;
    let resultModeTimer = null;
    let drawRunId = 0;

    const clearResultModeTimer = () => {
        if (resultModeTimer) {
            clearTimeout(resultModeTimer);
            resultModeTimer = null;
        }
    };

    const scheduleResultModeShow = (delayMs = 2000) => {
        clearResultModeTimer();
        resultModeTimer = setTimeout(() => {
            resultModeTimer = null;
            if (state.isDrawing || state.isSwitching || state.isPrizesPreviewMode) {
                return;
            }
            if (!shouldShowPrizesPreview()) {
                resultMode.show();
            }
        }, delayMs);
    };

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
                <li class="rounded-2xl border border-white/10 bg-black/20 px-4 py-6 text-sm text-slate-200/50">請按 Enter 開始抽獎</li>
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
                const details = winner.employee_email ? escapeHtml(winner.employee_email) : '';
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
        const isCanvas = isCanvasStyle(currentStyle);

        if (lottoWrapEl) {
            lottoWrapEl.classList.toggle('hidden', !isCanvas);
        }

        const driver = getAnimationDriver(currentStyle);
        const canPrepareIdle = !state.isDrawing && !state.isResultMode && !state.isPrizesPreviewMode && !state.isSwitching;
        if (canPrepareIdle) {
            driver.prepareIdle({ forceReset: state.needsAnimationReset });
            state.needsAnimationReset = false;
        }

        if (drawButtonEl) {
            drawButtonEl.disabled = !state.isOpen
                || !state.currentPrize
                || state.isDrawing
                || state.isPrizesPreviewMode
                || state.isSwitching
                || state.isResultMode;
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
            clearResultModeTimer();
            stopAllAnimations();
            clearCanvas();
            switchingMask.hide(0);
            stageEl?.classList.add('hidden');
            resultModeEl.classList.add('is-active');
            state.isResultMode = true;
            renderPage(true);
        };

        const hide = () => {
            clearResultModeTimer();
            stopTimer();
            if (!resultModeEl) return;
            resultModeEl.classList.remove('is-active');
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
            clearResultModeTimer();
            stopAllAnimations();
            clearCanvas();
            switchingMask.hide(0);
            stageEl?.classList.add('hidden');
            resultModeEl?.classList.remove('is-active');
            prizesPreviewModeEl.classList.add('is-active');
            state.isPrizesPreviewMode = true;
            state.isResultMode = false;
            renderPage(true);
        };

        const hide = () => {
            if (!prizesPreviewModeEl) return;
            prizesPreviewModeEl.classList.remove('is-active');
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
        if (!(state.currentPrize?.soundEnabled ?? true)) return;
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
        drawAudio.play().catch(() => { });
    };

    const stopDrawAudio = () => {
        if (!drawAudio) return;

        drawAudio.pause();
        drawAudio.currentTime = 0;
    };

    const sfx = (() => {
        // 音效設定由後台獎項設定控制，讀取 state.currentPrize?.soundEnabled
        const AudioContextRef = window.AudioContext || window.webkitAudioContext;
        const noop = () => { };
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
                context.resume().catch(() => { });
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
            if (!isEnabled()) return { stop: () => { } };
            console.log('[sfx] playScratch: start');
            const ctx = getContext();
            if (!ctx) return { stop: () => { } };

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
            if (!isEnabled()) return { stop: () => { } };
            console.log('[sfx] playBallRumble: start');
            const ctx = getContext();
            if (!ctx) return { stop: () => { } };

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
            if (!isEnabled()) return { stop: () => { } };
            console.log('[sfx] playDrumRoll: start');
            const ctx = getContext();
            if (!ctx) return { stop: () => { } };

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
    const easeOutBack = (t, s = 1.4) => 1 + (s + 1) * Math.pow(t - 1, 3) + s * Math.pow(t - 1, 2);
    const isScratchCardStyle = (style) => style === 'scratch_card';
    const isTreasureChestStyle = (style) => style === 'treasure_chest';
    const isBigTreasureChestStyle = (style) => style === 'big_treasure_chest';
    const isMarbleRaceStyle = (style) => style === 'marble_race';
    const isBattleTopStyle = (style) => style === 'battle_top';
    const isCanvasStyle = (style) => isLottoAirStyle(style)
        || isRedPacketStyle(style)
        || isScratchCardStyle(style)
        || isTreasureChestStyle(style)
        || isBigTreasureChestStyle(style)
        || isMarbleRaceStyle(style)
        || isBattleTopStyle(style);
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

        const createBall = (name, index, radius) => {
            const angle = rand(0, TAU);
            const spread = rand(0, Math.max(10, airState.drum.r - radius - 10));

            return {
                name,
                label: shortLabel(name),
                x: airState.drum.x + Math.cos(angle) * spread,
                y: airState.drum.y + Math.sin(angle) * spread,
                vx: rand(-80, 80),
                vy: rand(-80, 80),
                r: radius,
                hue: (index * 19) % 360,
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
            };
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
                const name = names[i % names.length];
                airState.balls.push(createBall(name, i, radius));
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
            let candidates = airState.balls.filter((ball) => !ball.grabbed && !ball.out);
            if (!candidates.length) {
                // one_by_one 重複抽獎或球池耗盡時，補一顆臨時球避免「沒有中獎動畫」
                const emergencyName = winnerName || '???';
                const emergencyRadius = clamp(Math.floor(airState.drum.r * 0.04), 10, 16);
                const emergencyBall = createBall(emergencyName, airState.balls.length + 1, emergencyRadius);
                airState.balls.push(emergencyBall);
                candidates = [emergencyBall];
                console.warn('[lottery] pickBall: no available balls, spawned emergency ball for animation');
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
            // 4. 球數不對且沒有進行中的抽獎狀態（out 球或 picked 球）
            const hasOutBalls = airState.balls.some((b) => b.out);
            const hasPicked = airState.picked.length > 0;
            const needsReset = forceReset
                || !airState.ctx
                || !airState.balls.length
                || (airState.balls.length !== lottoAirConfig.count && !hasOutBalls && !hasPicked);
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
            if (airState.finishPauseId) {
                clearTimeout(airState.finishPauseId);
                airState.finishPauseId = null;
            }
            airState.waiters.length = 0;
            airState.balls.length = 0;
            airState.picked.length = 0;
            airState.pending.length = 0;
            particles.length = 0;
            stopTrayCarousel();
            airState.mode = 'idle';
            airState.t = 0;
            airState.paused = false;
            airState.slowStop = false;
            airState.slowFactor = 1;
            shake.power = 0;
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
                        const openDur = clamp(holdSeconds * 150, 300, 800);
                        const revealHold = clamp(holdSeconds * 100, 200, 700);
                        animateTo(p, { openProgress: 1, scale: 3 }, openDur, () => {
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
                            }, revealHold);
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

        const showIdlePackets = (forceReset = false) => {
            ensureReady();
            if (!ctx || canvasRect.width === 0) return;

            if (packets.length > 0 && !forceReset && phase === 'idle_display') return;

            if (running) return;

            cleanup();
            phase = 'idle_display';

            const { width, height } = canvasRect;
            const count = Math.max(12, Math.floor((width * height) / 12000));

            for (let i = 0; i < count; i++) {
                const p = createPacket();
                p.x = rand(60, width - 60);
                p.y = rand(40, height - 40);
                p.rotation = rand(-0.25, 0.25);
                p.scale = rand(0.7, 1.1);
                p.vy = 0;
                p.vx = 0;
                packets.push(p);
            }

            drawFrame();
        };

        return {
            start,
            stop,
            setWinner,
            waitForReveal,
            prepareNext,
            setHoldSeconds,
            ensureReady,
            showIdlePackets,
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
            for (const card of cards) {
                card.maskCanvas = null;
                card.maskCtx = null;
            }
            cards.length = 0;
            revealedCount = 0;
            if (revealResolve) { revealResolve(); revealResolve = null; }
        };

        // 顯示卡片（在選擇動畫時就呼叫，不等抽獎）
        const showCards = (count, forceReset = false) => {
            if (running && !forceReset) return;
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
                    const shakeDur = clamp(holdSeconds * 0.12, 0.3, 0.8);
                    chest.shakeTime += dt;
                    chest.shakeOffset = Math.sin(chest.shakeTime * 45) * 5;
                    if (chest.shakeTime > shakeDur) {
                        chest.phase = 'opening';
                        chest.shakeOffset = 0;
                    }
                } else if (chest.phase === 'opening') {
                    // 處理延遲
                    if (chest.openDelay > 0) {
                        chest.openDelay -= dt;
                        continue;
                    }
                    // 開啟蓋子 — 速度依 holdSeconds 動態計算
                    const lidSpeed = 110 / clamp(holdSeconds * 0.15, 0.3, 0.6);
                    chest.lidAngle = Math.max(-110, chest.lidAngle - lidSpeed * dt);
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
                        const revealDelay = clamp(holdSeconds * 100, 200, 600);
                        if (chest.openResolve) {
                            setTimeout(() => {
                                if (chest.openResolve) {
                                    chest.openResolve();
                                    chest.openResolve = null;
                                }
                            }, revealDelay);
                        }

                        // 檢查是否全部開啟（相容舊的 startOpening + waitForAllOpened 流程）
                        if (openedCount >= chests.filter(c => c.winner).length && openResolve) {
                            setTimeout(() => {
                                if (openResolve) {
                                    openResolve();
                                    openResolve = null;
                                }
                            }, revealDelay + 200);
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
            openedCount = 0;
            if (openResolve) { openResolve(); openResolve = null; }
        };

        // 顯示寶箱
        const showChests = (count, forceReset = false) => {
            if (running && !forceReset) return;
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
        let revealResolve = null;
        let spawnTimer = 0;



        let ctx = null;
        let canvasRect = { width: 0, height: 0 };
        let dpr = 1;
        let rafId = null;
        let last = 0;

        const coins = [];
        const ingots = [];
        const gourds = [];
        const sparkles = [];
        const redPackets = [];
        const nameEruption = {
            active: false,
            phase: 'idle',   // 'idle' | 'erupting' | 'displaying' | 'exiting'
            t: 0, total: 0,
            name: '',
            x: 0, y: 0,
            startX: 0, startY: 0,
            targetX: 0, targetY: 0,
            scale: 0, opacity: 0,
            rotation: 0, floatPhase: 0,
            fontSize: 0,
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
            const erupt = clamp(total * 0.15, 0.4, 0.8);
            const display = clamp(total * 0.5, 1.0, 4.0);
            const exit = clamp(total * 0.1, 0.3, 0.6);
            return { erupt, display, exit };
        };

        const spawnBurst = () => {
            const base = chest.w * 0.08;
            const coinCount = 30;
            const ingotCount = 6;
            const redPacketCount = 5;
            const gourdCount = 2;
            for (let i = 0; i < coinCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(360, 780, 900, 1400);
                coins.push({
                    x, y, vx, vy,
                    size: rand(base * 0.8, base * 1.4),
                    rotation: rand(0, TAU),
                    rotSpeed: rand(-10, 10),
                    life: rand(1.6, 2.4),
                    t: 0, ax, ay, boostTime,
                });
            }
            for (let i = 0; i < ingotCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(300, 640, 800, 1200);
                ingots.push({
                    x, y, vx, vy,
                    w: rand(base * 1.0, base * 1.6), h: rand(base * 0.6, base * 1.0),
                    rotation: rand(-0.6, 0.6),
                    rotSpeed: rand(-6, 6),
                    life: rand(1.7, 2.5),
                    t: 0, ax, ay, boostTime,
                });
            }
            for (let i = 0; i < redPacketCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(280, 600, 780, 1150);
                redPackets.push({
                    x, y, vx, vy,
                    w: rand(base * 0.7, base * 1.1), h: rand(base * 1.0, base * 1.4),
                    rotation: rand(-0.5, 0.5),
                    rotSpeed: rand(-5, 5),
                    life: rand(1.6, 2.4),
                    t: 0, ax, ay, boostTime,
                });
            }
            for (let i = 0; i < gourdCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(260, 520, 720, 1100);
                gourds.push({
                    x, y, vx, vy,
                    size: rand(base * 0.9, base * 1.3),
                    rotation: rand(-0.4, 0.4),
                    rotSpeed: rand(-4, 4),
                    life: rand(1.6, 2.4),
                    t: 0, ax, ay, boostTime,
                });
            }
            const sparkleCount = 26;
            for (let i = 0; i < sparkleCount; i++) {
                const { x, y } = getEmitPoint();
                const angle = rand(-Math.PI * 0.9, -Math.PI * 0.1);
                const speed = rand(160, 300);
                sparkles.push({
                    x, y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 40,
                    size: rand(base * 0.25, base * 0.45),
                    life: rand(0.6, 1.1),
                    t: 0,
                });
            }
        };

        const spawnStream = (dt) => {
            spawnTimer += dt;
            const base = chest.w * 0.08;
            const interval = 0.10;
            const maxParticles = 100;

            while (spawnTimer >= interval) {
                spawnTimer -= interval;
                const total = coins.length + ingots.length + gourds.length + redPackets.length;
                if (total >= maxParticles) break;

                const roll = Math.random();
                if (roll < 0.55) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(220, 420, 520, 900);
                    coins.push({
                        x, y, vx, vy,
                        size: rand(base * 0.8, base * 1.4),
                        rotation: rand(0, TAU),
                        rotSpeed: rand(-10, 10),
                        life: rand(1.1, 1.9),
                        t: 0, ax, ay, boostTime,
                    });
                } else if (roll < 0.73) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(200, 380, 480, 820);
                    ingots.push({
                        x, y, vx, vy,
                        w: rand(base * 1.0, base * 1.6), h: rand(base * 0.6, base * 1.0),
                        rotation: rand(-0.6, 0.6),
                        rotSpeed: rand(-6, 6),
                        life: rand(1.2, 2.0),
                        t: 0, ax, ay, boostTime,
                    });
                } else if (roll < 0.90) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(200, 380, 480, 800);
                    redPackets.push({
                        x, y, vx, vy,
                        w: rand(base * 0.7, base * 1.1), h: rand(base * 1.0, base * 1.4),
                        rotation: rand(-0.5, 0.5),
                        rotSpeed: rand(-5, 5),
                        life: rand(1.2, 2.0),
                        t: 0, ax, ay, boostTime,
                    });
                } else {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(200, 360, 460, 760);
                    gourds.push({
                        x, y, vx, vy,
                        size: rand(base * 0.9, base * 1.3),
                        rotation: rand(-0.4, 0.4),
                        rotSpeed: rand(-4, 4),
                        life: rand(1.3, 2.1),
                        t: 0, ax, ay, boostTime,
                    });
                }
            }
        };

        let idleSpawnTimer = 0;
        const spawnIdleStream = (dt) => {
            idleSpawnTimer += dt;
            const base = chest.w * 0.08;
            const interval = 0.18;
            const maxParticles = 60;

            while (idleSpawnTimer >= interval) {
                idleSpawnTimer -= interval;
                const total = coins.length + ingots.length + gourds.length + redPackets.length;
                if (total >= maxParticles) break;

                const roll = Math.random();
                const speedMul = 0.7;
                if (roll < 0.50) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(220 * speedMul, 420 * speedMul, 520 * speedMul, 900 * speedMul);
                    coins.push({
                        x, y, vx, vy,
                        size: rand(base * 0.8, base * 1.4),
                        rotation: rand(0, TAU),
                        rotSpeed: rand(-10, 10),
                        life: rand(1.1, 1.9),
                        t: 0, ax, ay, boostTime,
                    });
                } else if (roll < 0.65) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(200 * speedMul, 380 * speedMul, 480 * speedMul, 820 * speedMul);
                    ingots.push({
                        x, y, vx, vy,
                        w: rand(base * 1.0, base * 1.6), h: rand(base * 0.6, base * 1.0),
                        rotation: rand(-0.6, 0.6),
                        rotSpeed: rand(-6, 6),
                        life: rand(1.2, 2.0),
                        t: 0, ax, ay, boostTime,
                    });
                } else if (roll < 0.85) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(200 * speedMul, 380 * speedMul, 480 * speedMul, 800 * speedMul);
                    redPackets.push({
                        x, y, vx, vy,
                        w: rand(base * 0.7, base * 1.1), h: rand(base * 1.0, base * 1.4),
                        rotation: rand(-0.5, 0.5),
                        rotSpeed: rand(-5, 5),
                        life: rand(1.2, 2.0),
                        t: 0, ax, ay, boostTime,
                    });
                } else if (roll < 0.90) {
                    const { x, y } = getEmitPoint();
                    const { vx, vy, ax, ay, boostTime } = getEmitVelocity(200 * speedMul, 360 * speedMul, 460 * speedMul, 760 * speedMul);
                    gourds.push({
                        x, y, vx, vy,
                        size: rand(base * 0.9, base * 1.3),
                        rotation: rand(-0.4, 0.4),
                        rotSpeed: rand(-4, 4),
                        life: rand(1.3, 2.1),
                        t: 0, ax, ay, boostTime,
                    });
                } else {
                    const { x, y } = getEmitPoint();
                    const angle = rand(-Math.PI * 0.9, -Math.PI * 0.1);
                    const speed = rand(100, 200);
                    sparkles.push({
                        x, y,
                        vx: Math.cos(angle) * speed,
                        vy: Math.sin(angle) * speed - 30,
                        size: rand(base * 0.25, base * 0.45),
                        life: rand(0.5, 0.9),
                        t: 0,
                    });
                }
            }
        };

        const spawnNameBurst = () => {
            // 粒子尺寸跟名字一樣大（fontSize 約 36-72）
            const fs = nameEruption.fontSize || 48;
            const coinCount = 30;
            const ingotCount = 8;
            const redPacketCount = 6;
            const sparkleCount = 16;
            for (let i = 0; i < coinCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(400, 850, 1000, 1500);
                coins.push({
                    x, y, vx: vx * 1.2, vy: vy * 1.2,
                    size: rand(fs * 0.6, fs * 1.0),
                    rotation: rand(0, TAU),
                    rotSpeed: rand(-10, 10),
                    life: rand(1.2, 1.8),
                    t: 0, ax, ay, boostTime,
                });
            }
            for (let i = 0; i < ingotCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(340, 700, 900, 1300);
                ingots.push({
                    x, y, vx: vx * 1.2, vy: vy * 1.2,
                    w: rand(fs * 0.7, fs * 1.1), h: rand(fs * 0.45, fs * 0.75),
                    rotation: rand(-0.6, 0.6),
                    rotSpeed: rand(-6, 6),
                    life: rand(1.3, 2.0),
                    t: 0, ax, ay, boostTime,
                });
            }
            for (let i = 0; i < redPacketCount; i++) {
                const { x, y } = getEmitPoint();
                const { vx, vy, ax, ay, boostTime } = getEmitVelocity(320, 660, 850, 1250);
                redPackets.push({
                    x, y, vx: vx * 1.2, vy: vy * 1.2,
                    w: rand(fs * 0.5, fs * 0.8), h: rand(fs * 0.7, fs * 1.0),
                    rotation: rand(-0.5, 0.5),
                    rotSpeed: rand(-5, 5),
                    life: rand(1.2, 1.8),
                    t: 0, ax, ay, boostTime,
                });
            }
            for (let i = 0; i < sparkleCount; i++) {
                const { x, y } = getEmitPoint();
                const angle = rand(-Math.PI * 0.85, -Math.PI * 0.15);
                const speed = rand(200, 380);
                sparkles.push({
                    x, y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 50,
                    size: rand(fs * 0.2, fs * 0.4),
                    life: rand(0.5, 0.9),
                    t: 0,
                });
            }
        };

        const spawnNameEruption = () => {
            if (!winner) return;
            const timing = getTiming();
            const emitPt = getEmitPoint();
            nameEruption.active = true;
            nameEruption.phase = 'erupting';
            nameEruption.t = 0;
            nameEruption.total = 0;
            nameEruption.name = winner;
            nameEruption.startX = chest.x + chest.w / 2;
            nameEruption.startY = chest.y + chest.h * 0.18;
            nameEruption.targetX = canvasRect.width * 0.5;
            nameEruption.targetY = canvasRect.height * 0.38;
            nameEruption.x = nameEruption.startX;
            nameEruption.y = nameEruption.startY;
            nameEruption.scale = 0.3;
            nameEruption.opacity = 0;
            nameEruption.rotation = rand(-0.08, 0.08);
            nameEruption.floatPhase = rand(0, TAU);
            const maxTextW = canvasRect.width * 0.7;
            nameEruption.fontSize = getFittedFontSize(winner, maxTextW, clamp(chest.w * 0.14, 36, 72), 18);
        };

        const updateNameEruption = (dt) => {
            if (!nameEruption.active) return false;
            const timing = getTiming();
            nameEruption.total += dt;
            nameEruption.t += dt;

            if (nameEruption.phase === 'erupting') {
                const progress = clamp(nameEruption.t / timing.erupt, 0, 1);
                const eased = easeOutBack(progress);
                nameEruption.x = lerp(nameEruption.startX, nameEruption.targetX, eased);
                nameEruption.y = lerp(nameEruption.startY, nameEruption.targetY, eased);
                nameEruption.scale = lerp(0.3, 1, eased);
                nameEruption.opacity = Math.min(1, progress * 2.5);
                if (progress >= 1) {
                    nameEruption.phase = 'displaying';
                    nameEruption.t = 0;
                }
            } else if (nameEruption.phase === 'displaying') {
                nameEruption.opacity = 1;
                nameEruption.scale = 1 + Math.sin(nameEruption.total * 2.5) * 0.025;
                nameEruption.y = nameEruption.targetY + Math.sin(nameEruption.total * 1.8 + nameEruption.floatPhase) * 6;
                if (nameEruption.t >= timing.display) {
                    nameEruption.phase = 'exiting';
                    nameEruption.t = 0;
                }
            } else if (nameEruption.phase === 'exiting') {
                const progress = clamp(nameEruption.t / timing.exit, 0, 1);
                nameEruption.opacity = 1 - progress;
                nameEruption.y = nameEruption.targetY - progress * canvasRect.height * 0.18;
                nameEruption.scale = 1 - progress * 0.15;
                if (progress >= 1) {
                    nameEruption.active = false;
                    nameEruption.phase = 'idle';
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

            for (let i = redPackets.length - 1; i >= 0; i--) {
                const rp = redPackets[i];
                rp.t += dt;
                if (rp.boostTime && rp.t <= rp.boostTime) {
                    rp.vx += rp.ax * dt;
                    rp.vy += rp.ay * dt;
                }
                if (rp.t >= rp.life) { redPackets.splice(i, 1); continue; }
                rp.vy += 520 * dt;
                rp.x += rp.vx * dt;
                rp.y += rp.vy * dt;
                rp.rotation += rp.rotSpeed * dt;
                rp.vx *= 0.985;
                rp.vy *= 0.985;
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

        const drawRedPacket = (rp) => {
            const alpha = 1 - rp.t / rp.life;
            ctx.save();
            ctx.translate(rp.x, rp.y);
            ctx.rotate(rp.rotation);
            ctx.globalAlpha = alpha;
            const w = rp.w;
            const h = rp.h;
            // 紅色圓角矩形
            ctx.fillStyle = '#d42a2a';
            roundRect(ctx, -w / 2, -h / 2, w, h, w * 0.18);
            ctx.fill();
            // 金色封口線
            const sealY = -h * 0.12;
            ctx.strokeStyle = '#f4c430';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(-w * 0.38, sealY);
            ctx.lineTo(w * 0.38, sealY);
            ctx.stroke();
            // 金色小圓點
            ctx.fillStyle = '#ffd24d';
            ctx.beginPath();
            ctx.arc(0, sealY, w * 0.1, 0, TAU);
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

        const drawNameEruption = () => {
            if (!nameEruption.active || !ctx) return;
            const displayName = nameEruption.name || '???';

            ctx.save();
            ctx.translate(nameEruption.x, nameEruption.y);
            ctx.rotate(nameEruption.rotation * (1 - clamp(nameEruption.total * 2, 0, 1)));
            ctx.scale(nameEruption.scale, nameEruption.scale);
            ctx.globalAlpha = nameEruption.opacity;

            // 金色放射光暈（脈動）
            const glowPulse = 0.6 + Math.sin(nameEruption.total * 4) * 0.15;
            const glowR = nameEruption.fontSize * 2.5 * glowPulse;
            const glowGrad = ctx.createRadialGradient(0, 0, 0, 0, 0, glowR);
            glowGrad.addColorStop(0, 'rgba(255, 215, 0, 0.45)');
            glowGrad.addColorStop(0.4, 'rgba(255, 180, 0, 0.2)');
            glowGrad.addColorStop(1, 'rgba(255, 180, 0, 0)');
            ctx.fillStyle = glowGrad;
            ctx.beginPath();
            ctx.arc(0, 0, glowR, 0, TAU);
            ctx.fill();

            // 文字：描邊 + 金色漸層填充 + 陰影
            const fs = nameEruption.fontSize;
            ctx.font = `700 ${fs}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            // 深色描邊
            ctx.strokeStyle = '#4a1800';
            ctx.lineWidth = Math.max(4, fs * 0.08);
            ctx.lineJoin = 'round';
            ctx.strokeText(displayName, 0, 0);

            // 金色漸層填充
            const textGrad = ctx.createLinearGradient(0, -fs * 0.5, 0, fs * 0.5);
            textGrad.addColorStop(0, '#fff5b5');
            textGrad.addColorStop(0.3, '#ffd24d');
            textGrad.addColorStop(0.7, '#ffb800');
            textGrad.addColorStop(1, '#e8a000');
            ctx.fillStyle = textGrad;
            ctx.shadowColor = 'rgba(255, 200, 60, 0.9)';
            ctx.shadowBlur = 18;
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
            redPackets.forEach(drawRedPacket);
            drawNameEruption();
            drawSparkles();
        };

        const update = (dt) => {
            phaseTime += dt;
            openProgress = 1; // 始終打開

            if (phase === 'idle') {
                spawnIdleStream(dt);
            } else if (phase === 'erupting') {
                spawnStream(dt);
                updateNameEruption(dt);
                if (nameEruption.phase === 'displaying') phase = 'displaying';
            } else if (phase === 'displaying') {
                spawnIdleStream(dt);
                updateNameEruption(dt);
                if (nameEruption.phase === 'exiting') phase = 'exiting';
            } else if (phase === 'exiting') {
                spawnIdleStream(dt);
                const done = updateNameEruption(dt);
                if (done) {
                    if (revealResolve) {
                        revealResolve();
                        revealResolve = null;
                    }
                    phase = 'idle';
                    phaseTime = 0;
                }
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
            redPackets.length = 0;
            spawnTimer = 0;
            nameEruption.active = false;
            nameEruption.phase = 'idle';
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
            openProgress = 1; // 始終打開
            spawnTimer = 0;
            idleSpawnTimer = 0;
            nameEruption.active = false;
            nameEruption.phase = 'idle';
            winner = null;
            last = performance.now();
            rafId = requestAnimationFrame(tick);
        };

        const setWinner = (name) => {
            if (!running) {
                showChest();
            }
            winner = name || '???';
            phase = 'erupting';
            phaseTime = 0;
            spawnTimer = 0;
            spawnNameEruption();
            spawnNameBurst();
            sfx.playChestOpen();
        };

        const waitForReveal = () => new Promise((resolve) => {
            revealResolve = resolve;
        });

        const prepareNext = () => {
            phase = 'idle';
            phaseTime = 0;
            openProgress = 1; // 保持打開
            winner = null;
            nameEruption.active = false;
            nameEruption.phase = 'idle';
            // 不清空粒子，讓現有粒子自然消散
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

    /* ============================================================
     // ─── MarbleRace — 圓球賽跑抽獎動畫模組 ───
       改造自 MarbleRace.html，適配 lottery.js Driver 介面
       類型：連續型（類似 lottoAir），使用 waitForNextPick()
       ============================================================ */
    const marbleRace = (() => {
        // ─── 賽道參數 ───
        const TRACK = {
            pegRadius: 10,
            pegRows: 10,
            pegCols: 7,
            rowSpacing: 85,
            gateY: 80,
            marbleRadius: 16,
            gravity: 620,
            restitution: 0.55,
            friction: 0.992,
            finishPadding: 80,
            stuckThreshold: 15,
            stuckTime: 1.5,
            raceTimeout: 55,
        };

        const MARBLE_COLORS = [
            '#FF4757', '#FF6B81', '#FFA502', '#FFDD59',
            '#2ED573', '#7BED9F', '#1E90FF', '#70A1FF',
            '#A855F7', '#D946EF', '#FF6348', '#ECCC68',
            '#2BCBBA', '#4BCFFA', '#FC427B', '#FD7272',
            '#3742fa', '#ff6b6b', '#feca57', '#54a0ff',
        ];

        // ─── 內部狀態 ───
        const mrState = {
            running: false,
            phase: 'idle', // idle | countdown | racing | finished
            marbles: [],
            pegs: [],
            finishY: 0,
            trackTop: 0,
            trackBottom: 0,
            trackLeft: 0,
            trackRight: 0,
            gateOpen: false,
            rankings: [],
            cameraY: 0,
            targetCameraY: 0,
            countdownValue: 3,
            countdownTimer: 0,
            raceTimer: 0,
            ctx: null,
            dpr: 1,
            rafId: null,
            last: 0,
            holdSeconds: 5,
            pending: [],
            winnerQueue: [],
            totalWinners: 0,
            waiters: [],
            W: 0,
            H: 0,
            finishShowcaseActive: false,
            finishShowcaseTimer: 0,
            finishShowcaseWinners: [],
        };

        const mrParticles = [];
        const FINISH_SHOWCASE = {
            moveDuration: 0.9,
            settleDuration: 0.45,
        };

        // ─── 粒子系統 ───
        const spawnParticles = (x, y, count = 20, power = 200, hueA = 0, hueB = 360) => {
            for (let i = 0; i < count; i++) {
                const a = rand(0, TAU);
                const sp = power * rand(0.3, 1);
                mrParticles.push({
                    x, y,
                    vx: Math.cos(a) * sp,
                    vy: Math.sin(a) * sp,
                    life: rand(0.4, 0.9),
                    t: 0,
                    r: rand(1.5, 4),
                    hue: rand(hueA, hueB),
                    drag: rand(0.93, 0.98),
                });
            }
        };

        const updateParticles = (dt) => {
            for (let i = mrParticles.length - 1; i >= 0; i--) {
                const p = mrParticles[i];
                p.t += dt;
                if (p.t >= p.life) { mrParticles.splice(i, 1); continue; }
                p.vy += 400 * dt;
                p.vx *= Math.pow(p.drag, dt * 60);
                p.vy *= Math.pow(p.drag, dt * 60);
                p.x += p.vx * dt;
                p.y += p.vy * dt;
            }
        };

        const drawParticles = () => {
            const { ctx } = mrState;
            if (!ctx || !mrParticles.length) return;
            ctx.save();
            ctx.globalCompositeOperation = 'lighter';
            mrParticles.forEach((p) => {
                const k = 1 - p.t / p.life;
                ctx.globalAlpha = clamp(k, 0, 1) * 0.85;
                ctx.fillStyle = `hsl(${p.hue} 90% 65%)`;
                ctx.beginPath();
                ctx.arc(p.x, p.y - mrState.cameraY, p.r * lerp(0.8, 2, k), 0, TAU);
                ctx.fill();
            });
            ctx.restore();
            ctx.globalAlpha = 1;
        };

        // ─── Canvas ───
        const resizeCanvas = () => {
            if (!lottoCanvasEl) return false;
            const wrapperRect = lottoWrapEl?.getBoundingClientRect();
            const rect = wrapperRect && wrapperRect.width && wrapperRect.height
                ? wrapperRect
                : lottoCanvasEl.getBoundingClientRect();
            if (!rect.width || !rect.height) return false;

            mrState.dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
            lottoCanvasEl.width = Math.floor(rect.width * mrState.dpr);
            lottoCanvasEl.height = Math.floor(rect.height * mrState.dpr);
            mrState.ctx = lottoCanvasEl.getContext('2d');
            if (mrState.ctx) {
                mrState.ctx.setTransform(mrState.dpr, 0, 0, mrState.dpr, 0, 0);
            }
            mrState.W = rect.width;
            mrState.H = rect.height;
            return true;
        };

        // ─── 賽道生成 ───
        const generateTrack = () => {
            const { W, H } = mrState;
            const trackW = Math.min(420, W * 0.75);
            const cx = W * 0.5;
            mrState.trackLeft = cx - trackW / 2;
            mrState.trackRight = cx + trackW / 2;
            mrState.trackTop = TRACK.gateY;

            mrState.pegs = [];
            const startY = TRACK.gateY + 100;
            for (let row = 0; row < TRACK.pegRows; row++) {
                const y = startY + row * TRACK.rowSpacing;
                const isOdd = row % 2 === 1;
                const cols = isOdd ? TRACK.pegCols - 1 : TRACK.pegCols;
                const offset = isOdd ? (trackW / TRACK.pegCols) / 2 : 0;
                const spacing = trackW / TRACK.pegCols;
                for (let col = 0; col < cols; col++) {
                    const px = mrState.trackLeft + spacing / 2 + col * spacing + offset;
                    const jx = rand(-3, 3);
                    const jy = rand(-3, 3);
                    mrState.pegs.push({
                        x: px + jx, y: y + jy,
                        r: TRACK.pegRadius,
                        isSpecial: Math.random() < 0.12,
                    });
                }
            }
            mrState.finishY = startY + TRACK.pegRows * TRACK.rowSpacing + TRACK.finishPadding;
            mrState.trackBottom = mrState.finishY + 100;
        };

        // ─── 彈珠名稱池 ───
        const buildNamePool = () => {
            const eligible = state.eligibleNames?.length ? [...state.eligibleNames] : [];
            const won = state.winners.map((w) => w.employee_name).filter(Boolean);
            const base = eligible.concat(won).filter(Boolean);
            // 不去重 — 可重複中獎時同一人可能出現多顆彈珠
            if (base.length === 0) {
                return Array.from({ length: 10 }, (_, i) => `Ball ${i + 1}`);
            }
            return shuffle(base);
        };

        const shortLabel = (name) => {
            if (!name) return '??';
            const chars = Array.from(name);
            if (chars.length <= 3) return name;
            return `${chars.slice(0, 3).join('')}…`;
        };

        // ─── 建立彈珠 ───
        const createMarbles = () => {
            mrState.marbles = [];
            mrState.rankings = [];
            const names = buildNamePool();
            const n = names.length;
            const trackW = mrState.trackRight - mrState.trackLeft;
            const cols = Math.min(n, 6);
            const spacingX = Math.min(28, (trackW - 30) / cols);
            const spacingY = 22;
            const cx = (mrState.trackLeft + mrState.trackRight) / 2;

            for (let i = 0; i < n; i++) {
                const col = i % cols;
                const row = Math.floor(i / cols);
                const x = cx - ((cols - 1) * spacingX) / 2 + col * spacingX + rand(-2, 2);
                const y = TRACK.gateY + 10 + row * spacingY + rand(-2, 2);
                mrState.marbles.push({
                    name: names[i],
                    label: shortLabel(names[i]),
                    color: MARBLE_COLORS[i % MARBLE_COLORS.length],
                    x, y,
                    vx: rand(-15, 15),
                    vy: rand(-5, 5),
                    r: TRACK.marbleRadius,
                    restitution: rand(0.45, 0.65),
                    mass: rand(0.9, 1.1),
                    finished: false,
                    rank: 0,
                    stuckTimer: 0,
                });
            }
        };

        const clearFinishShowcase = () => {
            mrState.finishShowcaseActive = false;
            mrState.finishShowcaseTimer = 0;
            mrState.finishShowcaseWinners = [];
        };

        const triggerFinishShowcase = () => {
            if (mrState.finishShowcaseActive || mrState.totalWinners <= 0) return;

            const winners = mrState.rankings.slice(0, mrState.totalWinners);
            if (winners.length < mrState.totalWinners) return;

            mrState.finishShowcaseActive = true;
            mrState.finishShowcaseTimer = 0;
            mrState.finishShowcaseWinners = winners.map((marble, index) => ({
                marble,
                rank: marble.rank || index + 1,
                sourceX: marble.x,
                sourceY: marble.y - mrState.cameraY,
            }));
        };

        const getFinishShowcaseDurationMs = () => (FINISH_SHOWCASE.moveDuration + FINISH_SHOWCASE.settleDuration) * 1000;

        // ─── 物理引擎 ───
        const updatePhysics = (dt) => {
            const g = TRACK.gravity;
            for (const m of mrState.marbles) {
                if (m.finished) {
                    // 已衝線：緩慢停下
                    m.vy += g * 0.3 * dt;
                    m.x += m.vx * dt; m.y += m.vy * dt;
                    m.vx *= 0.96; m.vy *= 0.96;
                    if (m.y > mrState.trackBottom - m.r) {
                        m.y = mrState.trackBottom - m.r;
                        m.vy *= -0.2;
                        if (Math.abs(m.vy) < 5) m.vy = 0;
                    }
                    continue;
                }

                if (!mrState.gateOpen) {
                    // 閘門未開：輕微重力 + 閘門限制
                    m.vy += g * 0.3 * dt;
                    m.x += m.vx * dt; m.y += m.vy * dt;
                    m.vx *= Math.pow(TRACK.friction, dt * 60);
                    m.vy *= Math.pow(TRACK.friction, dt * 60);
                    if (m.y + m.r > TRACK.gateY + 70) {
                        m.y = TRACK.gateY + 70 - m.r;
                        m.vy *= -TRACK.restitution;
                    }
                    if (m.y - m.r < TRACK.gateY - 10) {
                        m.y = TRACK.gateY - 10 + m.r;
                        m.vy *= -0.3;
                    }
                    if (m.x - m.r < mrState.trackLeft) { m.x = mrState.trackLeft + m.r; m.vx *= -TRACK.restitution; }
                    if (m.x + m.r > mrState.trackRight) { m.x = mrState.trackRight - m.r; m.vx *= -TRACK.restitution; }
                    continue;
                }

                // 比賽中：完整重力
                m.vy += g * dt;

                // 防卡住機制
                const speed = Math.hypot(m.vx, m.vy);
                if (speed < TRACK.stuckThreshold) {
                    m.stuckTimer += dt;
                    if (m.stuckTimer > TRACK.stuckTime) {
                        m.vx += rand(-180, 180);
                        m.vy += rand(80, 200);
                        m.stuckTimer = 0;
                        spawnParticles(m.x, m.y, 6, 60, 180, 220);
                    }
                } else {
                    m.stuckTimer = Math.max(0, m.stuckTimer - dt * 2);
                }

                if (Math.random() < 0.03 * dt * 60) { m.vx += rand(-60, 60); }

                m.x += m.vx * dt;
                m.y += m.vy * dt;
                m.vx *= Math.pow(TRACK.friction, dt * 60);
                m.vy *= Math.pow(TRACK.friction, dt * 60);

                // 牆壁碰撞
                if (m.x - m.r < mrState.trackLeft) { m.x = mrState.trackLeft + m.r; m.vx = Math.abs(m.vx) * m.restitution; }
                if (m.x + m.r > mrState.trackRight) { m.x = mrState.trackRight - m.r; m.vx = -Math.abs(m.vx) * m.restitution; }

                // 障礙物碰撞
                for (const peg of mrState.pegs) {
                    const dx = m.x - peg.x;
                    const dy = m.y - peg.y;
                    const dist = Math.hypot(dx, dy);
                    const minDist = m.r + peg.r;
                    if (dist < minDist && dist > 0) {
                        const nx = dx / dist, ny = dy / dist;
                        m.x += nx * (minDist - dist);
                        m.y += ny * (minDist - dist);
                        const vn = m.vx * nx + m.vy * ny;
                        if (vn < 0) {
                            m.vx -= (1 + m.restitution) * vn * nx;
                            m.vy -= (1 + m.restitution) * vn * ny;
                            m.vx += (-ny) * rand(-40, 40) * 0.08;
                            m.vy += nx * rand(-20, 20) * 0.05;
                        }
                        if (Math.abs(vn) > 100 && Math.random() < 0.25) {
                            const hue = peg.isSpecial ? rand(0, 30) : rand(200, 280);
                            spawnParticles(peg.x, peg.y, 4, 80, hue, hue + 40);
                        }
                    }
                }

                // 終點線檢查
                if (m.y > mrState.finishY && !m.finished) {
                    m.finished = true;
                    m.rank = mrState.rankings.length + 1;

                    // 從 winnerQueue 取出真正的得獎者名字
                    if (mrState.winnerQueue.length > 0) {
                        const realName = mrState.winnerQueue.shift();
                        m.name = realName;
                        m.label = shortLabel(realName);
                    }

                    mrState.rankings.push(m);

                    if (m.rank === 1) {
                        spawnParticles(m.x, m.y, 60, 300, 40, 65);
                    } else {
                        spawnParticles(m.x, m.y, 12, 120, 0, 360);
                    }

                    // resolve waitForNextPick
                    if (mrState.waiters.length > 0) {
                        const resolve = mrState.waiters.shift();
                        resolve();
                    }

                    if (mrState.rankings.length >= mrState.marbles.length) {
                        mrState.phase = 'finished';
                    }
                }

                // 超時：強制衝線
                if (mrState.raceTimer > TRACK.raceTimeout && !m.finished) {
                    m.finished = true;
                    m.rank = mrState.rankings.length + 1;
                    // 從 winnerQueue 取出真正的得獎者名字
                    if (mrState.winnerQueue.length > 0) {
                        const realName = mrState.winnerQueue.shift();
                        m.name = realName;
                        m.label = shortLabel(realName);
                    }
                    mrState.rankings.push(m);
                    spawnParticles(m.x, m.y, 6, 60, 0, 360);
                    if (mrState.waiters.length > 0) {
                        const resolve = mrState.waiters.shift();
                        resolve();
                    }
                    if (mrState.rankings.length >= mrState.marbles.length) {
                        mrState.phase = 'finished';
                    }
                }
            }

            // 彈珠間碰撞
            const arr = mrState.marbles;
            for (let i = 0; i < arr.length; i++) {
                if (arr[i].finished) continue;
                for (let j = i + 1; j < arr.length; j++) {
                    if (arr[j].finished) continue;
                    const a = arr[i], b = arr[j];
                    const dx = b.x - a.x, dy = b.y - a.y;
                    const dist = Math.hypot(dx, dy);
                    const minD = a.r + b.r;
                    if (dist >= minD || dist === 0) continue;

                    const nx = dx / dist, ny = dy / dist;
                    const overlap = minD - dist;
                    const ma = a.mass, mb = b.mass, sum = ma + mb;
                    a.x -= nx * overlap * (mb / sum);
                    a.y -= ny * overlap * (mb / sum);
                    b.x += nx * overlap * (ma / sum);
                    b.y += ny * overlap * (ma / sum);

                    const rvx = b.vx - a.vx, rvy = b.vy - a.vy;
                    const vn = rvx * nx + rvy * ny;
                    if (vn > 0) continue;
                    const e = Math.min(a.restitution, b.restitution);
                    const jj = -(1 + e) * vn / (1 / ma + 1 / mb);
                    a.vx -= jj * nx / ma; a.vy -= jj * ny / ma;
                    b.vx += jj * nx / mb; b.vy += jj * ny / mb;
                }
            }
        };

        // ─── 攝影機 ───
        const updateCamera = (dt) => {
            const { H } = mrState;
            if (mrState.finishShowcaseActive) {
                mrState.targetCameraY = Math.max(0, mrState.finishY - H * 0.52);
                mrState.cameraY = lerp(mrState.cameraY, mrState.targetCameraY, Math.min(1, dt * 3.5));
                return;
            }
            if (mrState.phase !== 'racing' && mrState.phase !== 'finished') {
                mrState.targetCameraY = 0;
                mrState.cameraY = lerp(mrState.cameraY, 0, Math.min(1, dt * 3));
                return;
            }
            let furthestY = 0;
            let leadCount = 0;
            for (const m of mrState.marbles) {
                if (!m.finished && m.y > furthestY) furthestY = m.y;
                if (!m.finished) leadCount++;
            }
            if (leadCount === 0) furthestY = mrState.finishY;
            const viewCenter = H * 0.4;
            mrState.targetCameraY = Math.max(0, furthestY - viewCenter);
            mrState.cameraY = lerp(mrState.cameraY, mrState.targetCameraY, Math.min(1, dt * 2.5));
        };

        // ─── 繪製 ───
        const lightenColor = (hex, pct) => {
            const num = parseInt(hex.replace('#', ''), 16);
            let r = (num >> 16) & 255, g = (num >> 8) & 255, b = num & 255;
            r = Math.min(255, r + pct); g = Math.min(255, g + pct); b = Math.min(255, b + pct);
            return `rgb(${r},${g},${b})`;
        };

        const drawFrame = () => {
            const { ctx, W, H } = mrState;
            if (!ctx) return;
            const camY = mrState.cameraY;

            // 背景
            ctx.fillStyle = '#0a0e17';
            ctx.fillRect(0, 0, W, H);
            const g1 = ctx.createRadialGradient(W / 2, H * 0.3, 50, W / 2, H * 0.3, Math.max(W, H) * 0.8);
            g1.addColorStop(0, 'rgba(99,102,241,0.06)');
            g1.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.fillStyle = g1; ctx.fillRect(0, 0, W, H);

            // 賽道背景
            const tl = mrState.trackLeft, tr = mrState.trackRight, tw = tr - tl;
            ctx.fillStyle = 'rgba(255,255,255,0.02)';
            ctx.fillRect(tl, mrState.trackTop - camY, tw, mrState.trackBottom - mrState.trackTop);
            ctx.strokeStyle = 'rgba(255,255,255,0.08)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(tl, mrState.trackTop - camY); ctx.lineTo(tl, mrState.trackBottom - camY);
            ctx.moveTo(tr, mrState.trackTop - camY); ctx.lineTo(tr, mrState.trackBottom - camY);
            ctx.stroke();

            // 閘門
            if (!mrState.gateOpen) {
                const gy = TRACK.gateY + 70 - camY;
                ctx.fillStyle = 'rgba(239,68,68,0.4)';
                ctx.fillRect(tl, gy - 4, tw, 8);
                ctx.fillStyle = 'rgba(239,68,68,0.7)';
                ctx.font = '700 13px Inter,ui-sans-serif,system-ui,sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('READY', (tl + tr) / 2, gy - 10);
            }

            // 障礙物
            for (const peg of mrState.pegs) {
                const py = peg.y - camY;
                if (py < -30 || py > H + 30) continue;
                ctx.beginPath();
                ctx.arc(peg.x, py, peg.r, 0, TAU);
                ctx.fillStyle = peg.isSpecial ? 'rgba(239,68,68,0.5)' : 'rgba(255,255,255,0.15)';
                ctx.fill();
                ctx.beginPath();
                ctx.arc(peg.x - peg.r * 0.2, py - peg.r * 0.25, peg.r * 0.35, 0, TAU);
                ctx.fillStyle = 'rgba(255,255,255,0.12)';
                ctx.fill();
            }

            // 終點線
            const fy = mrState.finishY - camY;
            if (fy > -20 && fy < H + 20) {
                const segW = 12;
                const segs = Math.ceil(tw / segW);
                for (let i = 0; i < segs; i++) {
                    ctx.fillStyle = i % 2 === 0 ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.3)';
                    ctx.fillRect(tl + i * segW, fy - 4, segW, 8);
                }
                ctx.fillStyle = 'rgba(255,255,255,0.6)';
                ctx.font = '700 11px Inter,ui-sans-serif,system-ui,sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('🏁 FINISH', (tl + tr) / 2, fy - 10);
            }

            // 彈珠（已完成的先畫，比賽中的後畫）
            const finishedArr = mrState.marbles.filter((m) => m.finished);
            const racingArr = mrState.marbles.filter((m) => !m.finished);
            [...finishedArr, ...racingArr].forEach((m) => {
                const sy = m.y - camY;
                if (sy < -40 || sy > H + 40) return;
                const r = m.r;
                const isShowcaseWinner = mrState.finishShowcaseActive
                    && mrState.finishShowcaseWinners.some((item) => item.marble === m);
                const marbleAlpha = mrState.finishShowcaseActive
                    ? (isShowcaseWinner ? 0.12 : (m.finished ? 0.22 : 0.08))
                    : 1;

                // 陰影
                ctx.globalAlpha = 0.25 * marbleAlpha;
                ctx.fillStyle = 'black';
                ctx.beginPath(); ctx.arc(m.x + 2, sy + 3, r * 1.05, 0, TAU); ctx.fill();

                // 主球體
                ctx.globalAlpha = (m.finished ? 0.5 : 1) * marbleAlpha;
                const grad = ctx.createRadialGradient(m.x - r * 0.3, sy - r * 0.3, r * 0.15, m.x, sy, r * 1.1);
                grad.addColorStop(0, lightenColor(m.color, 30));
                grad.addColorStop(1, m.color);
                ctx.fillStyle = grad;
                ctx.beginPath(); ctx.arc(m.x, sy, r, 0, TAU); ctx.fill();

                // 高光
                ctx.globalAlpha = 0.35 * marbleAlpha;
                ctx.fillStyle = '#fff';
                ctx.beginPath(); ctx.arc(m.x - r * 0.28, sy - r * 0.32, r * 0.35, 0, TAU); ctx.fill();
                ctx.globalAlpha = 1;

                // 名稱 — 放在球中間
                ctx.globalAlpha = marbleAlpha;
                ctx.fillStyle = m.finished ? 'rgba(255,255,255,0.5)' : 'rgba(255,255,255,0.95)';
                const fontSize = Math.max(7, Math.min(11, r * 0.7));
                ctx.font = `700 ${fontSize}px Inter,ui-sans-serif,system-ui,sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(m.label, m.x, sy + 1);
                ctx.globalAlpha = 1;

                // 名次徽章
                if (m.finished && m.rank <= 3) {
                    ctx.globalAlpha = marbleAlpha;
                    const medals = ['🥇', '🥈', '🥉'];
                    ctx.font = '12px serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(medals[m.rank - 1], m.x, sy);
                    ctx.globalAlpha = 1;
                }
            });

            // ─── 即時排名面板（右側，動態支援多人）───
            if (!mrState.finishShowcaseActive && (mrState.phase === 'countdown' || mrState.phase === 'racing' || mrState.phase === 'finished')) {
                const finished = mrState.rankings.slice();
                const racing = mrState.marbles
                    .filter((m) => !m.finished)
                    .sort((a, b) => b.y - a.y);
                const liveRank = [...finished, ...racing];
                const winCount = mrState.totalWinners || 1;

                // 決定要顯示幾筆：至少顯示「所有中獎者 + 1 個落榜者」，且最少顯示 10 筆，最多不超過總參與人數
                let maxShow = Math.max(10, winCount + 1);
                maxShow = Math.min(maxShow, liveRank.length);
                if (maxShow === 0) maxShow = 1;

                // 根據畫面高度動態縮放
                const maxAvailableHeight = H - 32; // 上下 padding 16
                let headerH = 48;
                let pad = 16;
                let rowH = 40;
                let panelW = 260;
                let scale = 1.0;

                // 如果裝不下，就要縮小 rowH
                if (headerH + maxShow * rowH + pad > maxAvailableHeight) {
                    rowH = (maxAvailableHeight - headerH - pad) / maxShow;
                    // 如果 rowH 太小，整體 UI 也等比縮小
                    if (rowH < 30) {
                        scale = Math.max(0.5, rowH / 30);
                        rowH = 30 * scale;
                        panelW = 260 * scale;
                        headerH = 48 * Math.min(1, scale * 1.2);
                        pad = 16 * scale;
                    }
                }

                const panelH = headerH + maxShow * rowH + pad;
                const px = W - panelW - Math.max(20, W * 0.05);
                const py = 16;

                // 面板背景
                ctx.save();
                ctx.globalAlpha = 0.78;
                ctx.fillStyle = '#0d1117';
                ctx.beginPath();
                ctx.roundRect(px, py, panelW, panelH, 12 * scale);
                ctx.fill();
                ctx.globalAlpha = 0.15;
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 1;
                ctx.stroke();
                ctx.restore();

                // 標題
                ctx.fillStyle = 'rgba(255,255,255,0.75)';
                ctx.font = `700 ${18 * Math.min(1, scale * 1.1)}px Inter,ui-sans-serif,system-ui,sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('🏆 即時排名', px + panelW / 2, py + headerH / 2);

                // 分隔線
                ctx.strokeStyle = 'rgba(255,255,255,0.12)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(px + 10 * scale, py + headerH);
                ctx.lineTo(px + panelW - 10 * scale, py + headerH);
                ctx.stroke();

                const medals = ['🥇', '🥈', '🥉'];

                for (let i = 0; i < maxShow; i++) {
                    const m = liveRank[i];
                    if (!m) break;
                    const ry = py + headerH + i * rowH + rowH / 2;
                    const isWinningSpot = i < winCount;

                    // 高亮背景：前三名維持金銀銅，其他中獎名額給綠色微光
                    if (isWinningSpot) {
                        ctx.globalAlpha = m.finished ? 0.15 : 0.08;
                        if (i === 0) ctx.fillStyle = '#fbbf24';
                        else if (i === 1) ctx.fillStyle = '#94a3b8';
                        else if (i === 2) ctx.fillStyle = '#cd7f32';
                        else ctx.fillStyle = '#10b981'; // 綠色表示中獎區間

                        const rr = 6;
                        ctx.beginPath();
                        ctx.roundRect(px + 6, ry - rowH / 2 + 3, panelW - 12, rowH - 6, rr);
                        ctx.fill();
                        ctx.globalAlpha = 1;
                    }

                    // 名次
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'middle';
                    if (i < 3 && m.finished) {
                        ctx.font = `${20 * scale}px serif`;
                        ctx.fillText(medals[i], px + 12 * scale, ry);
                    } else {
                        ctx.font = `700 ${16 * scale}px Inter,ui-sans-serif,system-ui,sans-serif`;
                        ctx.fillStyle = i < 3 ? 'rgba(255,255,255,0.7)' : 'rgba(255,255,255,0.35)';
                        ctx.fillText(`${i + 1}`, px + 14 * scale, ry);
                    }

                    // 色球標記
                    ctx.beginPath();
                    ctx.arc(px + 44 * scale, ry, 7 * scale, 0, TAU);
                    ctx.fillStyle = m.color;
                    ctx.fill();
                    // 高光
                    ctx.globalAlpha = 0.3;
                    ctx.fillStyle = '#fff';
                    ctx.beginPath();
                    ctx.arc(px + 42 * scale, ry - 2 * scale, 2.5 * scale, 0, TAU);
                    ctx.fill();
                    ctx.globalAlpha = 1;

                    // 名稱
                    ctx.font = `600 ${15 * scale}px Inter,ui-sans-serif,system-ui,sans-serif`;
                    ctx.fillStyle = m.finished
                        ? (i < 3 ? 'rgba(255,255,255,0.95)' : 'rgba(255,255,255,0.7)')
                        : 'rgba(255,255,255,0.55)';
                    ctx.fillText(m.label, px + 60 * scale, ry);

                    // 狀態標記
                    if (m.finished) {
                        ctx.font = `500 ${11 * scale}px Inter,ui-sans-serif,system-ui,sans-serif`;
                        ctx.fillStyle = isWinningSpot ? 'rgba(52,211,153,0.9)' : 'rgba(255,255,255,0.4)';
                        ctx.textAlign = 'right';
                        ctx.fillText(isWinningSpot ? '✓ 中獎' : '完成', px + panelW - 14 * scale, ry);
                        ctx.textAlign = 'left';
                    }

                    // 畫在最後一個中獎者下方的錄取線
                    if (i === winCount - 1 && i < maxShow - 1) {
                        const lineY = ry + rowH / 2;
                        ctx.save();
                        ctx.strokeStyle = 'rgba(239, 68, 68, 0.6)'; // 紅色虛線
                        ctx.lineWidth = Math.max(0.5, 1 * scale);
                        ctx.setLineDash([4 * scale, 4 * scale]);
                        ctx.beginPath();
                        ctx.moveTo(px + 10 * scale, lineY);
                        ctx.lineTo(px + panelW - 10 * scale, lineY);
                        ctx.stroke();
                        ctx.restore();

                        // 膠囊標籤
                        ctx.save();
                        ctx.fillStyle = 'rgba(239, 68, 68, 0.85)';
                        ctx.beginPath();
                        ctx.roundRect(px + panelW / 2 - 25 * scale, lineY - 8 * scale, 50 * scale, 16 * scale, 8 * scale);
                        ctx.fill();
                        ctx.fillStyle = '#fff';
                        ctx.font = `700 ${9 * scale}px Inter,ui-sans-serif,system-ui,sans-serif`;
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText('中獎線', px + panelW / 2, lineY);
                        ctx.restore();
                    }
                }
            }

            // 粒子
            drawParticles();

            if (mrState.finishShowcaseActive && mrState.finishShowcaseWinners.length > 0) {
                const moveProgress = clamp(mrState.finishShowcaseTimer / FINISH_SHOWCASE.moveDuration, 0, 1);
                const settleProgress = clamp(
                    (mrState.finishShowcaseTimer - FINISH_SHOWCASE.moveDuration) / FINISH_SHOWCASE.settleDuration,
                    0,
                    1
                );
                const easedMove = easeOutBack(moveProgress, 1.08);
                const overlayAlpha = clamp(moveProgress * 1.2, 0, 0.84);
                const spotlightAlpha = clamp(0.2 + settleProgress * 0.16, 0.2, 0.36);
                const pulse = 1 + Math.sin(mrState.finishShowcaseTimer * 5.2) * 0.025 * (0.3 + settleProgress * 0.7);
                const winners = mrState.finishShowcaseWinners;
                const count = winners.length;
                const maxWidth = W * 0.74;
                const gap = count > 1 ? clamp(W * 0.014, 4, 18) : 0;
                const radiusByWidth = (maxWidth - gap * Math.max(0, count - 1)) / (Math.max(1, count) * 2);
                const radius = Math.max(12, Math.min(radiusByWidth, Math.min(W, H) * 0.115));
                const totalWidth = count * radius * 2 + Math.max(0, count - 1) * gap;
                const centerY = H * 0.48;
                const startX = W / 2 - totalWidth / 2 + radius;

                ctx.save();
                ctx.fillStyle = `rgba(2, 6, 23, ${overlayAlpha})`;
                ctx.fillRect(0, 0, W, H);

                const spotlight = ctx.createRadialGradient(W / 2, centerY, radius * 0.5, W / 2, centerY, Math.max(W, H) * 0.5);
                spotlight.addColorStop(0, `rgba(250, 204, 21, ${spotlightAlpha})`);
                spotlight.addColorStop(0.45, 'rgba(59, 130, 246, 0.12)');
                spotlight.addColorStop(1, 'rgba(2, 6, 23, 0)');
                ctx.fillStyle = spotlight;
                ctx.fillRect(0, 0, W, H);
                ctx.restore();

                winners.forEach((item, index) => {
                    const marble = item.marble;
                    const targetX = startX + index * (radius * 2 + gap);
                    const targetY = centerY;
                    const x = lerp(item.sourceX, targetX, easedMove);
                    const y = lerp(item.sourceY, targetY, easedMove);
                    const r = radius * pulse;

                    ctx.save();

                    const halo = ctx.createRadialGradient(x, y, r * 0.45, x, y, r * 1.95);
                    halo.addColorStop(0, 'rgba(251, 191, 36, 0.45)');
                    halo.addColorStop(0.55, 'rgba(245, 158, 11, 0.18)');
                    halo.addColorStop(1, 'rgba(245, 158, 11, 0)');
                    ctx.fillStyle = halo;
                    ctx.beginPath();
                    ctx.arc(x, y, r * 2.05, 0, TAU);
                    ctx.fill();

                    ctx.globalAlpha = 0.28 + settleProgress * 0.2;
                    ctx.strokeStyle = 'rgba(255,255,255,0.95)';
                    ctx.lineWidth = Math.max(2, r * 0.07);
                    ctx.beginPath();
                    ctx.arc(x, y, r * 1.12, 0, TAU);
                    ctx.stroke();
                    ctx.globalAlpha = 1;

                    const grad = ctx.createRadialGradient(x - r * 0.35, y - r * 0.35, r * 0.18, x, y, r * 1.08);
                    grad.addColorStop(0, lightenColor(marble.color, 38));
                    grad.addColorStop(1, marble.color);
                    ctx.fillStyle = grad;
                    ctx.beginPath();
                    ctx.arc(x, y, r, 0, TAU);
                    ctx.fill();

                    ctx.globalAlpha = 0.42;
                    ctx.fillStyle = '#fff';
                    ctx.beginPath();
                    ctx.arc(x - r * 0.3, y - r * 0.34, r * 0.34, 0, TAU);
                    ctx.fill();
                    ctx.globalAlpha = 1;

                    ctx.fillStyle = 'rgba(255,255,255,0.98)';
                    ctx.font = `800 ${Math.max(14, Math.min(28, r * 0.54))}px Inter,ui-sans-serif,system-ui,sans-serif`;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(marble.label, x, y + 1);

                    ctx.restore();
                });
            }

            // 倒數計時覆蓋
            if (mrState.phase === 'countdown') {
                const elapsed = mrState.countdownTimer;
                const step = Math.floor(elapsed / 0.8);
                let text = '';
                let color = '#6366f1';
                if (step === 0) { text = '3'; color = '#FF4757'; }
                else if (step === 1) { text = '2'; color = '#FFA502'; }
                else if (step === 2) { text = '1'; color = '#2ED573'; }
                else if (step === 3) { text = 'GO!'; color = '#6366f1'; }

                if (text) {
                    const frac = (elapsed % 0.8) / 0.8;
                    const scale = frac < 0.3 ? lerp(2.5, 0.9, frac / 0.3)
                        : frac < 0.6 ? lerp(0.9, 1.05, (frac - 0.3) / 0.3)
                            : lerp(1.05, 1, (frac - 0.6) / 0.4);
                    const alpha = frac < 0.3 ? frac / 0.3 : frac > 0.7 ? 1 - (frac - 0.7) / 0.3 : 1;
                    ctx.save();
                    ctx.globalAlpha = clamp(alpha, 0, 1);
                    ctx.translate(W / 2, H / 2);
                    ctx.scale(scale, scale);
                    ctx.font = '900 120px Inter,ui-sans-serif,system-ui,sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = color;
                    ctx.shadowColor = color;
                    ctx.shadowBlur = 40;
                    ctx.fillText(text, 0, 0);
                    ctx.restore();
                    ctx.globalAlpha = 1;
                }
            }
        };

        // ─── 動畫迴圈 ───
        const loop = (now) => {
            if (!mrState.running) return;
            const rawDt = (now - mrState.last) / 1000;
            mrState.last = now;
            const dt = Math.min(rawDt, 0.033);

            if (mrState.phase === 'countdown') {
                mrState.countdownTimer += dt;
                // 彈珠在閘門前晃動
                updatePhysics(dt);
                if (mrState.countdownTimer >= 3.2) {
                    // 開閘！holdSeconds 控制比賽節奏：秒數越大重力越小，比賽越慢
                    const hs = Math.max(5, mrState.holdSeconds || 15);
                    TRACK.gravity = clamp(9300 / hs, 150, 1800);
                    TRACK.raceTimeout = hs * 2; // 安全超時：設定秒數的兩倍
                    mrState.gateOpen = true;
                    mrState.phase = 'racing';
                    mrState.raceTimer = 0;
                    const cx = (mrState.trackLeft + mrState.trackRight) / 2;
                    spawnParticles(cx, TRACK.gateY + 70, 40, 200, 40, 65);
                }
            } else if (mrState.phase === 'racing') {
                mrState.raceTimer += dt;
                const steps = 3;
                const subDt = dt / steps;
                for (let s = 0; s < steps; s++) updatePhysics(subDt);
                updateCamera(dt);
            } else if (mrState.phase === 'idle') {
                updatePhysics(dt);
            } else if (mrState.phase === 'finished') {
                updatePhysics(dt);
                updateCamera(dt);
            }

            if (mrState.finishShowcaseActive) {
                mrState.finishShowcaseTimer += dt;
            }

            updateParticles(dt);
            drawFrame();

            mrState.rafId = requestAnimationFrame(loop);
        };

        // ─── 重置 ───
        const reset = () => {
            mrParticles.length = 0;
            mrState.marbles = [];
            mrState.rankings = [];
            mrState.pending = [];
            mrState.winnerQueue = [];
            mrState.totalWinners = 0;
            mrState.waiters.forEach((r) => r()); // resolve all pending
            mrState.waiters = [];
            mrState.phase = 'idle';
            mrState.gateOpen = false;
            mrState.cameraY = 0;
            mrState.targetCameraY = 0;
            mrState.countdownTimer = 0;
            mrState.raceTimer = 0;
            clearFinishShowcase();

            if (!resizeCanvas()) return;
            generateTrack();
            createMarbles();
        };

        // ─── 公開 API ───
        const ensureReady = (forceReset = false) => {
            if (forceReset || mrState.marbles.length === 0) {
                reset();
                // 開始動畫迴圈（待機繪製）
                if (!mrState.running) {
                    mrState.running = true;
                    mrState.last = performance.now();
                    mrState.rafId = requestAnimationFrame(loop);
                }
            }
        };

        const start = () => {
            if (mrState.running) return;
            mrState.running = true;
            mrState.last = performance.now();
            mrState.rafId = requestAnimationFrame(loop);
        };

        const startDraw = () => {
            // 開始倒數
            if (mrState.phase === 'racing' || mrState.phase === 'countdown') return;
            const savedQueue = [...mrState.winnerQueue]; // 保留 queue
            const savedTotal = mrState.totalWinners;
            reset();
            mrState.winnerQueue = savedQueue; // reset 後恢復
            mrState.totalWinners = savedTotal;
            if (!mrState.running) {
                mrState.running = true;
                mrState.last = performance.now();
                mrState.rafId = requestAnimationFrame(loop);
            }
            mrState.phase = 'countdown';
            mrState.countdownTimer = 0;
        };

        const stop = () => {
            mrState.running = false;
            if (mrState.rafId) { cancelAnimationFrame(mrState.rafId); mrState.rafId = null; }
            // resolve 所有等待中的 Promise
            mrState.waiters.forEach((r) => r());
            mrState.waiters = [];
            mrState.phase = 'idle';
            mrState.gateOpen = false;
            clearFinishShowcase();
            clearCanvas();
        };

        const startFinishShowcase = () => {
            triggerFinishShowcase();
        };

        const setWinnerQueue = (names) => {
            mrState.winnerQueue = [...names];
            mrState.totalWinners = names.length;
        };

        const waitForNextPick = () => {
            return new Promise((resolve) => {
                // 如果已有彈珠衝線但尚未被消費
                const consumed = mrState.waiters.length;
                if (mrState.rankings.length > consumed) {
                    resolve();
                    return;
                }
                mrState.waiters.push(resolve);
            });
        };

        const ensureCount = (n) => {
            // 確保彈珠數量足夠
            if (mrState.marbles.length < n) {
                const names = buildNamePool();
                const trackW = mrState.trackRight - mrState.trackLeft;
                const cx = (mrState.trackLeft + mrState.trackRight) / 2;
                for (let i = mrState.marbles.length; i < n; i++) {
                    const name = names[i % names.length] || `Ball ${i + 1}`;
                    const x = cx + rand(-trackW * 0.3, trackW * 0.3);
                    const y = TRACK.gateY + 10 + rand(-5, 30);
                    mrState.marbles.push({
                        name, label: shortLabel(name),
                        color: MARBLE_COLORS[i % MARBLE_COLORS.length],
                        x, y,
                        vx: rand(-15, 15), vy: rand(-5, 5),
                        r: TRACK.marbleRadius,
                        restitution: rand(0.45, 0.65),
                        mass: rand(0.9, 1.1),
                        finished: false, rank: 0, stuckTimer: 0,
                    });
                }
            }
        };

        const setHoldSeconds = (s) => { mrState.holdSeconds = s; };

        const resize = () => {
            if (!mrState.running) return;
            resizeCanvas();
            generateTrack();
            // 彈珠維持在賽道內
            const cx = (mrState.trackLeft + mrState.trackRight) / 2;
            mrState.marbles.forEach((m) => {
                m.x = clamp(m.x, mrState.trackLeft + m.r + 2, mrState.trackRight - m.r - 2);
            });
            drawFrame();
        };

        return {
            ensureReady, start, startDraw, stop,
            setWinnerQueue, waitForNextPick, ensureCount,
            setHoldSeconds, resize, startFinishShowcase, getFinishShowcaseDurationMs,
        };
    })();

    // ─── BattleTop — 戰鬥陀螺抽獎動畫模組 ───
    const battleTop = (() => {
        // ─── 設定 ───
        const BT = {
            friction: 0.08,   // 每秒角速度衰減率
            minOmega: 1.5,    // 低於此才算倒下
            entrySpeed: 300,    // 入場速度
            elasticity: 0.9,    // 碰撞彈性
            walElasticity: 0.85,   // 牆壁反彈彈性
            omegaTransfer: 0.15,   // 碰撞轉移角速度比例
            graceTime: 4.0,    // 入場後多少秒才能被淘汰
        };
        const BATTLE_REVEAL_DURATION_MS = 3500;

        const WAVE_CAP = 15; // 場地同時最多顯示的陀螺數（超過此數啟用波浪模式）

        const TOP_COLORS = [
            '#f43f5e', '#f97316', '#eab308', '#22c55e',
            '#06b6d4', '#6366f1', '#a855f7', '#ec4899',
            '#10b981', '#3b82f6', '#f59e0b', '#dc2626',
        ];

        // ─── 內部狀態 ───
        const btState = {
            running: false,
            phase: 'idle', // idle | countdown | entry | battle | reveal | finished
            tops: [],
            dying: [],
            particles: [],
            countdownTimer: 0,
            entryTimer: 0,
            battleTimer: 0,
            revealTimer: 0,
            arenaR: 0,
            cx: 0,
            cy: 0,
            ctx: null,
            dpr: 1,
            rafId: null,
            last: 0,
            holdSeconds: 5,
            winnerQueue: [],
            totalWinners: 0,
            waiters: [],
            pendingResolves: 0,   // 已觸發但尚未被 waitForNextPick 消費的次數
            W: 0,
            H: 0,
            wavePool: [],           // 等待入場的名稱佇列
            waveMode: false,        // 是否啟用波浪模式（人數 > WAVE_CAP）
            totalParticipants: 0,   // 實際參與人數（用於 UI 提示）
            waveBatchWinners: 0,      // 場上當前受保護的中獎者數量
            waveElimTimer: 0,         // 淘汰計時累積
            waveElimInterval: 0,      // 淘汰間隔
            waveStartedElim: false,   // 是否已進入淘汰期
            entryQueue: [],           // 待入場的名稱佇列
            entrySpawnTimer: 0,       // spawn 計時
            entrySpawnInterval: 0.15, // spawn 間隔
        };

        // ─── Canvas ───
        const resizeCanvas = () => {
            if (!lottoCanvasEl) return false;
            const rect = (lottoWrapEl ?? lottoCanvasEl).getBoundingClientRect();
            if (!rect.width || !rect.height) return false;

            btState.dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
            btState.W = rect.width;
            btState.H = rect.height;
            lottoCanvasEl.width = Math.floor(btState.W * btState.dpr);
            lottoCanvasEl.height = Math.floor(btState.H * btState.dpr);
            btState.ctx = lottoCanvasEl.getContext('2d');
            if (btState.ctx) btState.ctx.setTransform(btState.dpr, 0, 0, btState.dpr, 0, 0);

            // 適應可用空間，留出 padding
            const padding = Math.min(btState.W, btState.H) * 0.08;
            btState.arenaR = Math.min(btState.W, btState.H) / 2 - padding;
            // 讓競技場稍微偏下一點，留空間給煙火和頂部
            btState.cx = btState.W / 2;
            btState.cy = btState.H / 2 + Math.min(btState.H * 0.05, 40);

            return true;
        };

        // ─── 色彩工具 ───
        function parseColor(hex) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return { r, g, b };
        }
        function clampRGB(v) { return Math.max(0, Math.min(255, v)); }
        function lightenColor(hex, amt) {
            const { r, g, b } = parseColor(hex);
            return `rgb(${clampRGB(r + amt)},${clampRGB(g + amt)},${clampRGB(b + amt)})`;
        }
        function darkenColor(hex, amt) {
            return lightenColor(hex, -amt);
        }

        // ─── 名稱池 ───
        const buildNamePool = () => {
            const eligible = state.eligibleNames?.length ? [...state.eligibleNames] : [];
            const won = state.winners.map((w) => w.employee_name).filter(Boolean);
            const queued = btState.winnerQueue?.length ? [...btState.winnerQueue] : [];
            // 合併後去重：避免 WS 尚未到達時 eligible 與 queued 重複
            const base = [...new Set([...eligible, ...won, ...queued])].filter(Boolean);
            if (base.length === 0) {
                return Array.from({ length: 10 }, (_, i) => `陀螺 ${i + 1}`);
            }
            return shuffle(base);
        };

        // ─── 陀螺建立 ───
        function calcTopRadius(n) {
            const ar = btState.arenaR || 250; // fallback 防止初始化前為 0
            return clamp(ar * 1.28 / Math.sqrt(Math.max(1, n)), ar * 0.07, ar * 0.18);
        }

        const shortLabel = (name) => {
            if (!name) return '??';
            const chars = Array.from(name);
            if (chars.length <= 4) return name;
            return `${chars.slice(0, 4).join('')}…`;
        };

        function createTop(name, r) {
            return {
                name,
                label: shortLabel(name),
                color: TOP_COLORS[Math.floor(Math.random() * TOP_COLORS.length)],
                x: btState.cx, y: btState.cy,
                vx: 0, vy: 0,
                r: r || 28,
                omega: rand(22, 34),
                angle: rand(0, TAU),
                mass: 1,
                alive: true,
                winner: false,
                alpha: 1,
                wobble: 0,
                entryDelay: 0,
                entered: false,
                dyingTimer: 0,
                dyingDuration: 0.5,
                winnerAssigned: false, // 是否已經被分配了 winnerQueue 裡的名字
            };
        }

        const buildIdleTops = () => {
            const n = 3;
            btState.tops = [];
            for (let i = 0; i < n; i++) {
                const a = (TAU / n) * i;
                const t = createTop(`Top${i}`);
                t.label = '';
                t.r = 36;
                t.x = btState.cx + Math.cos(a) * 80;
                t.y = btState.cy + Math.sin(a) * 80;
                t.vx = 0; t.vy = 0; t.omega = 20;
                t.entered = true; t.alive = true;
                btState.tops.push(t);
            }
        };

        const createTops = () => {
            btState.tops = [];
            btState.dying = [];
            btState.wavePool = [];
            btState.waveMode = false;

            const names = buildNamePool();
            btState.totalParticipants = names.length;

            const displayNames = names.length > WAVE_CAP
                ? names.slice(0, WAVE_CAP)
                : names;

            if (names.length > WAVE_CAP) {
                btState.waveMode = true;
                btState.wavePool = names.slice(WAVE_CAP);
            }

            const n = Math.max(1, displayNames.length);
            const r = calcTopRadius(n);
            displayNames.forEach((name, i) => {
                const t = createTop(name, r);
                t.mass = r * r / (28 * 28);
                // 分散在場外圓周等待
                const a = (TAU / n) * i + rand(-0.1, 0.1);
                const spawnR = btState.arenaR + r + 20;
                t.x = btState.cx + Math.cos(a) * spawnR;
                t.y = btState.cy + Math.sin(a) * spawnR;
                t.entryDelay = rand(0, 0.8);
                t.entered = false;
                btState.tops.push(t);
            });
        };

        // ─── 粒子 ───
        function spawnParticles(x, y, count, color) {
            for (let i = 0; i < count; i++) {
                const a = rand(0, TAU);
                const spd = rand(60, 220);
                btState.particles.push({
                    x, y,
                    vx: Math.cos(a) * spd, vy: Math.sin(a) * spd,
                    r: rand(2, 5),
                    life: 1, color,
                });
            }
        }

        function spawnWaveTop() {
            if (btState.wavePool.length === 0) return;
            const name = btState.wavePool.shift();
            const r = calcTopRadius(WAVE_CAP);
            const t = createTop(name, r);
            t.mass = r * r / (28 * 28);
            const a = rand(0, TAU);
            const spawnR = btState.arenaR + t.r + 10;
            t.x = btState.cx + Math.cos(a) * spawnR;
            t.y = btState.cy + Math.sin(a) * spawnR;
            const aimAngle = Math.atan2(btState.cy - t.y, btState.cx - t.x) + rand(-0.4, 0.4);
            t.vx = Math.cos(aimAngle) * BT.entrySpeed;
            t.vy = Math.sin(aimAngle) * BT.entrySpeed;
            t.omega = rand(28, 42);  // 高初始轉速，確保能撐一段時間
            t.entered = true;        // 直接進入戰鬥，不走 entry phase
            t.alive = true;
            btState.tops.push(t);
            spawnParticles(t.x, t.y, 12, t.color); // 入場特效
        }

        // 波浪加入時同步淘汰一個非中獎者
        function defeatOneLoser() {
            const victim = getAliveTops().find(t => !t.protectedWinner && !t.winner);
            if (victim) defeat(victim, 'wave_swap');
        }

        const WAVE_WINNER_CAP = 5;

        function tagWaveBatchWinners() {
            if (!btState.waveMode || btState.winnerQueue.length === 0) return;
            const aliveTops = getAliveTops();
            const currentlyProtected = aliveTops.filter(t => t.protectedWinner).length;
            // 受保護數量上限：取 WAVE_WINNER_CAP 與實際需要的中獎者數量中較小值
            // 避免 one_by_one 模式（winnerQueue.length=1）下標記過多保護陀螺導致 alive 無法降到 targetSurvivors
            const maxProtect = Math.min(WAVE_WINNER_CAP, btState.winnerQueue.length);
            const need = maxProtect - currentlyProtected;
            if (need <= 0) return;
            const unprotected = aliveTops.filter(t => !t.protectedWinner);
            for (let i = 0; i < need && i < unprotected.length; i++) {
                unprotected[i].protectedWinner = true;
            }
        }

        function updateParticles(dt) {
            for (let i = btState.particles.length - 1; i >= 0; i--) {
                const p = btState.particles[i];
                p.x += p.vx * dt;
                p.y += p.vy * dt;
                p.vx *= (1 - 3 * dt);
                p.vy *= (1 - 3 * dt);
                p.life -= dt * 1.8;
                if (p.life <= 0) btState.particles.splice(i, 1);
            }
        }

        // ─── 物理 ───
        function defeat(top, reason) {
            top.alive = false;
            spawnParticles(top.x, top.y, 25, top.color);
            btState.dying.push({ ...top, dyingTimer: 0, dyingDuration: 0.5 });
        }

        function getAliveTops() {
            return btState.tops.filter(t => t.alive && t.entered);
        }

        function updatePhysics(dt) {
            const { tops, cx, cy, arenaR } = btState;
            // 根據 holdSeconds 調整保護期 (預設 holdSeconds=5 時, graceTime=4)
            const actualGraceTime = Math.max(2, btState.holdSeconds * 0.8);
            const inGrace = btState.battleTimer < actualGraceTime;
            const targetSurvivors = Math.max(1, btState.winnerQueue.length);
            const isOvertime = btState.battleTimer > btState.holdSeconds;

            tops.forEach(t => {
                if (!t.alive || !t.entered) return;

                t.x += t.vx * dt;
                t.y += t.vy * dt;
                t.angle += t.omega * dt;

                if (isOvertime && btState.phase === 'battle') {
                    // 超時：強制大幅增加阻力，快速淘汰
                    t.omega *= (1 - 0.8 * dt);
                    t.vx *= (1 - 1.2 * dt);
                    t.vy *= (1 - 1.2 * dt);
                } else {
                    // 依靠 holdSeconds 縮放衰減率，讓比賽能撐到指定時間
                    const frictionScale = 5 / Math.max(1, btState.holdSeconds);
                    t.omega *= (1 - BT.friction * frictionScale * dt);
                    t.vx *= (1 - 0.05 * dt);
                    t.vy *= (1 - 0.05 * dt);
                }

                const omegaRatio = clamp(t.omega / 18, 0, 1);
                t.wobble = lerp(0.4, 0, omegaRatio);

                // 波浪模式：受保護的中獎者不會失速
                if (t.protectedWinner) {
                    t.omega = Math.max(t.omega, BT.minOmega + 1);
                }

                // 失去動力倒下
                if (!inGrace && t.omega < BT.minOmega && btState.phase === 'battle') {
                    let aliveCount = 0;
                    for (let n of tops) { if (n.alive && n.entered) aliveCount++; }
                    // 波浪模式下：wavePool 還有人時允許淘汰（確保 wavePool 能排乾）
                    // 非波浪模式或 wavePool 已空：只在存活數超過目標才淘汰
                    const canEliminate = btState.wavePool.length > 0 || aliveCount > targetSurvivors;
                    if (canEliminate) {
                        defeat(t, 'spin_out');
                        return; // 淘汰後不再計算後續碰撞
                    } else {
                        // 已經是倖存者，不准倒下，維持最低轉速等待揭曉
                        t.omega = BT.minOmega + 0.1;
                    }
                }

                // 圓形邊界處理
                const dx = t.x - cx;
                const dy = t.y - cy;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const boundary = arenaR - t.r;

                if (dist > boundary && dist > 0.001) {
                    const nx = dx / dist;
                    const ny = dy / dist;
                    t.x = cx + nx * boundary;
                    t.y = cy + ny * boundary;
                    const dot = t.vx * nx + t.vy * ny;
                    t.vx -= (1 + BT.walElasticity) * dot * nx;
                    t.vy -= (1 + BT.walElasticity) * dot * ny;
                    t.omega *= 0.92;
                    spawnParticles(t.x + nx * (-t.r), t.y + ny * (-t.r), 4, t.color);
                }
            });

            // 碰撞
            for (let i = 0; i < tops.length; i++) {
                for (let j = i + 1; j < tops.length; j++) {
                    const a = tops[i], b = tops[j];
                    if (!a.alive || !b.alive || !a.entered || !b.entered) continue;

                    const dx = b.x - a.x;
                    const dy = b.y - a.y;
                    const distSq = dx * dx + dy * dy;
                    const minDist = a.r + b.r;

                    if (distSq < minDist * minDist && distSq > 0.001) {
                        const dist = Math.sqrt(distSq);
                        const nx = dx / dist;
                        const ny = dy / dist;

                        const overlap = (minDist - dist) / 2;
                        const totalMass = a.mass + b.mass;
                        a.x -= nx * overlap * (b.mass / totalMass) * 2;
                        a.y -= ny * overlap * (b.mass / totalMass) * 2;
                        b.x += nx * overlap * (a.mass / totalMass) * 2;
                        b.y += ny * overlap * (a.mass / totalMass) * 2;

                        const dvx = b.vx - a.vx;
                        const dvy = b.vy - a.vy;
                        const dot = dvx * nx + dvy * ny;
                        if (dot < 0) {
                            const impulse = (-(1 + BT.elasticity) * dot) / totalMass;
                            const boost = 1.15;
                            a.vx -= impulse * b.mass * nx * boost;
                            a.vy -= impulse * b.mass * ny * boost;
                            b.vx += impulse * a.mass * nx * boost;
                            b.vy += impulse * a.mass * ny * boost;

                            const maxSpd = 600;
                            const sA = Math.hypot(a.vx, a.vy);
                            if (sA > maxSpd) { a.vx = a.vx / sA * maxSpd; a.vy = a.vy / sA * maxSpd; }
                            const sB = Math.hypot(b.vx, b.vy);
                            if (sB > maxSpd) { b.vx = b.vx / sB * maxSpd; b.vy = b.vy / sB * maxSpd; }
                        }

                        const omegaDiff = a.omega - b.omega;
                        a.omega -= omegaDiff * BT.omegaTransfer;
                        b.omega += omegaDiff * BT.omegaTransfer;
                        a.omega = Math.max(0, a.omega);
                        b.omega = Math.max(0, b.omega);

                        spawnParticles((a.x + b.x) / 2, (a.y + b.y) / 2, 6, '#ffe066');
                    }
                }
            }

            // dying
            for (let i = btState.dying.length - 1; i >= 0; i--) {
                const t = btState.dying[i];
                t.dyingTimer += dt;
                t.alpha = clamp(1 - t.dyingTimer / t.dyingDuration, 0, 1);
                t.r *= (1 + dt * 1.5);
                if (t.dyingTimer >= t.dyingDuration) btState.dying.splice(i, 1);
            }

            updateParticles(dt);
        }

        // ─── Phase 流程 ───
        function startEntry() {
            btState.phase = 'entry';
            btState.entryTimer = 0;
            // 清空 tops，改用 staggered spawn（逐一射入）
            const names = btState.tops.map(t => t.name);
            btState.tops = [];
            btState.entryQueue = [...names];
            btState.entrySpawnTimer = 0;
            // 根據數量算 spawn 間隔，1.2 秒內全部入場
            btState.entrySpawnInterval = Math.min(0.15, 1.2 / Math.max(1, names.length));
        }

        function assignWinnersFromQueue(winners) {
            // 從 queue 取出真正中獎者的名字，蓋到倖存陀螺上
            winners.forEach(t => {
                if (t.winnerAssigned) return;
                t.winnerAssigned = true;
                t.winner = true;
                if (btState.winnerQueue.length > 0) {
                    const realName = btState.winnerQueue.shift();
                    t.name = realName;
                    t.label = shortLabel(realName);
                }
                // 若 waiter 已在等待則直接 resolve，否則計入 pending
                if (btState.waiters.length > 0) {
                    btState.waiters.shift()();
                } else {
                    btState.pendingResolves++;
                }
            });
        }

        function startReveal(winners) {
            btState.phase = 'reveal';
            btState.revealTimer = 0;
            assignWinnersFromQueue(winners);

            // 淘汰所有非中獎的存活陀螺
            btState.tops.forEach(t => {
                if (t.alive && t.entered && !t.winner) {
                    defeat(t, 'reveal_lose');
                }
            });

            winners.forEach((t, i) => {
                spawnParticles(t.x, t.y, 50, '#fbbf24');
                const targetAngle = (TAU / winners.length) * i;
                const targetR = winners.length === 1 ? 0 : btState.arenaR * 0.3;
                t.vx = (btState.cx + Math.cos(targetAngle) * targetR - t.x) * 0.8;
                t.vy = (btState.cy + Math.sin(targetAngle) * targetR - t.y) * 0.8;
            });

            // 確保所有尚未消耗的 queue 都被排出
            // (波浪模式下存活陀螺數可能少於 winnerQueue，剩餘需全部 resolve)
            while (btState.winnerQueue.length > 0) {
                btState.winnerQueue.shift();
                if (btState.waiters.length > 0) {
                    btState.waiters.shift()();
                } else {
                    btState.pendingResolves++;
                }
            }
        }

        // ─── 繪製 ───
        function drawHex(ctx, cx, cy, r) {
            ctx.beginPath();
            for (let i = 0; i < 6; i++) {
                const a = (Math.PI / 3) * i;
                i === 0 ? ctx.moveTo(cx + Math.cos(a) * r, cy + Math.sin(a) * r)
                    : ctx.lineTo(cx + Math.cos(a) * r, cy + Math.sin(a) * r);
            }
            ctx.closePath();
            ctx.stroke();
        }

        function drawArena() {
            const { ctx, cx, cy, arenaR, W, H } = btState;

            // 地板背景 (去除全畫面黑影以免遮擋其他 UI，改在圓形範圍內漸層)
            const floorGrad = ctx.createRadialGradient(cx, cy - 40, 0, cx, cy, arenaR);
            floorGrad.addColorStop(0, 'rgba(30, 37, 51, 0.8)');
            floorGrad.addColorStop(1, 'rgba(17, 22, 31, 1)');
            ctx.beginPath();
            ctx.arc(cx, cy, arenaR, 0, TAU);
            ctx.fillStyle = floorGrad;
            ctx.fill();

            // 六角格
            ctx.save();
            ctx.clip();
            ctx.globalAlpha = 0.04;
            ctx.strokeStyle = '#6366f1';
            ctx.lineWidth = 0.8;
            const step = 36;
            for (let yy = cy - arenaR; yy < cy + arenaR; yy += step * 0.866) {
                for (let xx = cx - arenaR; xx < cx + arenaR; xx += step * 1.5) {
                    drawHex(ctx, xx, yy, step / 2);
                    drawHex(ctx, xx + step * 0.75, yy + step * 0.433, step / 2);
                }
            }
            ctx.restore();

            // 競技場邊框
            ctx.beginPath();
            ctx.arc(cx, cy, arenaR, 0, TAU);
            ctx.strokeStyle = 'rgba(99,102,241,0.5)';
            ctx.lineWidth = 3;
            ctx.stroke();

            // 內側發光圈
            ctx.beginPath();
            ctx.arc(cx, cy, arenaR - 8, 0, TAU);
            ctx.strokeStyle = 'rgba(99,102,241,0.15)';
            ctx.lineWidth = 6;
            ctx.stroke();
        }

        function drawTop(t, alpha = 1) {
            const { ctx } = btState;
            const { x, y, r, color, angle, omega, winner, wobble } = t;

            const tiltAngle = wobble > 0.05 ? Math.sin(angle * 2.5) * wobble * 0.4 : 0;

            ctx.save();
            ctx.globalAlpha = alpha;
            ctx.translate(x, y);
            ctx.rotate(tiltAngle);

            if (winner) {
                const glowR = r + 12 + Math.sin(Date.now() * 0.008) * 5;
                ctx.shadowColor = '#fbbf24';
                ctx.shadowBlur = 35;
                ctx.beginPath();
                ctx.arc(0, 0, glowR, 0, TAU);
                ctx.strokeStyle = `rgba(251,191,36,${0.5 + Math.sin(Date.now() * 0.01) * 0.25})`;
                ctx.lineWidth = 4;
                ctx.stroke();
                ctx.shadowBlur = 0;
            }

            // 扇形葉片
            ctx.save();
            ctx.rotate(angle);
            ctx.beginPath();
            ctx.arc(0, 0, r, 0, TAU);
            ctx.clip();

            const blades = 6;
            const bladeAngle = TAU / blades;
            for (let i = 0; i < blades; i++) {
                const a0 = bladeAngle * i;
                const a1 = a0 + bladeAngle * 0.5;
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.arc(0, 0, r, a0, a1);
                ctx.closePath();
                ctx.fillStyle = i % 2 === 0 ? color : lightenColor(color, 55);
                ctx.globalAlpha = alpha;
                ctx.fill();

                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.arc(0, 0, r, a1, a0 + bladeAngle);
                ctx.closePath();
                ctx.fillStyle = darkenColor(color, 40);
                ctx.fill();
            }

            ctx.beginPath();
            ctx.arc(0, 0, r * 0.38, 0, TAU);
            ctx.fillStyle = 'rgba(0,0,0,0.65)';
            ctx.fill();

            ctx.beginPath();
            ctx.arc(0, 0, r - 2, 0, TAU);
            ctx.strokeStyle = `rgba(255,255,255,0.18)`;
            ctx.lineWidth = 2.5;
            ctx.stroke();

            const speedRatio = clamp(omega / 35, 0, 1);
            if (speedRatio > 0.3) {
                ctx.globalAlpha = alpha * speedRatio * 0.18;
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 1;
                for (let i = 0; i < 12; i++) {
                    const a = (TAU / 12) * i;
                    ctx.beginPath();
                    ctx.moveTo(Math.cos(a) * r * 0.4, Math.sin(a) * r * 0.4);
                    ctx.lineTo(Math.cos(a) * r * 0.95, Math.sin(a) * r * 0.95);
                    ctx.stroke();
                }
                ctx.globalAlpha = alpha;
            }
            ctx.restore();

            // 名字
            if (t.label) {
                ctx.fillStyle = '#fff';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                const fontSize = Math.max(8, Math.min(13, r * 0.42));
                ctx.font = `700 ${fontSize}px Inter,system-ui,sans-serif`;
                ctx.fillText(t.label, 0, 0);
            }

            const hlGrad = ctx.createRadialGradient(-r * 0.3, -r * 0.3, 0, 0, 0, r * 0.7);
            hlGrad.addColorStop(0, 'rgba(255,255,255,0.28)');
            hlGrad.addColorStop(1, 'rgba(255,255,255,0)');
            ctx.beginPath();
            ctx.arc(0, 0, r, 0, TAU);
            ctx.fillStyle = hlGrad;
            ctx.fill();

            ctx.restore();
        }

        function drawCountdown() {
            if (btState.phase !== 'countdown') return;
            const { ctx, cx, cy } = btState;
            const t = btState.countdownTimer;
            const step = Math.floor(t);
            let text = '', color = '#fff';

            if (step === 0) { text = '3'; color = '#ef4444'; }
            else if (step === 1) { text = '2'; color = '#f97316'; }
            else if (step === 2) { text = '1'; color = '#22c55e'; }
            else if (step === 3) { text = 'GO!'; color = '#6366f1'; }

            if (!text) return;
            const frac = t - step;
            const scl = frac < 0.3 ? lerp(2.2, 0.9, frac / 0.3)
                : frac < 0.7 ? lerp(0.9, 1.05, (frac - 0.3) / 0.4)
                    : lerp(1.05, 1, (frac - 0.7) / 0.3);
            const alpha = frac < 0.2 ? frac / 0.2 : frac > 0.75 ? 1 - (frac - 0.75) / 0.25 : 1;

            ctx.save();
            ctx.globalAlpha = clamp(alpha, 0, 1);
            ctx.translate(cx, cy);
            ctx.scale(scl, scl);
            ctx.font = '900 100px Inter,system-ui,sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = color;
            ctx.shadowColor = color;
            ctx.shadowBlur = 40;
            ctx.fillText(text, 0, 0);
            ctx.restore();
        }

        function drawParticlesRender() {
            const { ctx } = btState;
            btState.particles.forEach(p => {
                ctx.globalAlpha = clamp(p.life, 0, 1);
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, TAU);
                ctx.fillStyle = p.color;
                ctx.fill();
            });
            ctx.globalAlpha = 1;
        }



        function drawFrame() {
            const { ctx, W, H } = btState;
            if (!ctx) return;
            ctx.clearRect(0, 0, W, H);

            drawArena();

            btState.dying.forEach(t => drawTop(t, t.alpha * 0.6));
            btState.tops.forEach(t => {
                if (t.alive || t.entered) drawTop(t, t.alive ? 1 : 0);
            });

            drawParticlesRender();
            drawCountdown();
        }

        // ─── 迴圈與控制 ───
        function loop(now) {
            if (!btState.running) return;
            const dt = Math.min((now - btState.last) / 1000, 0.05);
            btState.last = now;

            const { phase } = btState;

            if (phase === 'countdown') {
                btState.countdownTimer += dt;
                btState.tops.forEach(t => { t.angle += t.omega * dt; });
                if (btState.countdownTimer >= 3.5) startEntry();
            }
            else if (phase === 'entry') {
                btState.entryTimer += dt;
                btState.entrySpawnTimer += dt;

                // 定時從 entryQueue spawn 一個陀螺（逐一射入）
                while (btState.entrySpawnTimer >= btState.entrySpawnInterval && btState.entryQueue.length > 0) {
                    btState.entrySpawnTimer -= btState.entrySpawnInterval;
                    const name = btState.entryQueue.shift();
                    const totalCount = Math.max(btState.tops.length + btState.entryQueue.length + 1, 1);
                    const r = calcTopRadius(totalCount);
                    const t = createTop(name, r);
                    t.mass = r * r / (28 * 28);
                    const a = rand(0, TAU);
                    const spawnR = btState.arenaR + t.r + 10;
                    t.x = btState.cx + Math.cos(a) * spawnR;
                    t.y = btState.cy + Math.sin(a) * spawnR;
                    const aimAngle = Math.atan2(btState.cy - t.y, btState.cx - t.x) + rand(-0.4, 0.4);
                    t.vx = Math.cos(aimAngle) * BT.entrySpeed;
                    t.vy = Math.sin(aimAngle) * BT.entrySpeed;
                    t.omega = rand(28, 42);
                    t.entered = true;
                    t.alive = true;
                    btState.tops.push(t);
                    spawnParticles(t.x, t.y, 12, t.color);
                }

                updatePhysics(dt);

                // 全部 spawn 完且至少 1.2 秒後進入 battle
                if (btState.entryQueue.length === 0 && btState.entryTimer > 1.2) {
                    btState.phase = 'battle';
                    btState.battleTimer = 0;
                }
            }
            else if (phase === 'battle') {
                btState.battleTimer += dt;
                updatePhysics(dt);
                const alive = getAliveTops();
                // 動態保證倖存者數量至少能符合 winnerQueue 剩下的數量，但不會小於1
                const targetSurvivors = Math.max(1, btState.winnerQueue.length);
                const actualGraceTime = Math.max(2, btState.holdSeconds * 0.8);

                // 波浪模式：分批淘汰非中獎者、補入新陀螺
                const isOvertimePhase = btState.battleTimer > btState.holdSeconds;
                const elimPhaseStart = btState.holdSeconds / 6;

                if (btState.waveMode) {
                    // 確保場上保持足夠數量的受保護中獎者
                    tagWaveBatchWinners();

                    if (btState.battleTimer > elimPhaseStart && !isOvertimePhase) {
                        // 進入定時淘汰期
                        if (!btState.waveStartedElim) {
                            btState.waveStartedElim = true;
                            const remaining = alive.filter(t => !t.protectedWinner).length + btState.wavePool.length;
                            const remainTime = btState.holdSeconds - btState.battleTimer;
                            btState.waveElimInterval = remaining > 0 ? remainTime / remaining : 1;
                            btState.waveElimTimer = btState.waveElimInterval; // 立即觸發第一次
                        }

                        btState.waveElimTimer += dt;
                        if (btState.waveElimTimer >= btState.waveElimInterval) {
                            btState.waveElimTimer = 0;
                            // 直接淘汰一個非中獎者
                            defeatOneLoser();
                            // 補入新陀螺
                            if (btState.wavePool.length > 0) {
                                spawnWaveTop();
                                tagWaveBatchWinners();
                            }
                        }
                    } else if (!btState.waveStartedElim && btState.wavePool.length > 0) {
                        // 淘汰期前：補充到 WAVE_CAP，同時 1:1 置換
                        if (alive.length < WAVE_CAP) {
                            const slots = WAVE_CAP - alive.length;
                            for (let s = 0; s < slots && btState.wavePool.length > 0; s++) {
                                spawnWaveTop();
                            }
                            tagWaveBatchWinners();
                        } else {
                            // 場上已滿 WAVE_CAP：節流 1:1 置換
                            if (!btState.waveSwapTimer) btState.waveSwapTimer = 0;
                            btState.waveSwapTimer += dt;
                            const swapInterval = 1.5; // 每 1.5 秒置換一個
                            if (btState.waveSwapTimer >= swapInterval) {
                                btState.waveSwapTimer = 0;
                                defeatOneLoser();
                                spawnWaveTop();
                                tagWaveBatchWinners();
                            }
                        }
                    }

                    // 超時：補滿所有剩餘
                    if (isOvertimePhase && btState.wavePool.length > 0) {
                        while (btState.wavePool.length > 0) {
                            spawnWaveTop();
                        }
                    }
                }

                // 揭曉條件：超過保護期 + wavePool 已清空 + 倖存者 ≤ 目標
                if (btState.battleTimer > actualGraceTime
                    && btState.wavePool.length === 0
                    && alive.length <= targetSurvivors) {
                    if (btState.winnerQueue.length > 0) {
                        startReveal(alive);
                    }
                }
            }
            else if (phase === 'reveal') {
                btState.revealTimer += dt;
                btState.tops.filter(t => t.winner).forEach(t => {
                    t.angle += 8 * dt;
                    t.vx *= 0.88;
                    t.vy *= 0.88;
                });
                updateParticles(dt);
                for (let i = btState.dying.length - 1; i >= 0; i--) {
                    const d = btState.dying[i];
                    d.dyingTimer += dt;
                    d.alpha = clamp(1 - d.dyingTimer / d.dyingDuration, 0, 1);
                    if (d.dyingTimer >= d.dyingDuration) btState.dying.splice(i, 1);
                }
                if (btState.revealTimer >= BATTLE_REVEAL_DURATION_MS / 1000) {
                    btState.phase = 'finished';
                }
            }
            else if (phase === 'idle') {
                btState.tops.forEach(t => { t.angle += 10 * dt; });
            }
            else if (phase === 'finished') {
                btState.tops.filter(t => t.winner).forEach(t => { t.angle += 6 * dt; });
            }

            drawFrame();
            btState.rafId = requestAnimationFrame(loop);
        }

        const reset = () => {
            btState.particles = [];
            btState.tops = [];
            btState.dying = [];
            btState.winnerQueue = [];
            btState.totalWinners = 0;
            btState.waiters.forEach((r) => r()); // resolve all pending
            btState.waiters = [];
            btState.pendingResolves = 0;
            btState.phase = 'idle';
            btState.countdownTimer = 0;
            btState.entryTimer = 0;
            btState.battleTimer = 0;
            btState.revealTimer = 0;
            btState.wavePool = [];
            btState.waveMode = false;
            btState.totalParticipants = 0;
            btState.waveBatchWinners = 0;
            btState.waveElimTimer = 0;
            btState.waveElimInterval = 0;
            btState.waveStartedElim = false;
            btState.entryQueue = [];
            btState.entrySpawnTimer = 0;

            if (!resizeCanvas()) return;
            buildIdleTops();
        };

        // ─── 公開 API ───
        const ensureReady = (forceReset = false) => {
            if (forceReset || btState.tops.length === 0) {
                reset();
                if (!btState.running) {
                    btState.running = true;
                    btState.last = performance.now();
                    btState.rafId = requestAnimationFrame(loop);
                }
            }
        };

        const start = () => {
            if (btState.running) return;
            btState.running = true;
            btState.last = performance.now();
            btState.rafId = requestAnimationFrame(loop);
        };

        const startDraw = () => {
            // 從 idle 進入準備戰鬥
            if (btState.phase === 'battle' || btState.phase === 'countdown' || btState.phase === 'entry') return;
            const savedQueue = [...btState.winnerQueue];
            const savedTotal = btState.totalWinners;
            reset();
            btState.winnerQueue = savedQueue;
            btState.totalWinners = savedTotal;
            createTops();

            // 波浪模式下自動延長 holdSeconds，確保每個中獎者至少 0.5 秒
            if (btState.waveMode) {
                btState.holdSeconds = Math.max(btState.holdSeconds, btState.totalWinners * 0.5);
            }

            if (!btState.running) {
                btState.running = true;
                btState.last = performance.now();
                btState.rafId = requestAnimationFrame(loop);
            }
            btState.phase = 'countdown';
            btState.countdownTimer = 0;
        };

        const stop = () => {
            btState.running = false;
            if (btState.rafId) { cancelAnimationFrame(btState.rafId); btState.rafId = null; }
            btState.waiters.forEach((r) => r());
            btState.waiters = [];
            btState.pendingResolves = 0;   // 清空未消費的 pending
            btState.tops = [];             // 清空殘留陀螺，確保下次 ensureReady(false) 也會 reset
            btState.phase = 'idle';
            btState.wavePool = [];
            btState.waveMode = false;
            btState.waveBatchWinners = 0;
            btState.waveElimTimer = 0;
            btState.waveElimInterval = 0;
            btState.waveStartedElim = false;
            btState.entryQueue = [];
            btState.entrySpawnTimer = 0;
            clearCanvas();
        };

        const setWinnerQueue = (names) => {
            btState.winnerQueue = [...names];
            btState.totalWinners = names.length;
        };

        const waitForNextPick = () => {
            if (btState.pendingResolves > 0) {
                btState.pendingResolves--;
                return Promise.resolve();
            }
            return new Promise((resolve) => {
                btState.waiters.push(resolve);
            });
        };

        const setHoldSeconds = (s) => { btState.holdSeconds = s; };

        const resize = () => {
            if (!btState.running) return;
            resizeCanvas();
        };

        return {
            ensureReady, start, startDraw, stop,
            setWinnerQueue, waitForNextPick,
            setHoldSeconds, resize,
            getRevealDurationMs: () => BATTLE_REVEAL_DURATION_MS,
        };
    })();

    const stopAllAnimations = () => {
        lottoAir.stop();
        redPacketRain.stop();
        scratchCard.stop();
        treasureChest.stop();
        bigTreasureChest.stop();
        marbleRace.stop();
        battleTop.stop();
    };

    const appendWinner = (winner) => {
        if (!state.isDrawing) return;
        if (!winner) return;
        state.winners = [...state.winners, winner];
        render();
        const lastItem = winnerListEl?.lastElementChild;
        if (lastItem) {
            animateListItem(lastItem);
        }
    };

    const getIdleSlotCount = (max) => {
        const remaining = Math.max(0, (state.currentPrize?.winnersCount ?? 0) - (state.winners?.length ?? 0));
        const eligibleCount = state.eligibleNames?.length ?? 0;
        return Math.max(0, Math.min(remaining, eligibleCount, max));
    };

    const animationDrivers = {
        lotto_air: {
            prepareIdle: ({ forceReset = false } = {}) => {
                lottoAir.ensureReady(forceReset);
            },
            prepareToDraw: () => {
                lottoAir.ensureReady();
                lottoAir.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
                if (state.currentPrize?.drawMode === 'one_by_one' && state.eligibleNames?.length) {
                    lottoAir.ensureCount(state.eligibleNames.length);
                }
            },
            revealWinners: async (winners, runtime = {}) => {
                const append = runtime.appendWinner ?? appendWinner;
                const isRunStale = runtime.isRunStale ?? (() => false);
                const clock = runtime.clock;
                if (isRunStale()) return;

                if (state.currentPrize?.drawMode === 'one_by_one') {
                    // 先 startDraw() 讓 ensureReady() 完成可能的 reset()，
                    // 再 setWinners() 設定 pending，避免 WebSocket 已更新
                    // eligibleNames 導致計數不符觸發 reset 清空 pending。
                    lottoAir.startDraw();
                    lottoAir.setWinners([winners[0]?.employee_name ?? randomLabel()], {
                        resetBalls: false,
                        resetPicked: false,
                    });
                    await lottoAir.waitForNextPick();
                    if (isRunStale()) return;
                    append(winners[0]);
                    if (clock) await clock.waitUntilEnd();
                } else {
                    lottoAir.setWinners(winners.map((winner) => winner.employee_name ?? randomLabel()));
                    lottoAir.startDraw();
                    for (const winner of winners) {
                        await lottoAir.waitForNextPick();
                        if (isRunStale()) return;
                        append(winner);
                    }
                    if (clock) {
                        const finalWait = Math.min(1500, clock.remainingMs());
                        if (finalWait > 50) await delay(finalWait);
                    }
                }
            },
            stop: () => lottoAir.stop(),
        },
        red_packet: {
            prepareIdle: ({ forceReset = false } = {}) => {
                redPacketRain.showIdlePackets(forceReset);
            },
            prepareToDraw: () => {
                redPacketRain.ensureReady();
                redPacketRain.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
                redPacketRain.start();
            },
            revealWinners: async (winners, runtime = {}) => {
                const append = runtime.appendWinner ?? appendWinner;
                const isRunStale = runtime.isRunStale ?? (() => false);
                const clock = runtime.clock;
                if (isRunStale()) return;

                if (state.currentPrize?.drawMode === 'one_by_one') {
                    redPacketRain.setWinner(winners[0]?.employee_name ?? '???');
                    await redPacketRain.waitForReveal();
                    if (isRunStale()) return;
                    append(winners[0]);
                    if (clock) await clock.waitUntilEnd();
                    else await delay(1500);
                    if (isRunStale()) return;
                    redPacketRain.stop();
                } else {
                    for (let i = 0; i < winners.length; i++) {
                        const winner = winners[i];
                        redPacketRain.setWinner(winner.employee_name ?? '???');
                        await redPacketRain.waitForReveal();
                        if (isRunStale()) return;
                        append(winner);
                        if (i < winners.length - 1) {
                            const remaining = winners.length - 1 - i;
                            const gap = clock ? Math.max(200, clock.remainingMs() * 0.4 / remaining) : 800;
                            await delay(gap * 0.55);
                            if (isRunStale()) return;
                            redPacketRain.prepareNext();
                            await delay(gap * 0.45);
                            if (isRunStale()) return;
                        }
                    }
                    if (clock) {
                        const finalWait = Math.min(1500, clock.remainingMs());
                        if (finalWait > 50) await delay(finalWait);
                    } else {
                        await delay(1500);
                    }
                    if (isRunStale()) return;
                    redPacketRain.stop();
                }
            },
            stop: () => redPacketRain.stop(),
        },
        scratch_card: {
            prepareIdle: ({ forceReset = false } = {}) => {
                scratchCard.ensureReady();
                if (!state.isDrawing) {
                    const cardCount = state.currentPrize?.drawMode === 'one_by_one'
                        ? Math.min(1, getIdleSlotCount(1))
                        : Math.min(getIdleSlotCount(9), 9);
                    if (cardCount > 0) {
                        scratchCard.showCards(cardCount, forceReset);
                    }
                }
            },
            prepareToDraw: () => {
                scratchCard.ensureReady();
                scratchCard.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
            },
            revealWinners: async (winners, runtime = {}) => {
                const append = runtime.appendWinner ?? appendWinner;
                const isRunStale = runtime.isRunStale ?? (() => false);
                const clock = runtime.clock;
                if (isRunStale()) return;

                if (state.currentPrize?.drawMode === 'one_by_one') {
                    scratchCard.setWinner(winners[0]?.employee_name ?? '???');
                    await scratchCard.waitForReveal();
                    if (isRunStale()) return;
                    append(winners[0]);
                    if (clock) await clock.waitUntilEnd();
                    else await delay(1200);
                    if (isRunStale()) return;
                    scratchCard.prepareNext();
                } else {
                    let processedCount = 0;
                    while (processedCount < winners.length) {
                        if (isRunStale()) return;
                        const batchStart = processedCount;
                        const batchCount = Math.min(winners.length - processedCount, 9);
                        const batchWinners = winners.slice(batchStart, batchStart + batchCount);
                        scratchCard.showCards(batchCount, true);
                        scratchCard.setWinners(batchWinners.map((w) => w.employee_name ?? '???'));
                        const scratchOrder = shuffle(Array.from({ length: batchCount }, (_, i) => i));
                        for (let i = 0; i < batchCount; i++) {
                            const cardIndex = scratchOrder[i];
                            await scratchCard.scratchSingleCard(cardIndex);
                            if (isRunStale()) return;
                            append(batchWinners[cardIndex]);
                            if (i < batchCount - 1) {
                                const remainingTotal = (winners.length - processedCount - i - 1);
                                const gap = clock ? Math.max(200, clock.remainingMs() * 0.3 / remainingTotal) : 1500;
                                await delay(gap);
                                if (isRunStale()) return;
                            }
                        }
                        processedCount += batchCount;
                        if (processedCount < winners.length) {
                            const batchGap = clock ? Math.max(200, clock.remainingMs() * 0.15) : 1500;
                            await delay(batchGap);
                            if (isRunStale()) return;
                        }
                    }
                    if (clock) {
                        const finalWait = Math.min(1500, clock.remainingMs());
                        if (finalWait > 50) await delay(finalWait);
                    } else {
                        await delay(1500);
                    }
                    if (isRunStale()) return;
                    scratchCard.prepareNext();
                }
            },
            stop: () => scratchCard.stop(),
        },
        treasure_chest: {
            prepareIdle: ({ forceReset = false } = {}) => {
                treasureChest.ensureReady();
                if (!state.isDrawing) {
                    const chestCount = state.currentPrize?.drawMode === 'one_by_one'
                        ? Math.min(1, getIdleSlotCount(1))
                        : Math.min(getIdleSlotCount(9), 9);
                    if (chestCount > 0) {
                        treasureChest.showChests(chestCount, forceReset);
                    }
                }
            },
            prepareToDraw: () => {
                treasureChest.ensureReady();
                treasureChest.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
            },
            revealWinners: async (winners, runtime = {}) => {
                const append = runtime.appendWinner ?? appendWinner;
                const isRunStale = runtime.isRunStale ?? (() => false);
                const clock = runtime.clock;
                if (isRunStale()) return;

                if (state.currentPrize?.drawMode === 'one_by_one') {
                    treasureChest.setWinner(winners[0]?.employee_name ?? '???');
                    await treasureChest.waitForReveal();
                    if (isRunStale()) return;
                    append(winners[0]);
                    if (clock) await clock.waitUntilEnd();
                    else await delay(1500);
                    if (isRunStale()) return;
                    treasureChest.prepareNext();
                } else {
                    let processedCount = 0;
                    while (processedCount < winners.length) {
                        if (isRunStale()) return;
                        const batchStart = processedCount;
                        const batchCount = Math.min(winners.length - processedCount, 9);
                        const batchWinners = winners.slice(batchStart, batchStart + batchCount);
                        treasureChest.showChests(batchCount, true);
                        treasureChest.setWinners(batchWinners.map((w) => w.employee_name ?? '???'));
                        const openOrder = shuffle(Array.from({ length: batchCount }, (_, i) => i));
                        for (let i = 0; i < batchCount; i++) {
                            const chestIndex = openOrder[i];
                            await treasureChest.openSingleChest(chestIndex);
                            if (isRunStale()) return;
                            append(batchWinners[chestIndex]);
                            if (i < batchCount - 1) {
                                const remainingInBatch = batchCount - 1 - i;
                                const gap = clock ? Math.max(150, clock.remainingMs() * 0.3 / (remainingInBatch + (winners.length - processedCount - batchCount))) : 1000;
                                await delay(gap);
                                if (isRunStale()) return;
                            }
                        }
                        processedCount += batchCount;
                        if (processedCount < winners.length) {
                            const batchGap = clock ? Math.max(200, clock.remainingMs() * 0.15) : 1500;
                            await delay(batchGap);
                            if (isRunStale()) return;
                        }
                    }
                    if (clock) {
                        const finalWait = Math.min(1500, clock.remainingMs());
                        if (finalWait > 50) await delay(finalWait);
                    } else {
                        await delay(1500);
                    }
                    if (isRunStale()) return;
                    treasureChest.prepareNext();
                }
            },
            stop: () => treasureChest.stop(),
        },
        big_treasure_chest: {
            prepareIdle: ({ forceReset = false } = {}) => {
                bigTreasureChest.ensureReady();
                if (!state.isDrawing) {
                    bigTreasureChest.showChest();
                }
            },
            prepareToDraw: () => {
                bigTreasureChest.ensureReady();
                bigTreasureChest.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
            },
            revealWinners: async (winners, runtime = {}) => {
                const append = runtime.appendWinner ?? appendWinner;
                const isRunStale = runtime.isRunStale ?? (() => false);
                const clock = runtime.clock;
                if (isRunStale()) return;

                if (state.currentPrize?.drawMode === 'one_by_one') {
                    bigTreasureChest.setWinner(winners[0]?.employee_name ?? '???');
                    await bigTreasureChest.waitForReveal();
                    if (isRunStale()) return;
                    append(winners[0]);
                    if (clock) await clock.waitUntilEnd();
                    bigTreasureChest.prepareNext();
                } else {
                    for (let i = 0; i < winners.length; i++) {
                        const winner = winners[i];
                        bigTreasureChest.setWinner(winner.employee_name ?? '???');
                        await bigTreasureChest.waitForReveal();
                        if (isRunStale()) return;
                        append(winner);
                        if (i < winners.length - 1) {
                            const remaining = winners.length - 1 - i;
                            const gap = clock ? Math.max(200, clock.remainingMs() * 0.3 / remaining) : 480;
                            await delay(gap * 0.55);
                            if (isRunStale()) return;
                            bigTreasureChest.prepareNext();
                            await delay(gap * 0.45);
                            if (isRunStale()) return;
                        }
                    }
                    bigTreasureChest.prepareNext();
                }
            },
            stop: () => bigTreasureChest.stop(),
        },
        marble_race: {
            prepareIdle: ({ forceReset = false } = {}) => {
                marbleRace.ensureReady(forceReset);
            },
            prepareToDraw: () => {
                marbleRace.ensureReady();
                marbleRace.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
                if (state.currentPrize?.drawMode === 'one_by_one' && state.eligibleNames?.length) {
                    marbleRace.ensureCount(state.eligibleNames.length);
                }
            },
            revealWinners: async (winners, runtime = {}) => {
                const append = runtime.appendWinner ?? appendWinner;
                const isRunStale = runtime.isRunStale ?? (() => false);
                if (isRunStale()) return;

                const playFinishShowcase = async () => {
                    await delay(3000);
                    if (isRunStale()) return;
                    marbleRace.startFinishShowcase();
                    await delay(marbleRace.getFinishShowcaseDurationMs());
                    if (isRunStale()) return;
                    await delay(2000);
                    if (!isPrizeCompleted()) {
                        state.needsAnimationReset = true;
                    }
                };

                if (state.currentPrize?.drawMode === 'one_by_one') {
                    marbleRace.setWinnerQueue([winners[0]?.employee_name ?? randomLabel()]);
                    marbleRace.startDraw();
                    await marbleRace.waitForNextPick();
                    if (isRunStale()) return;
                    append(winners[0]);
                    await playFinishShowcase();
                    if (isRunStale()) return;
                } else {
                    marbleRace.setWinnerQueue(winners.map((w) => w.employee_name ?? randomLabel()));
                    marbleRace.startDraw();
                    for (const winner of winners) {
                        await marbleRace.waitForNextPick();
                        if (isRunStale()) return;
                        append(winner);
                    }
                    await playFinishShowcase();
                    if (isRunStale()) return;
                }
            },
            stop: () => marbleRace.stop(),
        },
        battle_top: {
            prepareIdle: ({ forceReset = false } = {}) => {
                battleTop.ensureReady(forceReset);
            },
            prepareToDraw: () => {
                battleTop.ensureReady();
                const baseHold = state.currentPrize?.lottoHoldSeconds ?? 5;
                const isOneByOne = state.currentPrize?.drawMode === 'one_by_one';
                // wave pool 只包含 eligibleNames（未得獎者），winners 已離場，不計入
                const eligibleForWave = state.eligibleNames?.length ?? 0;
                // 逐一抽出時應盡量貼近設定秒數，避免可抽人數一多每抽都被拉長。
                // 額外延長只保留給一次全抽，因為那時需要在同一輪展示更多補位陀螺。
                const waveExtra = !isOneByOne && eligibleForWave > 12 ? (eligibleForWave - 12) * 1.5 : 0;
                battleTop.setHoldSeconds(baseHold + waveExtra);
            },
            revealWinners: async (winners, runtime = {}) => {
                const append = runtime.appendWinner ?? appendWinner;
                const isRunStale = runtime.isRunStale ?? (() => false);
                if (isRunStale()) return;

                const finalizeBattleRound = async () => {
                    await delay(battleTop.getRevealDurationMs());
                    if (isRunStale()) return;
                    await delay(2000);
                    if (!isPrizeCompleted()) {
                        state.needsAnimationReset = true;
                    }
                };

                if (state.currentPrize?.drawMode === 'one_by_one') {
                    battleTop.setWinnerQueue([winners[0]?.employee_name ?? randomLabel()]);
                    battleTop.startDraw();
                    await battleTop.waitForNextPick();
                    if (isRunStale()) return;
                    append(winners[0]);
                    await finalizeBattleRound();
                    if (isRunStale()) return;
                } else {
                    battleTop.setWinnerQueue(winners.map((w) => w.employee_name ?? randomLabel()));
                    battleTop.startDraw();
                    for (const winner of winners) {
                        await battleTop.waitForNextPick();
                        if (isRunStale()) return;
                        append(winner);
                    }
                    await finalizeBattleRound();
                    if (isRunStale()) return;
                }
            },
            stop: () => battleTop.stop(),
        },
        fallback: {
            prepareIdle: () => { },
            prepareToDraw: () => { },
            revealWinners: async () => { },
            stop: () => { },
        },
    };

    const getAnimationDriver = (style) => {
        if (isLottoAirStyle(style)) return animationDrivers.lotto_air;
        if (isRedPacketStyle(style)) return animationDrivers.red_packet;
        if (isScratchCardStyle(style)) return animationDrivers.scratch_card;
        if (isTreasureChestStyle(style)) return animationDrivers.treasure_chest;
        if (isBigTreasureChestStyle(style)) return animationDrivers.big_treasure_chest;
        if (isMarbleRaceStyle(style)) return animationDrivers.marble_race;
        if (isBattleTopStyle(style)) return animationDrivers.battle_top;
        return animationDrivers.fallback;
    };


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
        if (!state.isOpen || !state.currentPrize || !config.drawUrl || state.isPrizesPreviewMode || state.isSwitching || state.isResultMode) {
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
        clearResultModeTimer();

        const backendRunId = generateBackendRunId();
        startDrawingHeartbeat(backendRunId);

        const runId = ++drawRunId;
        const isRunStale = () => runId !== drawRunId;
        const appendWinnerForRun = (winner) => {
            if (isRunStale() || !state.isDrawing) return;
            appendWinner(winner);
        };

        // 抽獎開始前清空中獎名單（避免顯示上一次結果）
        // one_by_one 模式且已有中獎者時才保留
        if (state.currentPrize?.drawMode !== 'one_by_one' || !state.winners?.length) {
            state.winners = [];
        }

        render();

        const style = state.currentPrize?.animationStyle ?? 'lotto_air';
        const driver = getAnimationDriver(style);
        const isLotto = isLottoStyle(style);
        let ballRumbleSound = null;
        let safetyTimer = null;

        try {
            startDrawAudio();
            lottoTimerLabel = '';

            await driver.prepareToDraw();
            if (isLotto) {
                ballRumbleSound = sfx.playBallRumble();
            }

            const drawAbort = new AbortController();
            const drawTimeout = setTimeout(() => drawAbort.abort(), 15000);
            const response = await fetch(config.drawUrl, {
                method: 'POST',
                headers: operatorHeaders(),
                body: JSON.stringify({ run_id: backendRunId }),
                signal: drawAbort.signal,
            });
            clearTimeout(drawTimeout);

            if (isRunStale()) {
                return;
            }

            if (!response.ok) {
                driver.stop();
                stopDrawingHeartbeat();
                state.isDrawing = false;
                render();
                if (response.status === 403) {
                    operatorToken = null;
                    showAccessCodeModal();
                }
                return;
            }

            const data = await response.json();
            if (isRunStale()) {
                return;
            }
            const winners = data?.winners ?? [];

            if (winners.length === 0) {
                console.warn('[lottery] no winners returned from /draw');
                driver.stop();
                stopDrawingHeartbeat();
                state.isDrawing = false;
                sfx.playError();
                // 可抽人數已用盡但名額未滿，若有中獎者則進入 resultMode
                if (state.winners?.length > 0 && !isPrizeCompleted()) {
                    scheduleResultModeShow();
                }
                render();
                return;
            }

            const holdMs = (state.currentPrize?.lottoHoldSeconds ?? 5) * 1000;
            const clock = createDrawClock(Math.max(3000, holdMs));

            // 75 秒安全計時器：動畫開始後若超時仍未結束，強制收尾
            safetyTimer = setTimeout(() => {
                console.warn('[lottery] 75s safety timeout — forcing end');
                safetyTimer = null;
                state.isDrawing = false;
                stopDrawAudio();
                stopDrawingHeartbeat();
                ballRumbleSound?.stop();

                if (state.winners?.length > 0) {
                    stopAllAnimations();
                    render();
                    resultMode.show();
                } else {
                    location.reload();
                }
            }, 75000);

            await driver.revealWinners(winners, {
                appendWinner: appendWinnerForRun,
                isRunStale,
                clock,
            });
        } catch {
            stopDrawingHeartbeat();
            if (isLotto) {
                lottoAir.slowStopMachine();
            } else {
                driver.stop();
            }
            return null;
        } finally {
            if (safetyTimer) {
                clearTimeout(safetyTimer);
                safetyTimer = null;
            }
            ballRumbleSound?.stop();

            // 不論 runId 是否過期，都需要清理狀態和檢查結果模式
            stopDrawAudio();
            stopDrawingHeartbeat();
            state.isDrawing = false;

            if (isRunStale()) {
                // 即使被中斷，若獎項已完成仍需顯示結果
                if (isPrizeCompleted() || ((state.eligibleNames?.length === 0) && state.winners?.length > 0)) {
                    render();
                    sfx.playVictory();
                    resultMode.show();
                }
                return;
            }

            render();

            // 檢查是否抽完，切換到結果模式
            const exhausted = (state.eligibleNames?.length === 0) && !isPrizeCompleted() && (state.winners?.length > 0);
            if (isPrizeCompleted() || exhausted) {
                sfx.playVictory();
                // 直接顯示結果，避免 timer 被後續 WebSocket 事件取消
                resultMode.show();
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

    document.addEventListener('keydown', handleKeydown);

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
        if (isMarbleRaceStyle(state.currentPrize?.animationStyle)) {
            marbleRace.resize();
        }
        if (isBattleTopStyle(state.currentPrize?.animationStyle)) {
            battleTop.resize();
        }
    });

    const applyLotteryPayload = (payload, options = {}) => {
        const { skipWinnersUpdate = false } = options;
        const previousPrizeId = state.currentPrize?.id ?? null;
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

        const nextIsSwitching = payload.event?.is_prize_switching ?? false;
        const nextPrizeId = nextPrize?.id ?? null;
        const prizeChanged = nextPrizeId !== previousPrizeId;

        if (prizeChanged) {
            clearResultModeTimer();

            if (state.isDrawing) {
                console.warn('[lottery] prize changed during drawing, aborting stale draw flow');
                stopDrawingHeartbeat();
                drawRunId += 1;
                state.isDrawing = false;
                stopDrawAudio();
                stopAllAnimations();
            }
        }

        const switchingStarted = !state.isSwitching && nextIsSwitching;
        if (switchingStarted) {
            switchingMask.show();
        }

        state.isOpen = payload.event?.is_lottery_open ?? state.isOpen;
        state.isTestMode = payload.event?.is_test_mode ?? state.isTestMode;
        state.isSwitching = nextIsSwitching;
        state.showPrizesPreview = payload.event?.show_prizes_preview ?? state.showPrizesPreview;
        state.allPrizes = payload.all_prizes ?? state.allPrizes ?? [];
        if (prizeChanged) {
            state.needsAnimationReset = true;
        }

        if (nextPrize) {
            nextPrize.musicUrl = payload.current_prize?.music_url ?? nextPrize.musicUrl;
        }

        // 音效關閉時，立即停止 BGM
        if (nextPrize && !(nextPrize.soundEnabled ?? true)) {
            stopDrawAudio();
        }

        // 獎項變更時強制清空 eligibleNames，避免使用舊獎項的資格名單
        state.currentPrize = nextPrize;
        const shouldSkipWinnersUpdate = skipWinnersUpdate && !prizeChanged;
        if (!shouldSkipWinnersUpdate) {
            const prevWinnersLen = state.winners?.length ?? 0;
            state.winners = payload.winners ?? [];
            // 只在獎項變更或中獎者數量變化時才重置分頁，避免輪詢導致跳頁
            if (prizeChanged || state.winners.length !== prevWinnersLen) {
                pageIndex = 0;
            }
        }
        state.eligibleNames = prizeChanged
            ? (payload.eligible_names ?? []) // 獎項變更：使用新值或清空
            : (payload.eligible_names ?? state.eligibleNames ?? []); // 同獎項：保留舊值

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

        if (!state.isDrawing && (prizeChanged || switchingStarted)) {
            stopAllAnimations();
            // clearCanvas 移除：render() 會立即呼叫 prepareIdle → reset 重繪，避免中間空白閃爍
        }

        if (prizeChanged && state.isResultMode) {
            resultMode.hide();
        }

        updateTitle(payload.current_prize?.name ?? payload.event?.name);
        render();

        // 模式切換邏輯：獎項預覽優先於結果展示
        if (!state.isDrawing) {
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
        }

        // showPrizesPreview 是一次性信號，處理完後重設
        state.showPrizesPreview = false;

        if (!state.isSwitching) {
            switchingMask.hide();
        }

        // 獎項切換中 → 回報前端已載入（以 switch_nonce 去重，確保每次切換都正確 ACK）
        const switchNonce = payload.event?.switch_nonce ?? null;
        if (payload.event?.is_prize_switching && config.switchAckUrl
            && switchNonce && switchNonce !== ackState.lastNonce && !ackState.pending) {
            ackState.lastNonce = switchNonce;
            ackState.pending = true;
            console.log('[lottery] sending switch-ack for nonce:', switchNonce);
            fetch(config.switchAckUrl, {
                method: 'POST',
                headers: operatorHeaders(),
                body: JSON.stringify({ prize_id: payload.current_prize?.id ?? null }),
            }).then(res => {
                console.log('[lottery] switch-ack sent, status:', res.status, 'nonce:', switchNonce);
            }).catch(err => {
                console.error('[lottery] switch-ack failed:', err);
                ackState.lastNonce = null; // 失敗時重設，允許重試
            }).finally(() => {
                ackState.pending = false;
            });
        }
    };

    // 暴露給外部（demo 工具面板使用）
    window.__lotteryApplyPayload = applyLotteryPayload;

    const pollUrl = (() => {
        const u = new URL(window.location.href);
        u.searchParams.set('payload', '1');
        return u.toString();
    })();

    const pollPayload = async () => {
        // 抽獎期間仍輪詢，但只更新切換狀態（不覆蓋 winners）
        const skipWinnersUpdate = state.isDrawing;
        try {
            const response = await fetch(pollUrl, {
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
                if (newWinners.length > 0) {
                    state.winners = [...state.winners, ...newWinners];
                    pageIndex = 0;
                }
                render();

                // 若在 resultMode，立即更新
                if (state.isResultMode) {
                    resultMode.renderPage();
                }

                // 若獎項已完成或可抽人數用盡，進入結果模式
                if (payload.is_completed || (payload.is_exhausted && state.winners?.length > 0)) {
                    if (!state.isResultMode && !shouldShowPrizesPreview()) {
                        scheduleResultModeShow();
                    }
                }
            })
            .listen('.danmaku.sent', (payload) => {
                console.log('[lottery] websocket: danmaku.sent', payload);
                showDanmaku(payload.employee_name, payload.message);
            });
    }

    // 清除舊的輪詢計時器（以防萬一）
    if (window.__lotteryPollTimer) {
        clearInterval(window.__lotteryPollTimer);
    }
    window.__lotteryPollTimer = setInterval(pollPayload, 5000);

    // ========== 彈幕功能 ==========
    const danmakuContainer = document.getElementById('danmaku-container');
    let danmakuLanes = [];
    const laneCount = 5;
    const laneHeight = 60;

    const updateDanmakuContainer = (enabled) => {
        if (!danmakuContainer) return;
        danmakuContainer.classList.toggle('hidden', !enabled);
    };

    const sendReadyPing = () => {
        if (!config.readyUrl) return;
        fetch(config.readyUrl, {
            method: 'POST',
            headers: operatorHeaders(),
            body: JSON.stringify({ ts: Date.now() }),
            keepalive: true,
        }).catch(() => { });
    };

    // --- Drawing heartbeat management ---
    let drawingHeartbeatTimer = null;
    let currentDrawBackendRunId = null;

    const generateBackendRunId = () =>
        `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

    const sendDrawingState = (isDrawing, runId) => {
        if (!config.drawingStateUrl) return;
        fetch(config.drawingStateUrl, {
            method: 'POST',
            headers: operatorHeaders(),
            body: JSON.stringify({ is_drawing: isDrawing, run_id: runId }),
            keepalive: true,
        }).catch(() => { });
    };

    const startDrawingHeartbeat = (runId) => {
        stopDrawingHeartbeat();
        currentDrawBackendRunId = runId;
        sendDrawingState(true, runId);
        drawingHeartbeatTimer = setInterval(() => sendDrawingState(true, runId), 1000);
    };

    const stopDrawingHeartbeat = () => {
        if (drawingHeartbeatTimer) {
            clearInterval(drawingHeartbeatTimer);
            drawingHeartbeatTimer = null;
        }
        if (currentDrawBackendRunId) {
            sendDrawingState(false, currentDrawBackendRunId);
            currentDrawBackendRunId = null;
        }
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

    sendReadyPing();
    if (window.__lotteryReadyTimer) {
        clearInterval(window.__lotteryReadyTimer);
    }
    window.__lotteryReadyTimer = setInterval(sendReadyPing, 5000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            sendReadyPing();
        }
    });

    state.needsAnimationReset = true;
    render();

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
