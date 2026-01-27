<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Filament\Resources\LotteryEventResource;
use App\Jobs\RunLotteryAnalysis;
use App\Models\LotteryAnalysisRun;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class AnalyzeLotteryEvent extends Page
{
    use InteractsWithRecord;

    protected static string $resource = LotteryEventResource::class;

    protected static string $view = 'filament.resources.lottery-event-resource.pages.analyze-lottery-event';

    public int $iterations = 200;

    public ?int $analysisRunId = null;

    public ?LotteryAnalysisRun $analysisRun = null;

    public array $analysisResult = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->loadLatestRun();
    }

    public function startAnalysis(): void
    {
        $data = $this->validate([
            'iterations' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $run = LotteryAnalysisRun::create([
            'lottery_event_id' => $this->record->id,
            'iterations' => $data['iterations'],
            'status' => LotteryAnalysisRun::STATUS_QUEUED,
            'progress' => 0,
        ]);

        $this->analysisRunId = $run->id;
        $this->analysisRun = $run;
        $this->analysisResult = [];

        RunLotteryAnalysis::dispatch($run->id);
    }

    public function refreshRun(): void
    {
        if (! $this->analysisRunId) {
            $this->loadLatestRun();

            return;
        }

        $run = LotteryAnalysisRun::query()
            ->where('lottery_event_id', $this->record->id)
            ->whereKey($this->analysisRunId)
            ->first();

        if (! $run) {
            return;
        }

        $this->analysisRun = $run;
        $this->analysisResult = $run->status === LotteryAnalysisRun::STATUS_COMPLETED
            ? ($run->result ?? [])
            : [];
    }

    public function getDownloadUrlProperty(): ?string
    {
        if (! $this->analysisRun || $this->analysisRun->status !== LotteryAnalysisRun::STATUS_COMPLETED) {
            return null;
        }

        return route('admin.lottery-analysis.download', ['analysisRun' => $this->analysisRun->id]);
    }

    public function getTitle(): string
    {
        return '抽獎分析';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('返回活動')
                ->url(LotteryEventResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    private function loadLatestRun(): void
    {
        $run = LotteryAnalysisRun::query()
            ->where('lottery_event_id', $this->record->id)
            ->latest('id')
            ->first();

        if (! $run) {
            return;
        }

        $this->analysisRunId = $run->id;
        $this->analysisRun = $run;
        $this->analysisResult = $run->status === LotteryAnalysisRun::STATUS_COMPLETED
            ? ($run->result ?? [])
            : [];
    }
}
