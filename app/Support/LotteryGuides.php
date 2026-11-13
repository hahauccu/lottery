<?php

namespace App\Support;

class LotteryGuides
{
    public static function all(): array
    {
        return [
            'year-end-lottery' => [
                'title' => '尾牙抽獎流程指南', 'eyebrow' => '從這裡開始', 'step' => '01',
                'summary' => '從名單、資格與獎項順序，到挑選動畫、主持彩排，把一場活動逐項準備好。',
                'detail' => '完整籌備流程・7 種動畫比較・現場檢查清單',
            ],
            'lottery-host-script' => [
                'title' => '尾牙抽獎主持稿與流程表', 'eyebrow' => '掌握現場節奏', 'step' => '02',
                'summary' => '開場怎麼說、一般獎怎麼唱名、壓軸怎麼帶？把可改寫的口播與工作分工放在同一張表。',
                'detail' => '4 段口播範例・可複製主持稿・下載流程表',
                'seoTitle' => '尾牙抽獎主持稿範例｜開場、唱名與補抽台詞',
                'description' => '提供尾牙與春酒抽獎主持稿範例、缺席處理口播與可下載的活動流程表，搭配一次全抽、逐一揭獎和主持交接，準備一場順暢的抽獎。',
                'entry' => 'guide_host',
                'sections' => ['before' => '口播前先確認', 'scripts' => '四段主持稿', 'schedule' => '流程與交接', 'rehearsal' => '彩排與異常處理'],
            ],
            'lottery-participant-import' => [
                'title' => '員工名單匯入教學', 'eyebrow' => '後台操作圖解・名單準備', 'step' => '03',
                'summary' => '將 Excel 名單另存為 CSV，再到後台匯入員工資料。',
                'detail' => '操作步驟・欄位說明・範例 CSV',
                'seoTitle' => '員工名單匯入圖文教學｜Excel 轉 CSV、實際後台操作示範',
                'description' => '尾牙抽獎員工名單匯入教學：Excel 轉 CSV、上傳檔案、確認匯入結果與核對群組，附操作畫面、範例檔及欄位說明。',
                'byline' => '畫面使用測試資料。',
                'entry' => 'guide_csv',
                'sections' => ['walkthrough' => '看實際操作', 'sample' => '下載示範名單', 'columns' => '準備 CSV 欄位', 'questions' => '匯入常見問題', 'next' => '接著準備什麼'],
            ],
            'lottery-rules' => [
                'title' => '重複中獎與不在場重抽規則', 'eyebrow' => '先說清楚規則', 'step' => '04',
                'summary' => '同一獎項能否再中、跨獎項怎麼排除、不在場如何補抽，用情境對照把規則和設定接起來。',
                'detail' => '同獎項／跨獎項對照・資格設定・釋出與補抽',
                'seoTitle' => '尾牙抽獎規則｜重複中獎與不在場重抽設定',
                'description' => '同一人可以重複中獎嗎？說明同獎項開關、跨獎項的包含與排除群組、不在場重抽流程，以及活動開始前應公告和核對的規則。',
                'entry' => 'guide_rule',
                'sections' => ['compare' => '先分清兩種重複', 'eligibility' => '包含與排除資格', 'redraw' => '不在場重抽', 'announce' => '事前公告與彩排'],
            ],
            ...LotteryWalkthroughs::guides(),
        ];
    }
}
