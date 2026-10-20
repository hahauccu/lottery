import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/lottery.js', import.meta.url), 'utf8');
const moduleSource = source.slice(
    source.indexOf('    const battleTop = (() => {'),
    source.indexOf('    const stopAllAnimations = () => {'),
);

// 執行正式模組的狀態機與物理；僅以虛擬時鐘、Canvas 取代瀏覽器環境。
function createBattle(names, { width = 1280, height = 720 } = {}) {
    let scheduledFrame = null;
    let now = 0;
    let randomSeed = 73;
    const labels = [];
    const translations = [];
    const rect = { width, height };
    const context = new Proxy({}, {
        get(target, key) {
            if (key in target) return target[key];
            if (key === 'createRadialGradient' || key === 'createLinearGradient') {
                return () => ({ addColorStop() {} });
            }
            if (key === 'arc') return (x, y, radius) => {
                assert.ok(Number.isFinite(x) && Number.isFinite(y) && Number.isFinite(radius));
                assert.ok(radius >= 0, `invalid Canvas radius ${radius}`);
            };
            if (key === 'fillText') return (label) => labels.push(label);
            if (key === 'translate') return (x, y) => translations.push({ x, y });
            return () => {};
        },
    });
    const canvas = { getBoundingClientRect: () => rect, getContext: () => context };
    const math = Object.create(Math);
    math.random = () => {
        randomSeed = (randomSeed * 16807) % 2147483647;
        return randomSeed / 2147483647;
    };
    const factory = new Function(
        'state', 'lottoCanvasEl', 'lottoWrapEl', 'window', 'performance',
        'requestAnimationFrame', 'cancelAnimationFrame', 'clearCanvas',
        'clamp', 'lerp', 'rand', 'shuffle', 'TAU', 'fittedCanvasFontSize', 'Math',
        `${moduleSource}\nreturn battleTop;`,
    );
    const battle = factory(
        { eligibleNames: names, winners: [] }, canvas, canvas, { devicePixelRatio: 2 }, { now: () => now },
        (callback) => { scheduledFrame = callback; return 1; }, () => { scheduledFrame = null; }, () => {},
        (value, min, max) => Math.max(min, Math.min(max, value)),
        (from, to, amount) => from + (to - from) * amount,
        (min, max) => min + math.random() * (max - min), (items) => items, Math.PI * 2,
        () => 12, math,
    );
    battle.ensureReady();
    return {
        battle, rect, labels, translations,
        advance(seconds) {
            for (let frame = 0; frame < Math.ceil(seconds * 20); frame++) {
                now += 50;
                scheduledFrame?.(now);
            }
        },
    };
}

test('正式 driver 不因一次全抽人數增加而改寫設定秒數', () => {
    const driverSource = source.slice(source.indexOf('        battle_top: {'), source.indexOf('        fallback: {'));
    let seconds = null;
    const driver = new Function('battleTop', 'state', `return ({${driverSource}}).battle_top;`)(
        { ensureReady() {}, setHoldSeconds(value) { seconds = value; } },
        { currentPrize: { lottoHoldSeconds: 10, drawMode: 'all_at_once' }, eligibleNames: Array(1000).fill('名單') },
    );
    driver.prepareToDraw();
    assert.equal(seconds, 10);
});

test('1000 人仍依設定秒數揭曉完整結果，不等到 75 秒保護計時器', async () => {
    const names = Array.from({ length: 1000 }, (_, index) => `員工${index}`);
    const { battle, advance, labels } = createBattle(names);
    battle.setHoldSeconds(10);
    battle.setWinnerQueue(names.slice(0, 5));
    battle.startDraw();
    let resolved = 0;
    const picks = Array.from({ length: 5 }, () => battle.waitForNextPick().then(() => resolved++));
    advance(16);
    await Promise.resolve();
    assert.equal(resolved, 5);
    await Promise.all(picks);
    assert.ok(labels.includes('員工0'));
    battle.stop();
});

test('超過場上容量與同名重複中獎，仍逐一 resolve 每筆 API 結果', async () => {
    const { battle, advance } = createBattle(['相同姓名', '另一位']);
    battle.setHoldSeconds(5);
    battle.setWinnerQueue(Array(30).fill('相同姓名'));
    battle.startDraw();
    let resolved = 0;
    const picks = Array.from({ length: 30 }, () => battle.waitForNextPick().then(() => resolved++));
    advance(11);
    await Promise.resolve();
    assert.equal(resolved, 30);
    await Promise.all(picks);
    battle.stop();
});

test('60 秒設定仍在 75 秒前揭曉；中途改為手機直向 Canvas 保持有效', async () => {
    const names = Array.from({ length: 100 }, (_, index) => `參加者${index}`);
    const { battle, advance, rect } = createBattle(names);
    battle.setHoldSeconds(60);
    battle.setWinnerQueue(names.slice(0, 15));
    battle.startDraw();
    let resolved = 0;
    const picks = Array.from({ length: 15 }, () => battle.waitForNextPick().then(() => resolved++));
    advance(10);
    Object.assign(rect, { width: 320, height: 560 });
    battle.resize();
    advance(56);
    await Promise.resolve();
    assert.equal(resolved, 15);
    await Promise.all(picks);
    battle.stop();
});

test('stop 解除舊 waiter；下輪不沿用前輪尚未消費的揭曉結果', async () => {
    const { battle, advance } = createBattle(['甲', '乙', '丙']);
    battle.setHoldSeconds(5);
    battle.setWinnerQueue(['甲']);
    battle.startDraw();
    let cancelledWaiterReleased = false;
    const oldWaiter = battle.waitForNextPick().then(() => { cancelledWaiterReleased = true; });
    advance(2);
    battle.stop();
    await oldWaiter;
    assert.equal(cancelledWaiterReleased, true);
    battle.ensureReady();
    battle.setWinnerQueue(['乙']);
    battle.startDraw();
    let nextResolved = false;
    const nextWaiter = battle.waitForNextPick().then(() => { nextResolved = true; });
    advance(1);
    await Promise.resolve();
    assert.equal(nextResolved, false);
    advance(10);
    await nextWaiter;
    assert.equal(nextResolved, true);
    battle.stop();
});

test('得主揭曉後縮小視窗，陀螺仍在新 Canvas 內', async () => {
    const { battle, advance, rect, translations } = createBattle(['甲']);
    battle.setHoldSeconds(5);
    battle.setWinnerQueue(['甲']);
    battle.startDraw();
    const pick = battle.waitForNextPick();
    advance(11);
    await pick;
    translations.length = 0;
    Object.assign(rect, { width: 320, height: 560 });
    battle.resize();
    advance(0.05);
    assert.ok(translations.length > 0);
    for (const { x, y } of translations) {
        assert.ok(x >= 0 && x <= rect.width, `winner x=${x} outside width=${rect.width}`);
        assert.ok(y >= 0 && y <= rect.height, `winner y=${y} outside height=${rect.height}`);
    }
    battle.stop();
});
