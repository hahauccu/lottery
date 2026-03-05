---
name: add-lottery-animation
description: 新增抽獎動畫風格的完整技能。依照此文件的步驟，可將一個新的抽獎動畫（如紅包雨、樂透氣流機等）完整整合進系統的前端 Canvas 動畫、Driver 路由、後端選項、驗證清單。
---

# 新增抽獎動畫 (Add Lottery Animation)

> 本技能描述從零開始建立一個新的抽獎動畫風格，並完整整合進現有系統的所有步驟。

---

## 前置知識

### 系統架構概覽

```
┌──────────────────── 後端 (Laravel/Filament) ────────────────────┐
│  PrizesRelationManager.php  ← 動畫選項 + validStyles 白名單    │
│  LotteryEventUpdated.php    ← WebSocket 廣播 animation_style   │
│  Prize.php (Model)          ← animation_style 欄位             │
└─────────────────────────────────────────────────────────────────┘
                              ↕ WebSocket
┌──────────────────── 前端 (lottery.js) ──────────────────────────┐
│                                                                 │
│  isXxxStyle()       ← 風格判斷函數                              │
│  isCanvasStyle()    ← 是否使用 Canvas (控制 lottoWrapEl 顯隱)   │
│  animationDrivers   ← 統一 Driver 介面物件                      │
│  getAnimationDriver ← style → driver 路由                       │
│  stopAllAnimations  ← 集中停止所有動畫                           │
│  render()           ← 待機時呼叫 driver.prepareIdle()           │
│  draw()             ← 抽獎流程，透過 driver 統一調度             │
│  applyLotteryPayload← 獎項切換時 stopAllAnimations()            │
│  window resize      ← 呼叫各動畫的 resize()                    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Driver 統一介面

每個動畫都必須透過 `animationDrivers` 物件註冊一個 driver，driver 有 4 個方法：

| 方法 | 呼叫時機 | 說明 |
|------|----------|------|
| `prepareIdle({ forceReset })` | `render()` 中，非抽獎/非結果/非預覽/非切換時 | 初始化待機畫面（Canvas 就緒、顯示元素） |
| `prepareToDraw()` | `draw()` 開始，API 呼叫前 | 設定動畫參數、啟動動畫迴圈 |
| `revealWinners(winners, runtime)` | `draw()` 收到中獎者後 | 逐一或批次揭曉中獎者 |
| `stop()` | 錯誤、切換、中止時 | 立即停止動畫，resolve 所有 Promise |

`runtime` 物件包含：
- `appendWinner(winner)` — 將中獎者加入 `state.winners` 並更新 UI
- `isRunStale()` — 檢查此次抽獎是否已過時（被新一輪覆蓋）
- `clock` — 計時器（`clock.waitUntilEnd()`, `clock.remainingMs()`）

### 動畫模組分類

| 類型 | 範例 | 特點 |
|------|------|------|
| **連續型** | lottoAir, marbleRace | 系統自動抽球，使用 `waitForNextPick()` |
| **互動型** | redPacketRain, scratchCard, treasureChest | 使用者點擊揭曉，使用 `waitForReveal()` |
| **單元素型** | bigTreasureChest | 畫面只有一個主元素 |
| **批次型** | scratchCard, treasureChest | 支援每批最多 9 個元素 |

### ⚠️ 連續型動畫的得獎者名稱匹配（winnerQueue）

連續型動畫（如彈珠賽跑）中，動畫元素（彈珠）的完成順序由物理引擎決定，**無法預測**。但 API 回傳的 `winners` 陣列順序是固定的。如果直接把 winner 名稱分配給彈珠，衝線的彈珠名字和實際得獎者會**不一致**。

**正確做法**：使用 `winnerQueue` 機制

```javascript
const setWinnerQueue = (names) => {
    internalState.winnerQueue = [...names];
};

// 在衝線/完成的物理判定中：
if (element.reachedFinish && !element.finished) {
    element.finished = true;
    // 從 queue 取出真正的得獎者名字
    if (internalState.winnerQueue.length > 0) {
        const realName = internalState.winnerQueue.shift();
        element.name = realName;
        element.label = shortLabel(realName);
    }
    // resolve waitForNextPick ...
}
```

Driver 端的呼叫順序：
```javascript
module.setWinnerQueue(winners.map(w => w.employee_name));
module.startDraw();  // startDraw 內的 reset 須保留 winnerQueue
for (const winner of winners) {
    await module.waitForNextPick();
    append(winner);  // 此時動畫裡顯示的名字 = winner 的名字
}
```

> [!CAUTION]
> `startDraw()` 通常會呼叫 `reset()` 清空狀態。**必須在 reset 前保存 winnerQueue 並在之後恢復**，否則 queue 會被清空。

### holdSeconds 的兩種用法

`lotto_hold_seconds`（後台欄位）控制每次抽獎的動畫時間。根據動畫類型有不同用法：

| 類型 | holdSeconds 的作用 | 範例 |
|------|------|------|
| 互動型 | 控制動畫速度（開啟蓋子速度、刮開速度等） | `scratchCard`: 刮開速度 = path.length / (hs * 0.65) |
| 連續型 | 控制物理參數讓比賽自然拉長 | `marbleRace`: gravity = 9300 / hs |

> [!WARNING]
> 連續型動畫**不應**用 `holdSeconds` 作為硬性超時來強制結束動畫。應透過調整物理參數（重力、速度、摩擦力等）讓比賽**自然**在設定時間內完成。`raceTimeout` 只是安全超時（建議設為 holdSeconds × 2）。

### 名稱池與重複中獎

`state.eligibleNames` 是後端傳來的可抽人員名單。在建立動畫元素的名稱池時：

- **不可使用 `new Set()` 去重** — 當「同一獎項可重複中獎」開啟時，同一人可能出現多次
- 應直接使用 `[...state.eligibleNames]` 保留重複

### 結束後延遲

動畫結束（所有得獎者揭曉）後，應加入適當延遲（建議 3-5 秒），讓使用者有時間欣賞結果，再跳轉到得獎名單畫面：

```javascript
await delay(5000); // 結束後多等 5 秒
if (isRunStale()) return;
```

---

## 步驟清單

以下用 `{{STYLE_KEY}}` 代表你的動畫風格鍵值（如 `marble_race`），  
`{{STYLE_LABEL}}` 代表中文名稱（如 `彈珠競賽`），  
`{{MODULE_NAME}}` 代表 JS 模組變數名（如 `marbleRace`）。

### 步驟 1：建立動畫模組 IIFE

**檔案**：`resources/js/lottery.js`  
**位置**：在 `stopAllAnimations` 函數之前（約 line 4897 前），所有動畫模組定義的區域。

建立模組 IIFE，回傳公開 API：

```javascript
const {{MODULE_NAME}} = (() => {
    // ─── 內部狀態 ───
    let running = false;
    let holdSeconds = 5;
    let revealResolve = null;  // waitForReveal 的 resolve
    let ctx = null;
    let rafId = null;
    let dpr = 1;

    // ─── Canvas 初始化 ───
    const resizeCanvas = () => {
        if (!lottoCanvasEl) return false;
        const rect = (lottoWrapEl ?? lottoCanvasEl).getBoundingClientRect();
        if (!rect.width || !rect.height) return false;
        dpr = Math.max(1, Math.min(2, window.devicePixelRatio || 1));
        lottoCanvasEl.width = Math.floor(rect.width * dpr);
        lottoCanvasEl.height = Math.floor(rect.height * dpr);
        ctx = lottoCanvasEl.getContext('2d');
        if (ctx) ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        // TODO: 計算佈局尺寸
        return true;
    };

    // ─── 公開 API ───
    const ensureReady = () => {
        resizeCanvas();
        // TODO: 初始化動畫元素
    };

    const start = () => {
        if (running) return;
        running = true;
        // TODO: 啟動 requestAnimationFrame 動畫迴圈
    };

    const stop = () => {
        running = false;
        if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
        // ⚠️ 必須 resolve 所有等待中的 Promise
        if (revealResolve) { revealResolve(); revealResolve = null; }
        // TODO: 清理動畫狀態
    };

    const setWinner = (name) => {
        // one_by_one 模式：設定單一中獎者名稱
        // TODO: 將名稱綁定到動畫元素
    };

    const setWinners = (names) => {
        // all_at_once 模式（批次型）：設定多位中獎者
        // TODO: 將名稱分配給各元素
    };

    const waitForReveal = () => {
        return new Promise((resolve) => {
            revealResolve = resolve;
            // TODO: 設定使用者互動事件（click 等）
            //       互動完成後呼叫 revealResolve()
        });
    };

    const prepareNext = () => {
        // 重置為下一輪準備狀態
        revealResolve = null;
        // TODO: 重置動畫元素
    };

    const setHoldSeconds = (s) => { holdSeconds = s; };

    const resize = () => {
        resizeCanvas();
        // TODO: 重新計算佈局
    };

    // 如果是批次型動畫，加入 showElements：
    // const showElements = (count, forceReset = false) => { ... };

    return {
        ensureReady, start, stop,
        setWinner, setWinners,
        waitForReveal, prepareNext,
        setHoldSeconds, resize,
        // showElements,  // 批次型才需要
    };
})();
```

> [!WARNING]
> `stop()` 中**必須** resolve 所有等待中的 Promise（`revealResolve` 等），  
> 否則 `draw()` 會永遠 hang 住。

---

### 步驟 2：新增風格判斷函數

**檔案**：`resources/js/lottery.js`  
**位置**：約 line 1196-1206，現有的 `isXxxStyle` 函數群。

```javascript
// 新增
const is{{PascalCase}}Style = (style) => style === '{{STYLE_KEY}}';
```

---

### 步驟 3：更新 `isCanvasStyle()`

**檔案**：`resources/js/lottery.js`  
**位置**：約 line 1201-1205，`isCanvasStyle` 函數定義。

在最後加入你的判斷：

```javascript
const isCanvasStyle = (style) => isLottoAirStyle(style)
    || isRedPacketStyle(style)
    || isScratchCardStyle(style)
    || isTreasureChestStyle(style)
    || isBigTreasureChestStyle(style)
    || is{{PascalCase}}Style(style);        // ← 新增
```

> [!IMPORTANT]
> 如果你的動畫使用 Canvas，**必須**加入 `isCanvasStyle()`，  
> 否則 `lottoWrapEl` 的顯隱邏輯會出錯，Canvas 不會顯示。

---

### 步驟 4：註冊 animationDriver

**檔案**：`resources/js/lottery.js`  
**位置**：約 line 4922-5217，`animationDrivers` 物件內。

在 `fallback` 之前新增你的 driver：

```javascript
{{STYLE_KEY}}: {
    prepareIdle: ({ forceReset = false } = {}) => {
        {{MODULE_NAME}}.ensureReady();
        if (!state.isDrawing) {
            // 互動型：計算並顯示元素數量
            const count = state.currentPrize?.drawMode === 'one_by_one'
                ? Math.min(1, getIdleSlotCount(1))
                : Math.min(getIdleSlotCount(9), 9);
            if (count > 0) {
                {{MODULE_NAME}}.showElements(count, forceReset);
            }
            // 連續型（如 lottoAir）：直接 ensureReady 即可

            // 單元素型：直接 showElement()
        }
    },
    prepareToDraw: () => {
        {{MODULE_NAME}}.ensureReady();
        {{MODULE_NAME}}.setHoldSeconds(state.currentPrize?.lottoHoldSeconds ?? 5);
        {{MODULE_NAME}}.start();  // 若需要在 API 呼叫前就開始動畫
    },
    revealWinners: async (winners, runtime = {}) => {
        const append = runtime.appendWinner ?? appendWinner;
        const isRunStale = runtime.isRunStale ?? (() => false);
        const clock = runtime.clock;
        if (isRunStale()) return;

        if (state.currentPrize?.drawMode === 'one_by_one') {
            // ─── one_by_one 模式 ───
            {{MODULE_NAME}}.setWinner(winners[0]?.employee_name ?? '???');
            await {{MODULE_NAME}}.waitForReveal();
            if (isRunStale()) return;
            append(winners[0]);
            if (clock) await clock.waitUntilEnd();
            else await delay(1500);
            if (isRunStale()) return;
            {{MODULE_NAME}}.prepareNext();
        } else {
            // ─── all_at_once 模式 ───
            // 方式 A: 逐一揭曉（參考 redPacketRain）
            for (let i = 0; i < winners.length; i++) {
                const winner = winners[i];
                {{MODULE_NAME}}.setWinner(winner.employee_name ?? '???');
                await {{MODULE_NAME}}.waitForReveal();
                if (isRunStale()) return;
                append(winner);
                if (i < winners.length - 1) {
                    const remaining = winners.length - 1 - i;
                    const gap = clock
                        ? Math.max(200, clock.remainingMs() * 0.4 / remaining)
                        : 800;
                    await delay(gap * 0.55);
                    if (isRunStale()) return;
                    {{MODULE_NAME}}.prepareNext();
                    await delay(gap * 0.45);
                    if (isRunStale()) return;
                }
            }

            // 方式 B: 批次揭曉（參考 scratchCard, 每批 ≤ 9）
            // let processedCount = 0;
            // while (processedCount < winners.length) { ... }

            if (clock) {
                const finalWait = Math.min(1500, clock.remainingMs());
                if (finalWait > 50) await delay(finalWait);
            } else {
                await delay(1500);
            }
            if (isRunStale()) return;
            {{MODULE_NAME}}.stop();
        }
    },
    stop: () => {{MODULE_NAME}}.stop(),
},
```

---

### 步驟 5：更新 `getAnimationDriver()`

**檔案**：`resources/js/lottery.js`  
**位置**：約 line 5220-5227，`getAnimationDriver` 函數。

在 `return animationDrivers.fallback` 之前新增：

```javascript
if (is{{PascalCase}}Style(style)) return animationDrivers.{{STYLE_KEY}};
```

---

### 步驟 6：更新 `stopAllAnimations()`

**檔案**：`resources/js/lottery.js`  
**位置**：約 line 4897-4903。

新增你的動畫停止呼叫：

```javascript
const stopAllAnimations = () => {
    lottoAir.stop();
    redPacketRain.stop();
    scratchCard.stop();
    treasureChest.stop();
    bigTreasureChest.stop();
    {{MODULE_NAME}}.stop();           // ← 新增
};
```

---

### 步驟 7：更新 `window resize` 處理

**檔案**：`resources/js/lottery.js`  
**位置**：約 line 5415-5434，`window.addEventListener('resize', ...)` 內。

新增：

```javascript
if (is{{PascalCase}}Style(state.currentPrize?.animationStyle)) {
    {{MODULE_NAME}}.resize();
}
```

---

### 步驟 8：後端 — 新增動畫選項

需修改 **3 個位置**：

#### 8.1 後台表單選項

**檔案**：`app/Filament/Resources/LotteryEventResource/RelationManagers/PrizesRelationManager.php`  
**位置**：約 line 163-174，`Select::make('animation_style')` 的 `options` 陣列。

```php
->options([
    'lotto_air' => '樂透氣流機',
    'red_packet' => '紅包雨',
    'scratch_card' => '刮刮樂',
    'treasure_chest' => '寶箱開啟',
    'big_treasure_chest' => '大寶箱',
    '{{STYLE_KEY}}' => '{{STYLE_LABEL}}',   // ← 新增
])
```

#### 8.2 validStyles 白名單

**同檔案**，約 line 513，`$validStyles` 陣列：

```php
$validStyles = ['lotto_air', 'red_packet', 'scratch_card', 'treasure_chest', 'big_treasure_chest', '{{STYLE_KEY}}'];
```

#### 8.3 中文名稱對照（3 處 match/switch 語句）

同檔案中有 3 處 `match` 語句將 `animation_style` 轉為中文，全部加入：

```php
'{{STYLE_KEY}}' => '{{STYLE_LABEL}}',
```

搜尋關鍵字：`match ($currentPrize->animation_style)` 和 `match ($state)`。

---

### 步驟 9：Demo 頁面整合

需修改 **2 個檔案**，讓 `/demo/lottery` 範例頁面也能切換到新動畫：

#### 9.1 DemoLotteryController

**檔案**：`app/Http/Controllers/DemoLotteryController.php`

在 `VALID_STYLES` 常數中新增：

```php
private const VALID_STYLES = [
    'lotto_air', 'red_packet', 'scratch_card',
    'treasure_chest', 'big_treasure_chest',
    '{{STYLE_KEY}}',   // ← 新增
];
```

#### 9.2 Demo Toolbar 模板

**檔案**：`resources/views/lottery/partials/demo-toolbar.blade.php`

在 `styles` 陣列中新增：

```javascript
styles: [
    // ...existing styles...
    { key: '{{STYLE_KEY}}', label: '{{STYLE_LABEL}}' },  // ← 新增
],
```

---

## 關鍵陷阱提醒

| # | 陷阱 | 說明 |
|---|------|------|
| 1 | **Promise 必須 resolve** | `stop()` 必須 resolve `revealResolve` 等，否則 `draw()` 永遠 hang |
| 2 | **isCanvasStyle 必須更新** | 否則 Canvas wrapper 不顯示，動畫看不到 |
| 3 | **stopAllAnimations 必須更新** | 否則切換獎項時舊動畫不會停止 |
| 4 | **validStyles 必須更新** | 否則後台「切換本輪抽獎」按鈕會被擋 |
| 5 | **isRunStale() 檢查** | 每個 await 後都必須檢查，避免跨輪混亂 |
| 6 | **state.winners 用展開運算符** | `state.winners = [...state.winners, w]`，不可用 `.push()` |
| 7 | **innerHTML 用 escapeHtml()** | 所有名稱插入 HTML 前必須轉義 |
| 8 | **eligibleNames ?? 0** | 計算元素數量時必須用 `state.eligibleNames?.length ?? 0` |
| 9 | **one_by_one + actualCanDraw** | 元素數量不能固定為 1，必須是 `Math.min(1, actualCanDraw)` |
| 10 | **winnerQueue 順序** | 連續型動畫必須用 winnerQueue 匹配名稱，不可直接分配名字到元素 |
| 11 | **名稱池不去重** | `buildNamePool` 不可用 `new Set()`，需支援重複中獎 |
| 12 | **holdSeconds 控制節奏** | 連續型用物理參數拉長比賽，不可用硬超時強制結束 |
| 13 | **Demo 頁面** | 新動畫必須也加入 `DemoLotteryController.VALID_STYLES` 和 `demo-toolbar` |
| 14 | **結束延遲** | 動畫結束後應 `await delay(5000)` 讓使用者欣賞，再跳名單 |

---

## 完成檢查清單

修改完成後，逐項確認：

**前端 (lottery.js)**
- [ ] 動畫模組 IIFE 已建立，實作所有必要 API
- [ ] `is{{PascalCase}}Style()` 判斷函數已新增
- [ ] `isCanvasStyle()` 已加入新風格
- [ ] `animationDrivers` 已註冊新 driver
- [ ] `getAnimationDriver()` 已加入新路由
- [ ] `stopAllAnimations()` 已加入新動畫 stop
- [ ] `window resize` 已加入新動畫 resize
- [ ] `stop()` 中有 resolve 所有等待中的 Promise
- [ ] `revealWinners` 處理了 `one_by_one` 和 `all_at_once`
- [ ] 每個 `await` 後都有 `isRunStale()` 檢查
- [ ] 連續型：`winnerQueue` 機制正確（衝線時更新名字）
- [ ] 連續型：`startDraw()` 保留 `winnerQueue` 不被 reset 清空
- [ ] `holdSeconds` 正確控制動畫節奏（非硬超時）
- [ ] 名稱池不使用 `new Set()` 去重（支援重複中獎）
- [ ] 動畫結束後有適當延遲（3-5 秒）

**後端 (PrizesRelationManager.php)**
- [ ] `Select::make('animation_style')` options 已新增
- [ ] `$validStyles` 白名單已新增
- [ ] 所有 `match` 中文對照已新增（共 3 處）

**Demo 頁面**
- [ ] `DemoLotteryController.VALID_STYLES` 已新增
- [ ] `demo-toolbar.blade.php` styles 已新增

**測試驗證**
- [ ] 後台可選擇新動畫風格
- [ ] 前台切換獎項後動畫正確顯示
- [ ] 抽獎流程 (one_by_one) 正常
- [ ] 抽獎流程 (all_at_once) 正常
- [ ] **得獎名單上的名字 = 動畫中衝線的名字**
- [ ] 切換到其他獎項時新動畫正確停止
- [ ] 視窗 resize 後動畫佈局正確
- [ ] 開啟「同一獎項可重複中獎」後正常運作
- [ ] `/demo/lottery` 可切換到新動畫

---

## 參考現有動畫

建立新動畫時，可參考以下現有模組作為範本：

| 動畫 | 類型 | 適合參考場景 |
|------|------|-------------|
| `lottoAir` | 連續型 | 自動抽取、物理碰撞、粒子效果 |
| `redPacketRain` | 互動型-逐一 | 飄落+點擊揭曉、簡單互動 |
| `scratchCard` | 互動型-批次 | 批次處理(≤9)、刮開互動 |
| `treasureChest` | 互動型-批次 | 批次處理(≤9)、開啟動畫 |
| `bigTreasureChest` | 單元素型 | 單一主元素、大型動畫效果 |

在 `lottery.js` 中搜尋對應模組名，可直接查看完整實作。
