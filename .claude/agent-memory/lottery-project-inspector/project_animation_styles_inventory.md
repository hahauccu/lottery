---
name: Animation Styles Inventory
description: Complete list of 7 animation styles, their module names in lottery.js, and stop() cleanup status as of 2026-03-29
type: project
---

7 animation styles registered in `getAnimationDriver()`:

| Style key | Module variable | Type | stop() clears all state |
|---|---|---|---|
| lotto_air | lottoAir | continuous (waitForNextPick) | YES -- balls, picked, pending, particles, waiters, trayCarousel, shake |
| red_packet | redPacketRain | reveal-based (waitForReveal) | YES -- packets, coins, selectedPacket, spawnTimer, revealResolve, animateRafId, revealTimeoutId |
| scratch_card | scratchCard | reveal-based (waitForReveal) | YES -- cards, particles, sparkles, revealedCount, revealResolve |
| treasure_chest | treasureChest | reveal-based (waitForReveal) | YES -- chests, coins, sparkles, openedCount, openResolve |
| big_treasure_chest | bigTreasureChest | reveal-based (waitForReveal) | YES -- coins, ingots, gourds, sparkles, redPackets, nameEruption, spawnTimer, revealResolve |
| marble_race | marbleRace | continuous (waitForNextPick) | PARTIAL -- does NOT clear marbles, rankings, pending, winnerQueue, mrParticles |
| battle_top | battleTop | continuous (waitForNextPick) | YES -- tops, waiters, pendingResolves, wavePool, entryQueue |

**Why:** marble_race.stop() only resets running/phase/gateOpen and resolves waiters, but leaves marbles/rankings/mrParticles arrays populated. However, the next ensureReady(forceReset=true) call from applyLotteryPayload will invoke reset() which clears everything. This means leftover state persists until the next prize change.

**How to apply:** When auditing animation stop() completeness, check that arrays holding visual elements (particles, balls, etc.) are cleared, not just logical state.
