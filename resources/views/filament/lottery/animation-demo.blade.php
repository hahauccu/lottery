<div class="rounded-xl border border-slate-200/10 bg-slate-900/70 px-4 py-4">
    <div class="text-xs uppercase tracking-[0.3em] text-slate-300">Demo</div>
        <div class="mt-3 flex items-center justify-center">
            <div class="demo-stage demo-stage-{{ $style ?? 'slot' }}">
                @if(($style ?? 'slot') === 'lotto_air')
                    <div class="demo-lotto-air">
                        <span class="demo-ball">05</span>
                        <span class="demo-ball">12</span>
                        <span class="demo-ball">23</span>
                        <span class="demo-ball">31</span>
                        <span class="demo-ball">47</span>
                    </div>
                @elseif(($style ?? 'slot') === 'scratch_card')
                    <div class="demo-scratch-card">
                        <div class="demo-scratch-base">中獎</div>
                        <div class="demo-scratch-overlay"></div>
                    </div>
                @elseif(($style ?? 'slot') === 'treasure_chest')
                    <div class="demo-treasure-chest">
                        <div class="demo-chest">
                            <div class="demo-chest-body">
                                <div class="demo-chest-lock"></div>
                            </div>
                            <div class="demo-chest-lid"></div>
                            <div class="demo-chest-glow"></div>
                            <div class="demo-chest-coins">
                                <span class="demo-coin"></span>
                                <span class="demo-coin"></span>
                                <span class="demo-coin"></span>
                            </div>
                        </div>
                    </div>
                @elseif(($style ?? 'slot') === 'big_treasure_chest')
                    <div class="demo-big-chest">
                        <div class="demo-big-chest-body"></div>
                        <div class="demo-big-chest-lid"></div>
                        <div class="demo-big-chest-glow"></div>
                        <div class="demo-big-coins">
                            <span class="demo-big-coin"></span>
                            <span class="demo-big-coin"></span>
                            <span class="demo-big-coin"></span>
                            <span class="demo-big-coin"></span>
                            <span class="demo-big-gourd"></span>
                        </div>
                        <div class="demo-big-hero">
                            <div class="demo-big-hero-token">中獎者</div>
                        </div>
                    </div>
                @elseif(($style ?? 'slot') === 'slot')
                    <div class="demo-slot-machine">
                        <div class="demo-reel">
                            <div class="demo-reel-track">
                                <span>7</span><span>BAR</span><span>★</span><span>◆</span><span>7</span><span>BAR</span>
                            </div>
                        </div>
                        <div class="demo-reel">
                            <div class="demo-reel-track">
                                <span>◆</span><span>7</span><span>BAR</span><span>★</span><span>◆</span><span>7</span>
                            </div>
                        </div>
                        <div class="demo-reel">
                            <div class="demo-reel-track">
                                <span>★</span><span>◆</span><span>7</span><span>BAR</span><span>★</span><span>◆</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="demo-text demo-{{ $style ?? 'slot' }}">DEMO</div>
                @endif
            </div>
        </div>
    <div class="mt-3 text-xs text-slate-400">切換抽獎動畫可即時預覽效果。</div>
</div>

<style>
    .demo-stage {
        position: relative;
        width: 100%;
        max-width: 360px;
        height: 140px;
        border-radius: 16px;
        background: radial-gradient(260px 140px at 50% 35%, rgba(120, 170, 255, 0.18), transparent 60%),
            radial-gradient(220px 120px at 50% 85%, rgba(255, 210, 120, 0.12), transparent 60%),
            rgba(15, 23, 42, 0.65);
        border: 1px solid rgba(148, 163, 184, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .demo-stage-big_treasure_chest {
        height: 170px;
    }

    .demo-text {
        font-size: 2.2rem;
        font-weight: 700;
        letter-spacing: 0.18em;
        color: #f8fafc;
        text-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
    }

    .demo-slot-machine {
        display: flex;
        gap: 10px;
    }

    .demo-reel {
        width: 70px;
        height: 110px;
        border-radius: 14px;
        background: rgba(3, 7, 18, 0.6);
        border: 1px solid rgba(148, 163, 184, 0.2);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .demo-reel-track {
        display: grid;
        gap: 8px;
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        color: #f8fafc;
        animation: demo-reel-spin 1.1s linear infinite;
    }

    .demo-reel:nth-child(2) .demo-reel-track {
        animation-duration: 1.25s;
    }

    .demo-reel:nth-child(3) .demo-reel-track {
        animation-duration: 1.45s;
    }

    @keyframes demo-reel-spin {
        0% { transform: translateY(0); }
        100% { transform: translateY(-120px); }
    }

    .demo-lotto-air {
        position: relative;
        width: 280px;
        height: 120px;
    }

    .demo-ball {
        position: absolute;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 30%, #fff, #fcd34d 55%, #f59e0b 100%);
        color: #0b0f14;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 14px;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.45);
        animation: demo-lotto-float 1.6s ease-in-out infinite;
    }

    .demo-ball:nth-child(1) { left: 12px; top: 28px; animation-delay: 0s; }
    .demo-ball:nth-child(2) { left: 70px; top: 64px; animation-delay: 0.2s; }
    .demo-ball:nth-child(3) { left: 138px; top: 16px; animation-delay: 0.4s; }
    .demo-ball:nth-child(4) { left: 196px; top: 64px; animation-delay: 0.6s; }
    .demo-ball:nth-child(5) { left: 238px; top: 26px; animation-delay: 0.8s; }

    @keyframes demo-lotto-float {
        0% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-10px) scale(1.05); }
        100% { transform: translateY(0) scale(1); }
    }

    .demo-slot {
        animation: demo-slot 0.8s linear infinite;
    }

    @keyframes demo-slot {
        0% { transform: translateY(-20px); opacity: 0.6; }
        50% { transform: translateY(10px); opacity: 1; }
        100% { transform: translateY(-20px); opacity: 0.6; }
    }

    .demo-roulette {
        animation: demo-roulette 1s ease-in-out infinite;
    }

    @keyframes demo-roulette {
        0% { transform: scale(1) rotate(0deg); }
        50% { transform: scale(1.08) rotate(6deg); }
        100% { transform: scale(1) rotate(0deg); }
    }

    .demo-scramble {
        animation: demo-scramble 0.3s steps(2) infinite;
        text-shadow: 0 0 10px rgba(148, 163, 184, 0.8), 0 0 20px rgba(56, 189, 248, 0.6);
    }

    @keyframes demo-scramble {
        0% { letter-spacing: 0.2em; opacity: 0.6; }
        100% { letter-spacing: 0.35em; opacity: 1; }
    }

    .demo-typewriter {
        border-right: 3px solid rgba(248, 250, 252, 0.8);
        white-space: nowrap;
        overflow: hidden;
        width: 0;
        animation: demo-typewriter 1.6s steps(4) infinite, demo-caret 0.6s step-end infinite;
    }

    @keyframes demo-typewriter {
        0% { width: 0; }
        60% { width: 4.8em; }
        100% { width: 4.8em; }
    }

    @keyframes demo-caret {
        50% { border-color: transparent; }
    }

    .demo-flip {
        animation: demo-flip 0.9s ease-in-out infinite;
        transform-origin: center;
    }

    @keyframes demo-flip {
        0% { transform: rotateX(0deg); }
        50% { transform: rotateX(90deg); }
        100% { transform: rotateX(0deg); }
    }

    .demo-test {
        animation: demo-test 0.8s linear infinite;
        filter: drop-shadow(0 0 18px rgba(250, 204, 21, 0.65));
    }

    @keyframes demo-test {
        0% { transform: scale(1); filter: hue-rotate(0deg); }
        50% { transform: scale(1.12); filter: hue-rotate(180deg); }
        100% { transform: scale(1); filter: hue-rotate(360deg); }
    }

    .demo-scratch-card {
        position: relative;
        width: 160px;
        height: 100px;
        border-radius: 12px;
        overflow: hidden;
    }

    .demo-scratch-base {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, hsl(45, 85%, 58%), hsl(40, 85%, 48%));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        font-weight: 800;
        color: hsl(25, 90%, 25%);
        border: 3px solid hsl(35, 75%, 35%);
        border-radius: 12px;
    }

    .demo-scratch-overlay {
        position: absolute;
        inset: 3px;
        background: linear-gradient(135deg, hsl(220, 5%, 75%), hsl(220, 8%, 82%), hsl(220, 5%, 70%));
        border-radius: 9px;
        animation: demo-scratch 2s ease-in-out infinite;
    }

    .demo-scratch-overlay::before {
        content: '刮開';
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 700;
        color: hsl(220, 10%, 45%);
    }

    @keyframes demo-scratch {
        0% { clip-path: inset(0 0 0 0); opacity: 1; }
        40% { clip-path: inset(0 0 0 0); opacity: 1; }
        70% { clip-path: inset(0 100% 0 0); opacity: 0.8; }
        75% { clip-path: inset(0 100% 0 0); opacity: 0; }
        100% { clip-path: inset(0 0 0 0); opacity: 1; }
    }

    /* 寶箱動畫 */
    .demo-treasure-chest {
        position: relative;
        width: 120px;
        height: 100px;
    }

    .demo-chest {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .demo-chest-body {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 90px;
        height: 45px;
        background: linear-gradient(135deg, #8B4513 0%, #A0522D 50%, #654321 100%);
        border-radius: 8px;
        border: 3px solid #DAA520;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .demo-chest-body::before {
        content: '';
        position: absolute;
        top: 40%;
        left: 5px;
        right: 5px;
        height: 6px;
        background: #B8860B;
        border-radius: 2px;
    }

    .demo-chest-lock {
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 18px;
        height: 18px;
        background: radial-gradient(circle at 30% 30%, #FFE066, #FFD700 50%, #B8860B 100%);
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        animation: demo-lock-shake 2.5s ease-in-out infinite;
    }

    .demo-chest-lock::after {
        content: '';
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 10px;
        background: #B8860B;
        border-radius: 0 0 3px 3px;
    }

    .demo-chest-lid {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        width: 94px;
        height: 32px;
        background: linear-gradient(135deg, #A0522D 0%, #8B4513 50%, #654321 100%);
        border-radius: 8px 8px 0 0;
        border: 3px solid #DAA520;
        border-bottom: none;
        transform-origin: bottom center;
        animation: demo-chest-open 2.5s ease-in-out infinite;
    }

    .demo-chest-glow {
        position: absolute;
        bottom: 45px;
        left: 50%;
        transform: translateX(-50%);
        width: 70px;
        height: 50px;
        background: radial-gradient(ellipse at center, rgba(255, 215, 0, 0.9), rgba(255, 180, 0, 0.4) 50%, transparent 70%);
        opacity: 0;
        animation: demo-chest-glow 2.5s ease-in-out infinite;
        pointer-events: none;
    }

    .demo-chest-coins {
        position: absolute;
        bottom: 55px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 40px;
    }

    .demo-coin {
        position: absolute;
        width: 12px;
        height: 12px;
        background: radial-gradient(circle at 30% 30%, #FFE066, #FFD700 50%, #B8860B 100%);
        border-radius: 50%;
        opacity: 0;
        animation: demo-coin-fly 2.5s ease-out infinite;
    }

    .demo-coin:nth-child(1) {
        left: 50%;
        animation-delay: 0s;
    }

    .demo-coin:nth-child(2) {
        left: 30%;
        animation-delay: 0.1s;
    }

    .demo-coin:nth-child(3) {
        left: 70%;
        animation-delay: 0.15s;
    }

    @keyframes demo-lock-shake {
        0%, 25% { transform: translateX(-50%) rotate(0deg); }
        27%, 29%, 31%, 33% { transform: translateX(-50%) rotate(-5deg); }
        28%, 30%, 32% { transform: translateX(-50%) rotate(5deg); }
        35%, 100% { transform: translateX(-50%) rotate(0deg); opacity: 1; }
        36% { opacity: 0; }
        85% { opacity: 0; }
        86% { opacity: 1; }
    }

    @keyframes demo-chest-open {
        0%, 35% { transform: translateX(-50%) rotateX(0deg); }
        45%, 75% { transform: translateX(-50%) rotateX(-110deg); }
        85%, 100% { transform: translateX(-50%) rotateX(0deg); }
    }

    @keyframes demo-chest-glow {
        0%, 35% { opacity: 0; }
        45%, 75% { opacity: 1; }
        85%, 100% { opacity: 0; }
    }

    @keyframes demo-coin-fly {
        0%, 40% { opacity: 0; transform: translateY(0) scale(0.5); }
        45% { opacity: 1; transform: translateY(-10px) scale(1); }
        70% { opacity: 1; transform: translateY(-30px) scale(1); }
        80% { opacity: 0; transform: translateY(-40px) scale(0.8); }
        100% { opacity: 0; transform: translateY(0) scale(0.5); }
    }

    /* 大寶箱動畫 */
    .demo-big-chest {
        position: relative;
        width: 230px;
        height: 150px;
    }

    .demo-big-chest-body {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 185px;
        height: 86px;
        background: linear-gradient(135deg, #7c3a16 0%, #b5622f 50%, #5b2c12 100%);
        border-radius: 14px;
        border: 4px solid #f4c430;
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.45);
    }

    .demo-big-chest-body::before {
        content: '';
        position: absolute;
        top: 45%;
        left: 8px;
        right: 8px;
        height: 12px;
        background: linear-gradient(90deg, #d4a017, #ffd24d, #d4a017);
        border-radius: 6px;
    }

    .demo-big-chest-lid {
        position: absolute;
        bottom: 72px;
        left: 50%;
        transform: translateX(-50%);
        width: 200px;
        height: 56px;
        background: linear-gradient(135deg, #a3542a 0%, #8b4513 50%, #5b2c12 100%);
        border-radius: 14px 14px 0 0;
        border: 4px solid #f4c430;
        border-bottom: none;
        transform-origin: bottom center;
        animation: demo-big-chest-open 2.6s ease-in-out infinite;
    }

    .demo-big-chest-glow {
        position: absolute;
        bottom: 78px;
        left: 50%;
        transform: translateX(-50%);
        width: 150px;
        height: 110px;
        background: radial-gradient(ellipse at center, rgba(255, 215, 0, 0.9), rgba(255, 180, 0, 0.3) 55%, transparent 75%);
        opacity: 0;
        animation: demo-big-glow 2.6s ease-in-out infinite;
        pointer-events: none;
    }

    .demo-big-coins {
        position: absolute;
        bottom: 86px;
        left: 50%;
        transform: translateX(-50%);
        width: 150px;
        height: 90px;
    }

    .demo-big-coin {
        position: absolute;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 30%, #fff2a8, #ffd24d 45%, #c58b00 100%);
        opacity: 0;
        animation: demo-big-coin 2.8s ease-out infinite;
    }

    .demo-big-coin:nth-child(1) { left: 45%; animation-delay: 0s; }
    .demo-big-coin:nth-child(2) { left: 20%; animation-delay: 0.08s; }
    .demo-big-coin:nth-child(3) { left: 70%; animation-delay: 0.12s; }
    .demo-big-coin:nth-child(4) { left: 55%; animation-delay: 0.18s; }

    .demo-big-gourd {
        position: absolute;
        width: 16px;
        height: 22px;
        border-radius: 45% 45% 50% 50%;
        background: linear-gradient(160deg, #fff1a8, #ffd24d 45%, #b8860b 100%);
        opacity: 0;
        left: 32%;
        animation: demo-big-gourd 2.8s ease-out infinite;
        animation-delay: 0.18s;
    }

    .demo-big-gourd::before {
        content: '';
        position: absolute;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: linear-gradient(160deg, #fff6c0, #ffd24d 55%, #c58b00 100%);
        top: -7px;
        left: 2px;
    }

    .demo-big-hero {
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        width: 150px;
        height: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .demo-big-hero-token {
        position: relative;
        width: 150px;
        height: 78px;
        border-radius: 32px;
        background: linear-gradient(160deg, #fff4b5, #ffd24d 45%, #b8860b 100%);
        border: 4px solid #f5d36a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        font-weight: 700;
        color: #6b1f06;
        letter-spacing: 0.12em;
        text-shadow: 0 2px 10px rgba(255, 220, 140, 0.9);
        overflow: hidden;
        animation: demo-big-hero 2.8s ease-in-out infinite;
    }

    .demo-big-hero-token::before {
        content: '';
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 28px;
        border-radius: 999px;
        background: rgba(255, 248, 200, 0.45);
    }

    @keyframes demo-big-chest-open {
        0%, 35% { transform: translateX(-50%) rotateX(0deg); }
        45%, 70% { transform: translateX(-50%) rotateX(-105deg); }
        85%, 100% { transform: translateX(-50%) rotateX(0deg); }
    }

    @keyframes demo-big-glow {
        0%, 35% { opacity: 0; }
        45%, 70% { opacity: 1; }
        85%, 100% { opacity: 0; }
    }

    @keyframes demo-big-coin {
        0%, 40% { opacity: 0; transform: translateY(0) scale(0.6); }
        50% { opacity: 1; transform: translateY(-20px) scale(1); }
        70% { opacity: 1; transform: translateY(-38px) scale(0.9); }
        85% { opacity: 0; transform: translateY(-45px) scale(0.75); }
        100% { opacity: 0; transform: translateY(0) scale(0.6); }
    }

    @keyframes demo-big-gourd {
        0%, 40% { opacity: 0; transform: translateY(0) scale(0.65); }
        50% { opacity: 1; transform: translateY(-16px) scale(1); }
        70% { opacity: 1; transform: translateY(-32px) scale(0.9); }
        85% { opacity: 0; transform: translateY(-40px) scale(0.75); }
        100% { opacity: 0; transform: translateY(0) scale(0.65); }
    }

    @keyframes demo-big-hero {
        0%, 38% { opacity: 0; transform: translateY(18px) scale(0.7); }
        50% { opacity: 1; transform: translateY(0) scale(1); }
        72% { opacity: 1; transform: translateY(-4px) scale(1); }
        90%, 100% { opacity: 0; transform: translateY(-8px) scale(0.9); }
    }
</style>
