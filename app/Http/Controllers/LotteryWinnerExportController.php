<?php

namespace App\Http\Controllers;

use App\Models\LotteryEvent;
use App\Models\PrizeWinner;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LotteryWinnerExportController extends Controller
{
    public function download(LotteryEvent $lotteryEvent)
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->organizations()->whereKey($lotteryEvent->organization_id)->exists()) {
            abort(403);
        }

        $winners = PrizeWinner::query()
            ->whereHas('prize', fn ($q) => $q->where('lottery_event_id', $lotteryEvent->id))
            ->with(['prize', 'employee', 'replacementFor.employee', 'replacement.employee'])
            ->join('prizes', 'prize_winners.prize_id', '=', 'prizes.id')
            ->orderBy('prizes.sort_order')
            ->orderBy('prize_winners.sequence')
            ->orderBy('prize_winners.won_at')
            ->orderBy('prize_winners.id')
            ->select('prize_winners.*')
            ->get();

        $fileName = sprintf('winners-%s-%s.csv', $lotteryEvent->brand_code, now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($winners) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '獎項',
                '序號',
                '抽獎時間',
                '中獎人',
                'Email',
                '部門',
                '記錄類型',
                '目前狀態',
                '資格取消時間',
                '取消原因',
                '補位自誰',
                '補位給誰',
            ]);

            foreach ($winners as $winner) {
                $recordType = $winner->replacement_for_winner_id ? '重抽補位' : '原始中獎';
                $status = $winner->isReleased() ? '已釋出' : '有效';
                $releaseReason = match ($winner->release_reason) {
                    'absent' => '不在場重抽',
                    null => '',
                    default => $winner->release_reason,
                };

                fputcsv($handle, [
                    $winner->prize->name ?? '',
                    $winner->sequence,
                    $winner->won_at?->format('Y-m-d H:i:s') ?? '',
                    $winner->employee->name ?? '',
                    $winner->employee->email ?? '',
                    $winner->employee->department ?? '',
                    $recordType,
                    $status,
                    $winner->released_at?->format('Y-m-d H:i:s') ?? '',
                    $releaseReason,
                    $winner->replacementFor?->employee?->name ?? '',
                    $winner->replacement?->employee?->name ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
