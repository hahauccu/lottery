<?php

namespace App\Filament\Resources\PrizeResource\Pages;

use App\Filament\Resources\PrizeResource;
use App\Models\PrizeRule;
use Filament\Resources\Pages\CreateRecord;

class CreatePrize extends CreateRecord
{
    protected static string $resource = PrizeResource::class;

    protected function afterCreate(): void
    {
        $this->syncRules();
    }

    private function syncRules(): void
    {
        $state = $this->form->getState();
        $prize = $this->record;

        $prize->rules()->delete();
        $prize->rules()->createMany($this->buildRules($state));
    }

    private function buildRules(array $state): array
    {
        $rules = [];

        foreach (array_unique($state['include_employee_ids'] ?? []) as $id) {
            $rules[] = ['type' => PrizeRule::TYPE_INCLUDE_EMPLOYEE, 'ref_id' => $id];
        }

        foreach (array_unique($state['include_group_ids'] ?? []) as $id) {
            $rules[] = ['type' => PrizeRule::TYPE_INCLUDE_GROUP, 'ref_id' => $id];
        }

        foreach (array_unique($state['exclude_employee_ids'] ?? []) as $id) {
            $rules[] = ['type' => PrizeRule::TYPE_EXCLUDE_EMPLOYEE, 'ref_id' => $id];
        }

        foreach (array_unique($state['exclude_group_ids'] ?? []) as $id) {
            $rules[] = ['type' => PrizeRule::TYPE_EXCLUDE_GROUP, 'ref_id' => $id];
        }

        foreach (array_unique($state['exclude_prize_ids'] ?? []) as $id) {
            $rules[] = ['type' => PrizeRule::TYPE_EXCLUDE_PRIZE_WINNERS, 'ref_id' => $id];
        }

        return $rules;
    }
}
