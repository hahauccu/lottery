<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrizeResource\Pages;
use App\Livewire\EligibleEmployeesPreview;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\LotteryEvent;
use App\Models\Prize;
use App\Services\SystemGroupService;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Livewire as LivewireComponent;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrizeResource extends Resource
{
    protected static ?string $model = Prize::class;

    protected static ?string $modelLabel = '抽獎項目';

    protected static ?string $pluralModelLabel = '抽獎項目';

    protected static bool $isScopedToTenant = false;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = '獎項';

    protected static ?string $navigationGroup = '抽獎管理';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('基本資訊')
                    ->schema([
                        Select::make('lottery_event_id')
                            ->label('抽獎活動')
                            ->options(fn () => LotteryEvent::query()
                                ->where('organization_id', Filament::getTenant()?->getKey())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->required()
                            ->searchable()
                            ->live(),
                        TextInput::make('name')
                            ->label('獎項名稱')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: Prize::class,
                                column: 'name',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get, ?Prize $record) => $rule
                                    ->where('lottery_event_id', $get('lottery_event_id') ?? $record?->lottery_event_id)
                            )
                            ->validationMessages([
                                'unique' => '此活動已有相同名稱的獎項',
                            ]),
                        TextInput::make('winners_count')
                            ->label('中獎人數')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn (Get $get) => $get('animation_style') === 'battle_top' ? 15 : 9999)
                            ->helperText(fn (Get $get) => $get('animation_style') === 'battle_top'
                                ? '⚔️ 戰鬥陀螺模式：場地最多容納 15 個陀螺，中獎人數上限為 15'
                                : null)
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
                                'marble_race' => '圓球賽跑',
                                'battle_top' => '戰鬥陀螺',
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
                        Toggle::make('sound_enabled')
                            ->label('開啟音效')
                            ->helperText('開啟後抽獎過程會播放音效')
                            ->default(true),
                        Toggle::make('allow_repeat_within_prize')
                            ->label(new \Illuminate\Support\HtmlString(
                                '同一獎項可重複中獎&nbsp;<span x-data x-tooltip.raw="開啟後，有資格中獎的人可在同一獎項中多次中獎" class="cursor-help inline-flex items-center align-middle text-gray-400 hover:text-gray-500"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.94 6.94a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm.06 2.25a.75.75 0 000 1.5h.01a.75.75 0 000-1.5H9z" clip-rule="evenodd" /></svg></span>'
                            ))
                            ->default(false),
                        FileUpload::make('bg_image_path')
                            ->label('獎項背景圖')
                            ->disk('public')
                            ->directory(function (?Prize $record) {
                                $slug = Filament::getTenant()?->slug
                                    ?? $record?->lotteryEvent?->organization?->slug;

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
                            ->directory(function (?Prize $record) {
                                $slug = Filament::getTenant()?->slug
                                    ?? $record?->lotteryEvent?->organization?->slug;

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
                            ->options(function (Get $get) {
                                $eventId = $get('lottery_event_id');
                                $event = $eventId ? LotteryEvent::find($eventId) : null;

                                $systemGroups = $event
                                    ? app(SystemGroupService::class)->getSystemGroupsForEvent($event)
                                    : collect();

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
                            ->options(function (Get $get) {
                                $eventId = $get('lottery_event_id');
                                $event = $eventId ? LotteryEvent::find($eventId) : null;

                                $systemGroups = $event
                                    ? app(SystemGroupService::class)->getSystemGroupsForEvent($event)
                                    : collect();

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
                            'eventId' => $get('lottery_event_id'),
                            'includeEmployeeIds' => $get('include_employee_ids') ?? [],
                            'includeGroupIds' => $get('include_group_ids') ?? [],
                            'excludeEmployeeIds' => $get('exclude_employee_ids') ?? [],
                            'excludeGroupIds' => $get('exclude_group_ids') ?? [],
                            'allowRepeatWithinPrize' => (bool) ($get('allow_repeat_within_prize') ?? false),
                            'currentPrizeId' => $record?->id,
                        ])
                            ->key(fn (Get $get, ?Prize $record) => 'prize-preview-'.md5(json_encode([
                                $record?->id,
                                $get('lottery_event_id'),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('獎項名稱')
                    ->searchable(),
                TextColumn::make('lotteryEvent.name')
                    ->label('活動')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('winners_count')
                    ->label('人數'),
                TextColumn::make('draw_mode')
                    ->label('抽獎模式')
                    ->formatStateUsing(fn (string $state) => $state === Prize::DRAW_MODE_ONE_BY_ONE ? '逐一抽出' : '一次全抽'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->extraModalFooterActions([
                        \Filament\Tables\Actions\DeleteAction::make(),
                    ]),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->when($tenant, fn (Builder $query) => $query->whereHas('lotteryEvent', fn (Builder $eventQuery) => $eventQuery->where('organization_id', $tenant->getKey())))
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrizes::route('/'),
            'create' => Pages\CreatePrize::route('/create'),
            'edit' => Pages\EditPrize::route('/{record}/edit'),
        ];
    }
}
