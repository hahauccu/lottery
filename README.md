# 抽獎系統 Lottery App — 專案總覽（PM 必讀）

> 這份文件的目標：**任何人或 AI 讀完，就能成為最懂這個專案的 PM。**
> 只放「重點與全貌」，深入的程式細節與前端狀態規範請看 [`CLAUDE.md`](./CLAUDE.md)。
>
> 🔴 **最重要的一條規則：開發任何新功能 / 改動商業邏輯時，請回頭更新這份 README 與 `CLAUDE.md`。**
> 文件過期 = 下一個人（或 AI）誤判全貌。把「更新文件」當成功能完成的一部分。

---

## 1. 一句話定位

> 給「組織 / 企業」使用的多租戶**線上抽獎系統**：後台設定活動與獎項，前台在尾牙、活動現場以動畫即時開獎，中獎者可掃 QR 兌獎，並提供免登入試玩（Demo）與訂閱付費。

---

## 2. 使用者角色與旅程

| 角色 | 在做什麼 | 入口 |
|------|---------|------|
| **組織管理員 (Admin)** | 在後台建立抽獎活動、獎項、匯入名單、設定動畫、訂閱付費 | `/admin/{tenant}/...`（Filament 後台） |
| **現場操作員 (Operator)** | 活動現場控制開獎節奏（需 Operator 存取碼） | `/{brandCode}/lottery` + 存取碼 |
| **觀眾 / 大螢幕** | 看即時抽獎動畫與中獎名單 | `/{brandCode}/lottery`、`/{brandCode}/winners` |
| **中獎者** | 掃描 QR 兌獎、驗證身分 | `/claim/{token}` |
| **訪客（潛在客戶）** | 免登入試玩各種動畫風格、套用範本 | `/demo/lottery` |

**核心金流旅程：** 註冊 → 取得試用 → 建活動試用 → 訂閱付費（綠界 ECPay）→ 解鎖額度限制。

---

## 3. 商業模式（別漏掉這塊）

- **多租戶 SaaS**：一個 `Organization` = 一個租戶，使用者透過 pivot 歸屬組織。
- **訂閱制**：`SubscriptionPlan` 定義方案與額度（如每場活動最大獎項數 `maxPrizesPerEvent`）。
- **試用**：`SignupTrialService` 在註冊時給予試用權限。
- **金流**：綠界 ECPay（`EcpayService` / `PaymentController`）。
  - `POST /payment/ecpay/notify`：Server-to-Server 通知（**已排除 CSRF**，勿加 auth）。
  - `POST/GET /payment/ecpay/result`：使用者付款後返回頁。
  - 沙箱設定看 `.env` 的 `ECPAY_*`。
- **額度守門**：`EnsureSubscription` middleware + `Organization::hasValidSubscription()` 控制能否使用功能。

---

## 4. 系統資料地圖

```
Organization (租戶)
 ├─ users (多對多)
 ├─ employees ── employeeGroups        ← 抽獎名單（可分組）
 ├─ subscriptions ── SubscriptionPlan  ← 訂閱與額度
 └─ lotteryEvents (抽獎活動)
      ├─ rules (EventRule)             ← 活動層級資格篩選
      ├─ currentPrize                  ← 目前正在抽的獎項
      └─ prizes (獎項)
           ├─ rules (PrizeRule)        ← 獎項層級資格篩選
           ├─ audioSetting             ← 音效
           └─ winners (PrizeWinner) ── Employee  ← 中獎紀錄（含兌獎狀態 / token）
```

**抽獎資格**怎麼算 → `EligibleEmployeesService`（套用 Event + Prize 規則）。
**抽獎怎麼抽** → `LotteryDrawService`（核心）；重抽 → `PrizeRedrawService`。

---

## 5. 功能模組清單（含「去哪找」）

| 模組 | 做什麼 | 主要進入點 |
|------|--------|-----------|
| **後台管理** | 活動 / 獎項 / 員工 / 分組 / 組織 / 方案 CRUD | `app/Filament/Resources/*` |
| **前台抽獎** | 公開大螢幕、即時開獎、操作控制 | `LotteryFrontendController` + `resources/js/lottery.js` |
| **抽獎核心邏輯** | 抽出中獎者、模式控制 | `LotteryDrawService`、`PrizeRedrawService` |
| **Demo 試玩** | 免登入體驗動畫、套用 / 回報範本 | `DemoLotteryController`、`DemoLotteryTemplateController`、`resources/js/demo-lottery.js` |
| **QR 兌獎** | 中獎者掃碼驗證領獎 | `ClaimController`、`/claim/{token}` |
| **彈幕 Danmaku** | 現場觀眾發送彈幕（有 throttle） | `LotteryFrontendController@sendDanmaku`、`resources/js/danmaku.js` |
| **得獎名單匯出** | 匯出中獎者 | `LotteryWinnerExportController`（Excel/CSV） |
| **抽獎數據分析** | 背景跑分析、下載報告 | `LotteryAnalysisService`、`LotteryAnalysisController`、Job `RunLotteryAnalysis` |
| **金流 / 訂閱** | 綠界付款、方案管理 | `PaymentController`、`SubscriptionService`、`EcpayService` |
| **通知** | 中獎 Email / 通知 | Jobs `SendWinnerNotification`、`SendPrizeNotifications` |
| **SEO** | sitemap、FAQ、landing | `SitemapController`、`FaqController`、`home.blade.php` |

---

## 6. 即時通訊（WebSocket）

用 **Laravel Reverb** 廣播，前端 **Laravel Echo**（`resources/js/echo.js`）接收。

| 事件 | 何時觸發 | 前端反應 |
|------|---------|---------|
| `LotteryEventUpdated` | Admin 改活動 / 獎項設定 | 更新畫面（抽獎中會忽略，避免覆蓋） |
| `PrizeWinnersUpdated` | 抽出中獎者 | 更新中獎名單、剩餘人數、獎項進度 |
| `DanmakuSent` | 觀眾發彈幕 | 顯示彈幕 |

- 頻道為公開 `lottery.{brand_code}`，活動代碼有全站唯一索引；抽獎鎖與重送防護依活動 ID、心跳依活動代碼區分。正常各自訂閱時不同公司不共用頻道或抽獎鎖。公開頻道不等於租戶讀取授權，活動 HTTP payload 目前也可公開讀取。
- 多公司隔離回歸：`php artisan test --filter=LotteryBroadcastIsolationTest`、`node --test tests/JavaScript/broadcast-isolation.test.mjs`。2026-10-20 本機另以獨立 Reverb 驗證雙連線、100 筆交錯彈幕與重連；大名單可能超過預設 10,000 bytes 的 Reverb HTTP 上限，且目前 winners 廣播例外可能在結果寫入後中斷抽獎回應。調查與待處理項目見 `todo/websocket-multi-company-audit.md`。

⚠️ 前端狀態與 WebSocket 有競爭條件，新增動畫前**務必先讀 `CLAUDE.md` 的「前端狀態管理注意事項」**。

---

## 7. 抽獎動畫風格

定義在 `app/Support/AnimationStyles.php`，每場獎項可獨立設定風格與停留時間。目前支援：

`lotto_air` 樂透氣流機 · `red_packet` 紅包雨 · `scratch_card` 刮刮樂 · `treasure_chest` 寶箱 · `big_treasure_chest` 大寶箱 · `marble_race` 圓球賽跑 · `battle_top` 戰鬥陀螺

**抽獎模式**：`all_at_once`（一次全抽）/ `one_by_one`（逐一抽）。

> 要**新增動畫風格**？用 `add-lottery-animation` skill，並逐項對齊 `CLAUDE.md` 的檢查清單（render / draw / stop / payload reset 等）。

---

## 8. 技術棧與啟動

**Stack:** Laravel 12 · Filament 3（後台）· Laravel Reverb（WebSocket）· Blade + Tailwind + Alpine.js · Vite · SQLite（預設）· 綠界 ECPay 金流。

```bash
composer dev          # 一鍵啟動全部（建議）
# 或分開啟：
php artisan serve --host=127.0.0.1 --port=8007   # 後端
npm run dev                                        # 前端資產
php artisan reverb:start                           # WebSocket

php artisan migrate                                # 資料庫
php artisan test                                   # 測試
node --test tests/JavaScript/*.test.mjs              # 動畫完成通知、尺寸切換與 API 結果收尾
./vendor/bin/pint                                  # 格式化
php artisan route:list | grep lottery              # 看路由
```

**踩雷提醒：** host 要一致（全用 `127.0.0.1` 或全用 `localhost`，別混用，否則 session 出問題）；即時更新失靈先確認 Reverb 有跑且 `.env` 的 `REVERB_*` 正確。

---

## 9. 路由速查

| 用途 | 路由 |
|------|------|
| 首頁 / 行銷頁 | `GET /` |
| 後台 | `/admin/{tenant}/...` |
| 前台抽獎大螢幕 | `GET /{brandCode}/lottery` |
| 開獎（需 Operator 碼） | `POST /{brandCode}/draw` |
| 中獎名單 | `GET /{brandCode}/winners` |
| QR 兌獎 | `GET /claim/{token}` |
| Demo 試玩 | `GET /demo/lottery`、`/demo/lottery/{slug}` |
| 綠界通知 | `POST /payment/ecpay/notify` |

完整清單：`php artisan route:list`。

### Demo 介紹頁與試玩頁

- `/demo/lottery` 是抽獎動畫總覽，提供七種風格的介紹與直接試玩入口。
- `/demo/lottery/{slug}` 是可索引的輕量介紹頁：獨立標題、說明、動畫示意圖、操作步驟與適用情境，不載入抽獎 JavaScript。
- `/demo/lottery/{slug}/play` 是互動試玩頁，設定 `noindex, follow`，不設指向介紹頁的 canonical，也不列入 sitemap；分享用 OG 資料仍保留。
- 介紹頁的 Product／Offer 僅描述免登入免費 Demo（NT$0），不代表正式活動所有功能免費。不填虛構評分或自動展延的價格期限。
- 舊 `?embedded=1` 網址轉往 `/play?embedded=1`；兩種頁面網址的 GET payload／JSON 回應均提供 `X-Robots-Tag: noindex`。
- 分享範本套用後直接前往 `/play`，保留同風格的 Demo session 設定。
- sitemap 僅列公開搜尋入口；未追蹤可靠的逐頁更新日期前省略 `lastmod`，不以部署或前端 build 時間代替內容更新時間。
- FAQ 保留作為訪客說明；不以 Google FAQ 複合式結果作為成效指標。
- 戰鬥陀螺與圓球賽跑提供設定／動畫／結果及手機實圖，檔案位於 `public/images/demos/{key}/`，由 `DemoPreviews` 讀取；均使用合成名單。介紹頁圖片有固定尺寸，非首屏圖片延遲載入，不內嵌試玩 iframe。

SEO 回歸驗證：`php artisan test --filter=DemoLotteryPagesTest`，涵蓋所有風格、JSON-LD、索引控制、試玩與範本導向。

### 尾牙抽獎內容指南

- `/guides/year-end-lottery` 提供名單與資格、重複中獎規則、主持流程範例、七種動畫比較、彩排與現場檢查清單。內容以現有功能為依據，流程範例不代表自動套用活動設定。
- 指南由 Blade 直接輸出，無抽獎 JS／iframe；具有獨立 title／description、self canonical、Article 與 BreadcrumbList，列入 sitemap。首頁、動畫總覽、FAQ 與七種動畫介紹頁提供可爬取連結。
- `AnimationStyles::all()` 的 `planningTips` 提供各動畫獨立使用提醒；秒數說明必須反映現況，不可把 pending 的時間一致化方案寫成已完成，也不保證任意裝置流暢或任意名單容量。
- 不為內容新增虛構評分、作者資歷、日期或 `lastmod`。回歸：`php artisan test --filter='LotteryGuideTest|DemoLotteryPagesTest'`。

### Demo 試玩成效

- 首頁、動畫總覽、介紹頁與非 embedded 試玩頁使用輕量 `demo-metrics.js`，透過 `POST /demo/metrics` 記錄瀏覽、試玩／註冊入口點擊、抽獎開始及動畫完成／失敗。正式品牌活動不啟用；統計送出失敗不影響抽獎。
- 僅按台灣日期、動畫、入口與事件累加次數和動畫耗時，不保存 IP、訪客識別碼、網址參數、名單或 Email；尊重 Do Not Track／Global Privacy Control。IP 僅供 Laravel 快取中的每分鐘 60 次頻率限制。
- 系統管理員（`is_admin`）在後台「系統管理 → 試玩成效」查看近 7／28 天。這是事件次數，不是獨立訪客漏斗；註冊點擊不代表註冊成功，開始／完成可能跨日或因離頁、網路與拒絕追蹤而有落差。
- 上版需執行 `php artisan migrate` 建立 `demo_metrics`，並以 `npm run build` 包含新增入口。初次上版沒有歷史資料，GSC 排名／收錄仍需上版後另行觀察。
- 回歸：`php artisan test --filter=DemoMetricsTest`、`node --test tests/JavaScript/*.test.mjs`。預設抽獎須還原 30 人／抽 5 人／一次全抽，不沿用上次自訂設定。

### 意見回饋與聯絡我們

- `/feedback` 免登入收集動畫卡住、顯示異常、功能建議與其他意見；首頁頁尾、Demo 總覽與試玩工具面板提供入口。此公開頁可索引並列入 sitemap（含尾牙指南目前共 13 個網址）。
- Email 選填；記錄來源公開頁面的路徑、動畫與視窗尺寸，不收集網址 query、存取碼或參加者名單。回饋不公開。
- 先存入 `feedback` 資料表再寄通知，寄信失敗不影響收件；系統管理員（`is_admin`）可在後台「系統管理 → 意見回饋」標記待處理／已處理與內部備註，一般租戶無權讀取。
- 通知收件人設定：`FEEDBACK_NOTIFICATION_EMAIL`；預設沿用客服信箱，留空可停用通知。仍需正確設定 `MAIL_*`；`MAIL_MAILER=log` 只寫入本機 log，不代表已寄達。
- 上版需執行 `php artisan migrate` 建立資料表；回歸驗證：`php artisan test --filter=FeedbackTest`。

---

## 10. 🔴 開發守則：改了什麼，就更新什麼文件

新功能 / 改動上線前，請確認下列文件已同步（這是 Definition of Done 的一部分）：

- [ ] **本 README**：新增模組、角色、商業邏輯、路由 → 更新對應章節。
- [ ] **`CLAUDE.md`**：改前端狀態、動畫、WebSocket 事件、後端廣播 payload → 更新規範與檢查清單。
- [ ] 新增**動畫風格** → 跑 `add-lottery-animation` skill，並對齊 `CLAUDE.md` 檢查清單。
- [ ] 新增/改 **WebSocket 事件** → 同步本檔第 6 節 + `CLAUDE.md`「後端事件廣播」。
- [ ] 改**金流 / 訂閱**邏輯 → 同步第 3 節，並確認 ECPay 端點的 CSRF 排除設定。

> 文件與程式分工：
> **README.md = 全貌 / 商業 / 模組地圖（PM 視角）**；
> **CLAUDE.md = 實作細節 / 前端狀態 / 動畫規範（工程視角）**。
> 兩份都要保持新鮮。
