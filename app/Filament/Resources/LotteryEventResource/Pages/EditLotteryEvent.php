<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Filament\Resources\LotteryEventResource;
use App\Models\PrizeWinner;
use App\Support\AccessCodeGenerator;
use App\Support\LotteryBroadcaster;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditLotteryEvent extends EditRecord
{
    protected static string $resource = LotteryEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openFrontendLottery')
                ->label('前台抽獎')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->requiresConfirmation()
                ->modalHeading('開啟前台抽獎')
                ->modalDescription(function (): HtmlString {
                    $code = AccessCodeGenerator::generate($this->record->brand_code);

                    return new HtmlString(
                        '<div class="space-y-3">'.
                        '<div class="rounded-xl bg-warning-50 dark:bg-warning-500/10 p-4 text-center">'.
                        '<p class="text-xs text-warning-600 dark:text-warning-400 mb-1">今日存取碼（已自動帶入，無需手動輸入）</p>'.
                        '<p class="text-3xl font-mono font-bold tracking-widest text-warning-900 dark:text-warning-100">'.$code.'</p>'.
                        '</div>'.
                        '<p class="text-sm text-gray-500 dark:text-gray-400">⚠ 若前台已有分頁開啟，舊分頁將自動失效。</p>'.
                        '</div>'
                    );
                })
                ->modalSubmitActionLabel('開啟前台')
                ->modalIcon('heroicon-o-arrow-top-right-on-square')
                ->action(function ($livewire): void {
                    $url = route('lottery.show', [
                        'brandCode' => $this->record->brand_code,
                        '_lac' => AccessCodeGenerator::generate($this->record->brand_code),
                    ]);
                    $livewire->js('window.open('.json_encode($url).", '_blank')");
                }),
            Actions\Action::make('openAnalysis')
                ->label('抽獎分析')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => LotteryEventResource::getUrl('analysis', ['record' => $this->record])),
            Actions\Action::make('manageClaims')
                ->label('領獎管理')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success')
                ->url(fn (): string => LotteryEventResource::getUrl('claims', ['record' => $this->record])),
            Actions\Action::make('openFrontendWinners')
                ->label('前台中獎清單')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => route('lottery.winners', ['brandCode' => $this->record->brand_code]), true),
            Actions\Action::make('resetEventWinners')
                ->label('清空本場抽獎')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('清空本場抽獎名單？')
                ->modalDescription('此操作會刪除本活動所有獎項的中獎紀錄，且不可復原。')
                ->action(function (): void {
                    PrizeWinner::query()
                        ->whereIn('prize_id', $this->record->prizes()->select('id'))
                        ->delete();

                    LotteryBroadcaster::dispatchUpdate($this->record->refresh());

                    Notification::make()
                        ->title('已清空本場抽獎名單')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        LotteryBroadcaster::dispatchUpdate($this->record->refresh());
    }
}
