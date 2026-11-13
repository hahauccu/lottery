<?php

namespace App\Support;

class LotteryWalkthroughs
{
    public static function all(): array
    {
        return [
            'lottery-event-setup' => [
                'title' => '建立抽獎活動',
                'summary' => '設定活動名稱與開始時間，再準備獎項及前台畫面。',
                'entry' => 'g_event',
                'steps' => [
                    ['建立活動', '在「抽獎活動」選擇新增，填寫活動名稱與抽獎開始時間，點選「建立」。活動代碼會自動產生。', 'event-create.jpg'],
                    ['調整活動設定', '活動建立後，可調整名稱、時間、預設背景及彈幕。準備完成時開啟「開放抽獎」並保存，前台才可執行抽獎。', 'event-settings.jpg'],
                    ['開啟前台', '在活動頁點選「前台抽獎」，確認後開啟抽獎分頁。請保留這個分頁；再次從後台開啟前台，會讓舊抽獎分頁失效。', 'open-frontend.jpg'],
                ],
                'note' => '建立活動後，還需要新增獎項並設定可抽對象。正式活動中的試抽也會產生中獎紀錄；彩排請使用獨立測試活動。',
            ],
            'lottery-prize-setup' => [
                'title' => '設定獎項與抽獎資格',
                'summary' => '設定中獎人數、動畫與可抽對象，確認資格後再開始抽獎。',
                'entry' => 'g_prize',
                'steps' => [
                    ['設定獎項', '在活動頁下方點選「新增抽獎項目」，填寫獎項名稱、中獎人數，選擇抽獎模式與動畫。可抽人數足夠時，一次全抽會抽出剩餘名額；逐一抽出則每次抽一人。', 'prize-settings.jpg'],
                    ['設定可抽對象', '在「包含群組」選擇全體員工，或指定群組及員工。若跨獎項不能重複中獎，在「排除群組」加入此活動的「已中獎員工」，確認可抽人數後建立獎項。', 'prize-eligibility.jpg'],
                    ['確認獎項列表', '回到獎項列表，核對獎項名稱、已抽／目標、抽獎模式與動畫。本例建立開場幸運獎與壓軸大獎，各抽一人。', 'switch-list.jpg'],
                ],
                'note' => '包含群組與包含員工合併計算，排除條件優先。沒有選擇任何包含對象時，可抽人數為 0。「同一獎項可重複中獎」只控制同一獎項，跨獎項需另外設定排除條件。抽獎秒數依動畫而有不同效果，完整流程也包含揭獎等時間。',
            ],
            'lottery-draw-operation' => [
                'title' => '切換獎項與前台抽獎',
                'summary' => '後台選擇本輪獎項，前台執行抽獎，完成後再切換下一獎。',
                'entry' => 'g_draw',
                'steps' => [
                    ['開啟前台', '先從活動頁的「前台抽獎」開啟抽獎分頁。還沒指定目前獎項時，前台會顯示獎項預覽。', 'frontend-preview.jpg'],
                    ['選擇本輪獎項', '回到後台，確認狀態為在線、未在抽獎中，點選獎項右側的「切換本輪抽獎」並確認。', 'switch-confirm.jpg'],
                    ['開始抽獎', '確認活動已開放抽獎，且前台顯示正確獎項。看到「請按 Enter 開始抽獎」後，在前台按 Enter 執行。', 'draw-ready.jpg'],
                    ['查看結果', '等待動畫與揭獎完成，前台會顯示本輪中獎名單。這次開場幸運獎抽出一位測試員工。', 'draw-result.jpg'],
                    ['切換下一獎', '本輪結束後，在後台對下一獎執行「切換本輪抽獎」。確認前台已顯示壓軸大獎，再按 Enter；若要展示獎項總覽，可在後台選「切換到預覽輪播」。', 'switch-next.jpg'],
                ],
                'note' => '抽獎與獎項切換都需要前台連線就緒。動畫進行中，請等待完成後再切換獎項或處理不在場補抽。',
            ],
            'lottery-absent-redraw' => [
                'title' => '不在場重抽操作教學',
                'summary' => '釋出未到場者的中獎名額，再回到同一獎項補抽。',
                'entry' => 'g_redraw',
                'steps' => [
                    ['選擇未到場者', '獎項有效中獎名額抽滿後，點選「不在場重抽」，勾選未到場且尚未領獎的中獎者，本例勾選一人後，點選「取消 1 人得獎並釋出重抽」。', 'redraw-select.jpg'],
                    ['確認釋出', '核對姓名與 Email，點選「確認釋出」。原中獎紀錄會保留，該名額可供補抽。', 'redraw-confirm.jpg'],
                    ['回前台補抽', '確認活動已開放抽獎、前台仍是剛釋出的獎項，再按 Enter 補抽；若已切換其他獎項，先在後台切回原獎項。釋出名額不會自動抽出替補者。', 'redraw-ready.jpg'],
                    ['確認替補結果', '等待揭獎完成。本例開場幸運獎的原得主測試丙釋出後，由測試甲補位；領獎管理與匯出清單都保留相關紀錄。', 'redraw-result.jpg'],
                ],
                'note' => '已領獎、已釋出的紀錄不能再次釋出；前台正在抽獎時也不能釋出。同獎項不允許重複中獎時，原得主不會再參加該獎項補抽。',
            ],
            'lottery-claims-export' => [
                'title' => '中獎通知、領獎與清單匯出',
                'summary' => '從領獎管理核對結果、發送通知，並下載中獎清單。',
                'entry' => 'g_claims',
                'steps' => [
                    ['查看中獎紀錄', '在活動頁選擇「領獎管理」。本例保留開場幸運獎的原得主與替補者，以及壓軸大獎的中獎紀錄；已釋出的紀錄會標示「已釋出」。', 'claims-list.jpg'],
                    ['發送中獎通知', '需要寄送通知時，點選「發送所有未通知」並確認；也可以在獎項編輯選單使用「發送本項未通知」。通知需由後台操作發送，並非抽出後自動寄送。', 'notifications-confirm.jpg'],
                    ['確認領獎', '工作人員可在中獎者右側點選「領獎」並確認，完成後台領獎登記。', 'claim-confirm.jpg'],
                    ['下載中獎清單', '點選「下載中獎清單」匯出 CSV，可用 Excel 開啟。內容包含獎項、姓名、Email、部門、紀錄類型、目前狀態及補位關係。', 'claims-complete.jpg'],
                ],
                'note' => '通知信含領獎 QR Code。工作人員也可登入後台掃碼，核對資料後確認領獎；一般訪客查看 QR Code 不會直接完成領獎登記。',
            ],
            'lottery-danmaku' => [
                'title' => '彈幕互動操作教學',
                'summary' => '開啟彈幕，讓員工從中獎清單頁送出訊息，顯示在抽獎畫面上。',
                'entry' => 'g_danmaku',
                'steps' => [
                    ['啟用彈幕', '在活動設定開啟「啟用彈幕」並保存，保留前台抽獎分頁連線。', 'danmaku-enable.jpg'],
                    ['進入中獎清單', '從活動的中獎清單網址，或前台獎項預覽的 QR Code 進入。輸入已匯入名單的員工 Email，點選「驗證並查看」。', 'danmaku-identity.jpg'],
                    ['發送訊息', '在「發送彈幕到抽獎頁面」輸入訊息，點選「發送彈幕」。每則最多 100 字，同一員工每 10 秒可發送一次。', 'danmaku-message.jpg'],
                    ['查看前台效果', '訊息會連同員工姓名出現在抽獎畫面上。本例由測試乙發送「尾牙快樂！」。', 'danmaku-screen.jpg'],
                ],
                'note' => 'Email 必須屬於該活動組織的員工，不需要先中獎。彈幕入口的 QR Code 是中獎清單網址，與個別中獎者的領獎 QR Code 不同。',
            ],
            'lottery-analysis-report' => [
                'title' => '抽獎模擬與分析報表教學',
                'summary' => '執行多次模擬，查看獎項統計與部門分布，再下載 Excel 報表。',
                'entry' => 'g_analysis',
                'download' => 'examples/lottery-analysis-sample.xlsx',
                'steps' => [
                    ['執行分析', '從活動頁進入「抽獎分析」，輸入模擬次數並點選「開始分析」。預設 200 次，每次可設定 1 至 1,000 次。', 'analysis-start.jpg'],
                    ['核對統計', '完成後查看「獎項統計」與「部門比較」。本例為四位員工、兩個獎項各一人的 200 次模擬，各獎平均可抽四人，中獎率為 25%。', 'analysis-result.jpg'],
                    ['獎項圖表', '分析完成後，往下查看各獎項的中獎率、平均可抽人數與中獎人數。本例每個獎項可抽四人、抽出一人，中獎率為 25%。', 'analysis-charts.jpg'],
                    ['部門圖表', '比較各部門的可抽占比與中獎占比。下方差異圖以 0 為基準：正值代表中獎占比較高，負值代表較低；結果可能隨每次模擬而不同。', 'analysis-departments.jpg'],
                    ['下載 Excel', '分析完成後，點選「下載 Excel」。報表包含總覽、獎項統計、部門比較、機率比對、圖表及 PivotData 工作表；也可下載本頁的範例報表查看。', 'analysis-result.jpg'],
                ],
                'note' => '分析不會新增正式中獎者、占用獎項名額或發送通知。本例使用一般群組；系統群組與動態資格不一定能由模擬完整呈現。報表供規劃參考，不保證正式結果或個人的中獎機會。',
            ],
            'lottery-qr-claim' => [
                'title' => 'QR Code 查驗與領獎教學',
                'summary' => '掃描或貼上領獎連結，核對中獎資訊後，再確認領獎。',
                'entry' => 'g_qr',
                'steps' => [
                    ['查看領獎 QR Code', '在活動的「領獎管理」中，點選中獎者右側的「QR」，查看該筆中獎的 QR Code 與領獎連結。發送中獎通知後，員工也能從信件取得領獎 QR Code。', 'qr-code.jpg'],
                    ['查驗中獎資訊', '工作人員登入後台「領獎管理」，可開啟相機掃碼，或貼上領獎代碼／完整連結後點選「查詢」。本例使用領獎連結，查到測試甲的開場幸運獎。', 'qr-preview.jpg'],
                    ['確認領獎', '核對姓名、獎項、序號與領獎狀態。確認資料正確且獎品交付後，點選「確認領獎」。查詢資料本身不會完成領獎登記。', 'qr-preview.jpg'],
                    ['查看完成狀態', '登記成功後，畫面顯示「已領獎」，領獎人數與列表也會更新。同一筆已領獎紀錄不能重複確認。', 'qr-complete.jpg'],
                ],
                'note' => '一般訪客掃描領獎 QR Code 只能查看資訊，不能直接寫入領獎狀態。已釋出的中獎紀錄不能領獎；相機無法使用時，可改貼領獎連結查詢。',
            ],
        ];
    }

    public static function guides(): array
    {
        $guides = [];
        foreach (self::all() as $slug => $guide) {
            $guides[$slug] = [
                'title' => $guide['title'], 'summary' => $guide['summary'],
                'eyebrow' => '抽獎操作教學', 'step' => str_pad((string) (count($guides) + 5), 2, '0', STR_PAD_LEFT),
                'detail' => count($guide['steps']).' 個操作步驟・後台畫面',
                'seoTitle' => $guide['title'].'｜尾牙抽獎系統圖文指南',
                'description' => $guide['summary'].'提供實際操作畫面與步驟說明，不需登入即可閱讀。',
                'byline' => '畫面使用測試資料。', 'entry' => $guide['entry'],
                'sections' => ['walkthrough' => '操作步驟', 'notes' => '使用提醒'],
            ];
        }
        return $guides;
    }

    public static function series(): array
    {
        return [
            'lottery-participant-import' => '員工匯入',
            'lottery-event-setup' => '建立活動',
            'lottery-prize-setup' => '獎項與資格',
            'lottery-draw-operation' => '切換與抽獎',
            'lottery-absent-redraw' => '不在場補抽',
            'lottery-claims-export' => '領獎與匯出',
            'lottery-danmaku' => '彈幕互動',
            'lottery-analysis-report' => '分析報表',
            'lottery-qr-claim' => 'QR Code 領獎',
        ];
    }
}
