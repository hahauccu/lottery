<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Events\LotteryEventUpdated;
use App\Filament\Resources\LotteryEventResource;
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
        event(new LotteryEventUpdated($this->record));
    }
}
