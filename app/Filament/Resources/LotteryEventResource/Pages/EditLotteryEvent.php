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
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        event(new LotteryEventUpdated($this->record));
    }
}
