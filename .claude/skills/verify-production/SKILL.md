---
name: verify-production
description: 部署到正式站後，使用 Chrome 瀏覽器自動化工具驗證上版功能沒有錯誤。當用戶要求「驗證正式站」、「檢查正式站」、「確認上版」、「production check」時使用。
---

# 正式站上版驗證

本 skill 使用 `mcp__claude-in-chrome__*` 工具連線到正式站，驗證本次上版的功能是否正常。

## 正式站資訊

- **網址**：<https://lottery.dindin168.com>
- **後台登入**：<https://lottery.dindin168.com/admin>
- **Demo 入口**：<https://lottery.dindin168.com/demo/lottery>
- **帳號密碼**：見同目錄 `credentials.local.md`（已 gitignore）

## 使用時機

- 剛推送 code 到正式站之後
- 用戶說「驗證一下」、「看看有沒有問題」、「上版測試」等
- 對 production 功能有疑慮時

## 執行流程

### 1. 取得 Chrome tab 連線

```
mcp__claude-in-chrome__tabs_context_mcp(createIfEmpty: true)
```
若沒連線就告知用戶需要啟動 Claude-in-Chrome extension，不要猜測或跳過。

### 2. 確認本次上版範圍

先跑 `git log main..HEAD --oneline` 或 `git log -5 --oneline`，列出這次要驗證的 commit，依功能擬定驗證清單。

### 3. 基本 smoke test（每次都跑）

| 檢查項目 | 方式 |
|---------|------|
| 首頁可載入 | `navigate` → `https://lottery.dindin168.com/` |
| Console 無錯誤 | `read_console_messages(onlyErrors: true)` |
| 網路請求無 5xx | `read_network_requests`（若可用）|
| Demo 列表可開 | `navigate` → `/demo/lottery` |
| 至少一種動畫能進入抽獎 | `navigate` → `/demo/lottery/lotto-air` |

### 4. 依 commit 範圍做針對性驗證

根據 `git log` 找到的 commit 類型：

- **lottery/animation 類**（如 `fix(lottery)`, `feat(lottery)`）：
  - 進入對應 Demo 動畫頁
  - 設定人數、觸發抽獎
  - 觀察是否卡住、console 錯誤
  - 手機模擬：用 `resize_window` 切 390x844 再跑一次
- **admin/filament 類**：
  - 登入後台（用 `credentials.local.md` 的帳密）
  - 瀏覽有改動的頁面
  - 點擊主要 CTA（如下載、發送通知）
- **seo/sitemap 類**：
  - `navigate` → `/sitemap.xml`、檢查內容
- **payment/webhook 類**：不要在 prod 實際觸發金流，僅檢查頁面可載入

### 5. 手機版驗證（建議）

用 `resize_window(390, 844)` 模擬手機，重跑首頁與 Demo 動畫頁，確認：
- 沒有橫向溢出
- 主要 CTA 可點到
- 動畫能啟動且完成

### 6. 產出驗證報告

簡短列出：
- ✅ 通過的項目
- ⚠️ 警告（console 有非致命錯誤）
- ❌ 失敗的項目（含截圖/錯誤訊息）
- 建議下一步

## 安全規範

- **不要在 production 執行破壞性操作**：刪除、批次送出通知、金流結帳等需使用者明確確認
- **不要儲存登入後的 session 到版控檔案**
- **避免連續重試登入** — 超過 2-3 次失敗就停下來問用戶

## 遇到 Chrome extension 未連線時

告訴用戶：
> Claude-in-Chrome 擴充未連線，請在瀏覽器啟動擴充後再重試。

不要改用 `curl` 或其他方式硬刷 — 本 skill 目的就是用真實瀏覽器驗證 JS/DOM 行為。

## 關聯檔案

- `credentials.local.md` — 後台帳密（gitignored）
