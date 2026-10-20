# 兩家公司同時抽獎：WebSocket 隔離確認

- 日期：2026-10-20。
- 分支：`codex/seo-demo-conversion`。
- 狀態：**功能隔離已驗證；大訊息與廣播失敗回應問題待修正；私密讀取授權需另定需求**。
- 本次新增回歸測試與調查紀錄，未修改正式抽獎、廣播或授權流程，也未操作正式站活動。

## 結論與證據範圍

兩家公司正常開啟各自活動頁、訂閱各自活動代碼時，不會因共用一個 Reverb 服務而自然混收結果；抽獎鎖也按活動分開。

本次驗證包含 HTTP／資料庫隔離測試、執行正式前端 listener 的 VM 測試，以及實際 Reverb WebSocket 傳輸。實際 socket 測試有兩個連線同時存活，A／B 廣播交錯送出；不是正式環境的多程序 HTTP／資料庫競爭壓測，不能據此保證任意連線數或尖峰延遲。

## 已確認的隔離設計

| 項目 | 隔離方式 |
|---|---|
| 活動代碼 | `lottery_events.brand_code` 有全站 unique 索引，後台建立時產生 |
| 即時訊息 | `LotteryEventUpdated`、`PrizeWinnersUpdated`、`DanmakuSent` 都送往 `lottery.{brand_code}` |
| 前端訂閱 | Echo 訂閱目前頁面的 `config.brandCode`；winners 更新另核對 prize_id |
| 抽獎鎖 | `draw:{event_id}`，持鎖期間不會鎖住另一活動 |
| 重送防護 | `draw-dedup:{event_id}:{run_id}`，兩活動可使用相同 run_id |
| 心跳／前台就緒 | `lottery-drawing:{brand_code}`、`lottery-ready:{brand_code}` |
| 切獎 ACK | `switch-ack:{event_id}`，且核對目前 prize_id |
| 操作碼 | 依活動代碼產生、驗證；A 碼不能操作 B 活動 |
| 抽選資料 | 由目前活動獎項與資格規則取得，同時檢查 organization_id |

## 自動化回歸

- `php artisan test --filter=LotteryBroadcastIsolationTest`：**4 通過、38 assertions**。
  - A／B 使用相同 run_id 各抽一人，資料與廣播內容均屬各自公司；A 的重送仍被拒絕。
  - 持有 A 的抽獎鎖時，A 回 423，B 仍可成功抽獎；釋放後 A 可抽。
  - A 操作碼呼叫 B 回 403；停止 A 心跳不會清掉 B 心跳。
  - 活動更新、獎項名單、可抽名單與彈幕頻道按活動隔離。
- `node --test tests/JavaScript/broadcast-isolation.test.mjs`：**2 通過**。
  - 使用目前正式 listener 程式碼建立兩個獨立頁面狀態，三種事件各自更新。
  - A 抽獎中不會壓住 B 更新；非目前獎項的 winners 訊息不套入 A。
- Pint 與 `git diff --check` 通過。

## 實際 Reverb 傳輸測試

- 使用專案已安裝的 Reverb，在 `127.0.0.1:18081` 啟動獨立測試服務，測試專用 app credentials，不改 `.env`。
- Python 標準函式庫建立實際 WebSocket 連線，執行 Pusher 協定握手與訂閱。
- PHP publisher 載入本專案事件、控制器與抽獎服務，資料庫改為記憶體 SQLite；兩家公司、名單與得主全為合成測試資料，不寫入使用者的本機資料庫。
- 兩連線分別訂閱 `lottery.WSAUDITA`／`lottery.WSAUDITB`，測試實際 winners／lottery 更新。
- A／B 各收到 50 筆交錯彈幕，共 100 筆：**0 串台、0 遺漏、0 重複**。
- A 斷線重連後，再發 A／B 更新，頻道隔離正常。
- A 的大訊息被拒絕後，B 仍可收到自己的小型更新，服務沒有因單一活動失敗而整體中斷。
- 測試 publisher 結束後記憶體資料銷毀，獨立 Reverb 已停止；既有服務不受影響。

臨時腳本與原始結果：`/tmp/lottery-ws-20261020/publisher.php`、`socket_probe.py`、`result.json`。以上檔案是本次調查證據，未加入產品運行流程。

## 發現一：大型名單的廣播被拒絕，且可能中斷抽獎回應

**已重現，待修正。**

1. A 建立 500 位合成參加者，已抽出 1 位後有 499 位可抽。
2. `LotteryEventUpdated::broadcastWith()` 包含完整 `eligible_names`，測得 JSON 約 22,768 bytes；經 Pusher 封裝的 HTTP 請求更大。
3. 目前專案 Reverb `max_request_size` 預設為 10,000 bytes。獨立服務回 HTTP 413，Laravel 拋出 `BroadcastException: Pusher error: Payload too large.`。
4. 再對同一大名單執行目前的抽獎控制器：中獎紀錄由 1 增為 2，接著 `PrizeWinnersUpdated` 廣播拋出例外。控制器目前沒有捕捉該例外，無法正常回傳這次 winners；經 HTTP 例外處理通常會成為 500。

這是單一活動也可能遇到的訊息大小／回應一致性問題，不是 A 的得主被送去 B。500 人只是本次重現條件，實際門檻取決於姓名長度、編碼、獎項數與封裝大小，不應宣稱固定 500 人上限。

後續建議：

- [ ] 優先讓已提交的抽獎結果在廣播失敗時仍正常回傳；保留錯誤紀錄，由既有 HTTP 快照／輪詢恢復其他螢幕。
- [ ] 縮減高頻廣播的完整名單／獎項資料，可採輕量失效通知搭配授權快照；避免每次都重送大量姓名。
- [ ] 依實際 payload 設計訊息限制，若提高 HTTP 上限，同時確認代理、SDK、WebSocket 與前端接收限制，不能只盲目放大一個值。
- [ ] 補上大型名單、廣播拒絕與斷線時「資料已保存且 API 仍有完整結果」的回歸測試。

## 發現二：目前為公開頻道，不是公司會員讀取授權

**已驗證的現行設計。是否需改為私密投影，應依產品的資料公開範圍決定。**

- 第三個沒有登入／Operator token 的測試連線，指定 `lottery.WSAUDITA` 即可成功訂閱並收到 A 的更新。
- 事件包含參加者姓名、中獎姓名與遮罩 Email 等。活動代碼分頻解決正常訂閱的串台問題，但知道代碼的人可自行指定相同頻道。
- Operator token 目前保護抽獎／切獎／心跳等控制操作，不是公開 Channel 的訂閱授權。
- `GET /{brandCode}/lottery` 與 `?payload=1` 也公開提供畫面／資料；只換成 PrivateChannel 不能完整解決資料讀取授權。

若要求公司名單保密：

- [ ] 定義投影端／觀看端可讀範圍與活動層級的讀取憑證，保留必要的公開領獎或展示需求。
- [ ] 頻道訂閱與 HTTP 快照採一致授權；讀取權限與抽獎控制權限分開。
- [ ] 測試 A 憑證不能讀 B 的頻道或 HTTP 資料，並處理過期、重連與切換活動。

## 部署與容量的確認界線

本機設定觀察為 Reverb、database cache、SQLite、scaling 關閉。這不是正式站設定證據。

單一 Reverb 可同時承載不同活動頻道；若正式部署多個 Reverb 實例，需依官方文件設定共用 Redis scaling，才能讓各節點交換廣播。正式環境還需確認 proxy、檔案描述符與連線上限、共享快取、資料庫併發及實際觀看端數量，本次未做正式站壓測。

## 原始碼與參考

- `app/Events/LotteryEventUpdated.php`、`PrizeWinnersUpdated.php`、`DanmakuSent.php`
- `app/Http/Controllers/LotteryFrontendController.php` 的 draw／drawingState／switchAck
- `app/Http/Middleware/VerifyOperatorAccess.php`、`app/Support/AccessCodeGenerator.php`
- `resources/js/lottery.js` 的 Echo 訂閱與事件處理
- `config/reverb.php`、`vendor/laravel/reverb/src/Servers/Reverb/Http/Request.php`
- [Laravel Reverb：設定與多節點 scaling](https://laravel.com/docs/12.x/reverb)
- [Laravel Broadcasting：公開與私人頻道](https://laravel.com/docs/12.x/broadcasting)

## Knowledge Consolidation

README／CLAUDE 已同步頻道與鎖的隔離邊界、公開讀取特性與已重現的訊息大小限制；未把待修正問題寫成已解決。本 repo 尚未啟用 aidlc-docs/knowledge，不在本次導入額外文件體系。抽獎時間標準仍維持原 pending。
