# 抽獎系統靜態程式碼檢查報告

**日期：** 2026-03-25
**審查範圍：** 後端抽獎 API（含排除員工邏輯）+ 前端所有動畫風格
**目標：** 杜絕抽獎過程中出錯的可能

---

## 審查檔案清單

| 類別 | 檔案 |
|------|------|
| 抽獎核心 | `app/Services/LotteryDrawService.php` |
| 資格篩選 | `app/Services/EligibleEmployeesService.php` |
| API 端點 | `app/Http/Controllers/LotteryFrontendController.php` |
| 資料模型 | `app/Models/Prize.php`、`app/Models/PrizeWinner.php` |
| 事件廣播 | `app/Events/LotteryEventUpdated.php`、`app/Events/PrizeWinnersUpdated.php` |
| 前端動畫 | `resources/js/lottery.js`（8063 行，7 種動畫風格） |

---

## 一、後端問題

### ~~B-01~~ [已排除] 釋出員工無法被重新抽中 — 符合業務邏輯

**位置：** `EligibleEmployeesService.php:68-78`

**原始疑慮：** 查詢未加 `whereNull('released_at')`，被釋出的員工仍被排除在可抽名單外。

**深入調查後結論：** 經查 `PrizeRedrawService::releaseWinners()` 與 `ReprizeSelector` Livewire 元件，釋出流程的業務場景為「**不在場重抽**」（reason = 'absent'）。管理員釋出不在場的中獎者，目的是讓**其他人**有機會補抽該名額。被釋出的人本身不在場，不應再被同一獎項抽中，因此排除邏輯**是正確的設計決策**。

**流程確認：**
1. 管理員在後台選擇不在場的中獎者 → `PrizeRedrawService::releaseWinners()` 設定 `released_at`
2. `Prize::winners()` 只計算 `whereNull('released_at')` → `remaining` 增加（有空位可補抽）
3. `vacantSequences` 收集已釋出且無替補的 sequence
4. 補抽的新中獎者填入空出的 sequence，記錄 `replacement_for_winner_id`
5. 被釋出者正確地從 `eligibleNames`（前端彩球名單）中消失

**不是 bug。** 不需修復。

---

### ~~B-02~~ [已排除] remaining=0 但有空位時無法抽獎 — 不會發生

**位置：** `LotteryDrawService.php:23-28`

**原始疑慮：** 當有效中獎者數 = `winners_count` 時，`$remaining = 0` 導致無法補抽空位。

**深入推演後結論：** 此場景不會發生。`Prize::winners()` 關聯使用 `whereNull('released_at')`（`Prize.php:62-65`），所以 `$alreadyDrawn` 只計算有效中獎者。當管理員釋出員工時，有效中獎者數自動減少，`remaining` 自動增加：

| 時間點 | 有效中獎者 | remaining | vacantSequences |
|--------|-----------|-----------|-----------------|
| 抽滿 5 人 | 5 | 0 | 0 |
| 釋出 A | 4 | 1 | 1（A 的 seq） |
| 補抽 B | 5 | 0 | 0（A 已有替補 B） |

`remaining` 和 `vacantSequences` 天然同步，不需額外檢查。

**不是 bug。** 不需修復。

---

### B-03 [HIGH] eligibleForStoredPrize() 重複查詢效能問題

**位置：** `EligibleEmployeesService.php:84-95`

```php
public function eligibleForStoredPrize(Prize $prize): Collection
{
    return $this->eligibleForPrize(
        $prize->lotteryEvent,
        $prize->rules()->where('type', TYPE_INCLUDE_EMPLOYEE)->pluck('ref_id')->all(),  // 查詢 1
        $prize->rules()->where('type', TYPE_INCLUDE_GROUP)->pluck('ref_id')->all(),      // 查詢 2
        $prize->rules()->where('type', TYPE_EXCLUDE_EMPLOYEE)->pluck('ref_id')->all(),   // 查詢 3
        $prize->rules()->where('type', TYPE_EXCLUDE_GROUP)->pluck('ref_id')->all(),      // 查詢 4
        ...
    );
}
```

**問題：** 每次呼叫產生 4 次 `rules()` 查詢。此方法在以下地方被呼叫：
- `LotteryDrawService::drawCurrentPrize()` — 1 次
- `PrizeWinnersUpdated::broadcastWith()` — 1 次（同一請求內第 2 次）
- `LotteryEventUpdated::broadcastWith()` — 管理員操作時

同一次抽獎請求中會執行 8 次 rules 查詢。

**建議修復：**
```php
public function eligibleForStoredPrize(Prize $prize): Collection
{
    $rules = $prize->rules()->get()->groupBy('type');

    return $this->eligibleForPrize(
        $prize->lotteryEvent,
        $rules->get(PrizeRule::TYPE_INCLUDE_EMPLOYEE)?->pluck('ref_id')->all() ?? [],
        $rules->get(PrizeRule::TYPE_INCLUDE_GROUP)?->pluck('ref_id')->all() ?? [],
        $rules->get(PrizeRule::TYPE_EXCLUDE_EMPLOYEE)?->pluck('ref_id')->all() ?? [],
        $rules->get(PrizeRule::TYPE_EXCLUDE_GROUP)?->pluck('ref_id')->all() ?? [],
        $prize->allow_repeat_within_prize,
        $prize->id,
    );
}
```

---

### B-04 [HIGH] PrizeWinnersUpdated 中 allPrizes 的 N+1 查詢

**位置：** `PrizeWinnersUpdated.php:50-56`

```php
$allPrizes = $this->prize->lotteryEvent->prizes
    ->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'winnersCount' => $p->winners_count,
        'drawnCount' => $p->winners()->count(),  // ← 每個獎項一次查詢
    ])->all();
```

**問題：** 若有 N 個獎項，會執行 N 次 `winners()->count()` 查詢。`LotteryEventUpdated.php:51-57` 有相同問題。

**建議修復：**
```php
$allPrizes = $this->prize->lotteryEvent->prizes()
    ->withCount(['winners' => fn ($q) => $q->whereNull('released_at')])
    ->get()
    ->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'winnersCount' => $p->winners_count,
        'drawnCount' => $p->winners_count,
    ])->all();
```

---

### B-05 [HIGH] draw API 回應缺少完成狀態

**位置：** `LotteryFrontendController.php:297-307`

```php
return response()->json([
    'prize_id' => $event->currentPrize->id,
    'prize_name' => $event->currentPrize->name,
    'winners' => $winners->map(fn ($winner) => [ ... ])->values()->all(),
    // ❌ 缺少 is_completed 和 is_exhausted
]);
```

**問題：** 前端 `draw()` 函數在 `finally` 區塊（`lottery.js:7601`）檢查 `isPrizeCompleted()` 和 `eligibleNames` 來判斷是否進入結果模式。但 draw API 回應不包含 `is_completed` 和 `is_exhausted`，前端必須等待 WebSocket 的 `winners.updated` 事件才能取得這些狀態。

**影響：** 若 WebSocket 延遲，前端可能短暫顯示不正確的狀態（如：名額已滿但不顯示結果畫面）。

**建議修復：** 在 draw 回應中加入狀態：
```php
$drawnCount = $event->currentPrize->winners()->count();
$isCompleted = $drawnCount >= $event->currentPrize->winners_count;

return response()->json([
    'prize_id' => $event->currentPrize->id,
    'prize_name' => $event->currentPrize->name,
    'is_completed' => $isCompleted,
    'is_exhausted' => !$isCompleted && app(EligibleEmployeesService::class)
        ->eligibleForStoredPrize($event->currentPrize)->isEmpty(),
    'winners' => $winners->map(...)->values()->all(),
]);
```

---

### B-06 [MEDIUM] run_id 缺乏冪等性保護

**位置：** `LotteryFrontendController.php:282-284`

```php
if ($runId = $request->input('run_id')) {
    Cache::put("lottery-drawing:{$brandCode}", $runId, now()->addSeconds(15));
}
```

**問題：** `run_id` 只被用於追蹤 drawing state，沒有檢查是否已處理過。若前端因網路問題重試同一個 `run_id`，Lock 已釋放後會產生重複抽獎。

**建議修復：**
```php
if ($runId = $request->input('run_id')) {
    $dedupKey = "draw-dedup:{$event->id}:{$runId}";
    if (Cache::has($dedupKey)) {
        return response()->json(['message' => 'already_processed'], 409);
    }
    Cache::put($dedupKey, true, now()->addMinutes(2));
    Cache::put("lottery-drawing:{$brandCode}", $runId, now()->addSeconds(15));
}
```

---

### B-07 [MEDIUM] sequence 欄位缺乏唯一約束

**位置：** `LotteryDrawService.php:45-47`

```php
$maxSequence = $prize->allWinnerRecords()->max('sequence') ?? 0;
$nextSequence = $maxSequence + 1;
```

**問題：** 雖然有 `lockForUpdate()` 保護 Prize 列，但 `allWinnerRecords()->max('sequence')` 本身並未鎖定。在 DB transaction isolation level 為 `READ COMMITTED`（MySQL 預設）的情況下，理論上兩個同時進行的 transaction 可能讀到相同的 max sequence。

**實際風險：** 低。因為有 Cache Lock 在外層保護，但如果 Lock 機制失效（如 Redis 故障），可能產生重複 sequence。

**建議：** 在 migration 中加入複合唯一索引：
```php
$table->unique(['prize_id', 'sequence']);
```

---

### B-08 [MEDIUM] allow_repeat_within_prize 時單次迴圈內可能重複抽中同一人

**位置：** `LotteryDrawService.php:49-69`

```php
if ($prize->allow_repeat_within_prize) {
    for ($index = 0; $index < $drawCount; $index++) {
        $winner = $eligible->random();  // ← 同一人可能連續被選中
        // ...
    }
}
```

**問題：** 在 `all_at_once` 模式下，若 `drawCount > 1` 且 `allow_repeat_within_prize = true`，`$eligible->random()` 完全隨機，同一個員工可能在同一次抽獎中多次被選為中獎者（佔用多個 sequence）。

**評估：** 若業務邏輯確實允許「同一人可以在同一獎項中獲得多個名額」，則此行為正確。但需確認這是否為預期行為 — 大多數抽獎場景中，同一人重複中獎通常指「跨獎項」重複，而非「同獎項多名額」。

---

### B-09 [LOW] 隨機數可預測性

**位置：** `LotteryDrawService.php:51, 79`

```php
$winner = $eligible->random();              // 使用 array_rand()
$selected = $eligible->shuffle()->take(...); // 使用 shuffle()
```

**問題：** Laravel 的 `Collection::random()` 和 `shuffle()` 底層使用 PHP 的 `mt_rand()`，這是一個偽隨機數生成器，理論上可被預測。

**實際風險：** 對大多數企業內部抽獎場景風險極低。但若需更高公信力，可改用 `random_int()` 搭配自定義 shuffle 實作。

---

### B-10 [LOW] draw API 缺乏 run_id 格式驗證

**位置：** `LotteryFrontendController.php:282`

```php
if ($runId = $request->input('run_id')) {
```

**問題：** `run_id` 沒有長度或格式驗證。惡意使用者可傳入超長字串或特殊字元，雖然 Cache driver 通常有限制，但防禦性驗證更安全。

**建議修復：**
```php
$validated = $request->validate([
    'run_id' => 'nullable|string|max:64|alpha_dash',
]);
```

---

### B-11 [LOW] syncPrizeWinnersGroupIfCompleted 可能重複觸發

**位置：** `LotteryDrawService.php:109-114`

```php
private function syncPrizeWinnersGroupIfCompleted(Prize $prize): void
{
    if ($prize->winners()->count() >= $prize->winners_count) {
        app(SystemGroupService::class)->syncPrizeWinnersGroup($prize);
    }
}
```

**問題：** 每次抽完都會執行此檢查，且 `syncPrizeWinnersGroup()` 會執行完整的群組同步操作。在 `one_by_one` 模式下，最後一次抽獎完成時會觸發，但之後若再次呼叫（如 WebSocket 事件重新觸發），會重複執行不必要的同步。

**影響：** 效能浪費，無功能性錯誤。

---

## 二、前端問題

### F-01 [HIGH] lottoAir.stop() 未完全清理內部狀態

**位置：** `lottery.js:2170-2186`

```javascript
const stop = () => {
    airState.running = false;
    if (airState.rafId) { cancelAnimationFrame(airState.rafId); airState.rafId = null; }
    if (airState.finishPauseId) { clearTimeout(airState.finishPauseId); airState.finishPauseId = null; }
    airState.waiters.length = 0;
    airState.balls.length = 0;
    airState.picked.length = 0;
    airState.pending.length = 0;
    particles.length = 0;
    stopTrayCarousel();
    // ❌ 缺少以下清理：
    // airState.mode — 停留在非 idle 狀態
    // airState.t — 殘留的時間值
    // airState.paused — 可能殘留 true
    // airState.slowStop / airState.slowFactor — 殘留減速狀態
    // shake.power — 殘留震動力道
};
```

**問題：** 快速切換獎項或停止/重啟動畫時，殘留的狀態標誌可能導致下次啟動時行為異常（如：啟動即為減速模式、震動持續）。

**建議修復：** 在 `stop()` 末尾加入：
```javascript
airState.mode = 'idle';
airState.t = 0;
airState.paused = false;
airState.slowStop = false;
airState.slowFactor = 1;
shake.power = 0;
```

---

### F-02 [HIGH] scratchCard cleanup() 未重置關鍵計數器

**位置：** `lottery.js:3405-3415`

```javascript
const cleanup = () => {
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    particles.length = 0;
    sparkles.length = 0;
    for (const card of cards) {
        card.maskCanvas = null;
        card.maskCtx = null;
    }
    cards.length = 0;
    if (revealResolve) { revealResolve(); revealResolve = null; }
    // ❌ 未重置 revealedCount
    // ❌ 未清理各卡片的 scratchResolve
};
```

**問題：** `revealedCount` 未歸零，若前一次動畫被中途停止，殘留的計數值可能影響下次動畫的揭曉判斷邏輯。各卡片的 `scratchResolve` Promise 被遺棄但未清理。

**建議修復：**
```javascript
const cleanup = () => {
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    particles.length = 0;
    sparkles.length = 0;
    for (const card of cards) {
        if (card.scratchResolve) { card.scratchResolve = null; }
        card.maskCanvas = null;
        card.maskCtx = null;
    }
    cards.length = 0;
    revealedCount = 0;
    if (revealResolve) { revealResolve(); revealResolve = null; }
};
```

---

### F-03 [HIGH] treasureChest cleanup() 未重置計數器

**位置：** `lottery.js:4071-4076`

```javascript
const cleanup = () => {
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    coins.length = 0;
    sparkles.length = 0;
    if (openResolve) { openResolve(); openResolve = null; }
    // ❌ 未重置 openedCount
};
```

**問題：** 與 scratchCard 相同 — `openedCount` 未歸零，中途停止後殘留值可能影響下次動畫。

**建議修復：** 在 `cleanup()` 中加入 `openedCount = 0;`

---

### F-04 [MEDIUM] bigTreasureChest cleanup() 未重置計數器

**位置：** `lottery.js:5042-5059`

```javascript
const cleanup = () => {
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    coins.length = 0;
    ingots.length = 0;
    gourds.length = 0;
    sparkles.length = 0;
    redPackets.length = 0;
    spawnTimer = 0;
    nameEruption.active = false;
    nameEruption.phase = 'idle';
    if (revealResolve) { revealResolve(); revealResolve = null; }
    // ❌ phase 未重置（由 stop() 處理，但 cleanup 單獨呼叫時可能遺漏）
};
```

**問題：** `cleanup()` 也被 `showChest()` 呼叫（`lottery.js:5061`），此時 `phase` 不一定被重置。

**影響：** 風險較低，因 `stop()` 會先設 `phase = 'idle'` 再呼叫 `cleanup()`。但防禦性程式碼應在 `cleanup()` 中也重置。

---

### F-05 [MEDIUM] safetyTimer 與 revealWinners 的衝突

**位置：** `lottery.js:7546-7567`

```javascript
let safetyTimer = setTimeout(() => {
    safetyTimer = null;
    state.isDrawing = false;
    stopAllAnimations();   // ← 強制停止
    // ...
}, 75000);

await driver.revealWinners(winners, { ... });  // ← 可能在超時後仍在執行
```

**問題：** 若 75 秒超時觸發，`safetyTimer` 回調會呼叫 `stopAllAnimations()` 並設 `state.isDrawing = false`。但 `driver.revealWinners()` 可能仍在 `await` 中，其內部的 `appendWinnerForRun` 有 `if (!state.isDrawing) return` 保護，所以中獎者不會再被加入。

**但：** `revealWinners` 的 Promise 最終 resolve 後，`finally` 區塊仍會執行，可能導致 `state.isDrawing = false` 被重複設定、`stopDrawAudio()` 被重複呼叫。

**實際影響：** 不會造成功能性錯誤（重複設 false 無害），但控制流不夠清晰。

---

### F-06 [LOW] EventListener 重複註冊風險

**位置：** `lottery.js:7630, 7632, 7638, 7644`

```javascript
document.addEventListener('keydown', handleKeydown);
drawButtonEl?.addEventListener('click', () => { draw(); });
stageEl?.addEventListener('click', () => { draw(); });
window.addEventListener('resize', () => { ... });
```

**問題：** 雖然有 `window.__lotteryInitialized` guard 防止重複初始化，但若 Vite HMR 熱重載繞過此 guard（如模組重新載入），事件監聽器可能重複註冊。

**實際風險：** 開發環境可見（HMR 時按一次鍵觸發多次 draw），生產環境風險極低。

---

## 三、事件廣播與同步問題

### E-01 [CRITICAL] LotteryEventUpdated 缺少 is_exhausted 欄位

**位置：** `LotteryEventUpdated.php:76-86`

```php
'current_prize' => $currentPrize ? [
    'id' => $currentPrize->id,
    // ...
    'is_completed' => $currentPrize->winners()->count() >= $currentPrize->winners_count,
    // ❌ 缺少 'is_exhausted'
] : null,
```

**前端依賴：** `lottery.js:7770`

```javascript
const isExhausted = payload.current_prize?.is_exhausted && state.winners?.length > 0;
```

**問題：** `LotteryEventUpdated` 事件不包含 `is_exhausted`，導致透過 `.lottery.updated` 事件（管理員切換獎項、開關抽獎等操作）觸發的 `applyLotteryPayload()` 永遠將 `isExhausted` 視為 `false`。

**影響場景：**
1. 可抽人數已用盡（但名額未滿）
2. 管理員操作任何設定（觸發 `LotteryEventUpdated`）
3. 前端收到 `.lottery.updated`，`is_exhausted` 為 `undefined`
4. 前端退出結果模式，顯示抽獎按鈕（但按下會得到 no_winners）

**建議修復：** 在 `LotteryEventUpdated.php` 加入：
```php
'current_prize' => $currentPrize ? [
    // ... existing fields ...
    'is_completed' => $drawnCount >= $currentPrize->winners_count,
    'is_exhausted' => !($drawnCount >= $currentPrize->winners_count) && empty($eligibleNames),
] : null,
```

---

### E-02 [HIGH] draw API 與 WebSocket 事件的資料結構不一致

**位置：**
- draw API 回應：`LotteryFrontendController.php:297-307`
- WebSocket 事件：`PrizeWinnersUpdated.php:58-72`

| 欄位 | draw API | PrizeWinnersUpdated | LotteryEventUpdated |
|------|----------|---------------------|---------------------|
| `winners` | ✅ | ✅ | ✅ |
| `is_completed` | ❌ | ✅ | ✅ |
| `is_exhausted` | ❌ | ✅ | ❌ |
| `eligible_names` | ❌ | ✅ | ✅ |
| `all_prizes` | ❌ | ✅ | ✅ |
| `bg_url` | ❌ | ❌ | ✅ |
| `music_url` | ❌ | ❌ | ✅（在 current_prize 內） |

**問題：** 前端依賴 WebSocket 事件補齊 draw API 回應中缺少的欄位。若 WebSocket 延遲或斷線，前端狀態會不完整。

**建議：** 在 draw API 回應中加入 `is_completed`、`is_exhausted`、`eligible_names`，使前端可在 HTTP 回應中就取得完整狀態。

---

### E-03 [MEDIUM] eligibleNames 同步邏輯的邊界情況

**位置：** `lottery.js:7737-7739`

```javascript
state.eligibleNames = prizeChanged
    ? (payload.eligible_names ?? [])
    : (payload.eligible_names ?? state.eligibleNames ?? []);
```

**問題：** 當同一獎項的 WebSocket 事件中 `eligible_names` 為 `null` 或 `undefined` 時（理論上不應發生，但防禦性考量），前端會保留舊的 `state.eligibleNames`。若此時實際可抽名單已改變（如其他獎項抽走了共用人員），可能導致樂透彩球名字與實際不符。

**實際風險：** 低。正常事件都會攜帶 `eligible_names`。

---

### E-04 [MEDIUM] PrizeWinnersUpdated 缺少背景和音樂 URL

**位置：** `PrizeWinnersUpdated.php:58-72`

**問題：** `PrizeWinnersUpdated` 事件不包含 `bg_url` 和 `music_url`。在正常流程中不影響功能（這些在 `LotteryEventUpdated` 中提供）。但若前端在 `LotteryEventUpdated` 之前收到 `PrizeWinnersUpdated`，且是首次載入，背景和音樂可能不可用。

**實際風險：** 極低。正常初始化流程會透過 HTTP 輪詢取得完整資料。

---

### E-05 [LOW] WebSocket 頻道無授權驗證

**位置：** `LotteryEventUpdated.php:27`、`PrizeWinnersUpdated.php:25`

```php
return new Channel('lottery.'.$this->brandCode);  // 公開頻道
```

`routes/channels.php` 中無對應的 `lottery.*` 頻道授權定義。

**評估：** 使用公開頻道（`Channel` 而非 `PrivateChannel`）意味著任何知道 `brandCode` 的人都可以監聽事件。對於公開展示的抽獎場景，這是合理設計。但需注意：
- 廣播的資料已有脫敏處理（`DataMasker::maskEmail`） ✅
- 員工姓名為明文廣播（`eligible_names`） ⚠️

若員工姓名為敏感資訊，應考慮使用 `PrivateChannel` 並搭配授權。

---

## 四、正確實作確認

以下項目經檢查確認為正確實作：

| 項目 | 狀態 | 位置 |
|------|------|------|
| Cache Lock 防止併發抽獎 | ✅ 正確 | `LotteryFrontendController.php:276-279` |
| DB Transaction 保護資料一致性 | ✅ 正確 | `LotteryDrawService.php:15` + `lockForUpdate()` |
| `state.winners` 使用展開操作符更新 | ✅ 正確 | `lottery.js:7014, 7477` |
| WebSocket 事件在抽獎中被正確忽略 | ✅ 正確 | `lottery.js:7864-7895` |
| `one_by_one` 模式保留既有中獎者 | ✅ 正確 | `lottery.js:7476` |
| drawRunId 防止過期 run 的回調汙染 | ✅ 正確 | `lottery.js:7467-7472` |
| 75 秒安全超時機制 | ✅ 正確 | `lottery.js:7546-7561` |
| `stopAllAnimations()` 呼叫所有 7 種動畫的 stop() | ✅ 正確 | `lottery.js:7001-7009` |
| marbleRace.stop() 清理完整（resolve waiters、reset phase） | ✅ 正確 | `lottery.js:5964-5973` |
| battleTop.stop() 清理完整（resolve waiters、清空 tops/pool） | ✅ 正確 | `lottery.js:6953-6970` |
| redPacketRain cleanup() 清理 Promise 和計時器 | ✅ 正確 | `lottery.js:2758-2782` |
| 樂透球名字 fallback 機制 | ✅ 正確（依 CLAUDE.md 規範） | `pickBall` 等函數 |
| 釋出後補抽流程（released → remaining 增加 → vacantSequences 填入） | ✅ 正確 | `LotteryDrawService.php:22-43` |
| 釋出員工排除邏輯（不在場者不再被同獎項抽中） | ✅ 正確（符合業務設計） | `EligibleEmployeesService.php:68-78` |

---

## 五、優先修復建議

### 立即修復（影響抽獎正確性）

1. **E-01** — `LotteryEventUpdated.php` 加入 `is_exhausted` 欄位

### 短期修復（改善穩定性）

2. **B-05 / E-02** — draw API 回應加入 `is_completed`、`is_exhausted`
3. **F-01** — lottoAir `stop()` 補齊狀態重置
4. **F-02** — scratchCard `cleanup()` 重置 `revealedCount`
5. **F-03** — treasureChest `cleanup()` 重置 `openedCount`
6. **B-03** — `eligibleForStoredPrize()` 改為單次查詢 rules

### 長期優化（效能與防禦性）

7. **B-04** — allPrizes 改用 `withCount` 消除 N+1
8. **B-06** — 加入 run_id 冪等性保護
9. **B-07** — 加入 `(prize_id, sequence)` 唯一索引
10. **B-10** — 加入 run_id 格式驗證
