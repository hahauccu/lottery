<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use League\Csv\Reader;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('downloadEmployeeSample')
                ->label('下載範例 CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(asset('examples/employee-import.csv'), true),
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
                        ->helperText(new HtmlString('欄位需包含：姓名、Email、電話、部門（員工編號、群組可選）。<a class="text-primary-600" href="'.asset('examples/employee-import.csv').'" target="_blank" rel="noopener noreferrer">下載範例 CSV</a>')),
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

                    $normalizeHeader = static function (string $value): string {
                        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? '';

                        return mb_strtolower(trim($value));
                    };

                    $headerAliases = [
                        'name' => ['name', '姓名'],
                        'email' => ['email', 'email address', '電子郵件', 'e-mail'],
                        'phone' => ['phone', '手機', '電話'],
                        'department' => ['department', '部門'],
                        'employee_no' => ['employee_no', '員工編號', '員工號', '工號', '編號'],
                        'groups' => ['groups', 'group', '群組', '群組名稱'],
                    ];

                    $headers = array_map($normalizeHeader, $csv->getHeader());
                    $headerSet = array_flip($headers);
                    $requiredFields = ['name', 'email', 'phone', 'department'];
                    $missing = [];

                    foreach ($requiredFields as $field) {
                        $hasField = false;
                        foreach ($headerAliases[$field] as $alias) {
                            if (isset($headerSet[$normalizeHeader($alias)])) {
                                $hasField = true;
                                break;
                            }
                        }
                        if (! $hasField) {
                            $missing[] = $field;
                        }
                    }

                    if ($missing !== []) {
                        Notification::make()
                            ->danger()
                            ->title('CSV 欄位錯誤')
                            ->body('需包含欄位：姓名、Email、電話、部門')
                            ->send();

                        return;
                    }

                    $created = 0;
                    $updated = 0;
                    $skipped = 0;

                    foreach ($csv->getRecords() as $record) {
                        $normalized = [];
                        foreach ($record as $key => $value) {
                            $normalized[$normalizeHeader((string) $key)] = $value;
                        }

                        $getValue = static function (array $data, array $aliases) use ($normalizeHeader): string {
                            foreach ($aliases as $alias) {
                                $key = $normalizeHeader($alias);
                                if (array_key_exists($key, $data)) {
                                    return trim((string) $data[$key]);
                                }
                            }

                            return '';
                        };

                        $name = $getValue($normalized, $headerAliases['name']);
                        $email = strtolower($getValue($normalized, $headerAliases['email']));
                        $phone = $getValue($normalized, $headerAliases['phone']);
                        $department = $getValue($normalized, $headerAliases['department']);
                        $employeeNo = $getValue($normalized, $headerAliases['employee_no']);
                        $groupsRaw = $getValue($normalized, $headerAliases['groups']);

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
                                'department' => $department !== '' ? $department : null,
                                'employee_no' => $employeeNo !== '' ? $employeeNo : null,
                            ]
                        );

                        if ($groupsRaw !== '') {
                            $groupNames = preg_split('/[|,;、]+/u', $groupsRaw) ?: [];
                            $groupNames = array_filter(array_map('trim', $groupNames));

                            if ($groupNames !== []) {
                                $groupIds = [];
                                foreach ($groupNames as $groupName) {
                                    $group = EmployeeGroup::firstOrCreate([
                                        'organization_id' => $tenant->getKey(),
                                        'name' => $groupName,
                                    ]);
                                    $groupIds[] = $group->id;
                                }

                                $employee->groups()->syncWithoutDetaching($groupIds);
                            }
                        }

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
