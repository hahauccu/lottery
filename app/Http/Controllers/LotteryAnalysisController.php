<?php

namespace App\Http\Controllers;

use App\Exports\LotteryAnalysisExport;
use App\Models\LotteryAnalysisRun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LotteryAnalysisController extends Controller
{
    public function download(LotteryAnalysisRun $analysisRun, Request $request)
    {
        $event = $analysisRun->lotteryEvent;
        $user = Auth::user();

        if (! $event || ! $user instanceof User || ! $user->organizations()->whereKey($event->organization_id)->exists()) {
            abort(403);
        }

        if ($analysisRun->status !== LotteryAnalysisRun::STATUS_COMPLETED) {
            return response()->json(['message' => 'analysis_not_ready'], 409);
        }

        $result = $analysisRun->result ?? [];
        $iterations = (int) ($result['iterations'] ?? $analysisRun->iterations);

        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $fileName = sprintf('analysis-%s-%s.csv', $event->brand_code, $analysisRun->id);

            return response()->streamDownload(function () use ($result, $iterations) {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, [
                    '統計類別',
                    '模擬次數',
                    '獎項名稱',
                    '抽獎模式',
                    '同一獎項可重複中獎',
                    '中獎人數',
                    '平均可抽人數',
                    '可抽人次累計',
                    '中獎次數',
                    '中獎率',
                    '部門',
                    '部門可抽人次',
                    '部門可抽占比',
                    '部門中獎次數',
                    '部門中獎占比',
                    '中獎占比差異',
                ]);

                foreach (($result['prizes'] ?? []) as $row) {
                    fputcsv($handle, [
                        'prize_summary',
                        $iterations,
                        $row['name'] ?? '',
                        $row['draw_mode'] ?? '',
                        isset($row['allow_repeat_within_prize']) ? (int) $row['allow_repeat_within_prize'] : '',
                        $row['winners_count'] ?? '',
                        $row['eligible_avg'] ?? '',
                        $row['eligible_exposures'] ?? '',
                        $row['total_wins'] ?? '',
                        $row['win_rate'] ?? '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ]);
                }

                foreach (($result['departments'] ?? []) as $row) {
                    fputcsv($handle, [
                        'department_summary',
                        $iterations,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        $row['department'] ?? '',
                        $row['eligible_exposures'] ?? '',
                        $row['eligible_pct'] ?? '',
                        $row['win_count'] ?? '',
                        $row['win_pct'] ?? '',
                        $row['delta_pct'] ?? '',
                    ]);
                }

                fclose($handle);
            }, $fileName);
        }

        $fileName = sprintf('analysis-%s-%s.xlsx', $event->brand_code, $analysisRun->id);

        return Excel::download(new LotteryAnalysisExport($result, [
            'event_name' => $event->name,
            'brand_code' => $event->brand_code,
            'analysis_id' => (string) $analysisRun->id,
            'iterations' => $iterations,
            'generated_at' => $result['generated_at'] ?? $analysisRun->created_at?->toDateTimeString() ?? '',
        ]), $fileName);
    }
}
