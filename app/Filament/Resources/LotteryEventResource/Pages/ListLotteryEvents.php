<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Filament\Resources\LotteryEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLotteryEvents extends ListRecords
{
    protected static string $resource = LotteryEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
