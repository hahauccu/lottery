<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Events\LotteryEventUpdated;
use App\Filament\Resources\LotteryEventResource;
use App\Models\EventRule;
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
        $this->syncRules();

        event(new LotteryEventUpdated($this->record));
    }

    private function syncRules(): void
    {
        $state = $this->form->getState();
        $event = $this->record;

        $event->rules()->delete();
        $event->rules()->createMany($this->buildRules($state));
    }

    private function buildRules(array $state): array
    {
        $rules = [];

        foreach (array_unique($state['include_employee_ids'] ?? []) as $id) {
            $rules[] = ['type' => EventRule::TYPE_INCLUDE_EMPLOYEE, 'ref_id' => $id];
        }

        foreach (array_unique($state['include_group_ids'] ?? []) as $id) {
            $rules[] = ['type' => EventRule::TYPE_INCLUDE_GROUP, 'ref_id' => $id];
        }

        foreach (array_unique($state['exclude_employee_ids'] ?? []) as $id) {
            $rules[] = ['type' => EventRule::TYPE_EXCLUDE_EMPLOYEE, 'ref_id' => $id];
        }

        foreach (array_unique($state['exclude_group_ids'] ?? []) as $id) {
            $rules[] = ['type' => EventRule::TYPE_EXCLUDE_GROUP, 'ref_id' => $id];
        }

        return $rules;
    }
}
