# AGENTS.md

這份文件是給 opencode / coding agent 使用的「專案技能（agent-skill）」。

## 專案概覽

- Framework: Laravel 11 + Breeze（前台登入/註冊）
- Admin: Filament（多租戶 tenant=Organization）
- Lottery frontend: Blade + JS（`resources/js/lottery.js`）
- Realtime: Laravel Reverb + Echo

## 開發啟動（本機）

在專案根目錄（本檔同層）執行：

1) 後端
- `php artisan serve --host=127.0.0.1 --port=8007`

2) 前端（Vite）
- `npm run dev`

3) Reverb（WebSocket）
- `php artisan reverb:start`

建議固定同一個 host（`127.0.0.1` 或 `localhost` 擇一）避免 session cookie 不一致。

## 入口與路由

### Filament 後台（抽獎設定選單在這）

- 後台入口：`/admin`
- 後台路由會導向 tenant：`/admin/{tenant}`
- 抽獎設定：
  - 抽獎活動：`/admin/{tenant}/lottery-events`
  - 獎項：`/admin/{tenant}/prizes`

Filament panel 設定位置：`app/Providers/Filament/AdminPanelProvider.php`

### 抽獎前台（實際抽獎畫面）

- 抽獎畫面：`GET /{brandCode}/lottery`
- 執行抽獎：`POST /{brandCode}/draw`
- 中獎名單：`GET /{brandCode}/winners`

路由定義位置：`routes/web.php`

## 認證行為（重要）

本專案已調整：登入/註冊成功後導向 Filament 後台入口 `/admin`（而不是 Breeze 的 `/dashboard`）。

- 登入後導向：`app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- 註冊後導向：`app/Http/Controllers/Auth/RegisteredUserController.php`

## 常見除錯

### 1) `php artisan serve --port=8007` 連不上

- 確認真的在監聽：`lsof -nP -iTCP:8007 -sTCP:LISTEN`
- 本機測試：`curl -I http://127.0.0.1:8007`
- 避免用別台設備連 `php artisan serve`（預設只綁 127.0.0.1）。若要外部連線需用：
  - `php artisan serve --host=0.0.0.0 --port=8007`

### 2) 登入後看不到後台/一直回登入

- 確認你從頭到尾使用同一個 host：
  - 全程 `http://127.0.0.1:8007/...` 或全程 `http://localhost:8007/...`

### 3) 抽獎頁即時更新不動

- 確認 Reverb 有跑：`php artisan reverb:start`
- 確认 Vite 有跑：`npm run dev`
- 確認 `.env` 的 Reverb/Echo 設定與目前 host/port 一致。

## 建議的 agent 工作方式

- 查路由：優先用 `php artisan route:list` + 關鍵字過濾
- 查 Filament 資源：`app/Filament/Resources/**`
- 查抽獎流程：`app/Http/Controllers/LotteryFrontendController.php`、`app/Services/LotteryDrawService.php`
- 不要改動無關功能；修 bug 優先找 root cause
