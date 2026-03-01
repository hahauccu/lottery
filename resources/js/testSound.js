const initTestSound = () => {
    const statusEl = document.querySelector('[data-role="status"]');
    const originalGridEl = document.getElementById('testsound-original-grid');
    const suggestedGridEl = document.getElementById('testsound-suggested-grid');
    const bgmUrlInput = document.querySelector('[data-role="bgm-url"]');
    const bgmLoopInput = document.querySelector('[data-role="bgm-loop"]');
    const volumeInputs = Array.from(document.querySelectorAll('[data-volume]'));
    const volumeLabels = {
        master: document.querySelector('[data-volume-label="master"]'),
        sfx: document.querySelector('[data-volume-label="sfx"]'),
        bgm: document.querySelector('[data-volume-label="bgm"]'),
    };

    if (!statusEl || !originalGridEl || !suggestedGridEl) {
        return;
    }

    const AudioContextRef = window.AudioContext || window.webkitAudioContext;

    const state = {
        context: null,
        noiseBuffer: null,
        masterGain: null,
        sfxGain: null,
        loops: new Map(),
        cardRefs: new Map(),
        bgmAudio: null,
        bgmUrl: '',
        masterVolume: 0.78,
        sfxVolume: 0.88,
        bgmVolume: 0.7,
        demoToken: 0,
    };

    const originalSounds = [
        {
            key: 'button_click',
            name: '按鈕點擊',
            player: 'playButtonClick',
            mode: 'once',
            linked: true,
            description: '按下抽獎按鈕時的短促確認音，讓操作有手感。',
            scene: 'draw() 進入抽獎流程前',
        },
        {
            key: 'error',
            name: '錯誤提示',
            player: 'playError',
            mode: 'once',
            linked: true,
            description: '不可抽獎、無可抽名單或 API 回傳空結果時的警示音。',
            scene: '流程防呆與錯誤提示',
        },
        {
            key: 'ball_rumble',
            name: '樂透滾球底噪',
            player: 'playBallRumbleLoop',
            mode: 'loop',
            linked: true,
            description: 'lotto_air 抽獎過程的持續滾球聲，營造緊張感。',
            scene: 'lotto_air 抽獎中',
        },
        {
            key: 'ball_pick',
            name: '樂透抽球命中',
            player: 'playBallPick',
            mode: 'once',
            linked: true,
            description: '每顆中獎球進入收納盤時的命中聲。',
            scene: 'lotto_air 每次 pickBall',
        },
        {
            key: 'machine_stop',
            name: '機器減速停機',
            player: 'playMachineStop',
            mode: 'once',
            linked: true,
            description: '樂透機慢停時的機械收束聲，適合收尾段。',
            scene: 'lotto_air slowStop',
        },
        {
            key: 'paper_tear',
            name: '紅包撕開',
            player: 'playPaperTear',
            mode: 'once',
            linked: true,
            description: '紅包被選中並開啟時的撕裂感音效。',
            scene: 'red_packet 揭曉瞬間',
        },
        {
            key: 'coin_drop',
            name: '金幣落下',
            player: 'playCoinDrop',
            mode: 'once',
            linked: true,
            description: '金屬叮噹的落幣聲，現在常用於寶箱與大寶箱爆發點。',
            scene: 'red_packet / treasure_chest / big_treasure_chest',
        },
        {
            key: 'reveal',
            name: '揭曉閃光',
            player: 'playReveal',
            mode: 'once',
            linked: true,
            description: '揭曉中獎者時的亮光 + 叮聲。',
            scene: 'scratch_card 刮開完成',
        },
        {
            key: 'chest_open',
            name: '寶箱開啟',
            player: 'playChestOpen',
            mode: 'once',
            linked: true,
            description: '箱蓋掀開時的厚重開箱聲。',
            scene: 'treasure_chest / big_treasure_chest',
        },
        {
            key: 'victory',
            name: '勝利收尾',
            player: 'playVictory',
            mode: 'once',
            linked: true,
            description: '本輪抽獎完成後的勝利和弦。',
            scene: '抽完進 result mode 前',
        },
        {
            key: 'scratch_loop',
            name: '刮卡持續聲',
            player: 'playScratchLoop',
            mode: 'loop',
            linked: false,
            description: '已實作但目前未串到主流程，可作為刮刮樂連續底噪。',
            scene: '預計對應 scratch_card 刮除中',
        },
        {
            key: 'slot_tick',
            name: '滴答節拍',
            player: 'playSlotTick',
            mode: 'once',
            linked: false,
            description: '短促滴答，可做倒數或節奏加速。',
            scene: '目前未使用',
        },
        {
            key: 'whoosh',
            name: '轉場 Whoosh',
            player: 'playWhoosh',
            mode: 'once',
            linked: false,
            description: '切換畫面或切獎項時的滑動轉場聲。',
            scene: '目前未使用',
        },
        {
            key: 'drum_roll',
            name: '鼓聲滾動',
            player: 'playDrumRollLoop',
            mode: 'loop',
            linked: false,
            description: '已實作但未串接，可用於揭曉前情緒堆疊。',
            scene: '目前未使用',
        },
    ];

    const suggestedSounds = [
        {
            key: 'coin_shower_loop',
            name: '寶箱噴金幣連續音',
            player: 'coinShowerLoop',
            mode: 'loop',
            linked: false,
            description: '對應你提到的需求：寶箱噴發期間應有持續金幣雨聲，而非只有單次落幣。',
            scene: '建議串到 treasure_chest / big_treasure_chest 噴發段',
        },
        {
            key: 'countdown_tick',
            name: '倒數 Tick',
            player: 'countdownTick',
            mode: 'once',
            linked: false,
            description: '3-2-1 倒數時的清楚節拍，可強化主持節奏。',
            scene: '建議加在按抽獎後 0.8~1.2 秒內',
        },
        {
            key: 'draw_start_stinger',
            name: '抽獎起手 Stinger',
            player: 'drawStartStinger',
            mode: 'once',
            linked: false,
            description: '按下抽獎後的起手音，明確告訴現場「本輪開始」。',
            scene: '建議加在 draw button click 後',
        },
        {
            key: 'switch_prize_whoosh',
            name: '切獎項轉場',
            player: 'switchPrizeWhoosh',
            mode: 'once',
            linked: false,
            description: '後台切換獎項時，前台遮罩切換搭配轉場音更不突兀。',
            scene: '建議加在 switching mask 顯示時',
        },
        {
            key: 'batch_reveal_stinger',
            name: '多抽整批揭曉',
            player: 'batchRevealStinger',
            mode: 'once',
            linked: false,
            description: 'all_at_once 一次顯示名單時的群體揭曉音。',
            scene: '建議加在 batch reveal 顯示當下',
        },
        {
            key: 'no_eligible_warning',
            name: '名單耗盡提示',
            player: 'noEligibleWarning',
            mode: 'once',
            linked: false,
            description: '區分一般 error，專門提示「可抽人員已耗盡」。',
            scene: '建議替換部份 playError 場景',
        },
        {
            key: 'result_enter_swell',
            name: '結果頁進場氛圍',
            player: 'resultEnterSwell',
            mode: 'once',
            linked: false,
            description: '從抽獎畫面切入結果展示時的空間感氛圍層。',
            scene: '建議加在 result mode 切換時',
            category: '原始建議',
        },
        // ── A. 金幣 / 金屬類 ──
        {
            key: 'coin_cascade',
            name: '金幣連鎖掉落',
            player: 'coinCascade',
            mode: 'once',
            linked: false,
            description: '多枚金幣依序落下，有節奏的叮叮聲。',
            scene: '中獎後金幣噴發、寶箱開啟',
            category: 'A. 金幣 / 金屬類',
        },
        {
            key: 'coin_spin',
            name: '金幣旋轉',
            player: 'coinSpin',
            mode: 'once',
            linked: false,
            description: '硬幣在桌上旋轉到停止的聲音。',
            scene: '轉盤停止、結果揭曉前',
            category: 'A. 金幣 / 金屬類',
        },
        {
            key: 'coin_insert',
            name: '投幣入口',
            player: 'coinInsert',
            mode: 'once',
            linked: false,
            description: '投幣機吃幣的金屬撞擊聲。',
            scene: '抽獎啟動、投幣動畫',
            category: 'A. 金幣 / 金屬類',
        },
        {
            key: 'jackpot_coins',
            name: '大獎噴幣',
            player: 'jackpotCoins',
            mode: 'once',
            linked: false,
            description: '吃角子老虎大獎噴出大量金幣。',
            scene: '特大獎中獎、頭獎揭曉',
            category: 'A. 金幣 / 金屬類',
        },
        {
            key: 'metal_chime',
            name: '金屬風鈴',
            player: 'metalChime',
            mode: 'once',
            linked: false,
            description: '清脆的金屬風鈴碰撞聲。',
            scene: '過場提示、輕量慶祝',
            category: 'A. 金幣 / 金屬類',
        },
        // ── B. 氣流 / 環境類 ──
        {
            key: 'air_burst',
            name: '氣流爆發',
            player: 'airBurst',
            mode: 'once',
            linked: false,
            description: '類似開香檳的氣壓噴發。',
            scene: '慶祝時刻、開獎瞬間',
            category: 'B. 氣流 / 環境類',
        },
        {
            key: 'wind_gust',
            name: '風聲呼嘯',
            player: 'windGust',
            mode: 'once',
            linked: false,
            description: '一陣強風呼嘯而過。',
            scene: '轉場過渡、快速切換',
            category: 'B. 氣流 / 環境類',
        },
        {
            key: 'air_suction',
            name: '吸入氣流',
            player: 'airSuction',
            mode: 'once',
            linked: false,
            description: '類似吸塵器開啟的吸入感。',
            scene: '抽取名單、匯聚特效',
            category: 'B. 氣流 / 環境類',
        },
        {
            key: 'bubble_pop',
            name: '泡泡破裂',
            player: 'bubblePop',
            mode: 'once',
            linked: false,
            description: '氣泡浮起後破裂的聲音。',
            scene: '彈窗出現、趣味提示',
            category: 'B. 氣流 / 環境類',
        },
        {
            key: 'steam_release',
            name: '蒸氣釋放',
            player: 'steamRelease',
            mode: 'once',
            linked: false,
            description: '壓力閥釋放蒸氣的嘶嘶聲。',
            scene: '壓力釋放、結果公布',
            category: 'B. 氣流 / 環境類',
        },
        // ── C. 中獎 / 慶祝類 ──
        {
            key: 'fanfare_short',
            name: '短號角',
            player: 'fanfareShort',
            mode: 'once',
            linked: false,
            description: '簡短的勝利號角（銅管感）。',
            scene: '中獎揭曉、勝利宣告',
            category: 'C. 中獎 / 慶祝類',
        },
        {
            key: 'firework_pop',
            name: '煙火爆開',
            player: 'fireworkPop',
            mode: 'once',
            linked: false,
            description: '單發煙火升空並爆開。',
            scene: '中獎慶祝、煙火特效',
            category: 'C. 中獎 / 慶祝類',
        },
        {
            key: 'applause_burst',
            name: '掌聲爆發',
            player: 'applauseBurst',
            mode: 'once',
            linked: false,
            description: '短暫的群眾歡呼掌聲。',
            scene: '中獎揭曉、頒獎時刻',
            category: 'C. 中獎 / 慶祝類',
        },
        {
            key: 'confetti_shower',
            name: '彩帶紛飛',
            player: 'confettiShowerLoop',
            mode: 'loop',
            linked: false,
            description: '持續的彩帶碎紙灑落聲。',
            scene: '慶祝持續期間、結果展示',
            category: 'C. 中獎 / 慶祝類',
        },
        {
            key: 'celebration_bell',
            name: '慶祝鐘聲',
            player: 'celebrationBell',
            mode: 'once',
            linked: false,
            description: '教堂大鐘的慶祝鐘響。',
            scene: '盛大中獎、頭獎揭曉',
            category: 'C. 中獎 / 慶祝類',
        },
        // ── D. 機械 / 科技類 ──
        {
            key: 'reel_spin',
            name: '轉盤旋轉',
            player: 'reelSpinLoop',
            mode: 'loop',
            linked: false,
            description: '老虎機轉盤持續旋轉的聲音。',
            scene: 'slot 模式旋轉中',
            category: 'D. 機械 / 科技類',
        },
        {
            key: 'lock_click',
            name: '鎖定卡榫',
            player: 'lockClick',
            mode: 'once',
            linked: false,
            description: '機械零件卡入定位的咔噠聲。',
            scene: '結果鎖定、轉盤停止',
            category: 'D. 機械 / 科技類',
        },
        {
            key: 'power_up',
            name: '充能啟動',
            player: 'powerUp',
            mode: 'once',
            linked: false,
            description: '電子設備充能到滿的上升音。',
            scene: '抽獎準備、能量蓄積',
            category: 'D. 機械 / 科技類',
        },
        {
            key: 'digital_beep',
            name: '數位嗶聲',
            player: 'digitalBeep',
            mode: 'once',
            linked: false,
            description: '科技感的短促嗶嗶提示。',
            scene: '系統提示、確認操作',
            category: 'D. 機械 / 科技類',
        },
        {
            key: 'servo_move',
            name: '伺服馬達',
            player: 'servoMove',
            mode: 'once',
            linked: false,
            description: '機械臂轉動的馬達聲。',
            scene: '機械動畫、轉盤啟動',
            category: 'D. 機械 / 科技類',
        },
        // ── E. 魔法 / 特效類 ──
        {
            key: 'magic_sparkle',
            name: '魔法閃光',
            player: 'magicSparkle',
            mode: 'once',
            linked: false,
            description: '魔法粒子飛散的閃爍聲。',
            scene: '特效粒子、魔法主題',
            category: 'E. 魔法 / 特效類',
        },
        {
            key: 'portal_open',
            name: '傳送門開啟',
            player: 'portalOpen',
            mode: 'once',
            linked: false,
            description: '時空裂縫打開的低頻共鳴。',
            scene: '場景切換、神秘揭曉',
            category: 'E. 魔法 / 特效類',
        },
        {
            key: 'enchant_rise',
            name: '附魔上升',
            player: 'enchantRise',
            mode: 'once',
            linked: false,
            description: '光柱從下方升起的魔幻音效。',
            scene: '中獎光柱、升級特效',
            category: 'E. 魔法 / 特效類',
        },
        {
            key: 'crystal_ring',
            name: '水晶共鳴',
            player: 'crystalRing',
            mode: 'once',
            linked: false,
            description: '水晶球碰撞產生的純淨共鳴。',
            scene: '高貴獎項、水晶主題',
            category: 'E. 魔法 / 特效類',
        },
        {
            key: 'warp_flash',
            name: '瞬移閃現',
            player: 'warpFlash',
            mode: 'once',
            linked: false,
            description: '瞬間傳送的壓縮/展開感。',
            scene: '快速切換、瞬間揭曉',
            category: 'E. 魔法 / 特效類',
        },
        // ── F. 節奏 / 提示類 ──
        {
            key: 'heartbeat',
            name: '心跳聲',
            player: 'heartbeatLoop',
            mode: 'loop',
            linked: false,
            description: '緊張時刻的心跳節奏。',
            scene: '揭曉前緊張感、倒數階段',
            category: 'F. 節奏 / 提示類',
        },
        {
            key: 'suspense_rise',
            name: '懸念上升',
            player: 'suspenseRise',
            mode: 'once',
            linked: false,
            description: '持續升高的張力音，適合揭曉前。',
            scene: '揭曉前蓄勢、懸念營造',
            category: 'F. 節奏 / 提示類',
        },
        {
            key: 'notification_ding',
            name: '通知叮咚',
            player: 'notificationDing',
            mode: 'once',
            linked: false,
            description: '友善的雙音通知聲。',
            scene: '系統通知、友善提醒',
            category: 'F. 節奏 / 提示類',
        },
        {
            key: 'timer_tick_fast',
            name: '快速滴答',
            player: 'timerTickFastLoop',
            mode: 'loop',
            linked: false,
            description: '倒數計時的加速滴答聲。',
            scene: '倒數計時、時間壓力',
            category: 'F. 節奏 / 提示類',
        },
        {
            key: 'grand_reveal',
            name: '盛大揭曉',
            player: 'grandReveal',
            mode: 'once',
            linked: false,
            description: '結合銅管+亮光的隆重揭曉音效。',
            scene: '頭獎揭曉、壓軸時刻',
            category: 'F. 節奏 / 提示類',
        },
    ];

    const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

    const setStatus = (text, type = '') => {
        statusEl.textContent = `狀態：${text}`;
        statusEl.classList.remove('is-error', 'is-success');
        if (type === 'error') {
            statusEl.classList.add('is-error');
        }
        if (type === 'success') {
            statusEl.classList.add('is-success');
        }
    };

    const applyVolumes = () => {
        if (state.masterGain && state.context) {
            state.masterGain.gain.setValueAtTime(state.masterVolume, state.context.currentTime);
        }
        if (state.sfxGain && state.context) {
            state.sfxGain.gain.setValueAtTime(state.sfxVolume, state.context.currentTime);
        }
        if (state.bgmAudio) {
            state.bgmAudio.volume = clamp(state.masterVolume * state.bgmVolume, 0, 1);
        }
    };

    const ensureContext = async () => {
        if (!AudioContextRef) {
            setStatus('瀏覽器不支援 WebAudio，僅能測試 BGM。', 'error');
            return null;
        }

        if (!state.context) {
            state.context = new AudioContextRef();
            state.masterGain = state.context.createGain();
            state.sfxGain = state.context.createGain();
            state.sfxGain.connect(state.masterGain);
            state.masterGain.connect(state.context.destination);
            applyVolumes();
        }

        if (state.context.state === 'suspended') {
            await state.context.resume();
        }

        setStatus('音效已啟用，可開始試聽。', 'success');
        return state.context;
    };

    const createSfxOutput = async (gainValue = 0.2) => {
        const ctx = await ensureContext();
        if (!ctx || !state.sfxGain) {
            return null;
        }
        const output = ctx.createGain();
        output.gain.setValueAtTime(gainValue, ctx.currentTime);
        output.connect(state.sfxGain);
        return { ctx, now: ctx.currentTime, output };
    };

    const getNoiseBuffer = async () => {
        const ctx = await ensureContext();
        if (!ctx) {
            return null;
        }
        if (!state.noiseBuffer) {
            const bufferSize = ctx.sampleRate * 2;
            state.noiseBuffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
            const data = state.noiseBuffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = Math.random() * 2 - 1;
            }
        }
        return state.noiseBuffer;
    };

    const safeStop = (handle) => {
        if (!handle || typeof handle.stop !== 'function') {
            return;
        }
        try {
            handle.stop();
        } catch {
            // ignore
        }
    };

    const markCardPulse = (key) => {
        const ref = state.cardRefs.get(key);
        if (!ref) {
            return;
        }
        ref.card.classList.add('is-active');
        window.setTimeout(() => {
            if (!state.loops.has(key)) {
                ref.card.classList.remove('is-active');
            }
        }, 460);
    };

    const syncLoopCardState = (key, active) => {
        const ref = state.cardRefs.get(key);
        if (!ref || ref.mode !== 'loop') {
            return;
        }
        ref.card.classList.toggle('is-active', active);
        if (ref.stopBtn) {
            ref.stopBtn.disabled = !active;
        }
    };

    const stopLoop = (key) => {
        const handle = state.loops.get(key);
        if (!handle) {
            return;
        }
        safeStop(handle);
        state.loops.delete(key);
        syncLoopCardState(key, false);
    };

    const stopAllLoops = () => {
        for (const [key, handle] of state.loops.entries()) {
            safeStop(handle);
            syncLoopCardState(key, false);
            state.loops.delete(key);
        }
    };

    const stopBgm = () => {
        if (!state.bgmAudio) {
            return;
        }
        state.bgmAudio.pause();
        state.bgmAudio.currentTime = 0;
    };

    const stopAll = () => {
        stopAllLoops();
        stopBgm();
        state.demoToken += 1;
        setStatus('所有音效已停止。', 'success');
    };

    const playChestOpen = async () => {
        const setup = await createSfxOutput(0.2);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const thump = ctx.createOscillator();
        const thumpGain = ctx.createGain();
        thump.type = 'triangle';
        thump.frequency.setValueAtTime(140, now);
        thump.frequency.exponentialRampToValueAtTime(70, now + 0.18);
        thumpGain.gain.setValueAtTime(0.0001, now);
        thumpGain.gain.exponentialRampToValueAtTime(0.7, now + 0.02);
        thumpGain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
        thump.connect(thumpGain);
        thumpGain.connect(output);

        const sparkle = ctx.createOscillator();
        const sparkleGain = ctx.createGain();
        sparkle.type = 'sine';
        sparkle.frequency.setValueAtTime(900, now + 0.01);
        sparkle.frequency.exponentialRampToValueAtTime(420, now + 0.13);
        sparkleGain.gain.setValueAtTime(0.0001, now);
        sparkleGain.gain.exponentialRampToValueAtTime(0.22, now + 0.015);
        sparkleGain.gain.exponentialRampToValueAtTime(0.001, now + 0.14);
        sparkle.connect(sparkleGain);
        sparkleGain.connect(output);

        thump.start(now);
        thump.stop(now + 0.22);
        sparkle.start(now + 0.01);
        sparkle.stop(now + 0.16);
    };

    const playBallPick = async () => {
        const setup = await createSfxOutput(0.25);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;

        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
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
        noiseGain.connect(output);

        const click = ctx.createOscillator();
        const clickGain = ctx.createGain();
        click.type = 'square';
        click.frequency.setValueAtTime(800, now + 0.12);
        click.frequency.exponentialRampToValueAtTime(200, now + 0.18);
        clickGain.gain.setValueAtTime(0.0001, now + 0.12);
        clickGain.gain.exponentialRampToValueAtTime(0.4, now + 0.125);
        clickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
        click.connect(clickGain);
        clickGain.connect(output);

        noise.start(now);
        noise.stop(now + 0.2);
        click.start(now + 0.12);
        click.stop(now + 0.22);
    };

    const playMachineStop = async () => {
        const setup = await createSfxOutput(0.15);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const hum = ctx.createOscillator();
        const humGain = ctx.createGain();
        hum.type = 'sawtooth';
        hum.frequency.setValueAtTime(120, now);
        hum.frequency.exponentialRampToValueAtTime(40, now + 0.8);
        humGain.gain.setValueAtTime(0.3, now);
        humGain.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
        hum.connect(humGain);
        humGain.connect(output);

        const friction = ctx.createOscillator();
        const frictionGain = ctx.createGain();
        friction.type = 'triangle';
        friction.frequency.setValueAtTime(80, now);
        friction.frequency.exponentialRampToValueAtTime(30, now + 0.7);
        frictionGain.gain.setValueAtTime(0.2, now);
        frictionGain.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
        friction.connect(frictionGain);
        frictionGain.connect(output);

        hum.start(now);
        hum.stop(now + 1);
        friction.start(now);
        friction.stop(now + 0.9);
    };

    const playScratchLoop = async () => {
        const setup = await createSfxOutput(0.12);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) {
            return { stop: () => {} };
        }
        const { ctx, now, output } = setup;

        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
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
        lfoGain.connect(output.gain);

        noise.connect(filter);
        filter.connect(output);

        noise.start(now);
        lfo.start(now);

        return {
            stop: () => {
                const t = ctx.currentTime;
                output.gain.exponentialRampToValueAtTime(0.001, t + 0.1);
                window.setTimeout(() => {
                    try {
                        noise.stop();
                        lfo.stop();
                    } catch {
                        // ignore
                    }
                }, 150);
            },
        };
    };

    const playReveal = async () => {
        const setup = await createSfxOutput(0.2);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const shimmer = ctx.createOscillator();
        const shimmerGain = ctx.createGain();
        shimmer.type = 'sine';
        shimmer.frequency.setValueAtTime(800, now);
        shimmer.frequency.exponentialRampToValueAtTime(1600, now + 0.15);
        shimmerGain.gain.setValueAtTime(0.0001, now);
        shimmerGain.gain.exponentialRampToValueAtTime(0.35, now + 0.05);
        shimmerGain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
        shimmer.connect(shimmerGain);
        shimmerGain.connect(output);

        const ding = ctx.createOscillator();
        const dingGain = ctx.createGain();
        ding.type = 'sine';
        ding.frequency.setValueAtTime(1200, now + 0.08);
        dingGain.gain.setValueAtTime(0.0001, now + 0.08);
        dingGain.gain.exponentialRampToValueAtTime(0.4, now + 0.1);
        dingGain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
        ding.connect(dingGain);
        dingGain.connect(output);

        shimmer.start(now);
        shimmer.stop(now + 0.35);
        ding.start(now + 0.08);
        ding.stop(now + 0.55);
    };

    const playSlotTick = async () => {
        const setup = await createSfxOutput(0.08);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const tick = ctx.createOscillator();
        const tickGain = ctx.createGain();
        tick.type = 'square';
        tick.frequency.setValueAtTime(1800, now);
        tick.frequency.exponentialRampToValueAtTime(600, now + 0.02);
        tickGain.gain.setValueAtTime(0.5, now);
        tickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.03);
        tick.connect(tickGain);
        tickGain.connect(output);

        tick.start(now);
        tick.stop(now + 0.04);
    };

    const playCoinDrop = async () => {
        const setup = await createSfxOutput(0.15);
        if (!setup) return;
        const { ctx, now, output } = setup;

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
            gain.connect(output);
            osc.start(now);
            osc.stop(now + 0.2);
        });

        const bounce = ctx.createOscillator();
        const bounceGain = ctx.createGain();
        bounce.type = 'triangle';
        bounce.frequency.setValueAtTime(1200, now + 0.08);
        bounce.frequency.exponentialRampToValueAtTime(800, now + 0.12);
        bounceGain.gain.setValueAtTime(0.0001, now + 0.08);
        bounceGain.gain.exponentialRampToValueAtTime(0.15, now + 0.09);
        bounceGain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        bounce.connect(bounceGain);
        bounceGain.connect(output);
        bounce.start(now + 0.08);
        bounce.stop(now + 0.18);
    };

    const playVictory = async () => {
        const setup = await createSfxOutput(0.18);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const notes = [523.25, 659.25, 783.99, 1046.5];
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
            gain.connect(output);
            osc.start(now + i * 0.1);
            osc.stop(now + 1);
        });

        const shimmer = ctx.createOscillator();
        const shimmerGain = ctx.createGain();
        shimmer.type = 'sine';
        shimmer.frequency.setValueAtTime(2093, now + 0.35);
        shimmerGain.gain.setValueAtTime(0.0001, now + 0.35);
        shimmerGain.gain.exponentialRampToValueAtTime(0.2, now + 0.4);
        shimmerGain.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
        shimmer.connect(shimmerGain);
        shimmerGain.connect(output);
        shimmer.start(now + 0.35);
        shimmer.stop(now + 1);
    };

    const playPaperTear = async () => {
        const setup = await createSfxOutput(0.2);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;

        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
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
        noiseGain.connect(output);

        const pop = ctx.createOscillator();
        const popGain = ctx.createGain();
        pop.type = 'sawtooth';
        pop.frequency.setValueAtTime(300, now);
        pop.frequency.exponentialRampToValueAtTime(80, now + 0.1);
        popGain.gain.setValueAtTime(0.0001, now);
        popGain.gain.exponentialRampToValueAtTime(0.25, now + 0.01);
        popGain.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
        pop.connect(popGain);
        popGain.connect(output);

        noise.start(now);
        noise.stop(now + 0.3);
        pop.start(now);
        pop.stop(now + 0.15);
    };

    const playBallRumbleLoop = async () => {
        const setup = await createSfxOutput(0.1);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) {
            return { stop: () => {} };
        }
        const { ctx, now, output } = setup;

        const rumble = ctx.createOscillator();
        const rumbleGain = ctx.createGain();
        rumble.type = 'triangle';
        rumble.frequency.setValueAtTime(60, now);
        rumbleGain.gain.setValueAtTime(0.3, now);
        rumble.connect(rumbleGain);
        rumbleGain.connect(output);

        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        noise.loop = true;
        const noiseFilter = ctx.createBiquadFilter();
        noiseFilter.type = 'lowpass';
        noiseFilter.frequency.setValueAtTime(800, now);
        const noiseGain = ctx.createGain();
        noiseGain.gain.setValueAtTime(0.15, now);
        noise.connect(noiseFilter);
        noiseFilter.connect(noiseGain);
        noiseGain.connect(output);

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
                const t = ctx.currentTime;
                output.gain.exponentialRampToValueAtTime(0.001, t + 0.3);
                window.setTimeout(() => {
                    try {
                        rumble.stop();
                        noise.stop();
                        lfo.stop();
                    } catch {
                        // ignore
                    }
                }, 340);
            },
        };
    };

    const playButtonClick = async () => {
        const setup = await createSfxOutput(0.12);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const click = ctx.createOscillator();
        const clickGain = ctx.createGain();
        click.type = 'sine';
        click.frequency.setValueAtTime(1000, now);
        click.frequency.exponentialRampToValueAtTime(600, now + 0.05);
        clickGain.gain.setValueAtTime(0.4, now);
        clickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
        click.connect(clickGain);
        clickGain.connect(output);

        click.start(now);
        click.stop(now + 0.1);
    };

    const playError = async () => {
        const setup = await createSfxOutput(0.15);
        if (!setup) return;
        const { ctx, now, output } = setup;

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
            gain.connect(output);
            osc.start(now + delay);
            osc.stop(now + delay + 0.15);
        });
    };

    const playWhoosh = async () => {
        const setup = await createSfxOutput(0.15);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;

        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
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
        noiseGain.connect(output);

        noise.start(now);
        noise.stop(now + 0.35);
    };

    const playDrumRollLoop = async () => {
        const setup = await createSfxOutput(0.12);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) {
            return { stop: () => {} };
        }
        const { ctx, now, output } = setup;

        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        noise.loop = true;
        const filter = ctx.createBiquadFilter();
        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(1200, now);
        const noiseGain = ctx.createGain();
        noiseGain.gain.setValueAtTime(0.25, now);
        noise.connect(filter);
        filter.connect(noiseGain);
        noiseGain.connect(output);

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
                const t = ctx.currentTime;
                output.gain.exponentialRampToValueAtTime(0.001, t + 0.2);
                window.setTimeout(() => {
                    try {
                        noise.stop();
                        lfo.stop();
                    } catch {
                        // ignore
                    }
                }, 240);
            },
        };
    };

    const coinShowerLoop = async () => {
        const setup = await createSfxOutput(0.14);
        if (!setup) {
            return { stop: () => {} };
        }
        const { ctx, output } = setup;
        let stopped = false;

        const playHit = () => {
            if (stopped) {
                return;
            }
            const t = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = Math.random() > 0.5 ? 'triangle' : 'sine';
            const base = 1500 + Math.random() * 1800;
            osc.frequency.setValueAtTime(base, t);
            osc.frequency.exponentialRampToValueAtTime(base * 0.74, t + 0.09);
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(0.16 + Math.random() * 0.05, t + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.13);
            osc.connect(gain);
            gain.connect(output);
            osc.start(t);
            osc.stop(t + 0.14);
        };

        const intervalId = window.setInterval(playHit, 85);
        playHit();

        return {
            stop: () => {
                stopped = true;
                window.clearInterval(intervalId);
                const t = ctx.currentTime;
                output.gain.exponentialRampToValueAtTime(0.001, t + 0.2);
            },
        };
    };

    const countdownTick = async () => {
        const setup = await createSfxOutput(0.1);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'square';
        osc.frequency.setValueAtTime(980, now);
        osc.frequency.exponentialRampToValueAtTime(720, now + 0.05);
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.28, now + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
        osc.connect(gain);
        gain.connect(output);
        osc.start(now);
        osc.stop(now + 0.1);
    };

    const drawStartStinger = async () => {
        const setup = await createSfxOutput(0.18);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const notes = [440, 587.33, 783.99];
        notes.forEach((freq, index) => {
            const start = now + index * 0.07;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(freq, start);
            osc.frequency.exponentialRampToValueAtTime(freq * 1.03, start + 0.08);
            gain.gain.setValueAtTime(0.0001, start);
            gain.gain.exponentialRampToValueAtTime(0.24, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, start + 0.13);
            osc.connect(gain);
            gain.connect(output);
            osc.start(start);
            osc.stop(start + 0.14);
        });
    };

    const switchPrizeWhoosh = async () => {
        const setup = await createSfxOutput(0.12);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;

        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const filter = ctx.createBiquadFilter();
        filter.type = 'highpass';
        filter.frequency.setValueAtTime(500, now);
        filter.frequency.exponentialRampToValueAtTime(2200, now + 0.26);
        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(0.24, now + 0.05);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
        noise.connect(filter);
        filter.connect(gain);
        gain.connect(output);

        noise.start(now);
        noise.stop(now + 0.34);
    };

    const batchRevealStinger = async () => {
        const setup = await createSfxOutput(0.2);
        if (!setup) return;
        const { ctx, now, output } = setup;

        const chord = [523.25, 659.25, 783.99];
        chord.forEach((freq) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq, now);
            osc.frequency.exponentialRampToValueAtTime(freq * 1.1, now + 0.18);
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.22, now + 0.03);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
            osc.connect(gain);
            gain.connect(output);
            osc.start(now);
            osc.stop(now + 0.36);
        });
    };

    const noEligibleWarning = async () => {
        const setup = await createSfxOutput(0.16);
        if (!setup) return;
        const { ctx, now, output } = setup;

        [0, 0.16, 0.32].forEach((delay, idx) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'square';
            const startFreq = idx === 2 ? 300 : 360;
            osc.frequency.setValueAtTime(startFreq, now + delay);
            osc.frequency.exponentialRampToValueAtTime(180, now + delay + 0.1);
            gain.gain.setValueAtTime(0.0001, now + delay);
            gain.gain.exponentialRampToValueAtTime(0.24, now + delay + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, now + delay + 0.11);
            osc.connect(gain);
            gain.connect(output);
            osc.start(now + delay);
            osc.stop(now + delay + 0.12);
        });
    };

    const resultEnterSwell = async () => {
        const setup = await createSfxOutput(0.13);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;

        const pad = ctx.createOscillator();
        const padGain = ctx.createGain();
        pad.type = 'sine';
        pad.frequency.setValueAtTime(280, now);
        pad.frequency.exponentialRampToValueAtTime(520, now + 0.8);
        padGain.gain.setValueAtTime(0.0001, now);
        padGain.gain.exponentialRampToValueAtTime(0.18, now + 0.3);
        padGain.gain.exponentialRampToValueAtTime(0.001, now + 1);
        pad.connect(padGain);
        padGain.connect(output);

        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const filter = ctx.createBiquadFilter();
        filter.type = 'bandpass';
        filter.frequency.setValueAtTime(900, now);
        filter.frequency.exponentialRampToValueAtTime(2200, now + 0.7);
        const noiseGain = ctx.createGain();
        noiseGain.gain.setValueAtTime(0.0001, now);
        noiseGain.gain.exponentialRampToValueAtTime(0.11, now + 0.28);
        noiseGain.gain.exponentialRampToValueAtTime(0.001, now + 0.95);
        noise.connect(filter);
        filter.connect(noiseGain);
        noiseGain.connect(output);

        pad.start(now);
        pad.stop(now + 1.05);
        noise.start(now);
        noise.stop(now + 0.96);
    };

    // ── A. 金幣 / 金屬類 ──────────────────────────────────────

    const coinCascade = async () => {
        const setup = await createSfxOutput(0.14);
        if (!setup) return;
        const { ctx, now, output } = setup;
        for (let i = 0; i < 8; i++) {
            const t = now + i * 0.09;
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = i % 2 === 0 ? 'triangle' : 'sine';
            const freq = 2000 + Math.random() * 1200 - i * 80;
            osc.frequency.setValueAtTime(freq, t);
            osc.frequency.exponentialRampToValueAtTime(freq * 0.7, t + 0.08);
            g.gain.setValueAtTime(0.0001, t);
            g.gain.exponentialRampToValueAtTime(0.2 - i * 0.015, t + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, t + 0.1);
            osc.connect(g);
            g.connect(output);
            osc.start(t);
            osc.stop(t + 0.12);
        }
    };

    const coinSpin = async () => {
        const setup = await createSfxOutput(0.12);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const dur = 1.2;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(3200, now);
        osc.frequency.exponentialRampToValueAtTime(800, now + dur);
        const lfo = ctx.createOscillator();
        const lfoG = ctx.createGain();
        lfo.frequency.setValueAtTime(30, now);
        lfo.frequency.exponentialRampToValueAtTime(4, now + dur);
        lfoG.gain.setValueAtTime(0.15, now);
        lfo.connect(lfoG);
        lfoG.connect(g.gain);
        g.gain.setValueAtTime(0.18, now);
        g.gain.exponentialRampToValueAtTime(0.001, now + dur);
        osc.connect(g);
        g.connect(output);
        osc.start(now);
        osc.stop(now + dur + 0.05);
        lfo.start(now);
        lfo.stop(now + dur + 0.05);
    };

    const coinInsert = async () => {
        const setup = await createSfxOutput(0.14);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const hit = ctx.createOscillator();
        const hg = ctx.createGain();
        hit.type = 'square';
        hit.frequency.setValueAtTime(1800, now);
        hit.frequency.exponentialRampToValueAtTime(400, now + 0.06);
        hg.gain.setValueAtTime(0.3, now);
        hg.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
        hit.connect(hg);
        hg.connect(output);
        hit.start(now);
        hit.stop(now + 0.1);
        const ring = ctx.createOscillator();
        const rg = ctx.createGain();
        ring.type = 'sine';
        ring.frequency.setValueAtTime(2400, now + 0.04);
        ring.frequency.exponentialRampToValueAtTime(1800, now + 0.2);
        rg.gain.setValueAtTime(0.0001, now + 0.04);
        rg.gain.exponentialRampToValueAtTime(0.15, now + 0.06);
        rg.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
        ring.connect(rg);
        rg.connect(output);
        ring.start(now + 0.04);
        ring.stop(now + 0.28);
    };

    const jackpotCoins = async () => {
        const setup = await createSfxOutput(0.16);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const nf = ctx.createBiquadFilter();
        nf.type = 'highpass';
        nf.frequency.setValueAtTime(4000, now);
        const ng = ctx.createGain();
        ng.gain.setValueAtTime(0.0001, now);
        ng.gain.exponentialRampToValueAtTime(0.12, now + 0.1);
        ng.gain.exponentialRampToValueAtTime(0.08, now + 1.0);
        ng.gain.exponentialRampToValueAtTime(0.001, now + 1.5);
        noise.connect(nf);
        nf.connect(ng);
        ng.connect(output);
        noise.start(now);
        noise.stop(now + 1.6);
        for (let i = 0; i < 14; i++) {
            const t = now + i * 0.1 + Math.random() * 0.05;
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = Math.random() > 0.5 ? 'triangle' : 'sine';
            const f = 1800 + Math.random() * 2200;
            osc.frequency.setValueAtTime(f, t);
            osc.frequency.exponentialRampToValueAtTime(f * 0.65, t + 0.1);
            g.gain.setValueAtTime(0.0001, t);
            g.gain.exponentialRampToValueAtTime(0.18 + Math.random() * 0.06, t + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, t + 0.13);
            osc.connect(g);
            g.connect(output);
            osc.start(t);
            osc.stop(t + 0.15);
        }
    };

    const metalChime = async () => {
        const setup = await createSfxOutput(0.13);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const freqs = [1200, 1800, 2400, 3000, 3600];
        freqs.forEach((f, i) => {
            const t = now + i * 0.06;
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(f, t);
            g.gain.setValueAtTime(0.0001, t);
            g.gain.exponentialRampToValueAtTime(0.14, t + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, t + 0.5);
            osc.connect(g);
            g.connect(output);
            osc.start(t);
            osc.stop(t + 0.55);
        });
    };

    // ── B. 氣流 / 環境類 ──────────────────────────────────────

    const airBurst = async () => {
        const setup = await createSfxOutput(0.16);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const f = ctx.createBiquadFilter();
        f.type = 'bandpass';
        f.frequency.setValueAtTime(800, now);
        f.frequency.exponentialRampToValueAtTime(3000, now + 0.08);
        f.frequency.exponentialRampToValueAtTime(600, now + 0.5);
        f.Q.setValueAtTime(0.8, now);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.35, now + 0.03);
        g.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
        noise.connect(f);
        f.connect(g);
        g.connect(output);
        noise.start(now);
        noise.stop(now + 0.55);
    };

    const windGust = async () => {
        const setup = await createSfxOutput(0.14);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const f = ctx.createBiquadFilter();
        f.type = 'bandpass';
        f.frequency.setValueAtTime(300, now);
        f.frequency.exponentialRampToValueAtTime(1800, now + 0.4);
        f.frequency.exponentialRampToValueAtTime(200, now + 1.0);
        f.Q.setValueAtTime(1.5, now);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.25, now + 0.25);
        g.gain.exponentialRampToValueAtTime(0.001, now + 1.0);
        noise.connect(f);
        f.connect(g);
        g.connect(output);
        noise.start(now);
        noise.stop(now + 1.1);
    };

    const airSuction = async () => {
        const setup = await createSfxOutput(0.14);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const f = ctx.createBiquadFilter();
        f.type = 'bandpass';
        f.frequency.setValueAtTime(2000, now);
        f.frequency.exponentialRampToValueAtTime(400, now + 0.5);
        f.Q.setValueAtTime(2, now);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.28, now + 0.08);
        g.gain.exponentialRampToValueAtTime(0.18, now + 0.35);
        g.gain.exponentialRampToValueAtTime(0.001, now + 0.55);
        noise.connect(f);
        f.connect(g);
        g.connect(output);
        noise.start(now);
        noise.stop(now + 0.6);
    };

    const bubblePop = async () => {
        const setup = await createSfxOutput(0.12);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const rise = ctx.createOscillator();
        const rg = ctx.createGain();
        rise.type = 'sine';
        rise.frequency.setValueAtTime(200, now);
        rise.frequency.exponentialRampToValueAtTime(600, now + 0.15);
        rg.gain.setValueAtTime(0.0001, now);
        rg.gain.exponentialRampToValueAtTime(0.12, now + 0.05);
        rg.gain.exponentialRampToValueAtTime(0.08, now + 0.14);
        rg.gain.exponentialRampToValueAtTime(0.001, now + 0.16);
        rise.connect(rg);
        rg.connect(output);
        rise.start(now);
        rise.stop(now + 0.18);
        const pop = ctx.createOscillator();
        const pg = ctx.createGain();
        pop.type = 'sine';
        pop.frequency.setValueAtTime(1200, now + 0.15);
        pop.frequency.exponentialRampToValueAtTime(400, now + 0.22);
        pg.gain.setValueAtTime(0.25, now + 0.15);
        pg.gain.exponentialRampToValueAtTime(0.001, now + 0.24);
        pop.connect(pg);
        pg.connect(output);
        pop.start(now + 0.15);
        pop.stop(now + 0.26);
    };

    const steamRelease = async () => {
        const setup = await createSfxOutput(0.13);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const f = ctx.createBiquadFilter();
        f.type = 'highpass';
        f.frequency.setValueAtTime(3000, now);
        f.frequency.exponentialRampToValueAtTime(5000, now + 0.1);
        f.frequency.exponentialRampToValueAtTime(2000, now + 0.8);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.22, now + 0.05);
        g.gain.exponentialRampToValueAtTime(0.1, now + 0.4);
        g.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
        noise.connect(f);
        f.connect(g);
        g.connect(output);
        noise.start(now);
        noise.stop(now + 0.9);
    };

    // ── C. 中獎 / 慶祝類 ──────────────────────────────────────

    const fanfareShort = async () => {
        const setup = await createSfxOutput(0.18);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const notes = [
            { f: 523.25, t: 0, d: 0.15 },
            { f: 659.25, t: 0.12, d: 0.15 },
            { f: 783.99, t: 0.24, d: 0.2 },
            { f: 1046.5, t: 0.36, d: 0.35 },
        ];
        notes.forEach(({ f, t: offset, d }) => {
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(f, now + offset);
            const filter = ctx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(2000, now + offset);
            g.gain.setValueAtTime(0.0001, now + offset);
            g.gain.exponentialRampToValueAtTime(0.2, now + offset + 0.02);
            g.gain.exponentialRampToValueAtTime(0.001, now + offset + d);
            osc.connect(filter);
            filter.connect(g);
            g.connect(output);
            osc.start(now + offset);
            osc.stop(now + offset + d + 0.02);
        });
    };

    const fireworkPop = async () => {
        const setup = await createSfxOutput(0.15);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const rise = ctx.createOscillator();
        const rg = ctx.createGain();
        rise.type = 'sine';
        rise.frequency.setValueAtTime(300, now);
        rise.frequency.exponentialRampToValueAtTime(1200, now + 0.35);
        rg.gain.setValueAtTime(0.0001, now);
        rg.gain.exponentialRampToValueAtTime(0.15, now + 0.1);
        rg.gain.exponentialRampToValueAtTime(0.001, now + 0.36);
        rise.connect(rg);
        rg.connect(output);
        rise.start(now);
        rise.stop(now + 0.38);
        const boom = ctx.createBufferSource();
        boom.buffer = noiseBuffer;
        const bf = ctx.createBiquadFilter();
        bf.type = 'bandpass';
        bf.frequency.setValueAtTime(1000, now + 0.35);
        bf.Q.setValueAtTime(0.5, now + 0.35);
        const bg = ctx.createGain();
        bg.gain.setValueAtTime(0.0001, now + 0.35);
        bg.gain.exponentialRampToValueAtTime(0.3, now + 0.37);
        bg.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
        boom.connect(bf);
        bf.connect(bg);
        bg.connect(output);
        boom.start(now + 0.35);
        boom.stop(now + 0.95);
    };

    const applauseBurst = async () => {
        const setup = await createSfxOutput(0.14);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const f = ctx.createBiquadFilter();
        f.type = 'bandpass';
        f.frequency.setValueAtTime(2000, now);
        f.Q.setValueAtTime(0.3, now);
        const lfo = ctx.createOscillator();
        const lg = ctx.createGain();
        lfo.frequency.setValueAtTime(12, now);
        lg.gain.setValueAtTime(0.08, now);
        lfo.connect(lg);
        lg.connect(f.frequency);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.22, now + 0.1);
        g.gain.exponentialRampToValueAtTime(0.18, now + 0.6);
        g.gain.exponentialRampToValueAtTime(0.001, now + 1.2);
        noise.connect(f);
        f.connect(g);
        g.connect(output);
        noise.start(now);
        noise.stop(now + 1.3);
        lfo.start(now);
        lfo.stop(now + 1.3);
    };

    const confettiShowerLoop = async () => {
        const setup = await createSfxOutput(0.1);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return { stop: () => {} };
        const { ctx, output } = setup;
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        noise.loop = true;
        const f = ctx.createBiquadFilter();
        f.type = 'highpass';
        f.frequency.setValueAtTime(6000, ctx.currentTime);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.1, ctx.currentTime);
        noise.connect(f);
        f.connect(g);
        g.connect(output);
        noise.start(ctx.currentTime);
        let stopped = false;
        let tid;
        const sparkle = () => {
            if (stopped) return;
            const t = ctx.currentTime;
            const osc = ctx.createOscillator();
            const sg = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(3000 + Math.random() * 3000, t);
            sg.gain.setValueAtTime(0.0001, t);
            sg.gain.exponentialRampToValueAtTime(0.06, t + 0.01);
            sg.gain.exponentialRampToValueAtTime(0.001, t + 0.06);
            osc.connect(sg);
            sg.connect(output);
            osc.start(t);
            osc.stop(t + 0.08);
            tid = window.setTimeout(sparkle, 60 + Math.random() * 80);
        };
        sparkle();
        return {
            stop: () => {
                stopped = true;
                window.clearTimeout(tid);
                const t = ctx.currentTime;
                output.gain.exponentialRampToValueAtTime(0.001, t + 0.3);
                window.setTimeout(() => { try { noise.stop(); } catch {} }, 340);
            },
        };
    };

    const celebrationBell = async () => {
        const setup = await createSfxOutput(0.16);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const fundamentals = [523.25, 659.25];
        fundamentals.forEach((base) => {
            [1, 2, 3, 4.2].forEach((ratio, i) => {
                const osc = ctx.createOscillator();
                const g = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(base * ratio, now);
                const vol = 0.15 / (i + 1);
                g.gain.setValueAtTime(0.0001, now);
                g.gain.exponentialRampToValueAtTime(vol, now + 0.01);
                g.gain.exponentialRampToValueAtTime(0.001, now + 1.2 - i * 0.15);
                osc.connect(g);
                g.connect(output);
                osc.start(now);
                osc.stop(now + 1.3);
            });
        });
    };

    // ── D. 機械 / 科技類 ──────────────────────────────────────

    const reelSpinLoop = async () => {
        const setup = await createSfxOutput(0.1);
        if (!setup) return { stop: () => {} };
        const { ctx, output } = setup;
        let stopped = false;
        let tid;
        const tick = () => {
            if (stopped) return;
            const t = ctx.currentTime;
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'square';
            osc.frequency.setValueAtTime(800 + Math.random() * 200, t);
            osc.frequency.exponentialRampToValueAtTime(400, t + 0.03);
            g.gain.setValueAtTime(0.12, t);
            g.gain.exponentialRampToValueAtTime(0.001, t + 0.04);
            osc.connect(g);
            g.connect(output);
            osc.start(t);
            osc.stop(t + 0.05);
            tid = window.setTimeout(tick, 50 + Math.random() * 15);
        };
        tick();
        return {
            stop: () => {
                stopped = true;
                window.clearTimeout(tid);
                const t = ctx.currentTime;
                output.gain.exponentialRampToValueAtTime(0.001, t + 0.2);
            },
        };
    };

    const lockClick = async () => {
        const setup = await createSfxOutput(0.14);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = 'square';
        osc.frequency.setValueAtTime(2000, now);
        osc.frequency.exponentialRampToValueAtTime(500, now + 0.025);
        g.gain.setValueAtTime(0.3, now);
        g.gain.exponentialRampToValueAtTime(0.001, now + 0.06);
        osc.connect(g);
        g.connect(output);
        osc.start(now);
        osc.stop(now + 0.08);
        const tail = ctx.createOscillator();
        const tg = ctx.createGain();
        tail.type = 'sine';
        tail.frequency.setValueAtTime(1200, now + 0.03);
        tg.gain.setValueAtTime(0.0001, now + 0.03);
        tg.gain.exponentialRampToValueAtTime(0.08, now + 0.04);
        tg.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        tail.connect(tg);
        tg.connect(output);
        tail.start(now + 0.03);
        tail.stop(now + 0.18);
    };

    const powerUp = async () => {
        const setup = await createSfxOutput(0.15);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(200, now);
        osc.frequency.exponentialRampToValueAtTime(1600, now + 0.6);
        const f = ctx.createBiquadFilter();
        f.type = 'lowpass';
        f.frequency.setValueAtTime(600, now);
        f.frequency.exponentialRampToValueAtTime(4000, now + 0.6);
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.2, now + 0.15);
        g.gain.exponentialRampToValueAtTime(0.22, now + 0.5);
        g.gain.exponentialRampToValueAtTime(0.001, now + 0.75);
        osc.connect(f);
        f.connect(g);
        g.connect(output);
        osc.start(now);
        osc.stop(now + 0.8);
    };

    const digitalBeep = async () => {
        const setup = await createSfxOutput(0.12);
        if (!setup) return;
        const { ctx, now, output } = setup;
        [0, 0.1].forEach((delay) => {
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'square';
            osc.frequency.setValueAtTime(1200, now + delay);
            g.gain.setValueAtTime(0.0001, now + delay);
            g.gain.exponentialRampToValueAtTime(0.18, now + delay + 0.005);
            g.gain.exponentialRampToValueAtTime(0.001, now + delay + 0.06);
            osc.connect(g);
            g.connect(output);
            osc.start(now + delay);
            osc.stop(now + delay + 0.07);
        });
    };

    const servoMove = async () => {
        const setup = await createSfxOutput(0.12);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(120, now);
        osc.frequency.linearRampToValueAtTime(180, now + 0.15);
        osc.frequency.linearRampToValueAtTime(120, now + 0.3);
        const f = ctx.createBiquadFilter();
        f.type = 'lowpass';
        f.frequency.setValueAtTime(800, now);
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.18, now + 0.03);
        g.gain.exponentialRampToValueAtTime(0.15, now + 0.25);
        g.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc.connect(f);
        f.connect(g);
        g.connect(output);
        osc.start(now);
        osc.stop(now + 0.38);
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const nf = ctx.createBiquadFilter();
        nf.type = 'bandpass';
        nf.frequency.setValueAtTime(2000, now);
        nf.Q.setValueAtTime(1, now);
        const ng = ctx.createGain();
        ng.gain.setValueAtTime(0.0001, now);
        ng.gain.exponentialRampToValueAtTime(0.06, now + 0.03);
        ng.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        noise.connect(nf);
        nf.connect(ng);
        ng.connect(output);
        noise.start(now);
        noise.stop(now + 0.38);
    };

    // ── E. 魔法 / 特效類 ──────────────────────────────────────

    const magicSparkle = async () => {
        const setup = await createSfxOutput(0.13);
        if (!setup) return;
        const { ctx, now, output } = setup;
        for (let i = 0; i < 6; i++) {
            const t = now + i * 0.07 + Math.random() * 0.03;
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'sine';
            const f = 2000 + Math.random() * 4000;
            osc.frequency.setValueAtTime(f, t);
            osc.frequency.exponentialRampToValueAtTime(f * 1.5, t + 0.08);
            g.gain.setValueAtTime(0.0001, t);
            g.gain.exponentialRampToValueAtTime(0.12, t + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, t + 0.12);
            osc.connect(g);
            g.connect(output);
            osc.start(t);
            osc.stop(t + 0.14);
        }
    };

    const portalOpen = async () => {
        const setup = await createSfxOutput(0.16);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const sub = ctx.createOscillator();
        const sg = ctx.createGain();
        sub.type = 'sine';
        sub.frequency.setValueAtTime(40, now);
        sub.frequency.exponentialRampToValueAtTime(80, now + 0.8);
        sg.gain.setValueAtTime(0.0001, now);
        sg.gain.exponentialRampToValueAtTime(0.25, now + 0.3);
        sg.gain.exponentialRampToValueAtTime(0.001, now + 1.0);
        sub.connect(sg);
        sg.connect(output);
        sub.start(now);
        sub.stop(now + 1.1);
        const noise = ctx.createBufferSource();
        noise.buffer = noiseBuffer;
        const nf = ctx.createBiquadFilter();
        nf.type = 'bandpass';
        nf.frequency.setValueAtTime(200, now);
        nf.frequency.exponentialRampToValueAtTime(800, now + 0.6);
        nf.Q.setValueAtTime(3, now);
        const ng = ctx.createGain();
        ng.gain.setValueAtTime(0.0001, now);
        ng.gain.exponentialRampToValueAtTime(0.12, now + 0.2);
        ng.gain.exponentialRampToValueAtTime(0.001, now + 1.0);
        noise.connect(nf);
        nf.connect(ng);
        ng.connect(output);
        noise.start(now);
        noise.stop(now + 1.1);
    };

    const enchantRise = async () => {
        const setup = await createSfxOutput(0.15);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(300, now);
        osc.frequency.exponentialRampToValueAtTime(2400, now + 0.7);
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.2, now + 0.2);
        g.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
        osc.connect(g);
        g.connect(output);
        osc.start(now);
        osc.stop(now + 0.9);
        const shimmer = ctx.createOscillator();
        const shg = ctx.createGain();
        shimmer.type = 'triangle';
        shimmer.frequency.setValueAtTime(600, now);
        shimmer.frequency.exponentialRampToValueAtTime(4800, now + 0.7);
        shg.gain.setValueAtTime(0.0001, now + 0.1);
        shg.gain.exponentialRampToValueAtTime(0.1, now + 0.3);
        shg.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
        shimmer.connect(shg);
        shg.connect(output);
        shimmer.start(now);
        shimmer.stop(now + 0.9);
    };

    const crystalRing = async () => {
        const setup = await createSfxOutput(0.14);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const harmonics = [880, 1320, 1760, 2640, 3520];
        harmonics.forEach((f, i) => {
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(f, now);
            const vol = 0.16 / (i + 1);
            g.gain.setValueAtTime(0.0001, now);
            g.gain.exponentialRampToValueAtTime(vol, now + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, now + 1.5 - i * 0.2);
            osc.connect(g);
            g.connect(output);
            osc.start(now);
            osc.stop(now + 1.6);
        });
    };

    const warpFlash = async () => {
        const setup = await createSfxOutput(0.15);
        const noiseBuffer = await getNoiseBuffer();
        if (!setup || !noiseBuffer) return;
        const { ctx, now, output } = setup;
        const sweep = ctx.createOscillator();
        const sg = ctx.createGain();
        sweep.type = 'sawtooth';
        sweep.frequency.setValueAtTime(100, now);
        sweep.frequency.exponentialRampToValueAtTime(3000, now + 0.12);
        sweep.frequency.exponentialRampToValueAtTime(200, now + 0.25);
        const sf = ctx.createBiquadFilter();
        sf.type = 'lowpass';
        sf.frequency.setValueAtTime(1000, now);
        sf.frequency.exponentialRampToValueAtTime(6000, now + 0.12);
        sf.frequency.exponentialRampToValueAtTime(500, now + 0.25);
        sg.gain.setValueAtTime(0.0001, now);
        sg.gain.exponentialRampToValueAtTime(0.22, now + 0.05);
        sg.gain.exponentialRampToValueAtTime(0.001, now + 0.28);
        sweep.connect(sf);
        sf.connect(sg);
        sg.connect(output);
        sweep.start(now);
        sweep.stop(now + 0.3);
        const pop = ctx.createBufferSource();
        pop.buffer = noiseBuffer;
        const pf = ctx.createBiquadFilter();
        pf.type = 'highpass';
        pf.frequency.setValueAtTime(4000, now + 0.1);
        const pg = ctx.createGain();
        pg.gain.setValueAtTime(0.0001, now + 0.1);
        pg.gain.exponentialRampToValueAtTime(0.18, now + 0.12);
        pg.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
        pop.connect(pf);
        pf.connect(pg);
        pg.connect(output);
        pop.start(now + 0.1);
        pop.stop(now + 0.35);
    };

    // ── F. 節奏 / 提示類 ──────────────────────────────────────

    const heartbeatLoop = async () => {
        const setup = await createSfxOutput(0.12);
        if (!setup) return { stop: () => {} };
        const { ctx, output } = setup;
        let stopped = false;
        let tid;
        const beat = () => {
            if (stopped) return;
            const t = ctx.currentTime;
            [0, 0.12].forEach((delay) => {
                const osc = ctx.createOscillator();
                const g = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(60, t + delay);
                osc.frequency.exponentialRampToValueAtTime(40, t + delay + 0.1);
                g.gain.setValueAtTime(0.0001, t + delay);
                g.gain.exponentialRampToValueAtTime(delay === 0 ? 0.25 : 0.18, t + delay + 0.02);
                g.gain.exponentialRampToValueAtTime(0.001, t + delay + 0.15);
                osc.connect(g);
                g.connect(output);
                osc.start(t + delay);
                osc.stop(t + delay + 0.18);
            });
            tid = window.setTimeout(beat, 800);
        };
        beat();
        return {
            stop: () => {
                stopped = true;
                window.clearTimeout(tid);
                const t = ctx.currentTime;
                output.gain.exponentialRampToValueAtTime(0.001, t + 0.2);
            },
        };
    };

    const suspenseRise = async () => {
        const setup = await createSfxOutput(0.14);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(150, now);
        osc.frequency.exponentialRampToValueAtTime(800, now + 1.5);
        const f = ctx.createBiquadFilter();
        f.type = 'lowpass';
        f.frequency.setValueAtTime(400, now);
        f.frequency.exponentialRampToValueAtTime(3000, now + 1.5);
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(0.18, now + 0.5);
        g.gain.exponentialRampToValueAtTime(0.22, now + 1.3);
        g.gain.exponentialRampToValueAtTime(0.001, now + 1.6);
        osc.connect(f);
        f.connect(g);
        g.connect(output);
        osc.start(now);
        osc.stop(now + 1.65);
    };

    const notificationDing = async () => {
        const setup = await createSfxOutput(0.12);
        if (!setup) return;
        const { ctx, now, output } = setup;
        [{ f: 880, t: 0 }, { f: 1318.5, t: 0.12 }].forEach(({ f, t: delay }) => {
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(f, now + delay);
            g.gain.setValueAtTime(0.0001, now + delay);
            g.gain.exponentialRampToValueAtTime(0.2, now + delay + 0.01);
            g.gain.exponentialRampToValueAtTime(0.001, now + delay + 0.3);
            osc.connect(g);
            g.connect(output);
            osc.start(now + delay);
            osc.stop(now + delay + 0.35);
        });
    };

    const timerTickFastLoop = async () => {
        const setup = await createSfxOutput(0.1);
        if (!setup) return { stop: () => {} };
        const { ctx, output } = setup;
        let stopped = false;
        let tid;
        let interval = 200;
        const tick = () => {
            if (stopped) return;
            const t = ctx.currentTime;
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(1000, t);
            g.gain.setValueAtTime(0.18, t);
            g.gain.exponentialRampToValueAtTime(0.001, t + 0.04);
            osc.connect(g);
            g.connect(output);
            osc.start(t);
            osc.stop(t + 0.05);
            if (interval > 80) interval -= 3;
            tid = window.setTimeout(tick, interval);
        };
        tick();
        return {
            stop: () => {
                stopped = true;
                window.clearTimeout(tid);
                interval = 200;
                const t = ctx.currentTime;
                output.gain.exponentialRampToValueAtTime(0.001, t + 0.1);
            },
        };
    };

    const grandReveal = async () => {
        const setup = await createSfxOutput(0.2);
        if (!setup) return;
        const { ctx, now, output } = setup;
        const brass = [
            { f: 261.63, t: 0 },
            { f: 329.63, t: 0 },
            { f: 392, t: 0.08 },
            { f: 523.25, t: 0.16 },
            { f: 659.25, t: 0.24 },
            { f: 783.99, t: 0.32 },
            { f: 1046.5, t: 0.4 },
        ];
        brass.forEach(({ f, t: offset }) => {
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(f, now + offset);
            const filter = ctx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(1500, now + offset);
            filter.frequency.exponentialRampToValueAtTime(3000, now + offset + 0.2);
            g.gain.setValueAtTime(0.0001, now + offset);
            g.gain.exponentialRampToValueAtTime(0.16, now + offset + 0.02);
            g.gain.exponentialRampToValueAtTime(0.001, now + offset + 0.5);
            osc.connect(filter);
            filter.connect(g);
            g.connect(output);
            osc.start(now + offset);
            osc.stop(now + offset + 0.55);
        });
        for (let i = 0; i < 5; i++) {
            const t = now + 0.3 + i * 0.05;
            const sparkle = ctx.createOscillator();
            const sg = ctx.createGain();
            sparkle.type = 'sine';
            sparkle.frequency.setValueAtTime(3000 + Math.random() * 3000, t);
            sg.gain.setValueAtTime(0.0001, t);
            sg.gain.exponentialRampToValueAtTime(0.08, t + 0.01);
            sg.gain.exponentialRampToValueAtTime(0.001, t + 0.1);
            sparkle.connect(sg);
            sg.connect(output);
            sparkle.start(t);
            sparkle.stop(t + 0.12);
        }
    };

    const soundPlayers = {
        playChestOpen,
        playBallPick,
        playMachineStop,
        playScratchLoop,
        playReveal,
        playSlotTick,
        playCoinDrop,
        playVictory,
        playPaperTear,
        playBallRumbleLoop,
        playButtonClick,
        playError,
        playWhoosh,
        playDrumRollLoop,
        coinShowerLoop,
        countdownTick,
        drawStartStinger,
        switchPrizeWhoosh,
        batchRevealStinger,
        noEligibleWarning,
        resultEnterSwell,
        // A. 金幣 / 金屬類
        coinCascade,
        coinSpin,
        coinInsert,
        jackpotCoins,
        metalChime,
        // B. 氣流 / 環境類
        airBurst,
        windGust,
        airSuction,
        bubblePop,
        steamRelease,
        // C. 中獎 / 慶祝類
        fanfareShort,
        fireworkPop,
        applauseBurst,
        confettiShowerLoop,
        celebrationBell,
        // D. 機械 / 科技類
        reelSpinLoop,
        lockClick,
        powerUp,
        digitalBeep,
        servoMove,
        // E. 魔法 / 特效類
        magicSparkle,
        portalOpen,
        enchantRise,
        crystalRing,
        warpFlash,
        // F. 節奏 / 提示類
        heartbeatLoop,
        suspenseRise,
        notificationDing,
        timerTickFastLoop,
        grandReveal,
    };

    const playOneShot = async (item) => {
        const player = soundPlayers[item.player];
        if (!player) {
            setStatus(`找不到音效播放器：${item.name}`, 'error');
            return;
        }
        await player();
        markCardPulse(item.key);
        setStatus(`試聽中：${item.name}`, 'success');
    };

    const startLoop = async (item) => {
        const player = soundPlayers[item.player];
        if (!player) {
            setStatus(`找不到音效播放器：${item.name}`, 'error');
            return;
        }

        stopLoop(item.key);
        const handle = await player();
        if (!handle || typeof handle.stop !== 'function') {
            setStatus(`無法啟動循環音效：${item.name}`, 'error');
            return;
        }
        state.loops.set(item.key, handle);
        syncLoopCardState(item.key, true);
        setStatus(`循環播放中：${item.name}`, 'success');
    };

    const stopLoopFromCard = (item) => {
        stopLoop(item.key);
        setStatus(`已停止：${item.name}`, 'success');
    };

    const buildBadge = (text, className) => {
        const badge = document.createElement('span');
        badge.className = `testsound-badge ${className}`;
        badge.textContent = text;
        return badge;
    };

    const createCard = (item) => {
        const card = document.createElement('article');
        card.className = 'testsound-card';
        card.dataset.key = item.key;

        const head = document.createElement('div');
        head.className = 'testsound-card-head';

        const title = document.createElement('h3');
        title.textContent = item.name;

        const badges = document.createElement('div');
        badges.className = 'testsound-badges';
        badges.appendChild(buildBadge(item.linked ? '主流程已串接' : '目前未串接', item.linked ? 'testsound-badge-link' : 'testsound-badge-unlink'));
        badges.appendChild(buildBadge(item.mode === 'loop' ? '循環音效' : '單次音效', item.mode === 'loop' ? 'testsound-badge-loop' : 'testsound-badge-once'));

        head.appendChild(title);
        head.appendChild(badges);

        const desc = document.createElement('p');
        desc.textContent = item.description;

        const scene = document.createElement('small');
        scene.textContent = `場景：${item.scene}`;

        const actions = document.createElement('div');
        actions.className = 'testsound-card-actions';

        const playBtn = document.createElement('button');
        playBtn.type = 'button';
        playBtn.className = 'testsound-btn testsound-btn-primary';
        playBtn.textContent = item.mode === 'loop' ? '開始循環' : '播放';

        actions.appendChild(playBtn);

        let stopBtn = null;
        if (item.mode === 'loop') {
            stopBtn = document.createElement('button');
            stopBtn.type = 'button';
            stopBtn.className = 'testsound-btn';
            stopBtn.textContent = '停止';
            stopBtn.disabled = true;
            actions.appendChild(stopBtn);
        }

        playBtn.addEventListener('click', async () => {
            if (item.mode === 'loop') {
                await startLoop(item);
                return;
            }
            await playOneShot(item);
        });

        if (stopBtn) {
            stopBtn.addEventListener('click', () => {
                stopLoopFromCard(item);
            });
        }

        card.appendChild(head);
        card.appendChild(desc);
        card.appendChild(scene);
        card.appendChild(actions);

        state.cardRefs.set(item.key, {
            card,
            mode: item.mode,
            playBtn,
            stopBtn,
        });

        return card;
    };

    const renderCards = () => {
        originalGridEl.innerHTML = '';
        suggestedGridEl.innerHTML = '';

        originalSounds.forEach((item) => {
            originalGridEl.appendChild(createCard(item));
        });

        const groups = new Map();
        suggestedSounds.forEach((item) => {
            const cat = item.category || '其他';
            if (!groups.has(cat)) groups.set(cat, []);
            groups.get(cat).push(item);
        });

        groups.forEach((items, category) => {
            const heading = document.createElement('h3');
            heading.className = 'testsound-category-title';
            heading.textContent = category;
            suggestedGridEl.appendChild(heading);

            const grid = document.createElement('div');
            grid.className = 'testsound-grid';
            items.forEach((item) => {
                grid.appendChild(createCard(item));
            });
            suggestedGridEl.appendChild(grid);
        });
    };

    const findItemByKey = (key) => {
        return [...originalSounds, ...suggestedSounds].find((item) => item.key === key) || null;
    };

    const getOrCreateBgmAudio = (url) => {
        const trimmed = (url || '').trim();
        if (!trimmed) {
            throw new Error('請先輸入可播放的音樂 URL');
        }

        if (!state.bgmAudio || state.bgmUrl !== trimmed) {
            if (state.bgmAudio) {
                state.bgmAudio.pause();
            }
            state.bgmAudio = new Audio(trimmed);
            state.bgmAudio.preload = 'auto';
            state.bgmUrl = trimmed;
        }

        state.bgmAudio.loop = Boolean(bgmLoopInput?.checked);
        applyVolumes();
        return state.bgmAudio;
    };

    const runTreasureDemo = async () => {
        const token = ++state.demoToken;
        stopAllLoops();

        const chest = findItemByKey('chest_open');
        const coinLoop = findItemByKey('coin_shower_loop');
        const coinDrop = findItemByKey('coin_drop');
        const stinger = findItemByKey('batch_reveal_stinger');

        if (!chest || !coinLoop || !coinDrop || !stinger) {
            setStatus('Demo 音效項目缺失，請檢查設定。', 'error');
            return;
        }

        setStatus('播放中：寶箱噴金幣 Demo', 'success');
        await playOneShot(chest);
        if (token !== state.demoToken) return;
        await startLoop(coinLoop);
        if (token !== state.demoToken) return;
        await wait(1800);
        if (token !== state.demoToken) return;
        await playOneShot(coinDrop);
        if (token !== state.demoToken) return;
        await wait(320);
        if (token !== state.demoToken) return;
        await playOneShot(stinger);
        if (token !== state.demoToken) return;
        await wait(460);
        if (token !== state.demoToken) return;
        stopLoop(coinLoop.key);
        setStatus('寶箱噴金幣 Demo 播放完成。', 'success');
    };

    const runFlowDemo = async () => {
        const token = ++state.demoToken;
        stopAllLoops();

        const click = findItemByKey('button_click');
        const start = findItemByKey('draw_start_stinger');
        const drum = findItemByKey('drum_roll');
        const pick = findItemByKey('ball_pick');
        const reveal = findItemByKey('reveal');
        const victory = findItemByKey('victory');

        if (!click || !start || !drum || !pick || !reveal || !victory) {
            setStatus('Demo 音效項目缺失，請檢查設定。', 'error');
            return;
        }

        setStatus('播放中：抽獎節奏 Demo', 'success');
        await playOneShot(click);
        if (token !== state.demoToken) return;
        await wait(140);
        if (token !== state.demoToken) return;
        await playOneShot(start);
        if (token !== state.demoToken) return;
        await startLoop(drum);
        if (token !== state.demoToken) return;
        await wait(340);
        if (token !== state.demoToken) return;
        await playOneShot(pick);
        if (token !== state.demoToken) return;
        await wait(280);
        if (token !== state.demoToken) return;
        await playOneShot(pick);
        if (token !== state.demoToken) return;
        await wait(280);
        if (token !== state.demoToken) return;
        await playOneShot(pick);
        if (token !== state.demoToken) return;
        await wait(280);
        if (token !== state.demoToken) return;
        stopLoop(drum.key);
        await playOneShot(reveal);
        if (token !== state.demoToken) return;
        await wait(240);
        if (token !== state.demoToken) return;
        await playOneShot(victory);
        setStatus('抽獎節奏 Demo 播放完成。', 'success');
    };

    const bindGlobalActions = () => {
        document.querySelector('[data-action="enable-audio"]')?.addEventListener('click', async () => {
            try {
                await ensureContext();
            } catch {
                setStatus('啟用音效失敗，請重新嘗試。', 'error');
            }
        });

        document.querySelector('[data-action="stop-all"]')?.addEventListener('click', () => {
            stopAll();
        });

        document.querySelector('[data-action="demo-flow"]')?.addEventListener('click', async () => {
            try {
                await runFlowDemo();
            } catch {
                setStatus('抽獎節奏 Demo 播放失敗。', 'error');
            }
        });

        document.querySelector('[data-action="demo-treasure"]')?.addEventListener('click', async () => {
            try {
                await runTreasureDemo();
            } catch {
                setStatus('寶箱噴金幣 Demo 播放失敗。', 'error');
            }
        });

        document.querySelector('[data-action="bgm-load-play"]')?.addEventListener('click', async () => {
            try {
                const audio = getOrCreateBgmAudio(bgmUrlInput?.value || '');
                await audio.play();
                setStatus('BGM 播放中。', 'success');
            } catch (error) {
                setStatus(error?.message || 'BGM 無法播放，請確認 URL。', 'error');
            }
        });

        document.querySelector('[data-action="bgm-pause"]')?.addEventListener('click', () => {
            if (!state.bgmAudio) {
                setStatus('目前沒有播放中的 BGM。');
                return;
            }
            state.bgmAudio.pause();
            setStatus('BGM 已暫停。', 'success');
        });

        document.querySelector('[data-action="bgm-stop"]')?.addEventListener('click', () => {
            if (!state.bgmAudio) {
                setStatus('目前沒有播放中的 BGM。');
                return;
            }
            stopBgm();
            setStatus('BGM 已停止。', 'success');
        });

        bgmLoopInput?.addEventListener('change', () => {
            if (state.bgmAudio) {
                state.bgmAudio.loop = Boolean(bgmLoopInput.checked);
            }
        });

        volumeInputs.forEach((input) => {
            input.addEventListener('input', () => {
                const key = input.dataset.volume;
                const percent = Number.parseInt(input.value || '0', 10);
                const normalized = clamp(percent / 100, 0, 1);

                if (key === 'master') {
                    state.masterVolume = normalized;
                }
                if (key === 'sfx') {
                    state.sfxVolume = normalized;
                }
                if (key === 'bgm') {
                    state.bgmVolume = normalized;
                }

                const label = volumeLabels[key];
                if (label) {
                    label.textContent = `${percent}%`;
                }

                applyVolumes();
            });
        });
    };

    const bootstrap = () => {
        renderCards();
        bindGlobalActions();

        if (!AudioContextRef) {
            setStatus('瀏覽器不支援 WebAudio，仍可使用 BGM 測試。', 'error');
        } else {
            setStatus('尚未啟用音效（可先直接按啟用音效）');
        }

        window.addEventListener('beforeunload', () => {
            stopAll();
            if (state.context) {
                state.context.close().catch(() => {});
            }
        });
    };

    bootstrap();
};

document.addEventListener('DOMContentLoaded', initTestSound);
