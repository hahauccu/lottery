<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Events\LotteryEventUpdated;
use App\Filament\Resources\LotteryEventResource;
use Filament\Actions;
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
            Actions\Action::make('openFrontendWinners')
                ->label('前台中獎清單')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => route('lottery.winners', ['brandCode' => $this->record->brand_code]), true),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        event(new LotteryEventUpdated($this->record));
    }
}
