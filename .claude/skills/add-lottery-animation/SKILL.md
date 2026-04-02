---
name: add-lottery-animation
description: 新增抽獎動畫風格的標準化流程。當用戶要求新增一種抽獎動畫時使用此 skill。
---

# 新增抽獎動畫風格

本 skill 涵蓋新增一種抽獎動畫風格所需的全部前後端修改。

---

## A. 動畫系統架構概覽

### 前端架構（`resources/js/lottery.js`）

- 每個動畫是一個 **IIFE 閉包**，回傳公開 API 物件
- 所有動畫透過 `animationDrivers` 物件統一介面
- Driver 介面：`{ prepareIdle, prepareToDraw, revealWinners, stop }`

### 生命週期流程

```
render() → prepareIdle()    # 待機畫面
draw()  → prepareToDraw()   # 準備抽獎
        → revealWinners()   # 揭曉中獎者
        → stop()            # 停止/清理
```

### 兩種動畫模式

| 模式 | 說明 | 範例 |
|------|------|------|
| **單一物件模式** | 一次顯示一個動畫物件，逐一揭曉 | `big_treasure_chest`、`lotto_air`、`red_packet` |
| **多物件模式** | 一次顯示多個物件，批次揭曉 | `scratch_card`、`treasure_chest` |

---

## B. 前端修改清單（`resources/js/lottery.js`）

> 以下行號為參考值，實際可能因程式碼更動而偏移。修改前請用搜尋確認。

### Checklist

- [ ] **B1. 風格判斷函數**（搜尋 `isCanvasStyle`，約行 1196–1206）
  - 新增 `const isXxxStyle = (style) => style === 'xxx';`
  - 在 `isCanvasStyle` 中加入 `|| isXxxStyle(style)`

- [ ] **B2. 動畫模組 IIFE**（加在 `bigTreasureChest` IIFE 之前，約行 4102 前）
  - 建立完整的動畫模組（見下方模板）

- [ ] **B3. animationDrivers 新增 key**（搜尋 `animationDrivers`，約行 4922，在 `fallback` 之前加入）
  - 加入 driver 物件（見下方模板）

- [ ] **B4. getAnimationDriver() 新增分支**（搜尋 `getAnimationDriver`，約行 5220）
  - 加入 `if (isXxxStyle(style)) return animationDrivers.xxx;`

- [ ] **B5. stopAllAnimations() 加入 stop**（搜尋 `stopAllAnimations`，約行 4897）
  - 加入 `xxx.stop();`

- [ ] **B6. resize 事件處理**（搜尋 `window.addEventListener('resize'`，約行 5415）
  - 加入：
    ```javascript
    if (isXxxStyle(state.currentPrize?.animationStyle)) {
        xxx.resize();
    }
    ```

---

## C. 後端修改清單

### Checklist

- [ ] **C1. PrizesRelationManager.php — 表單 Select options**
  - 檔案：`app/Filament/Resources/LotteryEventResource/RelationManagers/PrizesRelationManager.php`
  - 搜尋 `animation_style` 的 `->options([`（約行 163）
  - 加入 `'xxx' => '中文名稱'`

- [ ] **C2. PrizesRelationManager.php — 列表 TextColumn match**
  - 同檔案，搜尋 `formatStateUsing(fn (string $state) => match`（約行 398）
  - 加入 `'xxx' => '中文名稱'`

- [ ] **C3. PrizesRelationManager.php — validStyles 陣列**
  - 同檔案，搜尋 `$validStyles`（約行 513）
  - 加入 `'xxx'`

- [ ] **C4. PrizesRelationManager.php — 狀態列 match**
  - 同檔案，搜尋 `'animation_style' => match`（約行 79）
  - 加入 `'xxx' => '中文名稱'`

- [ ] **C5. PrizeResource.php — 表單 Select options**
  - 檔案：`app/Filament/Resources/PrizeResource.php`
  - 搜尋 `animation_style` 的 `->options([`（約行 90）
  - 加入 `'xxx' => '中文名稱'`

---

## D. 動畫模組 IIFE 模板

### 單一物件模式（參考 `bigTreasureChest`）

```javascript
// ============================================================
//  xxx 動畫
// ============================================================
const xxx = (() => {
    let running = false;
    let rafId = null;
    let canvas, ctx;
    let revealResolve = null;
    let holdSeconds = 5;
    // ... 其他內部狀態

    // ── helpers ──────────────────────────────────────────────
    const resizeCanvas = () => {
        const container = document.getElementById('lottery-canvas-container');
        if (!container || !canvas) return false;
        const w = container.clientWidth, h = container.clientHeight;
        if (canvas.width === w && canvas.height === h) return false;
        canvas.width = w; canvas.height = h;
        return true;
    };

    const drawFrame = () => {
        if (!running) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        // ... 繪製邏輯
        rafId = requestAnimationFrame(drawFrame);
    };

    // ── public API ──────────────────────────────────────────
    const showXxx = () => {
        // 初始化並開始動畫迴圈
        running = true;
        drawFrame();
    };

    const stop = () => {
        running = false;
        if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
        if (revealResolve) { revealResolve(); revealResolve = null; }
        // 清除所有內部狀態！
    };

    const setWinner = (name) => {
        // 設定要揭曉的中獎者名稱
    };

    const waitForReveal = () => new Promise((resolve) => {
        revealResolve = resolve;
        // 觸發揭曉動畫，完成時呼叫 revealResolve()
    });

    const prepareNext = () => {
        // 重置為下一次揭曉的待機狀態
    };

    const setHoldSeconds = (s) => { holdSeconds = Math.max(3, s); };

    const ensureReady = (prizeChanged = false) => {
        canvas = document.getElementById('lottery-canvas');
        if (!canvas) return;
        ctx = canvas.getContext('2d');
        resizeCanvas();
        if (prizeChanged) {
            stop();
            // 重新初始化
        }
    };

    return {
        showXxx,
        start: showXxx,
        stop,
        setWinner,
        waitForReveal,
        prepareNext,
        setHoldSeconds,
        ensureReady,
        resize: () => {
            if (!running) return;
            if (resizeCanvas()) { drawFrame(); }
        },
    };
})();
```

### 多物件模式（參考 `treasureChest` / `scratchCard`）

額外需要的公開 API：

```javascript
return {
    // 多物件 API
    showCards,              // showCards(count, forceReset) — 顯示 N 個物件
    setWinners,             // setWinners(names[]) — 設定多位中獎者
    startScratching,        // 開始批次動畫
    scratchSingleCard,      // scratchSingleCard(index) → Promise — 揭曉第 i 個
    waitForAllRevealed,     // Promise — 等待全部揭曉
    // 單物件相容 API（one_by_one 模式用）
    start,
    stop,
    setWinner,
    waitForReveal,
    prepareNext,
    setHoldSeconds,
    ensureReady,
    resize: () => { ... },
};
```

---

## E. Driver 模板

### 單一物件 Driver（參考 `big_treasure_chest`）

```javascript
xxx: {
    prepareIdle: ({ forceReset = false } = {}) => {
        xxx.ensureReady();
        if (!state.isDrawing) {
            xxx.showXxx();
        }
    },
    prepareToDraw: () => {
        xxx.ensureReady();
        xxx.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
    },
    revealWinners: async (winners, runtime = {}) => {
        const append = runtime.appendWinner ?? appendWinner;
        const isRunStale = runtime.isRunStale ?? (() => false);
        const clock = runtime.clock;
        if (isRunStale()) return;

        if (state.currentPrize?.drawMode === 'one_by_one') {
            // 單抽模式
            xxx.setWinner(winners[0]?.employee_name ?? '???');
            await xxx.waitForReveal();
            if (isRunStale()) return;
            append(winners[0]);
            if (clock) await clock.waitUntilEnd();
            xxx.prepareNext();
        } else {
            // 全抽模式 — 逐一揭曉
            for (let i = 0; i < winners.length; i++) {
                const winner = winners[i];
                xxx.setWinner(winner.employee_name ?? '???');
                await xxx.waitForReveal();
                if (isRunStale()) return;
                append(winner);
                if (i < winners.length - 1) {
                    const remaining = winners.length - 1 - i;
                    const gap = clock
                        ? Math.max(200, clock.remainingMs() * 0.3 / remaining)
                        : 480;
                    await delay(gap * 0.55);
                    if (isRunStale()) return;
                    xxx.prepareNext();
                    await delay(gap * 0.45);
                    if (isRunStale()) return;
                }
            }
            xxx.prepareNext();
        }
    },
    stop: () => xxx.stop(),
},
```

### 多物件 Driver（參考 `treasure_chest`）

```javascript
xxx: {
    prepareIdle: ({ forceReset = false } = {}) => {
        xxx.ensureReady();
        if (!state.isDrawing) {
            const count = state.currentPrize?.drawMode === 'one_by_one'
                ? Math.min(1, getIdleSlotCount(1))
                : Math.min(getIdleSlotCount(9), 9);
            if (count > 0) {
                xxx.showCards(count, forceReset);
            }
        }
    },
    prepareToDraw: () => {
        xxx.ensureReady();
        xxx.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
    },
    revealWinners: async (winners, runtime = {}) => {
        const append = runtime.appendWinner ?? appendWinner;
        const isRunStale = runtime.isRunStale ?? (() => false);
        const clock = runtime.clock;
        if (isRunStale()) return;

        if (state.currentPrize?.drawMode === 'one_by_one') {
            // 單抽模式
            xxx.setWinner(winners[0]?.employee_name ?? '???');
            await xxx.waitForReveal();
            if (isRunStale()) return;
            append(winners[0]);
            if (clock) await clock.waitUntilEnd();
            else await delay(1500);
            if (isRunStale()) return;
            xxx.prepareNext();
        } else {
            // 全抽模式 — 以 9 個為一批
            let processedCount = 0;
            while (processedCount < winners.length) {
                if (isRunStale()) return;
                const batchStart = processedCount;
                const batchCount = Math.min(winners.length - processedCount, 9);
                const batchWinners = winners.slice(batchStart, batchStart + batchCount);

                xxx.showCards(batchCount, true);
                xxx.setWinners(batchWinners.map((w) => w.employee_name ?? '???'));

                const revealOrder = shuffle(Array.from({ length: batchCount }, (_, i) => i));
                for (let i = 0; i < batchCount; i++) {
                    const idx = revealOrder[i];
                    await xxx.scratchSingleCard(idx);
                    if (isRunStale()) return;
                    append(batchWinners[idx]);
                    if (i < batchCount - 1) {
                        const remainingInBatch = batchCount - 1 - i;
                        const gap = clock
                            ? Math.max(150, clock.remainingMs() * 0.3 / (remainingInBatch + (winners.length - processedCount - batchCount)))
                            : 1000;
                        await delay(gap);
                        if (isRunStale()) return;
                    }
                }

                processedCount += batchCount;
                if (processedCount < winners.length) {
                    const batchGap = clock ? Math.max(200, clock.remainingMs() * 0.15) : 1500;
                    await delay(batchGap);
                    if (isRunStale()) return;
                }
            }

            if (clock) {
                const finalWait = Math.min(1500, clock.remainingMs());
                if (finalWait > 50) await delay(finalWait);
            } else {
                await delay(1500);
            }
            if (isRunStale()) return;
            xxx.prepareNext();
        }
    },
    stop: () => xxx.stop(),
},
```

---

## F. 關鍵規則

### 必須遵守

1. **展開運算符更新 winners**
   ```javascript
   // ✅ 正確
   state.winners = [...state.winners, winner];
   // ❌ 錯誤
   state.winners.push(winner);
   ```

2. **卡片數量計算**
   ```javascript
   // ✅ 使用 eligibleNames 長度
   state.eligibleNames?.length ?? 0
   // ❌ 不要用 remaining
   ```

3. **stop() 必須清除所有內部狀態**
   - `rafId`（cancelAnimationFrame）
   - `running = false`
   - `revealResolve`（呼叫後設為 null）
   - 所有粒子、物件、pending 陣列等
   - **`tops = []`（或等同的主物件陣列）** — 不清空會導致 `ensureReady(false)` 因陣列非空跳過 reset，idle 畫面顯示殘留舊物件
   - **`pendingResolves = 0`** — 不清空會導致下次 waitForNextPick 提前 resolve
   - 不能只停動畫迴圈

4. **pickBall 類函數的 fallback**
   ```javascript
   let target = candidates.find((ball) => ball.name === winnerName);
   if (!target) {
       target = candidates[0];
       console.warn(`[lottery] no ball named "${winnerName}", using fallback`);
       target.name = winnerName;  // 強制更新名字
       target.label = shortLabel(winnerName);
   }
   ```

5. **applyLotteryPayload() 中切換同風格獎項需強制 reset**
   - 參考 `lottoAir.ensureReady(prizeChanged)` 模式
   - `ensureReady` 接收 `prizeChanged` 參數，為 true 時強制重置

6. **`buildNamePool` 必須在各 IIFE 內部自行定義**
   - `buildNamePool` 是各動畫模組的私有 helper，不存在全域作用域
   - 若呼叫外部不存在的 `buildNamePool` 會拋出 `ReferenceError`
   - 每個使用名稱池的動畫模組（如 battleTop）需在 IIFE 內宣告自己的 `buildNamePool`
   - 標準實作：
     ```javascript
     const buildNamePool = () => {
         const eligible = state.eligibleNames?.length ? [...state.eligibleNames] : [];
         const won = state.winners.map((w) => w.employee_name).filter(Boolean);
         const base = eligible.concat(won).filter(Boolean);
         if (base.length === 0) {
             return Array.from({ length: 10 }, (_, i) => `物件 ${i + 1}`);
         }
         return shuffle(base);
     };
     ```

7. **同時揭曉型動畫的 `pendingResolves` 機制**（`all_at_once` 多人同時揭曉）
   - **問題根因**：driver 使用序列 `for...await` 循環，同一時間 waiters 陣列只有 1 個 Promise；但 `assignWinnersFromQueue` 一次處理所有 winners，第 2、3 個 resolve 事件發生時 waiter 還沒加入
   - **解法**：加入 `pendingResolves` 計數器
     ```javascript
     // 狀態物件加入欄位
     pendingResolves: 0,

     // assignWinnersFromQueue 中：
     if (btState.waiters.length > 0) {
         btState.waiters.shift()();
     } else {
         btState.pendingResolves++;
     }

     // waitForNextPick 中：
     const waitForNextPick = () => {
         if (btState.pendingResolves > 0) {
             btState.pendingResolves--;
             return Promise.resolve();
         }
         return new Promise((resolve) => {
             btState.waiters.push(resolve);
         });
     };
     ```
   - `reset()` 和 `stop()` 都需要 `btState.pendingResolves = 0`

8. **WebSocket 競爭條件防護**
   - 抽獎進行中（`state.isDrawing`），WebSocket `.lottery.updated` 事件會被忽略
   - `.winners.updated` 事件只更新 `eligibleNames` 和 `allPrizes`，不更新 `winners`
   - `draw()` 函數自己負責 `winners` 更新

---

## G. 驗證方式

完成所有修改後，依以下項目驗證：

- [ ] 後台：表單 Select 出現新選項
- [ ] 後台：將獎項切換到新動畫風格並儲存成功
- [ ] 前台：待機狀態正確顯示動畫
- [ ] 前台：`one_by_one` 模式正常抽獎，中獎者逐一揭曉
- [ ] 前台：`all_at_once` 模式正常抽獎，中獎者批次揭曉
- [ ] 前台：從其他動畫切換到新動畫後 `stop()` 清理正確
- [ ] 前台：從新動畫切換到其他動畫後無殘留（無記憶體洩漏）
- [ ] 前台：瀏覽器 resize 後動畫正確重繪

---

## H. 修改位置快速索引

| 步驟 | 檔案 | 搜尋關鍵字 |
|------|------|------------|
| B1 | `resources/js/lottery.js` | `isCanvasStyle` |
| B2 | `resources/js/lottery.js` | `bigTreasureChest` IIFE 前方 |
| B3 | `resources/js/lottery.js` | `animationDrivers` → `fallback` 前 |
| B4 | `resources/js/lottery.js` | `getAnimationDriver` |
| B5 | `resources/js/lottery.js` | `stopAllAnimations` |
| B6 | `resources/js/lottery.js` | `window.addEventListener('resize'` |
| C1 | `PrizesRelationManager.php` | `animation_style` → `->options` |
| C2 | `PrizesRelationManager.php` | `formatStateUsing(fn (string $state) => match` |
| C3 | `PrizesRelationManager.php` | `$validStyles` |
| C4 | `PrizesRelationManager.php` | `'animation_style' => match` |
| C5 | `PrizeResource.php` | `animation_style` → `->options` |
