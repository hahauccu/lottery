<?php

namespace App\Filament\Resources\EmployeeGroupResource\Pages;

use App\Filament\Resources\EmployeeGroupResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeGroup extends CreateRecord
{
    protected static string $resource = EmployeeGroupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = Filament::getTenant()?->getKey();

        return $data;
    }
}
