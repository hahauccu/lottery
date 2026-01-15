<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importCsv')
                ->label('匯入 CSV')
                ->modalHeading('匯入員工 CSV')
                ->modalSubmitActionLabel('開始匯入')
                ->form([
                    FileUpload::make('file')
                        ->label('CSV 檔案')
                        ->required()
                        ->directory('imports')
                        ->disk('local')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                        ])
                        ->helperText('欄位需包含 name, email, phone'),
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

                    $path = $data['file'];
                    $fullPath = Storage::disk('local')->path($path);
                    $csv = Reader::createFromPath($fullPath);
                    $csv->setHeaderOffset(0);

                    $headers = array_map('strtolower', $csv->getHeader());
                    $required = ['name', 'email', 'phone'];

                    if (array_diff($required, $headers)) {
                        Notification::make()
                            ->danger()
                            ->title('CSV 欄位錯誤')
                            ->body('需包含欄位：name, email, phone')
                            ->send();

                        return;
                    }

                    $created = 0;
                    $updated = 0;
                    $skipped = 0;

                    foreach ($csv->getRecords() as $record) {
                        $record = array_change_key_case($record, CASE_LOWER);

                        $name = trim((string) ($record['name'] ?? ''));
                        $email = strtolower(trim((string) ($record['email'] ?? '')));
                        $phone = trim((string) ($record['phone'] ?? ''));

                        if ($name === '' || $email === '') {
                            $skipped++;
                            continue;
                        }

                        $employee = Employee::updateOrCreate(
                            [
                                'organization_id' => $tenant->getKey(),
                                'email' => $email,
                            ],
                            [
                                'name' => $name,
                                'phone' => $phone !== '' ? $phone : null,
                            ]
                        );

                        if ($employee->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $updated++;
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('匯入完成')
                        ->body("新增 {$created} 筆，更新 {$updated} 筆，略過 {$skipped} 筆")
                        ->send();
                }),
        ];
    }
}
