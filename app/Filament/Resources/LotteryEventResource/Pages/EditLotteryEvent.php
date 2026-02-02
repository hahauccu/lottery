<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Filament\Resources\LotteryEventResource;
use App\Models\PrizeWinner;
use App\Support\LotteryBroadcaster;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLotteryEvent extends EditRecord
{
    protected static string $resource = LotteryEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openFrontendLottery')
                ->label('前台抽獎')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => route('lottery.show', ['brandCode' => $this->record->brand_code]), true),
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
