import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/lottery.js', import.meta.url), 'utf8');
const drawSource = source.slice(source.indexOf('    const draw = async () => {'), source.indexOf('    let lastKeyDrawAt ='));
const tick = async () => { for (let i = 0; i < 15; i++) await Promise.resolve(); };
const winners = [{ id: 1, employee_name: '甲' }, { id: 2, employee_name: '乙' }, { id: 3, employee_name: '丙' }];

function harness({ mode = 'all_at_once', style = 'lotto_air', hold = 5, previous = [], fetchError = false, reveal } = {}) {
    const timers = new Map();
    const calls = { render: 0, result: 0, stop: 0, audioStop: 0, heartbeatStop: 0, requests: 0, metrics: [] };
    const state = {
        isOpen: true, currentPrize: { id: 10, drawMode: mode, animationStyle: style, lottoHoldSeconds: hold, winnersCount: 30 },
        eligibleNames: ['甲', '乙', '丙'], winners: [...previous], isDrawing: false,
    };
    let timerId = 0;
    let runtime;
    let finish;
    const pending = new Promise(resolve => { finish = resolve; });
    const driver = {
        prepareToDraw() {},
        revealWinners(received, controls) { runtime = controls; return reveal ? reveal(received, controls) : pending; },
        stop() { calls.stop++; },
    };
    const context = vm.createContext({
        state, config: { drawUrl: '/draw' }, drawRunId: 0, operatorToken: null, lottoTimerLabel: '',
        console: { warn() {} }, AbortController,
        trackDemoEvent(event, style) { calls.metrics.push({ event, style }); },
        generateBackendRunId: () => 'test-run', startDrawingHeartbeat() {}, clearResultModeTimer() {},
        appendWinner(winner) { state.winners = [...state.winners, winner]; },
        render() { calls.render++; }, getAnimationDriver: () => driver, isLottoStyle: s => s === 'lotto_air',
        sfx: { playError() {}, playButtonClick() {}, playBallRumble: () => ({ stop() {} }), playVictory() {} },
        startDrawAudio() {}, stopDrawAudio() { calls.audioStop++; }, stopDrawingHeartbeat() { calls.heartbeatStop++; },
        operatorHeaders: () => ({}), showAccessCodeModal() {}, scheduleResultModeShow() {},
        createDrawClock: budgetMs => ({ budgetMs }),
        resultMode: { show() { calls.result++; } },
        isPrizeCompleted: () => state.winners.length >= state.currentPrize.winnersCount,
        setTimeout(fn, delay) { const id = ++timerId; timers.set(id, { fn, delay }); return id; },
        clearTimeout(id) { timers.delete(id); },
        async fetch() {
            calls.requests++;
            if (fetchError) throw new Error('network unavailable');
            return { ok: true, async json() { return { winners }; } };
        },
    });
    vm.runInContext(drawSource + '\nthis.draw = draw;', context);
    return { state, calls, context, timers, finish, draw: () => context.draw(), runtime: () => runtime,
        expire() { const timer = [...timers.values()].find(t => t.delay >= 75000); assert.ok(timer); timer.fn(); } };
}

test('animation timeout retains every API winner after only a partial reveal and settles draw()', async () => {
    const h = harness();
    const running = h.draw(); await tick();
    h.runtime().appendWinner(winners[0]);
    assert.equal(h.state.winners.length, 1);
    h.expire(); await running;
    assert.deepEqual(Array.from(h.state.winners, w => w.id), [1, 2, 3]);
    assert.equal(h.state.isDrawing, false);
    assert.equal(h.calls.result, 1);
    assert.equal(h.calls.requests, 1, 'recovery must never draw again');
    assert.deepEqual(h.calls.metrics.map(m => m.event), ['draw_start', 'draw_failed']);
    h.runtime().appendWinner(winners[1]);
    assert.equal(h.state.winners.length, 3, 'late animation cannot duplicate results');
});

test('timeout before first reveal presents API results without a reload', async () => {
    const h = harness();
    const running = h.draw(); await tick(); h.expire(); await running;
    assert.equal(h.state.winners.length, 3);
    assert.equal(h.calls.result, 1);
});

test('an animation exception after response keeps previous one-by-one wins and all returned occurrences', async () => {
    const previous = [{ id: 0, employee_name: '甲' }];
    const h = harness({ mode: 'one_by_one', previous, reveal() { throw new Error('canvas failed'); } });
    await h.draw();
    assert.deepEqual(Array.from(h.state.winners, w => w.id), [0, 1, 2, 3]);
    assert.equal(h.calls.result, 1);
    assert.equal(h.state.isDrawing, false);
});

test('finishing an old draw cannot stop or overwrite a replacement prize draw', async () => {
    const h = harness();
    const running = h.draw(); await tick();
    h.context.drawRunId++;
    h.state.currentPrize = { id: 20, winnersCount: 1 };
    h.state.winners = [{ id: 99, employee_name: '新獎項' }];
    h.state.isDrawing = true;
    h.finish(); await running;
    assert.equal(h.state.isDrawing, true);
    assert.equal(h.state.winners[0].id, 99);
    assert.equal(h.calls.audioStop, 0);
    assert.equal(h.calls.heartbeatStop, 0);
    assert.equal(h.calls.result, 0);
});

test('an old safety callback cannot stop a replacement animation', async () => {
    const h = harness();
    const running = h.draw(); await tick();
    h.context.drawRunId++;
    h.expire(); await running;
    assert.equal(h.calls.stop, 0);
    assert.equal(h.calls.result, 0);
    assert.equal(h.state.winners.length, 0);
});

test('a 60-second marble race can reach its internal two-times-duration recovery before the outer deadline', async () => {
    const h = harness({ style: 'marble_race', hold: 60 });
    const running = h.draw(); await tick();
    assert.ok([...h.timers.values()].some(t => t.delay === 135000));
    h.finish(); await running;
    assert.equal(h.state.winners.length, 3);
});

test('request failure clears its abort timer and unlocks the draw', async () => {
    const h = harness({ fetchError: true });
    await h.draw();
    assert.equal(h.timers.size, 0);
    assert.equal(h.state.isDrawing, false);
    assert.equal(h.calls.result, 0);
});

test('a poll sent before a draw cannot overwrite winners when it returns during the animation', async () => {
    const pollSource = source.slice(source.indexOf('    const pollPayload = async () => {'), source.indexOf('    if (window.Echo && config.brandCode'));
    let deliver;
    let applied;
    const state = { isDrawing: false };
    const response = new Promise(resolve => { deliver = resolve; });
    const context = vm.createContext({
        state, drawRunId: 0, pollUrl: '/play?payload=1', fetch: () => response,
        applyLotteryPayload(payload, options) { applied = options; },
    });
    vm.runInContext(pollSource + '\nthis.poll = pollPayload;', context);
    const pending = context.poll();
    state.isDrawing = true;
    deliver({ ok: true, async json() { return { winners }; } });
    await pending;
    assert.equal(applied.skipWinnersUpdate, true);
});

test('a delayed pre-draw poll is discarded even when it arrives after the draw has finished', async () => {
    const pollSource = source.slice(source.indexOf('    const pollPayload = async () => {'), source.indexOf('    if (window.Echo && config.brandCode'));
    let deliver;
    let applied = false;
    const response = new Promise(resolve => { deliver = resolve; });
    const context = vm.createContext({
        state: { isDrawing: false }, drawRunId: 0, pollUrl: '/play?payload=1', fetch: () => response,
        applyLotteryPayload() { applied = true; },
    });
    vm.runInContext(pollSource + '\nthis.poll = pollPayload;', context);
    const pending = context.poll();
    context.drawRunId++;
    deliver({ ok: true, async json() { return { winners: [] }; } });
    await pending;
    assert.equal(applied, false);
});
