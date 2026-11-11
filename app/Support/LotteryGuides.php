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
                'title' => 'Excel 名單整理與 CSV 匯入教學', 'eyebrow' => '整理參加名單', 'step' => '03',
                'summary' => '弄懂必要欄位、同名與重複 Email，以及群組留白的影響，先用範例檔走過一次匯入。',
                'detail' => '欄位對照・合成 CSV 樣本・匯入錯誤排查',
                'seoTitle' => '抽獎名單 Excel 整理與 CSV 匯入教學｜欄位、重複 Email 與群組',
                'description' => '抽獎名單怎麼從 Excel 匯入？依本站 CSV 匯入功能說明姓名、Email、電話、部門、員工編號與群組欄位，提供合成名單下載與常見錯誤修正。',
                'entry' => 'guide_csv',
                'sections' => ['sample' => '先下載合成樣本', 'columns' => '欄位怎麼填', 'import' => '從 Excel 到後台', 'duplicates' => '同名與更新規則', 'groups' => '群組的三種情況', 'errors' => '匯入後核對'],
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
        ];
    }
}
