# 功能介紹與實作核對

核對日期：2026-10-22。分支：`codex/seo-guide-library`。本批為本機內容預覽，未部署。

## 內容主軸

以本站能解決的實際情境介紹功能；不假設每間公司使用同一套主持流程。每篇包含情境、操作步驟、使用條件、常見問題、相關功能與設定指南。

| 公開頁面 | 對應需求 | 文案邊界與依據 |
| --- | --- | --- |
| `/features` | 功能總覽 | 串接活動前分析、現場主控、臨場補抽三個情境；Demo 與正式功能分開說明 |
| `/features/lottery-analysis` | 多次模擬、分析表 | 單次上限 **1,000**，預設 200；非單次一萬。獎項／部門統計、Excel 下載；不建立正式中獎紀錄。動態資格限制明列於正文 |
| `/features/lottery-control` | 後台像主控台 | 後台選目前獎項／預覽、開關抽獎；正式開抽仍由前台觸發，切換完成不會自動抽人 |
| `/features/absent-redraw` | 人不在立即處理 | 入口在有效名額抽滿時顯示；勾選未領獎者、確認釋出，再由前台補抽。保留原紀錄與替補關係 |

## 已核對的程式與行為

### 模擬分析

- `AnalyzeLotteryEvent::startAnalysis` 驗證 1 至 1,000，建立分析工作；完成後才提供下載。
- `RunLotteryAnalysis` 讀取活動設定、更新分析狀態與進度，不呼叫正式抽獎。
- `LotteryAnalysisService` 產出獎項及部門統計。可抽人次會跨獎項、跨輪累計；不是不同員工人數。中獎率為中獎次數／可抽人次。
- `LotteryAnalysisController::download` 限活動所屬組織成員，未完成回 409；預設 Excel，也有 `format=csv` 路徑，但現有後台僅放 Excel 按鈕，公開步驟以 Excel 為主。
- Excel 實際讀取驗證分頁：總覽、獎項統計、部門比較、機率比對、圖表、PivotData。

### 後台與前台

- `PrizesRelationManager` 的設目前獎項／切回預覽會檢查前台就緒與抽獎中狀態；設獎項還會檢查正式可抽者。
- `LotteryFrontendController::draw` 在未開放回 403、切換中回 423。`switchAck` 核對目前獎項、清除切換旗標及廣播，不產生中獎紀錄。
- `resources/js/lottery.js` 由前台按鈕、Enter 或空白鍵觸發抽獎；活動時間欄位不是已實作的自動抽獎排程。
- 本批沒有做雙機端到端即時廣播驗證，因此沒有宣稱零延遲或任意名單規模保證。既有大名單 WebSocket 訊息大小風險仍以 `todo/websocket-multi-company-audit.md` 為準。

### 不在場處理

- `ReprizeSelector` 勾選、二次確認後呼叫 `PrizeRedrawService`，不呼叫抽獎服務。
- `PrizeRedrawService` 在抽獎中阻擋操作；只釋出同獎項且未領獎、未釋出的紀錄，記錄時間、原因及人員並發送更新事件。
- `LotteryDrawService` 在之後的抽獎才補名額，沿用空缺 sequence，寫入 `replacement_for_winner_id`。逐一模式每次一位；一次全抽受剩餘名額與資格限制。
- `EligibleEmployeesService` 的同獎項不重複會排除所有歷史得獎者，包含已釋出紀錄；其他獎項仍依各自資格判定。
- `ClaimController` 對已釋出紀錄顯示已釋出頁，兌獎 API 回 410；`LotteryWinnerExportTest` 驗證匯出保留原始及補位紀錄。

## 發現的既有落差：待修正，未隨本批改服務

分析器目前獨立處理資格，尚未與正式 `EligibleEmployeesService` 完整一致。已用記憶體 SQLite、四位合成員工重現：

| 情境 | 正式可抽者 | 分析平均可抽者 | 原因 |
| --- | ---: | ---: | --- |
| 未選任何包含規則 | 0 | 4 | 分析器空包含視為全組織；正式為空 |
| 指定一人＋全體員工系統群組 | 4 | 1 | 分析器僅讀群組靜態 pivot，沒有動態展開系統群組 |
| 包含四人、排除已中獎系統群組，其中一人已中其他獎 | 3 | 4 | 分析器未重現該動態排除 |

此外，分析器仍讀舊 `exclude_prize_winners` 規則，正式資格服務已透過系統群組處理；分析不重播目前正式名單的領獎、釋出及補抽狀態。這些是既有產品落差，不能只用「多模擬幾次」改善。

目前公開頁明列適用範圍，不宣稱完整預演或公平性認證。要以「一萬次完整預演」作為宣傳，建議依序處理：

1. 統一正式抽獎與分析器的資格規則，為每輪模擬維護獨立的虛擬得獎狀態，涵蓋動態包含／排除、獎項順序與重複規則。
2. 決定分析是「活動從零開始」或「依目前進度補完」，在介面及報表明確區分，避免使用真實得獎紀錄污染模擬輪次。
3. 加入資格對照與隔離測試後，再以真實部署條件量測 10,000 次的耗時、記憶體、排隊、重試及進度更新成本，決定是否提高上限。
4. 由實際合成案例生成可下載樣本，附名單規模、規則與輪數，再更新功能頁宣稱。

診斷證據保留於本機 `/tmp/lottery-features-20261022/analysis-scope-audit.json`，測試腳本 `AnalysisScopeAuditTest.php` 同目錄；未把目前不一致的行為寫成永久產品保證。

## SEO 與驗證

- 四個新增頁皆為 Blade 直接輸出、單一 H1、獨立 title／description、self canonical、OG、可爬取連結、BreadcrumbList；功能明細使用 WebPage，總覽使用 CollectionPage／ItemList。無虛構評價、修改日期或不符內容的結構化資料。
- 功能頁不載入抽獎引擎、指南 JS、iframe 或抽獎 session；FAQ 使用原生 details／summary。範例明確標示為示意，未冒充實測成果或後台截圖。
- 首頁功能卡、FAQ、Demo 總覽、指南導覽連至功能系列；功能明細互連並與對應指南雙向連結。Sitemap 從前一批本機 17 個增加到 21 個；不新增部署時間當 lastmod。
- `LotteryFeatureClaimsTest`：6 個測試，驗證靜態模擬統計／不污染正式名單與郵件、表單上限、實際 Excel／CSV 與授權、釋出與後續補抽、釋出保護、切換確認與開抽。
- `LotteryFeaturePagesTest`：驗證 metadata、結構化資料、目錄、無引擎腳本、相關頁／指南連結與 sitemap。
- 本批相關 PHP 回歸 **48 個測試、798 個 assertions 通過**；另上述診斷 1 個測試、6 個 assertions。Vite build 通過，輸出保留於 `/tmp/lottery-features-20261022/build`，沒有覆寫 repo 的 public/build。
- 使用 Codex 提供的瀏覽器工具檢查桌面與手機版；示意、文字、表格與 FAQ 可閱讀操作。非正式活動全流程的現場雙機驗收。

內容策略參考 Google Search Central 的 [實用且可靠的原創內容](https://developers.google.com/search/docs/fundamentals/creating-helpful-content) 與 [可爬取且有描述的內部連結](https://developers.google.com/search/docs/crawling-indexing/links-crawlable)。新增頁面不代表保證排名或收錄。

## 後續功能系列候選

可在核對與截圖後繼續擴充：CSV 匯入與同名識別、群組資格與跨獎項排除、QR Code 兌獎、得獎通知、動畫與背景音樂、活動後紀錄匯出。每篇都需先確認操作入口、方案限制、錯誤與邊界情境，再發布；不以相同文案更換關鍵字大量生成頁面。

## 文案修訂：2026-10-22

- 依使用者回饋，參考 [Slido 產品頁](https://www.slido.com/product) 與 [Tally 功能頁](https://tally.so/features) 的功能標題、短說明與操作入口安排，未複製對方文案。
- 重寫總覽與三篇功能介紹。刪除「接住需求」「每個細節都有下一步」等抽象口號，標題直接說明功能，內文使用具體操作、數字及使用對象；縮短重複說明。
- 保留單次 1,000 次上限、動態資格差異、前台開抽、釋出後另外補抽等邊界；範例仍標示為示例資料。沒有調整正式抽獎或分析服務。
- 此次文案修訂回歸：`LotteryFeaturePagesTest`、`LotteryGuideTest`、`DemoLotteryPagesTest` 共 27 個測試、610 assertions 通過；桌面與手機首屏已確認。
