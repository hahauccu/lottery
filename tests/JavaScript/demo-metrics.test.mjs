import test from 'node:test';
import assert from 'node:assert/strict';
import { createDemoTracker } from '../../resources/js/demo-metrics.js';

const config = { endpoint: '/demo/metrics', csrf: 'test-csrf', entry: 'intro', style: 'battle_top' };

test('tracker sends only event, style, entry and bounded duration', () => {
    const sent = [];
    const track = createDemoTracker(config, { send: (url, request) => sent.push({ url, request }) });
    track('play_click');
    track('draw_complete', 'marble_race', 1000000);
    assert.deepEqual(JSON.parse(sent[0].request.body), { event: 'play_click', animation_style: 'battle_top', entry: 'intro' });
    assert.equal(JSON.parse(sent[1].request.body).duration_ms, 600000);
    assert.equal(sent[0].request.keepalive, true);
    assert.equal(sent[0].request.headers['X-CSRF-TOKEN'], 'test-csrf');
});

test('opt-out and unknown events never issue a request', () => {
    const sent = [];
    const send = (...args) => sent.push(args);
    createDemoTracker(config, { send, optedOut: true })('page_view');
    createDemoTracker(config, { send })('private_message');
    assert.equal(sent.length, 0);
});

test('tracking failures cannot throw into navigation or draw flow', async () => {
    assert.doesNotThrow(() => createDemoTracker(config, { send() { throw new Error('offline'); } })('page_view'));
    assert.doesNotThrow(() => createDemoTracker(config, { send: () => Promise.reject(new Error('offline')) })('play_click'));
    await Promise.resolve();
});
