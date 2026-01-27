<?php

namespace App\Jobs;

use App\Models\LotteryAnalysisRun;
use App\Models\LotteryEvent;
use App\Services\LotteryAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunLotteryAnalysis implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $analysisRunId;

    public function __construct(int $analysisRunId)
    {
        $this->analysisRunId = $analysisRunId;
    }

    public function handle(LotteryAnalysisService $service): void
    {
        $run = LotteryAnalysisRun::find($this->analysisRunId);

        if (! $run || $run->status !== LotteryAnalysisRun::STATUS_QUEUED) {
            return;
        }

        $event = LotteryEvent::find($run->lottery_event_id);

        if (! $event) {
            $run->forceFill([
                'status' => LotteryAnalysisRun::STATUS_FAILED,
                'error_message' => 'Event not found.',
                'finished_at' => now(),
            ])->save();

            return;
        }

        $run->forceFill([
            'status' => LotteryAnalysisRun::STATUS_RUNNING,
            'progress' => 0,
            'error_message' => null,
            'started_at' => now(),
        ])->save();

        try {
            $result = $service->analyze($event, $run->iterations, function (int $progress) use ($run) {
                if ($progress <= $run->progress) {
                    return;
                }

                $run->forceFill(['progress' => $progress])->save();
            });

            $run->forceFill([
                'status' => LotteryAnalysisRun::STATUS_COMPLETED,
                'progress' => 100,
                'result' => $result,
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => LotteryAnalysisRun::STATUS_FAILED,
                'error_message' => mb_substr($exception->getMessage(), 0, 500),
                'finished_at' => now(),
            ])->save();

            throw $exception;
        }
    }
}
