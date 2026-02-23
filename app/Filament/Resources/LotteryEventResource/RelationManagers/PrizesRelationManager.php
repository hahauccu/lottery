<?php

namespace App\Filament\Resources\LotteryEventResource\RelationManagers;

use App\Jobs\SendPrizeNotifications;
use App\Livewire\EligibleEmployeesPreview;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\Prize;
use App\Models\PrizeRule;
use App\Services\EligibleEmployeesService;
use App\Services\SystemGroupService;
use App\Support\LotteryBroadcaster;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Livewire as LivewireComponent;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class PrizesRelationManager extends RelationManager
{
    protected static string $relationship = 'prizes';

    protected static string $view = 'filament.resources.lottery-event-resource.relation-managers.prizes-relation-manager';

    public function getStatusBarData(): array
    {
        $event = $this->getOwnerRecord();
        $event->refresh();
        $brandCode = $event->brand_code;

        $isOnline = $this->frontendIsReady($brandCode);
        $isDrawing = $isOnline && $this->frontendIsDrawing($brandCode);
        $isSwitching = (bool) $event->is_prize_switching;

        // connectionStatus
        $connectionStatus = $isOnline ? 'online' : 'offline';

        // processStatus (priority: switching > drawing > offline > standby)
        if ($isSwitching) {
            $processStatus = 'switching';
        } elseif ($isDrawing) {
            $processStatus = 'drawing';
        } elseif (! $isOnline) {
            $processStatus = 'offline';
        } else {
            $processStatus = 'standby';
        }

        // currentView & prizeSummary & progress
        $currentPrize = $event->current_prize_id ? $event->currentPrize : null;

        if ($currentPrize) {
            $currentView = $currentPrize->name;
            $drawn = $currentPrize->winners()->count();
            $target = $currentPrize->winners_count;
            $remaining = max(0, $target - $drawn);
            $percentage = $target > 0 ? round(($drawn / $target) * 100) : 0;

            $prizeSummary = [
                'name' => $currentPrize->name,
                'draw_mode' => $currentPrize->draw_mode === Prize::DRAW_MODE_ONE_BY_ONE ? '逐一抽出' : '一次全抽',
                'animation_style' => match ($currentPrize->animation_style) {
                    'lotto_air' => '樂透氣流機',
                    'red_packet' => '紅包雨',
                    'scratch_card' => '刮刮樂',
                    'treasure_chest' => '寶箱開啟',
                    'big_treasure_chest' => '大寶箱',
                    default => $currentPrize->animation_style,
                },
                'lotto_hold_seconds' => $currentPrize->lotto_hold_seconds,
            ];

            $progress = [
                'drawn' => $drawn,
                'target' => $target,
                'remaining' => $remaining,
                'percentage' => $percentage,
            ];
        } else {
            $currentView = $isOnline ? '預覽畫面' : null;
            $prizeSummary = null;
            $progress = null;
        }

        // suggestion
        if (! $isOnline) {
            $suggestion = '請先開啟前台抽獎頁面';
        } elseif ($isSwitching) {
            $suggestion = '等待前台載入完成…';
        } elseif ($isDrawing) {
            $suggestion = '抽獎進行中，請等待完成';
        } elseif ($currentPrize && $progress && $progress['remaining'] === 0) {
            $suggestion = '此獎項已抽完，可切換下一個獎項';
        } elseif ($currentPrize) {
            $suggestion = '可至前台按下抽獎按鈕';
        } else {
            $suggestion = '請選擇一個獎項設為目前獎項';
        }

        return [
            'connectionStatus' => $connectionStatus,
            'processStatus' => $processStatus,
            'currentView' => $currentView,
            'prizeSummary' => $prizeSummary,
            'progress' => $progress,
            'suggestion' => $suggestion,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('基本資訊')
                    ->schema([
                        TextInput::make('name')
                            ->label('獎項名稱')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: Prize::class,
                                column: 'name',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule
                                    ->where('lottery_event_id', $this->getOwnerRecord()->getKey())
                            )
                            ->validationMessages([
                                'unique' => '此活動已有相同名稱的獎項',
                            ]),
                        TextInput::make('winners_count')
                            ->label('中獎人數')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Select::make('draw_mode')
                            ->label('抽獎模式')
                            ->options([
                                Prize::DRAW_MODE_ALL_AT_ONCE => '一次全抽',
                                Prize::DRAW_MODE_ONE_BY_ONE => '逐一抽出',
                            ])
                            ->required()
                            ->default(Prize::DRAW_MODE_ALL_AT_ONCE),
                        Select::make('animation_style')
                            ->label('抽獎動畫')
                            ->options([
                                'lotto_air' => '樂透氣流機',
                                'red_packet' => '紅包雨',
                                'scratch_card' => '刮刮樂',
                                'treasure_chest' => '寶箱開啟',
                                'big_treasure_chest' => '大寶箱',
                            ])
                            ->required()
                            ->default('lotto_air')
                            ->live(),
                        TextInput::make('lotto_hold_seconds')
                            ->label('抽獎秒數')
                            ->helperText('每次抽獎的動畫時間（秒），5–50 秒')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(50)
                            ->default(5)
                            ->suffix('秒'),
                        Placeholder::make('animation_demo')
                            ->label('動畫預覽')
                            ->content(fn (Get $get) => new HtmlString(
                                view('filament.lottery.animation-demo', [
                                    'style' => $get('animation_style') ?? 'slot',
                                ])->render()
                            ))
                            ->columnSpanFull(),
                        Toggle::make('allow_repeat_within_prize')
                            ->label(new \Illuminate\Support\HtmlString(
                                '同一獎項可重複中獎&nbsp;<span x-data x-tooltip.raw="開啟後，有資格中獎的人可在同一獎項中多次中獎" class="cursor-help inline-flex items-center align-middle text-gray-400 hover:text-gray-500"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.94 6.94a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm.06 2.25a.75.75 0 000 1.5h.01a.75.75 0 000-1.5H9z" clip-rule="evenodd" /></svg></span>'
                            ))
                            ->default(false),
                        FileUpload::make('bg_image_path')
                            ->label('獎項背景圖')
                            ->disk('public')
                            ->directory(function () {
                                $slug = Filament::getTenant()?->slug
                                    ?? $this->getOwnerRecord()?->organization?->slug;

                                return $slug
                                    ? 'lottery/'.$slug.'/prizes/backgrounds'
                                    : 'lottery/pending/prizes/backgrounds';
                            })
                            ->image()
                            ->imagePreviewHeight('120')
                            ->maxSize(4096),
                        FileUpload::make('music_path')
                            ->label('抽獎音樂')
                            ->disk('public')
                            ->directory(function () {
                                $slug = Filament::getTenant()?->slug
                                    ?? $this->getOwnerRecord()?->organization?->slug;

                                return $slug
                                    ? 'lottery/'.$slug.'/prizes/music'
                                    : 'lottery/pending/prizes/music';
                            })
                            ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav'])
                            ->maxSize(10240),
                    ])
                    ->columns(2),
                Section::make('抽獎範圍')
                    ->description('包含為聯集，排除優先。未選任何包含群組或員工時，可抽人數為 0。')
                    ->schema([
                        Select::make('include_group_ids')
                            ->label('包含群組')
                            ->options(function () {
                                $event = $this->getOwnerRecord();

                                $systemGroups = app(SystemGroupService::class)->getSystemGroupsForEvent($event);

                                $customGroups = EmployeeGroup::query()
                                    ->where('organization_id', Filament::getTenant()?->getKey())
                                    ->whereNull('system_key')
                                    ->orderBy('name')
                                    ->pluck('name', 'id');

                                $options = [];

                                if ($systemGroups->isNotEmpty()) {
                                    $options['系統群組'] = $systemGroups->mapWithKeys(fn ($g) => [
                                        $g->id => '🔴 '.$g->name,
                                    ])->all();
                                }

                                if ($customGroups->isNotEmpty()) {
                                    $options['自訂群組'] = $customGroups->all();
                                }

                                return $options;
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('include_employee_ids')
                            ->label('包含員工')
                            ->options(fn () => Employee::query()
                                ->where('organization_id', Filament::getTenant()?->getKey())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('exclude_group_ids')
                            ->label('排除群組')
                            ->options(function () {
                                $event = $this->getOwnerRecord();

                                $systemGroups = app(SystemGroupService::class)->getSystemGroupsForEvent($event);

                                $customGroups = EmployeeGroup::query()
                                    ->where('organization_id', Filament::getTenant()?->getKey())
                                    ->whereNull('system_key')
                                    ->orderBy('name')
                                    ->pluck('name', 'id');

                                $options = [];

                                if ($systemGroups->isNotEmpty()) {
                                    $options['系統群組'] = $systemGroups->mapWithKeys(fn ($g) => [
                                        $g->id => '🔴 '.$g->name,
                                    ])->all();
                                }

                                if ($customGroups->isNotEmpty()) {
                                    $options['自訂群組'] = $customGroups->all();
                                }

                                return $options;
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('exclude_employee_ids')
                            ->label('排除員工')
                            ->options(fn () => Employee::query()
                                ->where('organization_id', Filament::getTenant()?->getKey())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live(),
                        LivewireComponent::make(EligibleEmployeesPreview::class, fn (Get $get, ?Prize $record) => [
                            'context' => 'prize',
                            'organizationId' => Filament::getTenant()?->getKey(),
                            'eventId' => $this->getOwnerRecord()->getKey(),
                            'includeEmployeeIds' => $get('include_employee_ids') ?? [],
                            'includeGroupIds' => $get('include_group_ids') ?? [],
                            'excludeEmployeeIds' => $get('exclude_employee_ids') ?? [],
                            'excludeGroupIds' => $get('exclude_group_ids') ?? [],
                            'allowRepeatWithinPrize' => (bool) ($get('allow_repeat_within_prize') ?? false),
                            'currentPrizeId' => $record?->id,
                        ])
                            ->key(fn (Get $get, ?Prize $record) => 'event-prize-preview-'.md5(json_encode([
                                $this->getOwnerRecord()->getKey(),
                                $record?->id,
                                $get('include_employee_ids'),
                                $get('include_group_ids'),
                                $get('exclude_employee_ids'),
                                $get('exclude_group_ids'),
                                $get('allow_repeat_within_prize'),
                            ])))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function forceResetSwitching(): void
    {
        $event = $this->getOwnerRecord();
        if ($event->is_prize_switching) {
            $event->update(['is_prize_switching' => false]);
            LotteryBroadcaster::dispatchUpdate($event->refresh());
        }
    }

    protected function frontendIsDrawing(string $brandCode): bool
    {
        return Cache::has("lottery-drawing:{$brandCode}");
    }

    protected function frontendIsReady(string $brandCode): bool
    {
        $cacheKey = "lottery-ready:{$brandCode}";
        $lastSeen = Cache::get($cacheKey);
        if (! $lastSeen) {
            return false;
        }

        return (now()->timestamp - (int) $lastSeen) <= 12;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('獎項名稱')
                    ->searchable()
                    ->icon(fn (Prize $record): ?string =>
                        (int) $this->getOwnerRecord()->current_prize_id === (int) $record->getKey()
                            ? 'heroicon-s-play' : null)
                    ->iconPosition(IconPosition::After)
                    ->color(fn (Prize $record): ?string =>
                        (int) $this->getOwnerRecord()->current_prize_id === (int) $record->getKey()
                            ? 'success' : null),
                TextColumn::make('drawn_count')
                    ->label('已抽 / 目標')
                    ->getStateUsing(fn (Prize $record) => $record->winners()->count().' / '.$record->winners_count)
                    ->badge()
                    ->color(fn (Prize $record) => $record->winners()->count() >= $record->winners_count ? 'success' : 'gray'),
                TextColumn::make('winners_count')
                    ->label('中獎人數'),
                TextColumn::make('draw_mode')
                    ->label('抽獎模式')
                    ->formatStateUsing(fn (string $state) => $state === Prize::DRAW_MODE_ONE_BY_ONE ? '逐一抽出' : '一次全抽'),
                TextColumn::make('animation_style')
                    ->label('動畫')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'lotto_air' => '樂透氣流機',
                        'red_packet' => '紅包雨',
                        'scratch_card' => '刮刮樂',
                        'treasure_chest' => '寶箱開啟',
                        'big_treasure_chest' => '大寶箱',
                        default => $state,
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['lottery_event_id'] = $this->getOwnerRecord()->getKey();
                        $data['sort_order'] = $this->nextSortOrder();

                        return $data;
                    })
                    ->using(function (array $data): Prize {
                        $record = $this->getRelationship()->create($this->stripRuleFields($data));
                        $this->syncRules($record, $data);

                        return $record;
                    }),
                \Filament\Tables\Actions\Action::make('switch_to_preview')
                    ->label('切換至預覽畫面')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->action(function ($livewire): void {
                        $event = $this->getOwnerRecord();
                        $brandCode = $event->brand_code;
                        if (! $this->frontendIsReady($brandCode)) {
                            Notification::make()
                                ->danger()
                                ->title('無法切換預覽')
                                ->body('前台尚未就緒或離線，請先開啟抽獎頁面。')
                                ->send();

                            return;
                        }
                        if ($this->frontendIsDrawing($brandCode)) {
                            Notification::make()
                                ->danger()
                                ->title('無法切換預覽')
                                ->body('抽獎進行中，請等待完成後再切換。')
                                ->send();

                            return;
                        }
                        $event->update([
                            'current_prize_id' => null,
                            'is_prize_switching' => true,
                            'prize_switched_at' => now(),
                        ]);
                        LotteryBroadcaster::dispatchUpdate($event->refresh());

                        Notification::make()
                            ->title('切換中…')
                            ->info()
                            ->persistent()
                            ->id('prize-switching')
                            ->send();

                        $livewire->js("
                            // 清理舊的輪詢 timer
                            if (window.__prizeSwitchPollId) {
                                clearInterval(window.__prizeSwitchPollId);
                                window.__prizeSwitchPollId = null;
                            }
                            (function() {
                                let attempts = 0;
                                const maxAttempts = 10;
                                window.__prizeSwitchPollId = setInterval(async () => {
                                    attempts++;
                                    try {
                                        const res = await fetch('/{$brandCode}/lottery?payload=1');
                                        const data = await res.json();
                                        if (!data.event?.is_prize_switching) {
                                            clearInterval(window.__prizeSwitchPollId);
                                            window.__prizeSwitchPollId = null;
                                            new FilamentNotification()
                                                .title('切換成功')
                                                .icon('heroicon-o-check-circle')
                                                .iconColor('success')
                                                .id('prize-switching')
                                                .send();
                                            \$wire.\$refresh();
                                        }
                                    } catch (e) { console.error('[switch-poll]', e); }
                                    if (attempts >= maxAttempts) {
                                        clearInterval(window.__prizeSwitchPollId);
                                        window.__prizeSwitchPollId = null;
                                        \$wire.call('forceResetSwitching');
                                        new FilamentNotification()
                                            .title('切換失敗')
                                            .body('前端未回報載入完成，請確認前端頁面是否正常運作。')
                                            .icon('heroicon-o-x-circle')
                                            .iconColor('danger')
                                            .id('prize-switching')
                                            .send();
                                        \$wire.\$refresh();
                                    }
                                }, 1000);
                            })();
                        ");
                    })
                    ->disabled(fn () => $this->getOwnerRecord()->is_prize_switching),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('set_current')
                    ->label('設為目前獎項')
                    ->requiresConfirmation()
                    ->action(function (Prize $record, $livewire): void {
                        $validStyles = ['lotto_air', 'red_packet', 'scratch_card', 'treasure_chest', 'big_treasure_chest'];

                        if (! in_array($record->animation_style, $validStyles, true)) {
                            Notification::make()
                                ->danger()
                                ->title('無法設為目前獎項')
                                ->body("動畫風格「{$record->animation_style}」已不支援，請先編輯獎項選擇有效的動畫。")
                                ->send();

                            return;
                        }

                        $eligible = app(EligibleEmployeesService::class)->eligibleForStoredPrize($record);

                        if ($eligible->isEmpty()) {
                            Notification::make()
                                ->danger()
                                ->title('無法設為目前獎項')
                                ->body('此獎項沒有可抽人員，請先設定包含群組或員工。')
                                ->send();

                            return;
                        }

                        $event = $this->getOwnerRecord();
                        $brandCode = $event->brand_code;
                        if (! $this->frontendIsReady($brandCode)) {
                            Notification::make()
                                ->danger()
                                ->title('無法設為目前獎項')
                                ->body('前台尚未就緒或離線，請先開啟抽獎頁面。')
                                ->send();

                            return;
                        }

                        if ($this->frontendIsDrawing($brandCode)) {
                            Notification::make()
                                ->danger()
                                ->title('無法切換獎項')
                                ->body('抽獎進行中，請等待完成後再切換。')
                                ->send();

                            return;
                        }

                        $event->update([
                            'current_prize_id' => $record->getKey(),
                            'is_prize_switching' => true,
                            'prize_switched_at' => now(),
                        ]);

                        LotteryBroadcaster::dispatchUpdate($event->refresh());

                        Notification::make()
                            ->title('切換中…')
                            ->info()
                            ->persistent()
                            ->id('prize-switching')
                            ->send();

                        $livewire->js("
                            // 清理舊的輪詢 timer
                            if (window.__prizeSwitchPollId) {
                                clearInterval(window.__prizeSwitchPollId);
                                window.__prizeSwitchPollId = null;
                            }
                            (function() {
                                let attempts = 0;
                                const maxAttempts = 10;
                                window.__prizeSwitchPollId = setInterval(async () => {
                                    attempts++;
                                    try {
                                        const res = await fetch('/{$brandCode}/lottery?payload=1');
                                        const data = await res.json();
                                        if (!data.event?.is_prize_switching) {
                                            clearInterval(window.__prizeSwitchPollId);
                                            window.__prizeSwitchPollId = null;
                                            new FilamentNotification()
                                                .title('切換成功')
                                                .icon('heroicon-o-check-circle')
                                                .iconColor('success')
                                                .id('prize-switching')
                                                .send();
                                            \$wire.\$refresh();
                                        }
                                    } catch (e) { console.error('[switch-poll]', e); }
                                    if (attempts >= maxAttempts) {
                                        clearInterval(window.__prizeSwitchPollId);
                                        window.__prizeSwitchPollId = null;
                                        \$wire.call('forceResetSwitching');
                                        new FilamentNotification()
                                            .title('切換失敗')
                                            .body('前端未回報載入完成，請確認前端頁面是否正常運作。')
                                            .icon('heroicon-o-x-circle')
                                            .iconColor('danger')
                                            .id('prize-switching')
                                            .send();
                                        \$wire.\$refresh();
                                    }
                                }, 1000);
                            })();
                        ");
                    })
                    ->disabled(fn () => $this->getOwnerRecord()->is_prize_switching),
                \Filament\Tables\Actions\Action::make('send_notifications')
                    ->label('發送中獎通知')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('發送中獎通知')
                    ->modalDescription(fn (Prize $record) => sprintf(
                        '將發送通知給 %d 位尚未收到通知的中獎者。確定要發送嗎？',
                        $record->winners()->whereNull('notified_at')->count()
                    ))
                    ->visible(fn (Prize $record) => $record->winners()->count() > 0)
                    ->action(function (Prize $record): void {
                        $unnotifiedCount = $record->winners()->whereNull('notified_at')->count();

                        if ($unnotifiedCount === 0) {
                            Notification::make()
                                ->warning()
                                ->title('無需發送')
                                ->body('所有中獎者已收到通知')
                                ->send();

                            return;
                        }

                        SendPrizeNotifications::dispatch($record->id);

                        Notification::make()
                            ->success()
                            ->title('通知已排程')
                            ->body("正在發送 {$unnotifiedCount} 封通知信")
                            ->send();
                    }),
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, Prize $record): array {
                        return array_merge($data, $this->ruleStateFromRecord($record));
                    })
                    ->using(function (Prize $record, array $data): Prize {
                        $record->update($this->stripRuleFields($data));
                        $this->syncRules($record, $data);

                        return $record;
                    })
                    ->after(function (Prize $record): void {
                        $event = $this->getOwnerRecord();

                        if ((int) $event->current_prize_id === (int) $record->getKey()) {
                            LotteryBroadcaster::dispatchUpdate($event->refresh());
                        }
                    })
                    ->extraModalFooterActions([
                        DeleteAction::make()
                            ->before(function (Prize $record): void {
                                $event = $this->getOwnerRecord();

                                if ((int) $event->current_prize_id !== (int) $record->getKey()) {
                                    return;
                                }

                                $event->update(['current_prize_id' => null]);
                                LotteryBroadcaster::dispatchUpdate($event->refresh());
                            }),
                    ]),
            ]);
    }

    private function nextSortOrder(): int
    {
        $max = (int) ($this->getOwnerRecord()->prizes()->max('sort_order') ?? 0);

        return $max + 1;
    }

    private function stripRuleFields(array $data): array
    {
        return Arr::except($data, [
            'include_employee_ids',
            'include_group_ids',
            'exclude_employee_ids',
            'exclude_group_ids',
        ]);
    }

    private function ruleStateFromRecord(Prize $record): array
    {
        return [
            'include_employee_ids' => $record->rules()
                ->where('type', PrizeRule::TYPE_INCLUDE_EMPLOYEE)
                ->pluck('ref_id')
                ->all(),
            'include_group_ids' => $record->rules()
                ->where('type', PrizeRule::TYPE_INCLUDE_GROUP)
                ->pluck('ref_id')
                ->all(),
            'exclude_employee_ids' => $record->rules()
                ->where('type', PrizeRule::TYPE_EXCLUDE_EMPLOYEE)
                ->pluck('ref_id')
                ->all(),
            'exclude_group_ids' => $record->rules()
                ->where('type', PrizeRule::TYPE_EXCLUDE_GROUP)
                ->pluck('ref_id')
                ->all(),
        ];
    }

    private function syncRules(Prize $prize, array $state): void
    {
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

        return $rules;
    }
}
