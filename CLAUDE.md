# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Language

請使用繁體中文回覆。

## Project Overview

Lottery drawing application for organizations. Multi-tenant architecture where each Organization can have multiple LotteryEvents, each with Prizes and eligible Employees.

**Stack:** Laravel 11 + Filament 3 (admin) + Laravel Reverb (WebSocket) + Blade/Tailwind/Alpine.js (frontend)

## Development Commands

```bash
# Start all services (recommended)
composer dev

# Or start individually:
php artisan serve --host=127.0.0.1 --port=8007  # Backend
npm run dev                                      # Vite (frontend assets)
php artisan reverb:start                         # WebSocket server

# Database
php artisan migrate

# Tests
php artisan test
php artisan test --filter=TestName              # Single test

# Code formatting
./vendor/bin/pint

# Route inspection
php artisan route:list | grep lottery
```

## Architecture

### Multi-Tenancy
- Filament uses `Organization` as tenant (configured in `AdminPanelProvider.php`)
- Users belong to Organizations via pivot table
- All resources (Employees, LotteryEvents, Prizes) scoped to Organization

### Key Data Flow
```
Organization → LotteryEvent → Prize → PrizeWinner → Employee
                    ↓
              EventRule / PrizeRule (eligibility filters)
```

### Route Structure
- Admin panel: `/admin/{tenant}/...`
- Lottery frontend: `/{brandCode}/lottery` (public display)
- Draw action: `POST /{brandCode}/draw`
- Winners list: `/{brandCode}/winners`

### Real-time Updates
Events broadcast via Laravel Reverb:
- `LotteryEventUpdated` - when admin changes event/prize settings
- `PrizeWinnersUpdated` - when winners are drawn

Frontend (`resources/js/lottery.js`) listens via Laravel Echo and updates UI.

## Key Files

| Purpose | Location |
|---------|----------|
| Lottery draw logic | `app/Services/LotteryDrawService.php` |
| Eligibility rules | `app/Services/EligibleEmployeesService.php` |
| Frontend controller | `app/Http/Controllers/LotteryFrontendController.php` |
| Lottery UI logic | `resources/js/lottery.js` |
| Filament resources | `app/Filament/Resources/` |
| Admin panel config | `app/Providers/Filament/AdminPanelProvider.php` |

## Prize Draw Modes

- `all_at_once` - Draw all remaining winners in one action
- `one_by_one` - Draw one winner per action

Animation styles and hold times are configurable per prize.

## 前端狀態管理注意事項

新增抽獎動畫方式時，務必遵守以下規則以避免狀態不一致：

### 1. `draw()` 函數中的 `state.winners` 更新

```javascript
// ✅ 正確：使用展開操作符建立新陣列
state.winners = [...state.winners, winner];

// ❌ 錯誤：直接 push（不會觸發響應式更新）
state.winners.push(winner);
```

### 2. 抽獎開始時的初始化邏輯

```javascript
// draw() 開始時會清空 winners（除了 one_by_one 已有中獎者）
if (state.currentPrize?.drawMode !== 'one_by_one' || !state.winners?.length) {
    state.winners = [];
}
```

### 3. WebSocket 事件與 `draw()` 的競爭條件

- **`.lottery.updated`**：抽獎中會被忽略，避免覆蓋 `state.winners`
- **`.winners.updated`**：抽獎中只更新 `eligibleNames` 和 `allPrizes`，不更新 `winners`
- 原因：`draw()` 會自己處理 `winners` 更新，WebSocket 事件重複加入會導致中獎者重複顯示

### 4. 新增動畫風格的檢查清單

新增動畫風格時，確認以下項目：

- [ ] `render()` 中加入動畫風格判斷（如 `isScratchCardStyle()`）
- [ ] 卡片/元素數量計算使用 `state.eligibleNames?.length ?? 0`（非 `?? remaining`）
- [ ] `draw()` 中根據 `drawMode` 處理 `one_by_one` vs `all_at_once`
- [ ] 分批處理時（如超過 9 個），注意 WebSocket 事件可能在批次間到達
- [ ] 動畫結束後檢查 `isPrizeCompleted()` 或 `isExhausted` 決定是否進入結果模式
- [ ] `applyLotteryPayload()` 中加入動畫停止邏輯
- [ ] `stop()` 必須清除所有內部狀態（balls、picked、pending、particles 等），不能只停動畫迴圈
- [ ] `applyLotteryPayload()` 中切換同風格獎項時，必須強制 reset（參考 `lottoAir.ensureReady(prizeChanged)`）

### 5. 後端事件廣播

`PrizeWinnersUpdated` 事件需包含：
- `winners`：新抽出的中獎者
- `eligible_names`：剩餘可抽人員（用於計算卡片數量）
- `all_prizes`：所有獎項進度（用於獎項預覽）
- `is_completed`：名額是否已滿
- `is_exhausted`：可抽人數是否用盡

### 6. 樂透模式彩球名字同步

樂透動畫的彩球名字來自 `state.eligibleNames`，但 API 返回的中獎者可能因同步問題不在彩球名單中。

**重要：** `pickBall` 等函數必須在找不到匹配球時，強制更新 fallback 球的名字：

```javascript
let target = candidates.find((ball) => ball.name === winnerName);
if (!target) {
    target = candidates[0];
    console.warn(`[lottery] no ball named "${winnerName}", using fallback and renaming`);
    target.name = winnerName;  // 強制更新名字！
    target.label = shortLabel(winnerName);
}
```

涉及的函數：
- `lottoAir.pickBall()` - lotto_air / lotto2 模式
- `revealWithLotto()` - slot 模式的樂透揭曉
- `revealWithLotto3()` - lotto3 模式

## Debugging Tips

- Session issues: Ensure consistent host usage (always `127.0.0.1` OR `localhost`, not mixed)
- Real-time not working: Check Reverb is running and `.env` REVERB_* settings match
- Check server: `lsof -nP -iTCP:8007 -sTCP:LISTEN`
