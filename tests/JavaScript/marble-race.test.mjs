import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

// 執行正式動畫閉包，只略過 Canvas 繪圖；物理、完成通知與 resize 都使用原始程式。
const source = readFileSync(new URL('../../resources/js/lottery.js', import.meta.url), 'utf8');
const start = source.indexOf('    const marbleRace = (() => {');
const end = source.indexOf('    // ─── BattleTop', start);
assert.ok(start >= 0 && end > start);
const moduleSource = source.slice(start, end)
    .replaceAll('            drawFrame();', '            /* Canvas rendering omitted in this physics test. */')
    .replace('            ensureReady, start, startDraw, stop,',
        '            _state: mrState, _track: TRACK, _physics: updatePhysics, _loop: loop, ensureReady, start, startDraw, stop,');

function createRace({ width = 390, height = 844, seed = 1, count = 30 } = {}) {
    const math = Object.create(Math);
    math.random = () => {
        seed = (seed * 1664525 + 1013904223) >>> 0;
        return seed / 4294967296;
    };
    const rect = { width, height };
    const context = {
        Math: math,
        performance: { now: () => 0 },
        requestAnimationFrame: () => 1,
        cancelAnimationFrame: () => {},
        window: { devicePixelRatio: 1 },
        lottoCanvasEl: {
            getContext: () => ({ setTransform: () => {} }),
            getBoundingClientRect: () => rect,
        },
        lottoWrapEl: { getBoundingClientRect: () => rect },
        state: {
            eligibleNames: Array.from({ length: count }, (_, i) => `參加者${i + 1}`),
            winners: [],
        },
        clearCanvas: () => {},
        TAU: Math.PI * 2,
        clamp: (value, min, max) => Math.max(min, Math.min(max, value)),
        lerp: (a, b, amount) => a + (b - a) * amount,
        rand: (min, max) => min + math.random() * (max - min),
        shuffle: (names) => names,
    };
    vm.createContext(context);
    vm.runInContext(`${moduleSource}; globalThis.race = marbleRace;`, context);
    const race = context.race;
    race.ensureReady();
    race.setHoldSeconds(5);
    race.setWinnerQueue(['得獎甲', '得獎乙', '得獎丙', '得獎丁', '得獎戊']);
    race.startDraw();
    return { race, rect };
}

function finishBall(race, index) {
    race._state.phase = 'racing';
    race._state.gateOpen = true;
    race._state.marbles[index].y = race._state.finishY + 1;
    race._physics(0);
}

test('每顆衝線球僅消費一次，後續得獎者等待下一顆球', async () => {
    const { race } = createRace();
    finishBall(race, 0);
    await race.waitForNextPick();
    let secondResolved = false;
    const second = race.waitForNextPick().then(() => { secondResolved = true; });
    await Promise.resolve();
    assert.equal(secondResolved, false);
    assert.equal(race._state.rankings.length, 1);
    assert.equal(race._state.winnerQueue.length, 4);
    finishBall(race, 1);
    await second;
    assert.equal(race._state.rankings[1].name, '得獎乙');
    assert.equal(race._state.pendingPicks, 0);
});

test('同一幀先衝線的多顆球保留各自的完成通知', async () => {
    const { race } = createRace();
    race._state.phase = 'racing';
    race._state.gateOpen = true;
    race._state.marbles.slice(0, 3).forEach((ball) => { ball.y = race._state.finishY + 1; });
    race._physics(0);
    assert.equal(race._state.pendingPicks, 3);
    await race.waitForNextPick();
    await race.waitForNextPick();
    await race.waitForNextPick();
    assert.equal(race._state.pendingPicks, 0);
    let fourthResolved = false;
    const fourth = race.waitForNextPick().then(() => { fourthResolved = true; });
    await Promise.resolve();
    assert.equal(fourthResolved, false);
    finishBall(race, 3);
    await fourth;
});

test('安全超時完成剩餘球時，也只產生各一份完成通知', async () => {
    const { race } = createRace({ count: 3 });
    const waiting = race.waitForNextPick();
    race._state.phase = 'racing';
    race._state.gateOpen = true;
    race._state.raceTimer = race._track.raceTimeout + 1;
    race._physics(0);
    await waiting;
    assert.equal(race._state.phase, 'finished');
    assert.equal(race._state.rankings.length, 3);
    assert.equal(race._state.pendingPicks, 2);
    await race.waitForNextPick();
    await race.waitForNextPick();
    assert.equal(race._state.pendingPicks, 0);
    race._physics(0);
    assert.equal(race._state.pendingPicks, 0);
});

for (const [width, height] of [[320, 568], [390, 844], [720, 844], [844, 390], [1280, 720]]) {
    test(`${width}×${height}：通道容得下球，前五名在強制結束前自然衝線`, () => {
        for (const seed of [1, 2, 3]) {
            const { race } = createRace({ width, height, seed });
            const firstRow = race._state.pegs.slice(0, race._state.pegs.findIndex((peg) => peg.y > 200));
            for (let i = 1; i < firstRow.length; i++) {
                assert.ok(firstRow[i].x - firstRow[i - 1].x > 2 * (race._track.marbleRadius + race._track.pegRadius));
            }
            let now = 0;
            while (race._state.rankings.length < 5 && now < 14_000) {
                now += 1000 / 60;
                race._loop(now);
            }
            assert.ok(race._state.rankings.length >= 5, `seed=${seed}`);
            assert.ok(race._state.raceTimer < race._track.raceTimeout, `seed=${seed} 不可依賴強制結束`);
            assert.ok(race._state.rankings.slice(0, 5).every((ball) => ball.y > race._state.finishY));
            assert.equal(race._state.winnerQueue.length, 0);
        }
    });
}

test('橫豎切換保留分散位置，且仍能自然完成得獎球', () => {
    const { race, rect } = createRace({ width: 1280, height: 720 });
    let now = 0;
    while (now < 4500) {
        now += 1000 / 60;
        race._loop(now);
    }
    rect.width = 390;
    rect.height = 844;
    race.resize();
    const unfinished = race._state.marbles.filter((ball) => !ball.finished);
    assert.ok(new Set(unfinished.map((ball) => Math.round(ball.x))).size > 5);
    assert.ok(unfinished.every((ball) => ball.x >= race._state.trackLeft + ball.r && ball.x <= race._state.trackRight - ball.r));
    while (race._state.rankings.length < 5 && now < 14_000) {
        now += 1000 / 60;
        race._loop(now);
    }
    assert.ok(race._state.rankings.length >= 5);
    assert.ok(race._state.raceTimer < race._track.raceTimeout);
});

test('暫時零尺寸不重建賽道；停止會清除舊結果並解除等待', async () => {
    const { race, rect } = createRace();
    const oldPegs = race._state.pegs;
    rect.width = 0;
    rect.height = 0;
    race.resize();
    assert.equal(race._state.pegs, oldPegs);
    const waiting = race.waitForNextPick();
    race.stop();
    await waiting;
    assert.equal(race._state.pendingPicks, 0);
    assert.equal(race._state.winnerQueue.length, 0);
    assert.equal(race._state.marbles.length, 0);
    rect.width = 390;
    rect.height = 844;
    race.ensureReady();
    assert.equal(race._state.running, true);
    assert.equal(race._state.marbles.length, 30);
});
