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
    const slotDisplayEl = document.getElementById('slot-display');
    const lottoWrapEl = document.getElementById('lotto-canvas-wrap');
    const lottoCanvasEl = document.getElementById('lotto-canvas');
    const drawButtonEl = document.getElementById('draw-button');
    const drawProgressEl = document.getElementById('draw-progress');
    const stageEl = document.getElementById('lottery-stage');
    const lotto3ControlsEl = document.getElementById('lotto3-controls');
    const lotto3CountEl = document.getElementById('lotto3-count');
    const lotto3CountValueEl = document.getElementById('lotto3-count-value');
    const lotto3TurbEl = document.getElementById('lotto3-turb');
    const lotto3TurbValueEl = document.getElementById('lotto3-turb-value');
    const lotto3SwirlEl = document.getElementById('lotto3-swirl');
    const lotto3SwirlValueEl = document.getElementById('lotto3-swirl-value');
    const lotto3GravEl = document.getElementById('lotto3-grav');
    const lotto3GravValueEl = document.getElementById('lotto3-grav-value');

    // 結果展示模式元素
    const resultModeEl = document.getElementById('result-mode');
    const resultPrizeNameEl = document.getElementById('result-prize-name');
    const resultWinnerCountEl = document.getElementById('result-winner-count');
    const resultGridEl = document.getElementById('result-winners-grid');
    const resultPageInfoEl = document.getElementById('result-page-info');

    let state = {
        isOpen: config.isOpen,
        currentPrize: config.currentPrize,
        winners: config.winners ?? [],
        eligibleNames: config.eligibleNames ?? [],
        isDrawing: false,
        isResultMode: false,
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
                const details = [winner.employee_email, winner.employee_phone].filter(Boolean).join(' · ');
                return `
                    <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                        <span class="font-semibold">${winner.employee_name ?? ''}</span>
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

        updateTitle(state.currentPrize?.name ?? config.eventName);

        const currentStyle = state.currentPrize?.animationStyle;
        const isLotto = isLottoStyle(currentStyle);
        const isRedPacket = isRedPacketStyle(currentStyle);
        const isScratchCard = isScratchCardStyle(currentStyle);
        const isTreasureChest = isTreasureChestStyle(currentStyle);
        const isCanvas = isCanvasStyle(currentStyle);

        if (slotDisplayEl) {
            slotDisplayEl.classList.toggle('hidden', !state.isDrawing || isCanvas);
        }

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
                const cardCount = state.currentPrize?.drawMode === 'one_by_one' ? 1 : Math.min(remaining, 9);
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
                const chestCount = state.currentPrize?.drawMode === 'one_by_one' ? 1 : Math.min(remaining, 9);
                if (chestCount > 0) {
                    treasureChest.showChests(chestCount);
                }
            }
        }

        if (drawButtonEl) {
            drawButtonEl.disabled = !state.isOpen || !state.currentPrize || state.isDrawing;
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
                        <div class="text-xl font-bold text-white">${w.employee_name ?? ''}</div>
                        <div class="text-sm text-white/60 mt-1">${w.employee_email || ''}</div>
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
    const isLotto2Style = (style) => style === 'lotto2';
    const isRedPacketStyle = (style) => style === 'red_packet';
    const isScratchCardStyle = (style) => style === 'scratch_card';
    const isTreasureChestStyle = (style) => style === 'treasure_chest';
    const isCanvasStyle = (style) => isLottoAirStyle(style) || isLotto2Style(style) || isRedPacketStyle(style) || isScratchCardStyle(style) || isTreasureChestStyle(style);
    const isLottoStyle = (style) => isLottoAirStyle(style) || isLotto2Style(style);
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

        const syncCountFromEligible = () => {
            const eligibleCount = state.eligibleNames?.length ?? 0;
            if (eligibleCount > 0) {
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
            const target = candidates.find((ball) => ball.name === winnerName) ?? candidates[0];
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

        const ensureReady = () => {
            syncCountFromEligible();
            if (!airState.ctx || !airState.balls.length || airState.balls.length !== lottoAirConfig.count) {
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
            if (lottoAirConfig.count !== count) {
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
                        animateTo(p, { openProgress: 1, scale: 3 }, 800, () => {
                            if (!running) return;
                            phase = 'reveal';
                            launchCoins();
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
                const fontSize = Math.min(w * 0.18, h * 0.28, 36);
                ctx.font = `bold ${fontSize}px "Noto Sans TC", "Noto Sans SC", sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.save();
                ctx.shadowColor = 'rgba(0, 0, 0, 0.25)';
                ctx.shadowBlur = 4;
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
                        revealedCount++;

                        // 檢查是否全部揭曉
                        if (revealedCount >= cards.length && revealResolve) {
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
                        spawnCoins(chest);
                        spawnSparkles(chest);
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

    const syncLotto3ControlLabels = () => {
        if (lotto3CountValueEl && lotto3CountEl) {
            lotto3CountValueEl.textContent = lotto3CountEl.value;
        }
        if (lotto3TurbValueEl && lotto3TurbEl) {
            lotto3TurbValueEl.textContent = lotto3TurbEl.value;
        }
        if (lotto3SwirlValueEl && lotto3SwirlEl) {
            lotto3SwirlValueEl.textContent = lotto3SwirlEl.value;
        }
        if (lotto3GravValueEl && lotto3GravEl) {
            lotto3GravValueEl.textContent = lotto3GravEl.value;
        }
    };

    const getLotto3Config = () => {
        const countLevel = Number(lotto3CountEl?.value ?? 6);
        const turbLevel = Number(lotto3TurbEl?.value ?? 8);
        const swirlLevel = Number(lotto3SwirlEl?.value ?? 7);
        const gravLevel = Number(lotto3GravEl?.value ?? 6);

        return {
            count: Math.round(mapRange(countLevel, 24, 90)),
            turb: mapRange(turbLevel, 0, 100),
            swirl: mapRange(swirlLevel, 0, 100),
            grav: mapRange(gravLevel, 0, 100),
        };
    };

    const getLotto3TrayPage = () => {
        const winners = state.winners ?? [];
        const perPage = 10;
        const totalPages = Math.max(1, Math.ceil(winners.length / perPage));
        const now = performance.now();
        if (!lotto3State.pageStart || lotto3State.lastWinnerCount !== winners.length) {
            lotto3State.pageStart = now;
            lotto3State.lastWinnerCount = winners.length;
        }
        const pageDuration = 3500;
        const pageIndex = totalPages > 1
            ? Math.floor((now - lotto3State.pageStart) / pageDuration) % totalPages
            : 0;
        const slice = winners.slice(pageIndex * perPage, (pageIndex + 1) * perPage);
        return { slice, pageIndex, totalPages };
    };

    const lottoState = {
        running: false,
        balls: [],
        picked: [],
        drum: { x: 0, y: 0, r: 0, exitX: 0, exitY: 0 },
        last: 0,
        rafId: null,
        namesKey: '',
        ctx: null,
        dpr: 1,
    };

    const lotto3State = {
        running: false,
        balls: [],
        picked: [],
        mode: 'idle',
        fieldT: 0,
        drum: { x: 0, y: 0, r: 0 },
        chute: { x: 0, y: 0, w: 0, h: 0, active: false },
        tray: { x: 0, y: 0, w: 0, h: 0 },
        last: 0,
        rafId: null,
        namesKey: '',
        ctx: null,
        dpr: 1,
        pageStart: 0,
        lastWinnerCount: 0,
    };

    const particleState = {
        particles: [],
    };

    const shakeState = {
        power: 0,
        decay: 18,
    };

    const getLottoNames = () => {
        const base = (state.eligibleNames?.length ? state.eligibleNames : [])
            .concat(state.winners.map((winner) => winner.employee_name).filter(Boolean))
            .filter(Boolean);
        const unique = Array.from(new Set(base));

        if (unique.length === 0) {
            return Array.from({ length: 16 }, (_, index) => `抽${index + 1}`);
        }

        const shuffled = shuffle(unique);
        return shuffled.slice(0, 48);
    };

    function resizeLottoCanvas() {
        if (!lottoCanvasEl) return false;
        const wrapperRect = lottoWrapEl?.getBoundingClientRect();
        const rect = wrapperRect && wrapperRect.width && wrapperRect.height
            ? wrapperRect
            : lottoCanvasEl.getBoundingClientRect();

        if (!rect.width || !rect.height) {
            return false;
        }

        lottoState.dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
        lottoCanvasEl.width = Math.max(1, Math.floor(rect.width * lottoState.dpr));
        lottoCanvasEl.height = Math.max(1, Math.floor(rect.height * lottoState.dpr));
        lottoState.ctx = lottoCanvasEl.getContext('2d');
        if (lottoState.ctx) {
            lottoState.ctx.setTransform(lottoState.dpr, 0, 0, lottoState.dpr, 0, 0);
        }

        lottoState.drum.x = rect.width * 0.5;
        lottoState.drum.y = rect.height * 0.52;
        lottoState.drum.r = Math.min(rect.width, rect.height) * 0.42;
        lottoState.drum.exitX = rect.width * 0.5;
        lottoState.drum.exitY = rect.height * 0.12;

        return true;
    }

    function initLottoBalls() {
        if (!lottoCanvasEl) return;

        if (!resizeLottoCanvas()) {
            return;
        }
        const names = getLottoNames();
        const key = names.join('|');
        if (key && key === lottoState.namesKey && lottoState.balls.length) {
            return;
        }

        lottoState.namesKey = key;
        lottoState.balls = [];
        lottoState.picked = [];

        const rect = lottoCanvasEl.getBoundingClientRect();
        const count = Math.max(6, names.length || 0);
        const base = Math.min(rect.width, rect.height);
        const radius = clamp(base / Math.sqrt(count * 2.6), 18, 34);

        names.forEach((name, index) => {
            const angle = rand(0, Math.PI * 2);
            const spread = rand(0, lottoState.drum.r - radius - 8);
            lottoState.balls.push({
                id: index,
                name,
                label: name.length > 4 ? `${name.slice(0, 4)}…` : name,
                x: lottoState.drum.x + Math.cos(angle) * spread,
                y: lottoState.drum.y + Math.sin(angle) * spread,
                vx: rand(-55, 55),
                vy: rand(-55, 55),
                r: radius,
                hue: (index * 37) % 360,
                grabbed: false,
                arrived: false,
                phase: 'drum',
                glow: 0,
                floatPhase: rand(0, Math.PI * 2),
                showcaseStart: 0,
                pulse: 0,
                onArrive: null,
                onShowcase: null,
                pauseUntil: 0,
                showcaseStatic: false,
            });
        });
    }

    const ensureLottoReady = () => {
        requestAnimationFrame(() => {
            if (resizeLottoCanvas()) {
                initLottoBalls();
                drawLotto();
                return;
            }

            setTimeout(() => {
                if (resizeLottoCanvas()) {
                    initLottoBalls();
                    drawLotto();
                }
            }, 250);
        });
    };

    const resizeLotto3Canvas = () => {
        if (!lottoCanvasEl) return false;
        const wrapperRect = lottoWrapEl?.getBoundingClientRect();
        const rect = wrapperRect && wrapperRect.width && wrapperRect.height
            ? wrapperRect
            : lottoCanvasEl.getBoundingClientRect();

        if (!rect.width || !rect.height) {
            return false;
        }

        lotto3State.dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
        lottoCanvasEl.width = Math.max(1, Math.floor(rect.width * lotto3State.dpr));
        lottoCanvasEl.height = Math.max(1, Math.floor(rect.height * lotto3State.dpr));
        lotto3State.ctx = lottoCanvasEl.getContext('2d');
        if (lotto3State.ctx) {
            lotto3State.ctx.setTransform(lotto3State.dpr, 0, 0, lotto3State.dpr, 0, 0);
        }

        let baseR = Math.min(rect.width, rect.height) * 0.34;
        let drumX = rect.width * 0.5;
        let drumY = rect.height * 0.58;

        const trayWidth = Math.min(rect.width * 0.7, 520);
        const trayHeight = Math.min(rect.height * 0.18, 92);
        const trayTopY = rect.height * 0.06;
        const trayBottom = trayTopY + trayHeight;
        const drumTop = drumY - baseR - 12;

        let trayPosition = 'top';
        if (trayBottom > drumTop) {
            trayPosition = 'right';
            baseR = Math.min(rect.width, rect.height) * 0.38;
            drumX = rect.width * 0.45;
        }

        lotto3State.drum = { x: drumX, y: drumY, r: baseR };
        lotto3State.chute = {
            x: drumX + baseR * 0.62,
            y: rect.height * 0.22,
            w: baseR * 0.44,
            h: baseR * 0.3,
            active: true,
        };

        let trayX = rect.width * 0.5 - trayWidth / 2;
        let trayY = trayTopY;
        if (trayPosition === 'right') {
            trayX = rect.width - trayWidth - 24;
            trayX = Math.max(trayX, lotto3State.drum.x + lotto3State.drum.r + 16);
            trayX = Math.min(trayX, rect.width - trayWidth - 8);
            trayY = clamp(rect.height * 0.3, 16, rect.height - trayHeight - 16);
        }

        lotto3State.tray = {
            x: trayX,
            y: trayY,
            w: trayWidth,
            h: trayHeight,
            position: trayPosition,
        };

        return true;
    };

    const initLotto3Balls = () => {
        if (!lottoCanvasEl) return;
        if (!resizeLotto3Canvas()) {
            return;
        }

        const { count } = getLotto3Config();
        const names = getLottoNames();
        const preparedNames = [];
        for (let i = 0; i < count; i += 1) {
            preparedNames.push(names[i % Math.max(1, names.length)] ?? `抽${i + 1}`);
        }

        const key = `${preparedNames.join('|')}|${count}`;
        if (key && key === lotto3State.namesKey && lotto3State.balls.length) {
            return;
        }

        lotto3State.namesKey = key;
        lotto3State.balls = [];
        lotto3State.picked = [];
        lotto3State.fieldT = 0;

        const rect = lottoCanvasEl.getBoundingClientRect();
        const radius = clamp(Math.floor(Math.min(rect.width, rect.height) * 0.018), 10, 18);
        preparedNames.forEach((name, index) => {
            const angle = rand(0, TAU);
            const spread = rand(0, lotto3State.drum.r - radius - 10);
            lotto3State.balls.push({
                name,
                label: name.length > 4 ? `${name.slice(0, 4)}…` : name,
                x: lotto3State.drum.x + Math.cos(angle) * spread,
                y: lotto3State.drum.y + Math.sin(angle) * spread,
                vx: rand(-90, 90),
                vy: rand(-90, 90),
                r: radius,
                hue: (index * 7) % 360,
                spin: rand(-8, 8),
                jitter: rand(0.6, 1.4),
                restitution: rand(0.82, 0.94),
                friction: rand(0.965, 0.988),
                mass: rand(0.85, 1.15),
                grabbed: false,
                out: false,
                phase: rand(0, TAU),
                onOut: null,
            });
        });
    };

    const ensureLotto3Ready = () => {
        requestAnimationFrame(() => {
            if (resizeLotto3Canvas()) {
                initLotto3Balls();
                drawLotto3();
                return;
            }

            setTimeout(() => {
                if (resizeLotto3Canvas()) {
                    initLotto3Balls();
                    drawLotto3();
                }
            }, 250);
        });
    };

    const confineLotto3Ball = (ball) => {
        const { x: cx, y: cy, r } = lotto3State.drum;
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

    const collideLotto3Balls = (first, second) => {
        const dx = second.x - first.x;
        const dy = second.y - first.y;
        const dist = Math.hypot(dx, dy);
        const minDist = first.r + second.r;
        if (!dist || dist >= minDist) return;

        const nx = dx / dist;
        const ny = dy / dist;
        const overlap = minDist - dist;
        const sum = first.mass + second.mass;
        first.x -= nx * overlap * (second.mass / sum);
        first.y -= ny * overlap * (second.mass / sum);
        second.x += nx * overlap * (first.mass / sum);
        second.y += ny * overlap * (first.mass / sum);

        const rvx = second.vx - first.vx;
        const rvy = second.vy - first.vy;
        const vn = rvx * nx + rvy * ny;
        if (vn > 0) return;

        const e = Math.min(first.restitution, second.restitution);
        const impulse = -(1 + e) * vn / (1 / first.mass + 1 / second.mass);
        const ix = impulse * nx;
        const iy = impulse * ny;
        first.vx -= ix / first.mass;
        first.vy -= iy / first.mass;
        second.vx += ix / second.mass;
        second.vy += iy / second.mass;

        const tx = -ny;
        const ty = nx;
        const vt = rvx * tx + rvy * ty;
        const mu = 0.015 + 0.02 * Math.abs(vt) / 400;
        const jt = clamp(-vt * mu, -140, 140);
        first.vx -= jt * tx / first.mass;
        first.vy -= jt * ty / first.mass;
        second.vx += jt * tx / second.mass;
        second.vy += jt * ty / second.mass;
        first.spin -= jt * 0.004;
        second.spin += jt * 0.004;

        if (Math.abs(vn) > 180 && Math.random() < 0.25) {
            emitExplosion((first.x + second.x) / 2, (first.y + second.y) / 2, 0.35);
        }
    };

    const updateLotto3 = (dt) => {
        const { turb, swirl, grav } = getLotto3Config();
        const turbRatio = turb / 100;
        const swirlRatio = swirl / 100;
        const gravRatio = grav / 100;

        lotto3State.fieldT += dt * lerp(0.9, 2.4, turbRatio);

        const { x: cx, y: cy, r: radius } = lotto3State.drum;
        const swirlStrength = lerp(0.6, 4.8, swirlRatio);
        const turbStrength = lerp(50, 720, turbRatio);
        const gravity = lerp(150, 980, gravRatio);

        const jetAngle = lotto3State.fieldT * 1.2 + 1.6 * Math.sin(lotto3State.fieldT * 0.7);
        const jetX = cx + Math.cos(jetAngle) * radius * 0.55;
        const jetY = cy + Math.sin(jetAngle * 0.9) * radius * 0.45;

        lotto3State.balls.forEach((ball) => {
            if (ball.out) {
                ball.vy += 880 * dt;
                ball.x += ball.vx * dt;
                ball.y += ball.vy * dt;
                ball.vx *= Math.pow(0.985, dt * 60);
                ball.vy *= Math.pow(0.985, dt * 60);
                const tray = lotto3State.tray;
                if (ball.y > tray.y + tray.h - ball.r) {
                    ball.y = tray.y + tray.h - ball.r;
                    ball.vy *= -0.35;
                    ball.vx *= 0.85;
                    if (Math.abs(ball.vy) < 20) {
                        ball.vy = 0;
                    }
                }
                if (ball.x < tray.x + ball.r) {
                    ball.x = tray.x + ball.r;
                    ball.vx *= -0.35;
                }
                if (ball.x > tray.x + tray.w - ball.r) {
                    ball.x = tray.x + tray.w - ball.r;
                    ball.vx *= -0.35;
                }
                return;
            }

            if (ball.grabbed) {
                const intakeX = lotto3State.chute.x - lotto3State.chute.w * 0.08;
                const intakeY = lotto3State.chute.y + lotto3State.chute.h * 0.55;
                const pull = 7.2;
                ball.vx += (intakeX - ball.x) * pull * dt;
                ball.vy += (intakeY - ball.y) * pull * dt;
                ball.vx += rand(-1, 1) * turbStrength * 0.05 * dt;
                ball.vy += rand(-1, 1) * turbStrength * 0.05 * dt;

                const inChute = (
                    ball.x > lotto3State.chute.x - lotto3State.chute.w * 0.46
                    && ball.x < lotto3State.chute.x + lotto3State.chute.w * 0.1
                    && ball.y > lotto3State.chute.y + lotto3State.chute.h * 0.18
                    && ball.y < lotto3State.chute.y + lotto3State.chute.h * 0.9
                );

                if (inChute) {
                    ball.out = true;
                    ball.x = lotto3State.tray.x + lotto3State.tray.w * 0.5 + rand(-80, 80);
                    ball.y = lotto3State.tray.y - ball.r - rand(12, 30);
                    ball.vx = rand(-140, 140);
                    ball.vy = rand(40, 120);
                    emitExplosion(ball.x, ball.y + 30, 1.1);
                    shakeCamera(10);
                    if (ball.onOut) {
                        ball.onOut();
                        ball.onOut = null;
                    }
                }
            } else {
                const dx = ball.x - cx;
                const dy = ball.y - cy;
                ball.vx += -dy * swirlStrength * dt;
                ball.vy += dx * swirlStrength * dt;

                const nx = ball.x * 0.004 + lotto3State.fieldT * 0.22 + ball.phase * 0.02;
                const ny = ball.y * 0.004 - lotto3State.fieldT * 0.18 + ball.phase * 0.02;
                const noiseX = fbm(nx, ny);
                const noiseY = fbm(nx + 7.13, ny + 3.77);
                ball.vx += (noiseX - 0.5) * turbStrength * ball.jitter * dt;
                ball.vy += (noiseY - 0.5) * turbStrength * ball.jitter * dt;

                const jetDx = ball.x - jetX;
                const jetDy = ball.y - jetY;
                const jetDist = Math.max(1, Math.hypot(jetDx, jetDy));
                const jet = Math.exp(-(jetDist * jetDist) / (2 * (radius * 0.18) * (radius * 0.18)));
                const jetTx = -jetDy / jetDist;
                const jetTy = jetDx / jetDist;
                ball.vx += jetTx * jet * turbStrength * 0.22 * dt;
                ball.vy += jetTy * jet * turbStrength * 0.22 * dt;

                ball.vy += gravity * dt;

                if (Math.random() < 0.018 * turbRatio * dt * 60) {
                    ball.vx += rand(-220, 220) * turbRatio * ball.jitter * 0.25;
                    ball.vy += rand(-220, 120) * turbRatio * ball.jitter * 0.25;
                    ball.spin += rand(-10, 10);
                }
            }

            ball.x += ball.vx * dt;
            ball.y += ball.vy * dt;
            ball.vx *= Math.pow(ball.friction, dt * 60);
            ball.vy *= Math.pow(ball.friction, dt * 60);

            confineLotto3Ball(ball);
        });

        for (let i = 0; i < lotto3State.balls.length; i += 1) {
            const first = lotto3State.balls[i];
            if (first.out) continue;
            for (let j = i + 1; j < lotto3State.balls.length; j += 1) {
                const second = lotto3State.balls[j];
                if (second.out) continue;
                collideLotto3Balls(first, second);
            }
        }

        updateParticles(dt);
        updateShake(dt);
    };

    const drawLotto3Background = (ctx, width, height) => {
        const g1 = ctx.createRadialGradient(width * 0.5, height * 0.26, 80, width * 0.5, height * 0.26, Math.max(width, height));
        g1.addColorStop(0, 'rgba(120,170,255,0.11)');
        g1.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = g1;
        ctx.fillRect(0, 0, width, height);

        const g2 = ctx.createRadialGradient(width * 0.5, height * 0.92, 80, width * 0.5, height * 0.92, Math.max(width, height));
        g2.addColorStop(0, 'rgba(255,210,120,0.1)');
        g2.addColorStop(1, 'rgba(0,0,0,0)');
        ctx.fillStyle = g2;
        ctx.fillRect(0, 0, width, height);

        const vg = ctx.createRadialGradient(width * 0.5, height * 0.55, Math.min(width, height) * 0.1, width * 0.5, height * 0.55, Math.max(width, height));
        vg.addColorStop(0, 'rgba(0,0,0,0)');
        vg.addColorStop(1, 'rgba(0,0,0,0.55)');
        ctx.fillStyle = vg;
        ctx.fillRect(0, 0, width, height);
    };

    const drawLotto3Machine = (ctx) => {
        const { x, y, r } = lotto3State.drum;
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
        ctx.strokeStyle = 'rgba(200,230,255,0.1)';
        ctx.lineWidth = 10;
        ctx.stroke();

        const chute = lotto3State.chute;
        ctx.save();
        ctx.globalAlpha = 0.92;
        ctx.strokeStyle = 'rgba(255,255,255,0.16)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        if (ctx.roundRect) {
            ctx.roundRect(chute.x - chute.w * 0.6, chute.y + chute.h * 0.18, chute.w * 0.55, chute.h * 0.62, 18);
        } else {
            ctx.rect(chute.x - chute.w * 0.6, chute.y + chute.h * 0.18, chute.w * 0.55, chute.h * 0.62);
        }
        ctx.stroke();

        const pipe = ctx.createLinearGradient(chute.x - chute.w * 0.6, 0, chute.x, 0);
        pipe.addColorStop(0, 'rgba(255,255,255,0.04)');
        pipe.addColorStop(1, 'rgba(255,220,120,0.06)');
        ctx.fillStyle = pipe;
        ctx.beginPath();
        if (ctx.roundRect) {
            ctx.roundRect(chute.x - chute.w * 0.6, chute.y + chute.h * 0.18, chute.w * 0.55, chute.h * 0.62, 18);
        } else {
            ctx.rect(chute.x - chute.w * 0.6, chute.y + chute.h * 0.18, chute.w * 0.55, chute.h * 0.62);
        }
        ctx.fill();

        ctx.globalAlpha = 0.95;
        ctx.fillStyle = 'rgba(255,220,120,0.1)';
        ctx.beginPath();
        ctx.arc(chute.x - chute.w * 0.45, chute.y + chute.h * 0.5, 14, 0, TAU);
        ctx.fill();
        ctx.restore();

        const tray = lotto3State.tray;
        ctx.save();
        ctx.fillStyle = 'rgba(0,0,0,0.28)';
        ctx.strokeStyle = 'rgba(255,220,120,0.18)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        if (ctx.roundRect) {
            ctx.roundRect(tray.x, tray.y, tray.w, tray.h, 18);
        } else {
            ctx.rect(tray.x, tray.y, tray.w, tray.h);
        }
        ctx.fill();
        ctx.stroke();
        ctx.restore();
    };

    const drawLotto3TrayBalls = (ctx) => {
        const tray = lotto3State.tray;
        if (!tray?.w || !tray?.h) return;

        const { slice, pageIndex, totalPages } = getLotto3TrayPage();
        const cols = 5;
        const rows = 2;
        const padding = 12;
        const cellW = (tray.w - padding * 2) / cols;
        const cellH = (tray.h - padding * 2) / rows;
        const radius = Math.min(cellW, cellH) * 0.34;

        slice.forEach((winner, index) => {
            const col = index % cols;
            const row = Math.floor(index / cols);
            const cx = tray.x + padding + cellW * col + cellW / 2;
            const cy = tray.y + padding + cellH * row + cellH / 2;
            const labelText = (winner.employee_name ?? winner.name ?? '').toString();
            const label = labelText.length > 4 ? `${labelText.slice(0, 4)}…` : labelText;

            ctx.save();
            const grad = ctx.createRadialGradient(cx - radius * 0.35, cy - radius * 0.35, radius * 0.25, cx, cy, radius * 1.2);
            grad.addColorStop(0, 'rgba(255, 255, 255, 0.95)');
            grad.addColorStop(1, 'rgba(245, 158, 11, 0.95)');
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.arc(cx, cy, radius, 0, TAU);
            ctx.fill();

            ctx.fillStyle = 'rgba(0,0,0,0.8)';
            ctx.font = `${Math.max(10, Math.floor(radius * 0.85))}px ui-sans-serif, system-ui`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(label, cx, cy + 0.5);
            ctx.restore();
        });

        ctx.save();
        ctx.fillStyle = 'rgba(255,255,255,0.65)';
        ctx.font = '11px ui-sans-serif, system-ui';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillText('中獎名單', tray.x + 10, tray.y + 8);
        if (totalPages > 1) {
            ctx.textAlign = 'right';
            ctx.fillText(`${pageIndex + 1}/${totalPages}`, tray.x + tray.w - 10, tray.y + 8);
        }
        ctx.restore();
    };

    const drawLotto3Ball = (ctx, ball) => {
        ctx.save();
        ctx.globalAlpha = 0.3;
        ctx.fillStyle = 'black';
        ctx.beginPath();
        ctx.arc(ball.x + 6, ball.y + 9, ball.r * 1.02, 0, TAU);
        ctx.fill();

        ctx.globalAlpha = 1;
        const gradient = ctx.createRadialGradient(ball.x - ball.r * 0.35, ball.y - ball.r * 0.35, ball.r * 0.25, ball.x, ball.y, ball.r * 1.15);
        gradient.addColorStop(0, `hsla(${ball.hue} 95% 72% / 1)`);
        gradient.addColorStop(1, `hsla(${(ball.hue + 25) % 360} 95% 46% / 1)`);
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.arc(ball.x, ball.y, ball.r, 0, TAU);
        ctx.fill();

        ctx.globalAlpha = 0.26;
        ctx.fillStyle = 'white';
        ctx.beginPath();
        ctx.arc(ball.x - ball.r * 0.32, ball.y - ball.r * 0.36, ball.r * 0.36, 0, TAU);
        ctx.fill();

        ctx.globalAlpha = 1;
        ctx.fillStyle = 'rgba(255,255,255,0.92)';
        ctx.beginPath();
        ctx.arc(ball.x, ball.y, ball.r * 0.58, 0, TAU);
        ctx.fill();

        ctx.fillStyle = 'rgba(0,0,0,0.82)';
        ctx.font = `${Math.floor(ball.r * 0.95)}px ui-sans-serif, system-ui`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(ball.label, ball.x, ball.y + 0.5);

        if (ball.grabbed && !ball.out) {
            ctx.globalAlpha = 0.25;
            ctx.strokeStyle = `hsla(${ball.hue} 95% 70% / 1)`;
            ctx.lineWidth = 6;
            ctx.beginPath();
            ctx.arc(ball.x, ball.y, ball.r + 6, 0, TAU);
            ctx.stroke();
        }

        ctx.restore();
    };

    const drawLotto3 = () => {
        const ctx = lotto3State.ctx;
        if (!ctx || !lottoCanvasEl) return;
        const rect = lottoCanvasEl.getBoundingClientRect();
        // 清除畫布以顯示背景
        ctx.clearRect(0, 0, rect.width, rect.height);

        drawLotto3Background(ctx, rect.width, rect.height);

        ctx.save();
        applyCameraShake(ctx);
        drawLotto3Machine(ctx);
        drawLotto3TrayBalls(ctx);

        const { x, y, r } = lotto3State.drum;
        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, r - 1, 0, TAU);
        ctx.clip();
        lotto3State.balls.forEach((ball) => {
            if (!ball.out) {
                drawLotto3Ball(ctx, ball);
            }
        });
        ctx.restore();

        lotto3State.balls.forEach((ball) => {
            if (ball.out) {
                drawLotto3Ball(ctx, ball);
            }
        });

        drawParticles(ctx);
        ctx.restore();
    };

    const startLotto3 = () => {
        if (!lottoCanvasEl || lotto3State.running) return;
        stopLotto();
        initLotto3Balls();
        lotto3State.balls.forEach((ball) => {
            ball.grabbed = false;
            ball.out = false;
            ball.onOut = null;
        });
        lotto3State.mode = 'mixing';
        lotto3State.running = true;
        lotto3State.last = performance.now();

        const tick = (now) => {
            if (!lotto3State.running) return;
            const dt = Math.min(0.033, (now - lotto3State.last) / 1000);
            lotto3State.last = now;
            updateLotto3(dt);
            drawLotto3();
            lotto3State.rafId = requestAnimationFrame(tick);
        };

        lotto3State.rafId = requestAnimationFrame(tick);
    };

    const stopLotto3 = () => {
        lotto3State.running = false;
        if (lotto3State.rafId) {
            cancelAnimationFrame(lotto3State.rafId);
            lotto3State.rafId = null;
        }
        drawLotto3();
    };

    const freezeLotto3 = () => {
        lotto3State.running = false;
        if (lotto3State.rafId) {
            cancelAnimationFrame(lotto3State.rafId);
            lotto3State.rafId = null;
        }
        lotto3State.balls.forEach((ball) => {
            ball.vx = 0;
            ball.vy = 0;
        });
        particleState.particles = [];
        shakeState.power = 0;
        drawLotto3();
    };

    const revealWithLotto3 = async (name, delayMs = 0, maxWaitMs = 6000) => {
        if (!lottoCanvasEl) {
            await revealWithScramble(name);
            return null;
        }

        if (delayMs > 0) {
            await new Promise((r) => setTimeout(r, delayMs));
        }

        initLotto3Balls();
        if (!lotto3State.running) {
            startLotto3();
        }

        const targetBall = lotto3State.balls.find((ball) => !ball.grabbed && ball.name === name)
            ?? lotto3State.balls.find((ball) => !ball.grabbed)
            ?? lotto3State.balls[0];

        if (!targetBall) {
            return null;
        }

        targetBall.grabbed = true;
        lotto3State.mode = 'picking';
        const revealedBall = await new Promise((resolve) => {
            let timeoutId = null;
            const done = () => {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                resolve(targetBall);
            };

            targetBall.onOut = done;
            if (maxWaitMs > 0) {
                timeoutId = setTimeout(done, maxWaitMs);
            }
        });

        return revealedBall;
    };

    const resolveLottoCollision = (a, b) => {
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        const dist = Math.hypot(dx, dy);
        const minDist = a.r + b.r;
        if (!dist || dist >= minDist) return;

        const nx = dx / dist;
        const ny = dy / dist;
        const overlap = minDist - dist;
        a.x -= nx * overlap * 0.5;
        a.y -= ny * overlap * 0.5;
        b.x += nx * overlap * 0.5;
        b.y += ny * overlap * 0.5;

        const rvx = b.vx - a.vx;
        const rvy = b.vy - a.vy;
        const vn = rvx * nx + rvy * ny;
        if (vn > 0) return;

        const restitution = 0.995;
        const impulse = -(1 + restitution) * vn * 0.5;
        const ix = impulse * nx;
        const iy = impulse * ny;
        a.vx -= ix;
        a.vy -= iy;
        b.vx += ix;
        b.vy += iy;
    };

    const resolveCircleCollision = (a, b) => {
        resolveLottoCollision(a, b);
    };

    const physicsUpdate = (objects, dt) => {
        objects.forEach((object) => {
            if (object.phase === 'showcase') {
                return;
            }
            object.vx *= Math.pow(0.998, dt * 60);
            object.vy *= Math.pow(0.998, dt * 60);
        });
    };

    const emitExplosion = (x, y, power = 1, color = 'rgba(255, 214, 90, 0.9)') => {
        const count = Math.floor(60 + power * 80);
        for (let i = 0; i < count; i += 1) {
            const angle = rand(0, Math.PI * 2);
            const speed = rand(140, 320) * (0.9 + power * 0.6);
            particleState.particles.push({
                x,
                y,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                life: rand(0.7, 1.6),
                ttl: rand(0.7, 1.6),
                size: rand(2.6, 5.2),
                color,
            });
        }
    };

    const updateParticles = (dt) => {
        particleState.particles = particleState.particles.filter((particle) => {
            particle.life -= dt;
            if (particle.life <= 0) {
                return false;
            }
            particle.x += particle.vx * dt;
            particle.y += particle.vy * dt;
            particle.vx *= Math.pow(0.86, dt * 60);
            particle.vy *= Math.pow(0.86, dt * 60);
            return true;
        });
    };

    const drawParticles = (ctx) => {
        if (!particleState.particles.length) return;
        ctx.save();
        ctx.globalCompositeOperation = 'screen';
        particleState.particles.forEach((particle) => {
            const alpha = clamp(particle.life / particle.ttl, 0, 1);
            ctx.fillStyle = particle.color.replace('0.9', alpha.toFixed(2));
            ctx.beginPath();
            ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            ctx.fill();
        });
        ctx.restore();
    };

    const shakeCamera = (power = 10) => {
        shakeState.power = Math.max(shakeState.power, power);
    };

    const applyCameraShake = (ctx) => {
        if (shakeState.power <= 0) return;
        const angle = rand(0, Math.PI * 2);
        const offset = shakeState.power * rand(0.4, 1);
        ctx.translate(Math.cos(angle) * offset, Math.sin(angle) * offset);
    };

    const updateShake = (dt) => {
        if (shakeState.power <= 0) return;
        shakeState.power = Math.max(0, shakeState.power - shakeState.decay * dt);
    };

    const confineLottoBall = (ball) => {
        const { x: cx, y: cy, r } = lottoState.drum;
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
            ball.vx -= 2 * vn * nx;
            ball.vy -= 2 * vn * ny;
            ball.vx *= 0.9;
            ball.vy *= 0.9;
        }
    };

    const getShowcaseTarget = (ball, useFloat = true) => {
        const targetIndex = lottoState.picked.indexOf(ball);
        const offset = (targetIndex - Math.max(0, lottoState.picked.length - 1) / 2) * (ball.r * 1.55);
        const floatY = useFloat ? Math.sin(performance.now() * 0.003 + ball.floatPhase) * 4 : 0;
        const tx = lottoState.drum.exitX + offset;
        const ty = lottoState.drum.y - lottoState.drum.r - 30 + floatY;
        return { tx, ty };
    };

    function updateLotto(dt) {
        const useLotto2 = state.currentPrize?.animationStyle === 'lotto2';
        const speedBoost = useLotto2 ? 2 : 1;
        const spin = 4.8 * speedBoost;
        const turbulence = 760 * speedBoost;
        const gravity = 680 * speedBoost;
        const drag = 0.972;

        updateShake(dt);

        lottoState.balls.forEach((ball) => {
            let showcaseTarget = null;
            if (ball.phase === 'showcase') {
                const useFloat = !ball.showcaseStatic;
                showcaseTarget = getShowcaseTarget(ball, useFloat);
                ball.vx += (showcaseTarget.tx - ball.x) * 6.5 * dt;
                ball.vy += (showcaseTarget.ty - ball.y) * 6.5 * dt;
                ball.vx *= Math.pow(0.3, dt);
                ball.vy *= Math.pow(0.3, dt);
                ball.glow = clamp(ball.glow + dt * 1.8, 0, 1);
            } else if (ball.phase === 'pause') {
                if (performance.now() >= ball.pauseUntil) {
                    const target = getShowcaseTarget(ball, false);
                    ball.phase = 'showcase';
                    ball.showcaseStatic = true;
                    ball.x = target.tx;
                    ball.y = target.ty;
                    ball.vx = 0;
                    ball.vy = 0;
                }
                ball.vx *= Math.pow(0.2, dt);
                ball.vy *= Math.pow(0.2, dt);
                ball.glow = clamp(ball.glow + dt * 2.2, 0, 1);
            } else if (ball.grabbed && !ball.arrived) {
                const targetIndex = lottoState.picked.indexOf(ball);
                const offset = (targetIndex - Math.max(0, lottoState.picked.length - 1) / 2) * (ball.r * 1.4);
                const tx = lottoState.drum.exitX + offset;
                const ty = lottoState.drum.exitY;
                const grabPull = useLotto2 ? 14 : 8;
                const grabDamp = useLotto2 ? 0.25 : 0.4;
                ball.vx += (tx - ball.x) * grabPull * dt;
                ball.vy += (ty - ball.y) * grabPull * dt;
                ball.vx *= Math.pow(grabDamp, dt);
                ball.vy *= Math.pow(grabDamp, dt);
                if (useLotto2 && Math.random() > 0.7) {
                    emitExplosion(ball.x, ball.y, 0.25, 'rgba(255, 214, 90, 0.65)');
                }
            } else if (!ball.arrived) {
                const dx = ball.x - lottoState.drum.x;
                const dy = ball.y - lottoState.drum.y;
                ball.vx += -dy * spin * dt;
                ball.vy += dx * spin * dt;
                ball.vx += rand(-1, 1) * turbulence * dt;
                ball.vy += rand(-1, 1) * turbulence * dt;
                ball.vy += gravity * dt;
            }

            ball.x += ball.vx * dt;
            ball.y += ball.vy * dt;
            ball.vx *= Math.pow(drag, dt * 60);
            ball.vy *= Math.pow(drag, dt * 60);

            if (ball.phase !== 'showcase' && ball.phase !== 'pause') {
                confineLottoBall(ball);
            }

            if (ball.grabbed && !ball.arrived) {
                const dx = ball.x - lottoState.drum.exitX;
                const dy = ball.y - lottoState.drum.exitY;
                if (Math.hypot(dx, dy) < ball.r * 0.55) {
                    ball.arrived = true;
                    ball.phase = useLotto2 ? 'pause' : 'showcase';
                    ball.pauseUntil = useLotto2 ? performance.now() + 520 : 0;
                    ball.showcaseStatic = !useLotto2 ? false : true;
                    ball.vx = 0;
                    ball.vy = 0;
                    ball.pulse = 1;
                    ball.showcaseStart = performance.now();
                    ball.glow = Math.max(ball.glow, 0.6);
                    if (useLotto2) {
                        emitExplosion(ball.x, ball.y, 2.4);
                        shakeCamera(10);
                    }
                    if (ball.onArrive) {
                        ball.onArrive();
                        ball.onArrive = null;
                    }
                }
            } else if (ball.phase !== 'showcase' && ball.glow > 0) {
                ball.glow = Math.max(0, ball.glow - dt * 1.2);
            }

            if (ball.phase === 'showcase' && ball.onShowcase && showcaseTarget) {
                const dx = ball.x - showcaseTarget.tx;
                const dy = ball.y - showcaseTarget.ty;
                if (Math.hypot(dx, dy) < ball.r * 0.35) {
                    ball.onShowcase();
                    ball.onShowcase = null;
                }
            }
        });

        const balls = lottoState.balls;
        if (useLotto2) {
            physicsUpdate(balls, dt);
            updateParticles(dt);
        }
        for (let i = 0; i < balls.length; i += 1) {
            for (let j = i + 1; j < balls.length; j += 1) {
                if (balls[i].phase === 'showcase' || balls[j].phase === 'showcase') {
                    continue;
                }
                resolveCircleCollision(balls[i], balls[j]);
            }
        }
    }

    function drawLotto() {
        const ctx = lottoState.ctx;
        if (!ctx || !lottoCanvasEl) return;
        const rect = lottoCanvasEl.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);

        ctx.save();
        applyCameraShake(ctx);

        const { x, y, r } = lottoState.drum;
        ctx.save();
        const glow = ctx.createRadialGradient(x, y, r * 0.2, x, y, r * 1.2);
        glow.addColorStop(0, 'rgba(255,255,255,0.08)');
        glow.addColorStop(1, 'rgba(0,0,0,0.55)');
        ctx.fillStyle = glow;
        ctx.beginPath();
        ctx.arc(x, y, r * 1.08, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();

        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, r, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(255,255,255,0.18)';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();

        const drawGlowCircle = (x, y, r, color) => {
            ctx.save();
            ctx.globalCompositeOperation = 'screen';
            const halo = ctx.createRadialGradient(x, y, r * 0.2, x, y, r);
            halo.addColorStop(0, color);
            halo.addColorStop(1, 'rgba(255, 214, 90, 0)');
            ctx.fillStyle = halo;
            ctx.beginPath();
            ctx.arc(x, y, r, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        };

        const isLotto2 = state.currentPrize?.animationStyle === 'lotto2';

        const drawBall = (ball, spotlight = false) => {
            const shadow = 6;
            const pulseScale = ball.pulse ? 1 + ball.pulse * 0.25 : 1;

            ctx.save();
            ctx.globalAlpha = 0.9;
            ctx.beginPath();
            ctx.fillStyle = 'rgba(0,0,0,0.35)';
            ctx.arc(ball.x + shadow * 0.3, ball.y + shadow * 0.4, ball.r * 1.02, 0, Math.PI * 2);
            ctx.fill();

            const g = ctx.createRadialGradient(ball.x - ball.r * 0.35, ball.y - ball.r * 0.35, ball.r * 0.25, ball.x, ball.y, ball.r * 1.12);
            g.addColorStop(0, `hsla(${ball.hue} 95% 70% / 1)`);
            g.addColorStop(1, `hsla(${(ball.hue + 25) % 360} 95% 45% / 1)`);
            ctx.beginPath();
            ctx.fillStyle = g;
            ctx.arc(ball.x, ball.y, ball.r * pulseScale, 0, Math.PI * 2);
            ctx.fill();

            ctx.globalAlpha = 0.9;
            ctx.beginPath();
            ctx.fillStyle = 'rgba(255,255,255,0.85)';
            ctx.arc(ball.x, ball.y, ball.r * 0.6 * pulseScale, 0, Math.PI * 2);
            ctx.fill();

            if (spotlight && ball.glow > 0) {
                const glowStrength = clamp(ball.glow, 0.35, 1);
                const glowScale = isLotto2 ? 4.2 : 3.3;
                drawGlowCircle(ball.x, ball.y, ball.r * glowScale, `rgba(255, 239, 170, ${0.7 * glowStrength})`);
                if (isLotto2) {
                    drawGlowCircle(ball.x, ball.y, ball.r * 5.2, `rgba(255, 214, 90, ${0.35 * glowStrength})`);
                }

                ctx.save();
                ctx.globalCompositeOperation = 'screen';
                ctx.strokeStyle = `rgba(255, 227, 130, ${0.75 * glowStrength})`;
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.arc(ball.x, ball.y, ball.r * (1.35 + glowStrength * 0.5), 0, Math.PI * 2);
                ctx.stroke();
                ctx.restore();
            }

            ctx.globalAlpha = 1;
            ctx.fillStyle = 'rgba(15,23,42,0.9)';
            ctx.font = `${Math.max(10, Math.floor(ball.r * 0.6))}px ui-sans-serif, system-ui`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(ball.label, ball.x, ball.y + 0.5);

            ctx.restore();
        };


        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, r - 1, 0, Math.PI * 2);
        ctx.clip();

        lottoState.balls.forEach((ball) => {
            if (ball.pulse) {
                ball.pulse = Math.max(0, ball.pulse - 0.02);
            }
            if (ball.phase !== 'showcase') {
                drawBall(ball);
            }
        });

        ctx.restore();


        lottoState.balls.forEach((ball) => {
            if (ball.phase === 'showcase') {
                drawBall(ball, true);
            }
        });

        if (isLotto2) {
            drawParticles(ctx);
        }

        ctx.restore();
    }

    const startLotto = () => {
        if (!lottoCanvasEl || lottoState.running) return;
        stopLotto3();
        initLottoBalls();
        lottoState.balls.forEach((ball) => {
            ball.grabbed = false;
            ball.arrived = false;
            ball.phase = 'drum';
            ball.glow = 0;
            ball.showcaseStart = 0;
            ball.onArrive = null;
            ball.onShowcase = null;
            ball.pauseUntil = 0;
            ball.showcaseStatic = false;
            ball.pulse = 0;
        });
        lottoState.picked = [];
        lottoState.running = true;
        lottoState.last = performance.now();

        const tick = (now) => {
            if (!lottoState.running) return;
            const dt = Math.min(0.033, (now - lottoState.last) / 1000);
            lottoState.last = now;
            updateLotto(dt);
            drawLotto();
            lottoState.rafId = requestAnimationFrame(tick);
        };

        lottoState.rafId = requestAnimationFrame(tick);
    };

    const stopLotto = () => {
        lottoState.running = false;
        if (lottoState.rafId) {
            cancelAnimationFrame(lottoState.rafId);
            lottoState.rafId = null;
        }
        drawLotto();
    };

    const freezeLotto = (winnerBall) => {
        lottoState.running = false;
        if (lottoState.rafId) {
            cancelAnimationFrame(lottoState.rafId);
            lottoState.rafId = null;
        }
        lottoState.balls.forEach((ball) => {
            ball.vx = 0;
            ball.vy = 0;
        });
        if (winnerBall) {
            const target = getShowcaseTarget(winnerBall, false);
            winnerBall.showcaseStatic = true;
            winnerBall.x = target.tx;
            winnerBall.y = target.ty;
        }
        particleState.particles = [];
        shakeState.power = 0;
        drawLotto();
    };

    const revealWithLotto = async (name, delayMs = 0, maxWaitMs = 3500) => {
        if (!lottoCanvasEl) {
            await revealWithScramble(name);
            return null;
        }

        if (delayMs > 0) {
            await new Promise((r) => setTimeout(r, delayMs));
        }

        initLottoBalls();
        if (!lottoState.running) {
            startLotto();
        }

        const targetBall = lottoState.balls.find((ball) => !ball.grabbed && ball.name === name)
            ?? lottoState.balls.find((ball) => !ball.grabbed)
            ?? lottoState.balls[0];

        if (!targetBall) {
            return null;
        }

        targetBall.grabbed = true;
        lottoState.picked.push(targetBall);

        const revealedBall = await new Promise((resolve) => {
            let timeoutId = null;
            const done = () => {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                resolve(targetBall);
            };

            targetBall.onShowcase = done;
            if (maxWaitMs > 0) {
                timeoutId = setTimeout(done, maxWaitMs);
            }
        });

        return revealedBall;
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

    const revealWinner = async (winnerName, delayMs = 0) => {
        if (!slotDisplayEl) return null;

        const name = winnerName || '（未知）';
        let revealedBall = null;

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
        return revealedBall;
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

        const style = state.currentPrize?.animationStyle ?? 'slot';
        const useLottoAir = isLottoAirStyle(style);
        const useLotto2 = isLotto2Style(style);
        const useRedPacket = isRedPacketStyle(style);
        const useScratchCard = isScratchCardStyle(style);
        const useTreasureChest = isTreasureChestStyle(style);

        try {
            const minSpin = (useLottoAir || useLotto2 || useRedPacket || useScratchCard || useTreasureChest) ? Promise.resolve() : runSpin(700);
            startDrawAudio();
            lottoTimerLabel = '';

            // lotto2: 先啟動混合動畫，不等待 API 回應
            if (useLotto2) {
                lottoAir.ensureReady();
                if (state.eligibleNames?.length) {
                    lottoAir.ensureCount(state.eligibleNames.length);
                }
                lottoAir.start();
            }

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
                if (useLotto2) {
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
                state.isDrawing = false;
                render();
                return;
            }

            const data = await response.json();
            const winners = data?.winners ?? [];

            if (winners.length === 0) {
                if (useLotto2) {
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
                if (slotDisplayEl && !useLottoAir && !useLotto2 && !useRedPacket && !useScratchCard && !useTreasureChest) {
                    slotDisplayEl.textContent = '沒有更多中獎者';
                    animateFlash(slotDisplayEl);
                }
                return;
            }

            if (useLotto2) {
                // lotto2: API 回應後設定中獎者，等待抽球，最後緩慢停止
                lottoAir.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
                if (state.currentPrize?.drawMode === 'one_by_one') {
                    lottoAir.setWinners([winners[0]?.employee_name ?? randomLabel()], {
                        resetBalls: false,
                        resetPicked: false,
                    });
                    // eslint-disable-next-line no-await-in-loop
                    await lottoAir.waitForNextPick();
                    state.winners = [...state.winners, winners[0]];
                    render();

                    const lastItem = winnerListEl?.lastElementChild;
                    if (lastItem) {
                        animateListItem(lastItem);
                    }
                } else {
                    lottoAir.setWinners(
                        winners.map((w) => w.employee_name ?? randomLabel()),
                        { resetBalls: false, resetPicked: false }
                    );
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
                // 緩慢停止動畫
                lottoAir.slowStopMachine();
            } else if (useLottoAir) {
                lottoAir.ensureReady();
                lottoAir.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
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
                    // 一次全抽模式：同時顯示多張卡片並同時刮開
                    const cardCount = Math.min(winners.length, 9);
                    scratchCard.showCards(cardCount);

                    // 設定所有中獎者名字
                    const winnerNames = winners.slice(0, cardCount).map((w) => w.employee_name ?? '???');
                    scratchCard.setWinners(winnerNames);

                    // 開始同時刮除所有卡片
                    scratchCard.startScratching();

                    // 等待所有卡片揭曉
                    await scratchCard.waitForAllRevealed();

                    // 將所有中獎者加入列表
                    for (const winner of winners.slice(0, cardCount)) {
                        state.winners = [...state.winners, winner];
                    }
                    render();

                    // 動畫顯示每個中獎者
                    const listItems = winnerListEl?.querySelectorAll(':scope > *');
                    if (listItems) {
                        const startIdx = Math.max(0, listItems.length - cardCount);
                        for (let i = startIdx; i < listItems.length; i++) {
                            animateListItem(listItems[i]);
                        }
                    }

                    // 等待一下再重置
                    await new Promise((r) => setTimeout(r, 2000));
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

                        // 逐一開啟每個寶箱
                        for (let i = 0; i < batchCount; i++) {
                            // eslint-disable-next-line no-await-in-loop
                            await treasureChest.openSingleChest(i);

                            // 加入中獎者列表
                            state.winners = [...state.winners, batchWinners[i]];
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
            } else {
                const totalDuration = 10000;
                const perWinnerDelay = Math.max(1200, Math.floor(totalDuration / Math.max(1, winners.length)));

                for (const winner of winners) {
                    // eslint-disable-next-line no-await-in-loop
                    await revealWinner(winner.employee_name, perWinnerDelay);

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
        } catch {
            if (useLotto2) {
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
            return null;
        } finally {
            stopDrawAudio();
            state.isDrawing = false;
            render();

            // 延遲檢查是否抽完，切換到結果模式
            if (isPrizeCompleted()) {
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
    });

    const applyLotteryPayload = (payload) => {
        const nextPrize = payload.current_prize
            ? {
                id: payload.current_prize.id,
                name: payload.current_prize.name,
                drawMode: payload.current_prize.draw_mode,
                animationStyle: payload.current_prize.animation_style ?? state.currentPrize?.animationStyle,
                lottoHoldSeconds: payload.current_prize.lotto_hold_seconds ?? 5,
                winnersCount: payload.current_prize.winners_count ?? 0,
            }
            : null;

        state.isOpen = payload.event?.is_lottery_open ?? state.isOpen;
        if (nextPrize) {
            nextPrize.musicUrl = payload.current_prize?.music_url ?? nextPrize.musicUrl;
        }

        state.currentPrize = nextPrize;
        state.winners = payload.winners ?? [];
        state.eligibleNames = payload.eligible_names ?? state.eligibleNames ?? [];
        pageIndex = 0;

        updateTitle(payload.current_prize?.name ?? payload.event?.name);
        render();
        if (isLottoStyle(nextPrize?.animationStyle)) {
            lottoAir.ensureReady();
        } else {
            lottoAir.stop();
        }
        if (!isScratchCardStyle(nextPrize?.animationStyle)) {
            scratchCard.stop();
        }
        if (!isTreasureChestStyle(nextPrize?.animationStyle)) {
            treasureChest.stop();
        }

        // 結果展示模式切換
        const isCompleted = payload.current_prize?.is_completed ?? isPrizeCompleted();
        if (isCompleted && !state.isResultMode) {
            resultMode.show();
        } else if (!isCompleted && state.isResultMode) {
            resultMode.hide();
        }
    };

    const pollPayload = async () => {
        if (state.isDrawing) return;
        const url = new URL(window.location.href);
        url.searchParams.set('payload', '1');

        try {
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            if (!response.ok) return;
            const payload = await response.json();
            applyLotteryPayload(payload);
        } catch {
            // ignore
        }
    };

    if (window.Echo && config.brandCode) {
        window.Echo.channel(`lottery.${config.brandCode}`)
            .listen('.lottery.updated', (payload) => {
                console.log('[lottery] websocket: lottery.updated', payload);
                applyLotteryPayload(payload);
            })
            .listen('.winners.updated', (payload) => {
                console.log('[lottery] websocket: winners.updated', payload);
                if (!state.currentPrize || payload.prize_id !== state.currentPrize.id) {
                    return;
                }

                state.winners = [...state.winners, ...(payload.winners ?? [])];
                pageIndex = 0;
                render();
            });
    }

    setInterval(pollPayload, 5000);

    render();
    if (isLottoAirStyle(state.currentPrize?.animationStyle)) {
        lottoAir.ensureReady();
    }

    // 初始化時檢查是否已經抽完，直接顯示結果模式
    const initialCompleted = state.currentPrize?.isCompleted ?? isPrizeCompleted();
    if (initialCompleted) {
        resultMode.show();
    }
};

window.addEventListener('load', () => {
    initLottery();
});
