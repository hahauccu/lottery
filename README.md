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

SEO 回歸驗證：`php artisan test --filter=DemoLotteryPagesTest`，涵蓋所有風格、JSON-LD、索引控制、試玩與範本導向。

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
