import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/lottery.js', import.meta.url), 'utf8');
const listeners = source.slice(source.indexOf('    if (window.Echo && config.brandCode'), source.indexOf('    // 清除舊的輪詢計時器'));

function client(hub, brandCode, prizeId) {
    const calls = { applied: [], danmaku: [], render: 0 };
    const state = { currentPrize: { id: prizeId }, winners: [], eligibleNames: [brandCode + '參加者'], allPrizes: [], isDrawing: false };
    const context = vm.createContext({
        window: { Echo: { channel(name) {
            const channel = { listen(event, callback) {
                const key = name + ':' + event;
                hub.set(key, [...(hub.get(key) ?? []), callback]);
                return channel;
            } };
            return channel;
        } } },
        config: { brandCode }, state, console: { log() {} }, pageIndex: 0,
        applyLotteryPayload(payload, options) { calls.applied.push({ payload, options }); },
        updateDanmakuContainer() {}, showDanmaku(name, message) { calls.danmaku.push({ name, message }); },
        render() { calls.render++; }, resultMode: { renderPage() {} },
        shouldShowPrizesPreview: () => false, scheduleResultModeShow() {},
    });
    vm.runInContext(listeners, context);
    return { state, calls };
}

test('two company pages independently receive event updates, winners and danmaku', () => {
    const hub = new Map();
    const a = client(hub, 'COMPANYA', 11);
    const b = client(hub, 'COMPANYB', 22);
    const publish = (brand, event, payload) => (hub.get(`lottery.${brand}:${event}`) ?? []).forEach(fn => fn(payload));
    for (const [brand, prize] of [['COMPANYA', 11], ['COMPANYB', 22]]) {
        publish(brand, '.lottery.updated', { event: { brand_code: brand, danmaku_enabled: true } });
        publish(brand, '.winners.updated', { prize_id: prize, winners: [{ id: prize, employee_name: brand + '得主' }], eligible_names: [brand + '剩餘者'] });
        publish(brand, '.danmaku.sent', { employee_name: brand, message: brand + '加油' });
    }
    for (const [page, brand] of [[a, 'COMPANYA'], [b, 'COMPANYB']]) {
        assert.equal(page.calls.applied.length, 1);
        assert.equal(page.calls.applied[0].payload.event.brand_code, brand);
        assert.equal(page.state.winners.length, 1);
        assert.equal(page.state.winners[0].employee_name, brand + '得主');
        assert.equal(page.state.eligibleNames[0], brand + '剩餘者');
        assert.deepEqual(page.calls.danmaku, [{ name: brand, message: brand + '加油' }]);
    }
});

test('one company drawing cannot suppress another company updates, and mismatched prize events are ignored', () => {
    const hub = new Map();
    const a = client(hub, 'COMPANYA', 11);
    const b = client(hub, 'COMPANYB', 22);
    a.state.isDrawing = true;
    for (const handler of hub.get('lottery.COMPANYB:.winners.updated')) {
        handler({ prize_id: 22, winners: [{ id: 1, employee_name: 'B得主' }], eligible_names: ['B剩餘者'] });
    }
    for (const handler of hub.get('lottery.COMPANYA:.winners.updated')) {
        handler({ prize_id: 22, winners: [{ id: 1, employee_name: '錯誤獎項' }], eligible_names: ['不得套用'] });
    }
    assert.equal(b.state.winners[0].employee_name, 'B得主');
    assert.equal(a.state.winners.length, 0);
    assert.equal(a.state.eligibleNames[0], 'COMPANYA參加者');
    assert.equal(a.state.isDrawing, true);
});
