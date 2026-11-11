<?php

namespace App\Support;

class LotteryFeatures
{
    public static function all(): array
    {
        return [
            'lottery-analysis' => [
                'title' => '模擬抽獎與分析報表',
                'seoTitle' => '模擬抽獎分析｜單次最多 1,000 次、獎項與部門統計報表',
                'description' => '單次模擬 1 至 1,000 次抽獎，查看各獎項的中獎次數與部門分布，下載 Excel 分析報表。目前以指定員工或有成員的一般群組為主，系統群組與依中獎結果變動的資格需另行核對。',
                'stage' => '活動前', 'number' => '01',
                'headline' => '模擬抽獎，下載分析報表',
                'summary' => '單次最多模擬 1,000 次，查看各獎項的中獎次數與部門分布，下載 Excel 報表，供活動規劃參考。',
                'detail' => '單次最多 1,000 次模擬・Excel 報表',
                'sections' => ['workflow' => '產生分析報表', 'report' => '報表內容', 'scope' => '適用範圍', 'questions' => '常見問題'],
                'guide' => 'lottery-participant-import',
            ],
            'lottery-control' => [
                'title' => '後台主控與前台投影',
                'seoTitle' => '尾牙抽獎後台｜切換獎項、投影畫面與前台操作',
                'description' => '從抽獎後台切換目前獎項、返回預覽並查看前台狀態。工作人員確認投影畫面後，由前台開始抽獎，適合尾牙、春酒的現場分工。',
                'stage' => '活動中', 'number' => '02',
                'headline' => '從後台切換獎項與投影畫面',
                'summary' => '工作人員在後台選擇獎項或返回預覽，現場確認畫面後，再從前台開始抽獎。',
                'detail' => '選獎與預覽・前台狀態・切換確認',
                'sections' => ['division' => '後台與前台分工', 'workflow' => '操作步驟', 'connection' => '操作條件', 'questions' => '常見問題'],
                'guide' => 'lottery-host-script',
            ],
            'absent-redraw' => [
                'title' => '不在場重抽與紀錄保留',
                'seoTitle' => '尾牙抽獎人不在怎麼辦？不在場重抽與紀錄保留',
                'description' => '中獎者不在場，可從後台勾選未領獎者、確認釋出名額，再由前台補抽。系統保留原中獎紀錄、釋出原因與替補關係，方便活動後核對。',
                'stage' => '臨場處理', 'number' => '03',
                'headline' => '中獎者不在場，可以釋出名額補抽',
                'summary' => '在後台勾選不在場的中獎者，確認釋出名額後，由前台補抽。系統保留原中獎與替補紀錄，方便事後核對。',
                'detail' => '勾選與確認・釋出名額・保留替補紀錄',
                'sections' => ['workflow' => '不在場補抽步驟', 'history' => '中獎與替補紀錄', 'conditions' => '釋出與補抽條件', 'questions' => '常見問題'],
                'guide' => 'lottery-rules',
            ],
        ];
    }
}
