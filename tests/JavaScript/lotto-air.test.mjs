import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

// Execute the real canvas engine with deterministic physics inputs, without a browser.
const source = fs.readFileSync(new URL('../../resources/js/lottery.js', import.meta.url), 'utf8');
const start = source.indexOf('    const lottoAir = (() => {');
const end = source.indexOf('    // ===========================================\n    // 紅包雨', start);
assert.ok(start !== -1 && end !== -1, 'lottoAir module boundaries must exist');
const moduleSource = source.slice(start, end).replace(
    '        return {\n            ensureReady,',
    '        globalThis.probe = { airState, update, pickBall, reset, resizeCanvas };\n        return {\n            ensureReady,',
);

function engine(width = 390, height = 600) {
    let now = 10000;
    const dimensions = { width, height };
    const context = {
        console: { warn() {} }, Math, Promise,
        setTimeout: () => 1, clearTimeout() {},
        setInterval: () => 1, clearInterval() {},
        performance: { now: () => now },
        window: { devicePixelRatio: 2 },
        state: { eligibleNames: ['甲', '乙'], winners: [] },
        lottoAirConfig: { count: 2, turb: 0.78, swirl: 0.72, grav: 0.52 },
        TAU: Math.PI * 2,
        rand: (min, max) => (min + max) / 2,
        clamp: (value, min, max) => Math.min(max, Math.max(min, value)),
        lerp: (a, b, t) => a + (b - a) * t,
        fbm: () => 0.5, shuffle: (items) => items,
        sfx: { playBallPick() {} },
        lottoCanvasEl: {
            getBoundingClientRect: () => dimensions,
            getContext: () => ({ setTransform() {} }),
        },
        lottoWrapEl: { getBoundingClientRect: () => dimensions },
    };
    vm.createContext(context);
    vm.runInContext(`${moduleSource}; globalThis.api = lottoAir;`, context);
    const { airState, update, pickBall, reset, resizeCanvas } = context.probe;
    reset();
    return {
        api: context.api, airState, update, pickBall, reset, resizeCanvas, dimensions,
        elapse(ms) { now += ms; },
        putAtIntake(ball) {
            ball.x = airState.chute.x;
            ball.y = airState.chute.y + airState.chute.h * 0.55;
        },
    };
}

test('two balls finishing in the same frame preserve both sequential await notifications', async () => {
    const e = engine();
    e.pickBall('甲');
    e.pickBall('乙');
    let completed = 0;
    const revealing = (async () => {
        await e.api.waitForNextPick(); completed += 1;
        await e.api.waitForNextPick(); completed += 1;
    })();
    // Simulate resuming a throttled tab after both six-second escape deadlines.
    e.elapse(7000);
    e.update(0);
    await Promise.resolve();
    await Promise.resolve();
    assert.equal(completed, 2);
    assert.equal(e.airState.waiters.length, 0);
    await revealing;
});

test('a later API winner arriving first cannot reveal an earlier winner prematurely', async () => {
    const e = engine();
    e.pickBall('甲');
    e.pickBall('乙');
    let completed = false;
    const first = e.api.waitForNextPick().then(() => { completed = true; });
    e.putAtIntake(e.airState.picked[1]);
    e.update(0);
    await Promise.resolve();
    assert.equal(e.airState.picked[1].out, true);
    assert.equal(completed, false);
    e.putAtIntake(e.airState.picked[0]);
    e.update(0);
    await first;
    assert.equal(completed, true);
    assert.equal(e.airState.completedPicks, 1);
    await e.api.waitForNextPick();
    assert.equal(e.airState.completedPicks, 0);
});

test('stop settles existing waits and does not strand subsequent sequential waits', async () => {
    const e = engine();
    let completed = 0;
    const waiting = e.api.waitForNextPick().then(() => { completed += 1; });
    e.api.stop();
    await waiting;
    await e.api.waitForNextPick().then(() => { completed += 1; });
    assert.equal(completed, 2);
    assert.equal(e.airState.waiters.length, 0);
});

test('one-by-one continuation retains old balls without reusing an old completion', async () => {
    const e = engine();
    e.pickBall('甲');
    const first = e.api.waitForNextPick();
    e.elapse(7000);
    e.update(0);
    await first;
    e.api.setWinners(['乙'], { resetBalls: false, resetPicked: false });
    e.pickBall('乙');
    let settled = false;
    const second = e.api.waitForNextPick().then(() => { settled = true; });
    await Promise.resolve();
    assert.equal(settled, false);
    assert.equal(e.airState.picked.length, 2);
    e.elapse(7000);
    e.update(0);
    await second;
    assert.equal(settled, true);
    assert.equal(e.airState.completionCursor, 2);
    assert.equal(e.airState.completedPicks, 0);
});

test('reset settles pending waits and clears completion credits before the next round', async () => {
    const e = engine();
    let settled = false;
    const waiting = e.api.waitForNextPick().then(() => { settled = true; });
    e.reset();
    await waiting;
    assert.equal(settled, true);
    assert.equal(e.airState.completedPicks, 0);
    assert.equal(e.airState.completionCursor, 0);
    assert.equal(e.airState.picked.length, 0);
});

test('escape deadline still completes a winning ball after a portrait resize', async () => {
    const e = engine(1280, 720);
    e.pickBall('甲');
    e.dimensions.width = 320;
    e.dimensions.height = 850;
    assert.equal(e.resizeCanvas(), true);
    const waiting = e.api.waitForNextPick();
    e.elapse(7000);
    e.update(0);
    assert.equal(e.airState.picked[0].out, true);
    assert.equal(e.airState.waiters.length, 0);
    await waiting;
});

test('an API winner absent from the canvas pool is displayed under the API name', () => {
    const e = engine();
    e.pickBall('真正得獎者');
    assert.equal(e.airState.picked[0].name, '真正得獎者');
    assert.equal(e.airState.picked[0].label, '真正得獎…');
});
