# 中獎清單下載功能規劃

## 目標

新增後台下載中獎清單功能，提供管理者匯出指定抽獎活動的中獎紀錄。

下載內容需包含：

- 抽獎時間
- 中獎人
- 若有不在場重抽，需一併顯示原始中獎者與補抽結果

## 下載格式

第一版先實作 `CSV`。

原因：

- 實作最精簡
- 後台下載流程可先上線驗證需求
- 後續若需要 `xlsx`，可再沿用既有 `maatwebsite/excel` 擴充

## 下載入口

下載入口放在後台「領獎管理」頁面最合適。

建議位置：

- `app/Filament/Resources/LotteryEventResource/Pages/ManageClaims.php`

原因：

- 此頁本來就面向管理者
- 已集中顯示中獎、通知、領獎狀態
- 管理者在處理不在場重抽後，可直接下載最新結果

## 現有資料結構判讀

目前資料表已足夠支撐下載需求，不需要新增欄位。

### 原始中獎紀錄

`prize_winners` 已記錄：

- `sequence`
- `won_at`
- `released_at`
- `release_reason`
- `replacement_for_winner_id`

相關檔案：

- `database/migrations/2026_01_14_094304_create_prize_winners_table.php`
- `database/migrations/2026_03_13_100000_add_release_fields_to_prize_winners_table.php`
- `app/Models/PrizeWinner.php`

### 不在場重抽的記錄方式

目前不在場重抽不是覆蓋原資料，而是保留完整歷程：

1. 原中獎者被釋出時，寫入：
   - `released_at`
   - `release_reason`
   - `released_by_user_id`
2. 補抽者建立新的一筆 `PrizeWinner`
3. 補抽者的 `replacement_for_winner_id` 指向被釋出的那筆中獎紀錄

相關檔案：

- `app/Services/PrizeRedrawService.php`
- `app/Services/LotteryDrawService.php`

這代表下載報表時，可以同時呈現：

- 原始中獎者
- 被取消資格的時間與原因
- 補抽上來的新中獎者
- 補位關聯

## 注意事項

目前前台中獎名單與部分查詢使用的是 `Prize::winners()`，只會取出 `released_at` 為空的有效中獎者：

- `app/Models/Prize.php`

因此下載功能不能直接沿用前台顯示資料，否則會漏掉被取消資格的原始中獎者。

下載必須改查：

- `PrizeWinner` 全量紀錄

也就是要使用：

- `Prize::allWinnerRecords()`
  或
- 直接查 `PrizeWinner` 並依 `lottery_event_id` 篩選

## CSV 欄位建議

建議採用「一列代表一筆中獎紀錄」而不是「一列代表一個名額」。

原因：

- 同一個序號可能經歷原始中獎、取消資格、補抽
- 若只保留每個序號最後結果，會遺失抽獎歷程

建議欄位如下：

| 欄位 | 說明 |
| --- | --- |
| 獎項 | 獎項名稱 |
| 序號 | `sequence` |
| 抽獎時間 | `won_at` |
| 中獎人 | 員工姓名 |
| Email | 員工 Email |
| 部門 | 員工部門 |
| 記錄類型 | `原始中獎` / `重抽補位` |
| 目前狀態 | `有效` / `已釋出` |
| 資格取消時間 | `released_at` |
| 取消原因 | 例如 `不在場重抽` |
| 補位自誰 | 此筆若為補抽，顯示原始被釋出者 |
| 補位給誰 | 此筆若已被替補，顯示其替補者 |

### 欄位判斷規則

- `記錄類型`
  - `replacement_for_winner_id` 有值：`重抽補位`
  - 否則：`原始中獎`
- `目前狀態`
  - `released_at` 有值：`已釋出`
  - 否則：`有效`
- `取消原因`
  - 若 `release_reason = absent`，建議輸出 `不在場重抽`
  - 其他值保留原始內容，避免未來擴充時失真

## 後端實作建議

### 1. 新增下載路由

建議新增一條需登入的後台下載路由，例如：

- `GET /lottery-events/{event}/winners/download`

建議放在：

- `routes/web.php` 的 `auth` middleware 區塊內

### 2. 新增下載 Controller

建議新增獨立 controller，例如：

- `app/Http/Controllers/LotteryWinnerExportController.php`

職責：

- 驗證登入者是否屬於該活動的組織
- 撈取完整中獎資料
- 以 `response()->streamDownload()` 輸出 CSV

### 3. 權限驗證

可比照分析報表下載邏輯：

- `app/Http/Controllers/LotteryAnalysisController.php`

驗證原則：

- 使用者需已登入
- 使用者必須屬於該 `LotteryEvent` 的 `organization`
- 不符合則回傳 `403`

### 4. 查詢資料方式

建議主查詢：

- `PrizeWinner::query()`

關聯建議預載：

- `prize`
- `employee`
- `replacementFor.employee`
- `replacement.employee`

篩選條件：

- 僅取指定 `LotteryEvent` 底下的獎項紀錄

排序建議：

1. `prize.sort_order`
2. `sequence`
3. `won_at`
4. `id`

## 後台 UI 實作建議

在 `ManageClaims` 的 header actions 加上一個下載按鈕：

- 按鈕名稱：`下載中獎清單`
- 下載格式：CSV
- 行為：直接導向下載 route

建議和既有「發送所有未通知」按鈕並列。

## 範例輸出情境

假設某獎項序號 `#3` 發生不在場重抽：

1. `王小明` 先中獎，`won_at = 2026-04-07 10:00:00`
2. 因不在場被取消，`released_at = 2026-04-07 10:03:00`
3. `陳小華` 補抽中獎，`replacement_for_winner_id` 指向 `王小明`

CSV 會有兩列：

### 第一列

- 序號：`3`
- 中獎人：`王小明`
- 記錄類型：`原始中獎`
- 目前狀態：`已釋出`
- 資格取消時間：`2026-04-07 10:03:00`
- 取消原因：`不在場重抽`
- 補位給誰：`陳小華`

### 第二列

- 序號：`3`
- 中獎人：`陳小華`
- 記錄類型：`重抽補位`
- 目前狀態：`有效`
- 補位自誰：`王小明`

## 測試建議

建議至少補兩個 Feature Test：

1. 權限測試
   - 非同組織使用者下載應回 `403`

2. 重抽紀錄測試
   - 建立一筆被釋出的原始中獎者
   - 建立一筆補抽中獎者
   - 驗證 CSV 同時包含兩筆資料與正確欄位值

## 第一版實作範圍

第一版建議只做以下內容：

- 後台領獎管理頁提供 CSV 下載
- 匯出完整中獎歷程
- 包含不在場重抽紀錄
- 完成基本權限驗證

先不做：

- 前台下載
- 多格式下載切換
- 自訂欄位勾選
- Excel 樣式美化

## 結論

這個功能可以直接利用現有 `PrizeWinner` 資料結構完成，不需要新增 migration。

真正的關鍵是：

- 下載必須查全部中獎紀錄，而不是只查目前有效中獎者
- 報表要能保留原始中獎與補抽之間的關聯

依照目前專案結構，最合適的實作位置是：

- 後台 `ManageClaims` 頁面提供下載按鈕
- 新增獨立下載 controller 輸出 CSV
