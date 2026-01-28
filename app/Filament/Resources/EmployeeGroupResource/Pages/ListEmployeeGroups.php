<?php

namespace App\Filament\Resources\EmployeeGroupResource\Pages;

use App\Filament\Resources\EmployeeGroupResource;
use App\Models\EmployeeGroup;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeGroups extends ListRecords
{
    protected static string $resource = EmployeeGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('clearGroups')
                ->label('清空群組')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalHeading('清空所有群組？')
                ->modalDescription('此操作會刪除本公司所有群組資料，且不可復原。')
                ->modalSubmitActionLabel('確認清空')
                ->form([
                    TextInput::make('confirm')
                        ->label('輸入「清空」以確認')
                        ->required()
                        ->rules(['required', 'in:清空'])
                        ->validationMessages([
                            'in' => '請輸入「清空」以確認。',
                        ]),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        Notification::make()
                            ->danger()
                            ->title('找不到公司資訊')
                            ->send();

                        return;
                    }

                    if (($data['confirm'] ?? '') !== '清空') {
                        Notification::make()
                            ->danger()
                            ->title('清空失敗')
                            ->body('請輸入「清空」以確認。')
                            ->send();

                        return;
                    }

                    EmployeeGroup::query()
                        ->where('organization_id', $tenant->getKey())
                        ->delete();

                    Notification::make()
                        ->success()
                        ->title('已清空群組')
                        ->send();
                }),
        ];
    }
}
