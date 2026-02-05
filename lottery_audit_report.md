# 抽獎前端 JS 全面審查報告

**審查檔案:** `resources/js/lottery.js` (~5754 行)
**審查日期:** 2026-02-05
**審查範圍:** 7 種動畫風格 × 2 種 drawMode = 14 種組合

---

## 摘要

| 嚴重程度 | 數量 |
|----------|------|
| Critical | 1    |
| Major    | 2    |
| Minor    | 5    |

---

## Critical Bug

### Bug #1: `revealWinner` 中 `style` 變數作用域錯誤 — Slot 模式完全無法運作

- **動畫風格**: slot（含 test / typewriter / scramble / flip / roulette 子風格）
- **drawMode**: both
- **檔案位置**: `lottery.js:4917`
- **問題描述**:

  `revealWinner` 函數定義在 `initLottery` 作用域（行 4911），內部使用 `style` 變數（行 4917）：

  ```javascript
  // 行 4911 — initLottery 作用域
  const revealWinner = async (winnerName, delayMs = 0) => {
      // ...
      if (style === 'test') {           // ← style 未定義！
  ```

  但 `style` 只在 `draw()` 函數內部定義（行 4958）：

  ```javascript
  // 行 4937 — draw() 作用域
  const draw = async () => {
      // ...
      const style = state.currentPrize?.animationStyle ?? 'slot'; // 行 4958
  ```

  `revealWinner` 無法存取 `draw()` 內部的區域變數。因為此檔案透過 `@vite` 以 ESM 模組載入（`<script type="module">`），運行在 **嚴格模式（strict mode）**，存取未宣告的變數會拋出 `ReferenceError`。

- **影響**:
  - `ReferenceError` 被 `draw()` 的 `catch` 區塊靜默捕獲
  - `state.winners` 不會被更新（更新在 `revealWinner` 之後，行 5396）
  - 使用者看到抽獎突然停止，沒有結果
  - 伺服器端抽獎成功但前端不顯示，造成資料不一致
  - WebSocket 或 5 秒輪詢後才會同步中獎者

- **重現步驟**:
  1. 設定任意獎項的動畫風格為 slot / test / typewriter / scramble / flip / roulette
  2. 開啟前端抽獎頁面
  3. 點擊抽獎按鈕
  4. 觀察：動畫短暫執行後突然停止，無中獎者顯示

- **建議修正**:

  **方案 A（推薦）**：在 `revealWinner` 內部從 `state` 讀取動畫風格：
  ```javascript
  // lottery.js:4911
  const revealWinner = async (winnerName, delayMs = 0) => {
      if (!slotDisplayEl) return null;

      const name = winnerName || '（未知）';
      let revealedBall = null;
      const style = state.currentPrize?.animationStyle ?? 'slot'; // ← 加入此行

      if (style === 'test') {
  ```

  **方案 B**：將 `style` 作為參數傳入 `revealWinner`：
  ```javascript
  const revealWinner = async (winnerName, style, delayMs = 0) => {
  ```
  並在 `draw()` 中呼叫時傳入：
  ```javascript
  await revealWinner(winner.employee_name, style, perWinnerDelay);
  ```

---

## Major Bugs

### Bug #2: `applyLotteryPayload` 缺少 `redPacketRain.stop()`

- **動畫風格**: red_packet
- **drawMode**: both
- **檔案位置**: `lottery.js:5544-5557`
- **問題描述**:

  切換獎項時，其他 Canvas 動畫都有對應的停止邏輯，唯獨 `redPacketRain` 缺少：

  ```javascript
  // lottery.js:5544-5557
  if (isLottoStyle(nextPrize?.animationStyle)) {
      lottoAir.ensureReady();
  } else {
      lottoAir.stop();                                    // ✅ lottoAir
  }
  if (!isScratchCardStyle(nextPrize?.animationStyle)) {
      scratchCard.stop();                                 // ✅ scratchCard
  }
  if (!isTreasureChestStyle(nextPrize?.animationStyle)) {
      treasureChest.stop();                               // ✅ treasureChest
  }
  if (!isBigTreasureChestStyle(nextPrize?.animationStyle)) {
      bigTreasureChest.stop();                            // ✅ bigTreasureChest
  }
  // ❌ 缺少 redPacketRain.stop()
  ```

- **影響**:
  - 從 `red_packet` 切換到其他風格時，紅包雨 Canvas 動畫持續運行
  - CPU 浪費：紅包持續生成和渲染
  - Canvas 疊加：新動畫和舊紅包雨同時在同一 Canvas 上繪製

- **重現步驟**:
  1. 設定獎項 A 為 `red_packet` 風格
  2. 在前端頁面上開始抽獎或等待紅包雨動畫啟動
  3. 後台切換到獎項 B（任何其他風格）
  4. 觀察：紅包雨持續飄落，新動畫疊加在上面

- **建議修正**:

  在 `applyLotteryPayload` 中加入 `redPacketRain.stop()`：

  ```javascript
  // lottery.js:5549 後加入
  if (!isRedPacketStyle(nextPrize?.animationStyle)) {
      redPacketRain.stop();
  }
  ```

---

### Bug #3: `draw()` catch 區塊缺少 lotto_air 動畫停止

- **動畫風格**: lotto_air
- **drawMode**: both
- **檔案位置**: `lottery.js:5408-5424`
- **問題描述**:

  `draw()` 的 `catch` 區塊停止了 5 種動畫，但缺少 `lotto_air`：

  ```javascript
  // lottery.js:5408-5423
  catch {
      if (useLotto2) {
          lottoAir.slowStopMachine();      // ✅ lotto2
      }
      // ❌ 缺少: if (useLottoAir) { lottoAir.slowStopMachine(); }
      if (useRedPacket) {
          redPacketRain.stop();            // ✅ red_packet
      }
      if (useScratchCard) {
          scratchCard.stop();              // ✅ scratch_card
      }
      if (useTreasureChest) {
          treasureChest.stop();            // ✅ treasure_chest
      }
      if (useBigTreasureChest) {
          bigTreasureChest.stop();         // ✅ big_treasure_chest
      }
      return null;
  }
  ```

  lotto_air 路徑在 `lottoAir.startDraw()` 之後（行 5124/5136）才可能發生錯誤。如果錯誤發生，球機動畫會持續運行，`finally` 區塊的 `render()` 只呼叫 `ensureReady()` 不會停止動畫。

- **影響**:
  - lotto_air 動畫在錯誤後持續運行，球持續旋轉
  - 實務上不常觸發（`waitForNextPick` 的 Promise 不會 reject）

- **建議修正**:

  ```javascript
  // lottery.js:5409 後加入
  if (useLottoAir) {
      lottoAir.slowStopMachine();
  }
  ```

---

## Minor Bugs

### Bug #4: `draw()` finally 區塊缺少 `isExhausted` 檢查

- **動畫風格**: 所有風格
- **drawMode**: both
- **檔案位置**: `lottery.js:5433-5437`
- **問題描述**:

  ```javascript
  // lottery.js:5433-5437
  finally {
      // ...
      if (isPrizeCompleted()) {                    // 只檢查名額是否已滿
          sfx.playVictory();
          setTimeout(() => resultMode.show(), 2000);
      }
      // ❌ 缺少 isExhausted 檢查
  }
  ```

  Draw API 回傳的 response 不包含 `is_exhausted` 狀態。當可抽人數用盡但名額未滿時，`isPrizeCompleted()` 回傳 `false`，使用者需等待：
  - WebSocket `.winners.updated` 事件（但抽獎中被 `isDrawing` 攔截，只更新 `eligibleNames`）
  - 5 秒輪詢 `pollPayload`

  所以進入 `resultMode` 最多延遲 5 秒。

- **建議修正**:

  Draw API 回傳加入 exhaustion 狀態，或在 finally 中根據 `state.eligibleNames` 判斷：

  ```javascript
  // lottery.js:5433 後
  const exhausted = (state.eligibleNames?.length === 0) && !isPrizeCompleted() && (state.winners?.length > 0);
  if (isPrizeCompleted() || exhausted) {
      sfx.playVictory();
      setTimeout(() => resultMode.show(), 2000);
  }
  ```

---

### Bug #5: scratch_card / treasure_chest `one_by_one` 模式在 eligibleNames 為空時仍顯示元素

- **動畫風格**: scratch_card, treasure_chest
- **drawMode**: one_by_one
- **檔案位置**: `lottery.js:185`, `lottery.js:201`
- **問題描述**:

  ```javascript
  // lottery.js:185
  const cardCount = state.currentPrize?.drawMode === 'one_by_one'
      ? 1                                   // ← 無條件顯示 1 張
      : Math.min(actualCanDraw, 9);
  ```

  `one_by_one` 模式下 `cardCount` 固定為 1，不考慮 `actualCanDraw` 是否為 0。當沒有可抽人員時，仍顯示一張空卡片/寶箱。

- **建議修正**:

  ```javascript
  const cardCount = state.currentPrize?.drawMode === 'one_by_one'
      ? Math.min(1, actualCanDraw)     // 考慮可抽人數
      : Math.min(actualCanDraw, 9);
  ```

---

### Bug #6: innerHTML 使用未轉義的用戶資料（XSS 風險）

- **動畫風格**: 所有風格
- **drawMode**: both
- **檔案位置**: `lottery.js:111`, `lottery.js:280`, `lottery.js:340`
- **問題描述**:

  `employee_name`、`employee_email`、`prize.name` 直接插入 HTML：

  ```javascript
  // lottery.js:111
  <span class="font-semibold">${winner.employee_name ?? ''}</span>
  ```

  如果名稱包含 HTML（如 `<img onerror="alert(1)">`），可能導致 XSS。

- **風險等級**: 低（內部系統，資料來自管理員設定）
- **建議修正**:

  新增轉義函數：
  ```javascript
  const escapeHtml = (str) => {
      if (!str) return '';
      return str.replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  };
  ```

---

### Bug #7: 抽獎中 animateListItem 操作隱藏/過時元素

- **動畫風格**: 所有風格
- **drawMode**: both
- **檔案位置**: `lottery.js:5100-5103`, `lottery.js:5143-5146` 等
- **問題描述**:

  抽獎中 `render()` 會：
  1. 隱藏 `winnersContainerEl`（行 224）
  2. 不呼叫 `renderWinnersPage`（行 226-228 的 `if (!state.isDrawing)` 判斷）

  但各動畫路徑在每次添加中獎者後都嘗試：
  ```javascript
  const lastItem = winnerListEl?.lastElementChild;
  if (lastItem) {
      animateListItem(lastItem);    // 動畫在隱藏元素上執行
  }
  ```

  由於 `renderWinnersPage` 未被呼叫，`lastElementChild` 是舊的 DOM 元素。動畫在隱藏容器上執行，使用者看不到。

- **影響**: 無功能影響，僅浪費 DOM 動畫資源
- **建議修正**: 移除抽獎中的 `animateListItem` 呼叫，或在 `finally` 區塊中統一處理列表動畫。

---

### Bug #8: scratchCard `waitForAllRevealed` 完成條件不一致

- **動畫風格**: scratch_card
- **drawMode**: all_at_once
- **檔案位置**: `lottery.js:3053` vs `lottery.js:3207`
- **問題描述**:

  動畫更新迴圈中的完成檢查：
  ```javascript
  // lottery.js:3053 — 使用 cards.length
  if (revealedCount >= cards.length && revealResolve) {
  ```

  `waitForAllRevealed` 中的完成檢查：
  ```javascript
  // lottery.js:3207 — 使用 cards.filter(c => c.winner).length
  if (revealedCount >= cards.filter(c => c.winner).length) {
  ```

  前者用 `cards.length`（全部卡片數），後者用有 winner 的卡片數。如果有卡片沒設定 winner，行為會不一致。

- **影響**: 目前實務上所有卡片都有 winner，不會觸發問題
- **建議修正**: 統一使用 `cards.filter(c => c.winner).length`

---

## 各組合檢查結果矩陣

| 動畫風格 | drawMode | A.啟動 | B.進行 | C.結束 | D.結果 | E.切換 | F.邊界 | 結果 |
|----------|----------|--------|--------|--------|--------|--------|--------|------|
| slot | one_by_one | ✅ | ❌ #1 | ✅ | ✅ | ✅ | ❌ #1 | FAIL |
| slot | all_at_once | ✅ | ❌ #1 | ✅ | ✅ | ✅ | ❌ #1 | FAIL |
| lotto_air | one_by_one | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ #3 | WARN |
| lotto_air | all_at_once | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ #3 | WARN |
| lotto2 | one_by_one | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | PASS |
| lotto2 | all_at_once | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | PASS |
| red_packet | one_by_one | ✅ | ✅ | ✅ | ✅ | ❌ #2 | ⚠️ #4 | FAIL |
| red_packet | all_at_once | ✅ | ✅ | ✅ | ✅ | ❌ #2 | ⚠️ #4 | FAIL |
| scratch_card | one_by_one | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ #5,#8 | WARN |
| scratch_card | all_at_once | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ #8 | WARN |
| treasure_chest | one_by_one | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ #5 | WARN |
| treasure_chest | all_at_once | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | PASS |
| big_treasure_chest | one_by_one | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | PASS |
| big_treasure_chest | all_at_once | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | PASS |

**圖例:** ✅ = 通過, ❌ = 有 Critical/Major bug, ⚠️ = 有 Minor bug

---

## 修正優先順序

| 優先級 | Bug # | 修正內容 | 預估影響範圍 |
|--------|-------|----------|-------------|
| P0 | #1 | `revealWinner` 加入 `style` 變數定義 | 1 行 |
| P1 | #2 | `applyLotteryPayload` 加入 `redPacketRain.stop()` | 3 行 |
| P1 | #3 | `draw()` catch 加入 lotto_air stop | 3 行 |
| P2 | #4 | `draw()` finally 加入 isExhausted 檢查 | 5 行 |
| P2 | #5 | one_by_one cardCount/chestCount 考慮 actualCanDraw | 2 行 |
| P3 | #6 | 新增 HTML 轉義函數並套用 | 15 行 |
| P3 | #7 | 移除抽獎中的 animateListItem（或保留不處理） | 可選 |
| P3 | #8 | 統一 scratchCard 完成條件 | 1 行 |

---

## 正面發現（設計良好的部分）

1. **`state.winners` 更新方式**: 所有路徑都正確使用 `[...state.winners, winner]` 展開操作符
2. **WebSocket 競爭條件**: `.lottery.updated` 在抽獎中被正確忽略；`.winners.updated` 只更新 `eligibleNames` 和 `allPrizes`
3. **pickBall fallback**: `lottoAir.pickBall` 正確實作了找不到匹配球時的 fallback 和重命名
4. **isDrawing guard**: `draw()` 入口有 `state.isDrawing` 檢查，防止重複抽獎
5. **Promise 清理**: 所有動畫模組的 `cleanup()`/`stop()` 都正確 resolve 等待中的 Promise
6. **分批處理**: scratch_card 和 treasure_chest 的分批邏輯（每批最多 9 個）正確
7. **Fisher-Yates 洗牌**: 隨機順序刮開/開啟的實作正確
8. **lottoAir ensureCount**: 正確防止 one_by_one 模式下已抽出球被重置
9. **一次全抽的球數同步**: `ensureReady()` → `syncCountFromEligible()` → `reset()` 鏈條正確
