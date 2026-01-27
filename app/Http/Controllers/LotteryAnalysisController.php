<?php

namespace App\Http\Controllers;

use App\Models\LotteryAnalysisRun;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LotteryAnalysisController extends Controller
{
    public function download(LotteryAnalysisRun $analysisRun)
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

        $fileName = sprintf('analysis-%s-%s.csv', $event->brand_code, $analysisRun->id);

        return response()->streamDownload(function () use ($result, $iterations) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'section',
                'iterations',
                'prize_id',
                'prize_name',
                'draw_mode',
                'allow_repeat_within_prize',
                'winners_count',
                'eligible_avg',
                'eligible_exposures',
                'total_wins',
                'win_rate',
                'department',
                'dept_eligible_exposures',
                'dept_eligible_pct',
                'dept_win_count',
                'dept_win_pct',
                'dept_delta_pct',
            ]);

            foreach (($result['prizes'] ?? []) as $row) {
                fputcsv($handle, [
                    'prize_summary',
                    $iterations,
                    $row['id'] ?? '',
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
}
