# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Language

請使用繁體中文回覆。
這是一個laravel 專案
如果在接收到一個任務但是不知道在哪的話 可以先看看route 再在用你原本的找法

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

### Demo SEO 與互動頁邊界

- `AnimationStyles::all()` 管理各風格的 `seoTitle`／`seoDescription`；七種風格維持原 slug，不因拆頁變更搜尋入口。
- `/demo/lottery/{slug}` 使用 `lottery.intro`：可索引、self canonical、完整伺服器渲染內容；不可載入 `lottery.js` 或初始化抽獎 session。只有 payload／JSON 請求例外。
- `/demo/lottery/{slug}/play` 使用 `lottery.show`：`noindex, follow`，不輸出 canonical 或 Product／FAQ JSON-LD，保留描述與 OG 分享資料。正式品牌抽獎畫面仍為 `noindex, nofollow`。
- `/play` 不顯示舊版 SEO 導覽浮層或長文，避免手機動畫被覆蓋；切換與重新設定由 Demo 工具選單提供。
- 「查看介紹」連向介紹頁；「立即試玩」與範本套用連向 `/play`；舊 embedded 重新導向須保留 `embedded=1`。
- GET payload／JSON 回應使用 `X-Robots-Tag: noindex`；不在 robots.txt 封鎖試玩頁，以便爬蟲讀到 noindex。
- 介紹頁 Product 的零元 Offer 僅對應可見的免費 Demo 說明，不虛構評價、庫存期限或正式方案價格；動畫示意圖位於 `public/images/previews/{key}.svg`。
- sitemap 不列試玩／payload 網址；尚無逐頁內容日期來源，省略 `lastmod`，不可使用 build manifest 或部署檔案 mtime 代替。
- 修改上述契約時，同步更新 README 及 `tests/Feature/DemoLotteryPagesTest.php`。
- `DemoPreviews` 只在四張實圖齊全時回傳預覽；戰鬥陀螺／圓球賽跑使用 `public/images/demos/{key}/{setup,action,result,mobile}.jpg`，皆為合成名單的真實截圖。首屏高優先載入，圖集延遲載入並標註尺寸；OG 維持既有 SVG。

### Demo 匿名成效邊界

- `demo-metrics.js` 僅在 `#demo-metrics` 設定存在時啟用，介紹頁可載入此輕量模組，但不得因此載入 `lottery.js`。正式活動與 embedded 預覽不啟用統計；Do Not Track／GPC 時不送出。
- `DemoMetricController` 僅接受事件、動畫、入口與限額耗時；`DemoMetric::record` 依台灣日期及組合鍵原子累加。不可持久保存原始 payload、名單、Email、IP、網址參數或新增訪客識別碼。
- 抽獎開始後，每次執行只記錄一個完成或失敗事件；保護性收尾、取消或過期執行記失敗，不可因統計送出失敗影響結果。跨頁離開可能沒有結束事件，不能將事件次數當同一批訪客轉換率。
- `DemoMetricsPage` 全站資料必須由 `is_admin` 授權；7／28 天使用台灣日期，註冊入口點擊不可標成註冊成功。回歸測試 `DemoMetricsTest` 與 `tests/JavaScript/demo-metrics.test.mjs`。
- `demo-style-toolbar` 預設抽獎透過 configure 明確重設空名單、5 人及一次全抽；空名單由 controller 還原預設 30 人，不能以 reset 冒充預設設定。

### 活動指南與內容事實

- `/guides/year-end-lottery` 為 `Route::view` 的公開 Blade 指南，self canonical、Article／BreadcrumbList、無外部 JS 或抽獎 session 初始化。首頁、總覽、FAQ、七種介紹頁均須保留指南連結；指南連回動畫介紹頁與試玩總覽。
- `AnimationStyles::all()` 的 `planningTips` 是各風格的活動安排提醒；渲染於介紹頁，不加到 `/play`。手動刮除、搶紅包、自訂紅包圖樣、保證容量／效能等未證實功能不能寫成既有能力。
- 時間相關文案描述目前引擎：圓球秒數影響重力、不改地圖長度；陀螺另有倒數入場與展示；刮刮樂與寶箱多人可能分批或逐位呈現。時間標準仍在 `todo/animation-timing-standard-pending.md`，不可當成已上線規格。
- 指南流程範例為編排建議，不是新產品功能。結構化資料與可見內容一致，不編造日期、評價或人員資歷；sitemap 仍不使用部署時間生成 lastmod。
- 回歸：`LotteryGuideTest` 檢查索引與結構化資料、目錄錨點及雙向內部連結；`DemoLotteryPagesTest` 檢查 13 個 sitemap 入口與 `/play` 邊界。

### 意見回饋邊界

- `FeedbackController` 的 `/feedback` 為公開 GET／POST，POST 有 CSRF、每 IP 10 分鐘 5 次與蜜罐欄位；僅保存驗證通過的表單欄位，不接受訪客寫入 status、admin_notes 或 notified_at。
- 來源路徑以 `Feedback::sourcePaths()` 白名單驗證，不保存 query/token；Email 選填。先寫資料庫，再寄 `FeedbackReceivedMail`，通知失敗保留資料；`notified_at` 只表示 Mail transport 已接受寄送，log mailer 不表示實際投遞。
- `FeedbackResource` 不綁租戶，必須由 `is_admin` 授權 list/edit；不得讓一般組織管理員瀏覽全站回饋。原始回饋欄位唯讀，只能修改狀態與內部備註，無公開讀取端點。
- 回歸測試：`FeedbackTest` 涵蓋匿名提交、通知失敗、Email 選填、來源資訊驗證、XSS、頻率限制，以及一般租戶禁止讀寫與系統管理員處理。

### Real-time Updates
Events broadcast via Laravel Reverb:
- `LotteryEventUpdated` - when admin changes event/prize settings
- `PrizeWinnersUpdated` - when winners are drawn

Frontend (`resources/js/lottery.js`) listens via Laravel Echo and updates UI.

### 多公司 WebSocket 隔離與已知限制

- 三種抽獎事件與 Echo 均使用公開頻道 `lottery.{brand_code}`；代碼由資料庫全站唯一索引約束。不可把頻道改成全域共用名稱；抽獎鎖、dedup、切獎 ACK、心跳與操作碼須維持活動範圍。
- 公開 Channel 不會做會員／Operator 訂閱授權；HTTP `/{brandCode}/lottery?payload=1` 也是公開讀取。若加入私密投影／公司資料讀取授權，必須同時處理頻道與 HTTP 快照，不能只改成 PrivateChannel 後宣稱隔離完成。
- 已確認限制：含完整 eligible_names 的大型廣播可能超過 Reverb HTTP 預設 10,000 bytes；`draw()` 保存 winners 後同步派送 `PrizeWinnersUpdated`，目前未捕捉其廣播例外。此項尚未修正，不可把廣播成功當成結果保存成功的必要條件。
- 回歸測試：`LotteryBroadcastIsolationTest`、`tests/JavaScript/broadcast-isolation.test.mjs`。調查證據與後續建議：`todo/websocket-multi-company-audit.md`；本機隔離測試不代表正式環境尖峰容量測試。

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
- 輪詢回應也要檢查「回應當下」是否正在抽獎；不能只使用請求送出時的 `isDrawing`。請求期間若 `drawRunId` 改變，丟棄該舊快照，避免延遲回應在揭曉後清掉結果。

### 動畫完成與異常收尾契約

- API 成功回傳後，`draw()` 保留本輪完整得獎者；動畫正常結束、拋錯或超時，皆以該結果收尾。不得因動畫失敗重抽、只留下已揭曉的部分名單，或直接 reload。
- 逐一抽出保留既有得獎紀錄；同姓名的不同得獎紀錄不可依姓名去重。
- 每次球完成只解除一次等待；同幀多球完成必須保存尚未消費的通知，不能依 `waiters.length` 推算已消費數量。樂透依 API 選球順序通知，避免出球順序顛倒造成姓名與清單不同步。
- `stop()`／reset 必須解除舊等待並清掉佇列與完成通知；過期 `drawRunId` 的 finally 不得操作新一輪的音效、心跳、抽獎狀態或名單。
- 圓球賽跑障礙物間距須容納球徑及抖動餘裕；resize 保留球的相對賽道位置。內層安全期限為設定秒數的兩倍，外層期限至少再留 15 秒，最低 75 秒。
- 戰鬥陀螺設定秒數代表戰鬥階段（5–60 秒），另含倒數、入場與揭曉；不再依參加人數無上限延長。resize 必須同步轉換實體座標，包含已揭曉得主。
- 回歸測試：`node --test tests/JavaScript/*.test.mjs`，直接執行正式 JS 模組的狀態機／物理與共用 draw 流程。

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
