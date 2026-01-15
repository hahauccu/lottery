<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Events\LotteryEventUpdated;
use App\Filament\Resources\LotteryEventResource;
use App\Models\EventRule;
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['include_employee_ids'] = $this->record->rules()
            ->where('type', EventRule::TYPE_INCLUDE_EMPLOYEE)
            ->pluck('ref_id')
            ->all();
        $data['include_group_ids'] = $this->record->rules()
            ->where('type', EventRule::TYPE_INCLUDE_GROUP)
            ->pluck('ref_id')
            ->all();
        $data['exclude_employee_ids'] = $this->record->rules()
            ->where('type', EventRule::TYPE_EXCLUDE_EMPLOYEE)
            ->pluck('ref_id')
            ->all();
        $data['exclude_group_ids'] = $this->record->rules()
            ->where('type', EventRule::TYPE_EXCLUDE_GROUP)
            ->pluck('ref_id')
            ->all();

        return $data;
    }

    protected function afterSave(): void
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
