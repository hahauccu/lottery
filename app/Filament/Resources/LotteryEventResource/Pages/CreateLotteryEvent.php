<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Filament\Resources\LotteryEventResource;
use App\Support\LotteryBroadcaster;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLotteryEvent extends CreateRecord
{
    protected static string $resource = LotteryEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = Filament::getTenant()?->getKey();

        return $data;
    }

    protected function afterCreate(): void
    {
        LotteryBroadcaster::dispatchUpdate($this->record->refresh());
    }
}
