<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrizeResource\Pages;
use App\Livewire\EligibleEmployeesPreview;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\LotteryEvent;
use App\Models\Prize;
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
                            ->maxLength(255),
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
                            ])
                            ->required()
                            ->default('lotto_air')
                            ->live(),
                        TextInput::make('lotto_hold_seconds')
                            ->label('抽獎秒數')
                            ->helperText('每次抽獎的動畫時間（秒），最低 3 秒')
                            ->numeric()
                            ->minValue(3)
                            ->default(5)
                            ->suffix('秒'),
                        Toggle::make('allow_repeat_within_prize')
                            ->label('同一獎項可重複中獎')
                            ->default(false),
                        FileUpload::make('bg_image_path')
                            ->label('獎項背景圖')
                            ->disk('public')
                            ->directory('lottery/prizes/backgrounds')
                            ->image()
                            ->imagePreviewHeight('120')
                            ->maxSize(4096),
                        FileUpload::make('music_path')
                            ->label('抽獎音樂')
                            ->disk('public')
                            ->directory('lottery/prizes/music')
                            ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav'])
                            ->maxSize(10240),
                    ])
                    ->columns(2),
                Section::make('抽獎範圍')
                    ->description('包含為聯集，排除優先。重複包含不影響抽獎機率。')
                    ->schema([
                        Select::make('include_group_ids')
                            ->label('包含群組')
                            ->options(fn () => EmployeeGroup::query()
                                ->where('organization_id', Filament::getTenant()?->getKey())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false),
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
                            ->live()
                            ->dehydrated(false),
                        Select::make('exclude_group_ids')
                            ->label('排除群組')
                            ->options(fn () => EmployeeGroup::query()
                                ->where('organization_id', Filament::getTenant()?->getKey())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false),
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
                            ->live()
                            ->dehydrated(false),
                        Select::make('exclude_prize_ids')
                            ->label('排除其他獎項中獎者')
                            ->options(fn (Get $get) => Prize::query()
                                ->where('lottery_event_id', $get('lottery_event_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false),
                        LivewireComponent::make(EligibleEmployeesPreview::class, fn (Get $get, ?Prize $record) => [
                            'context' => 'prize',
                            'organizationId' => Filament::getTenant()?->getKey(),
                            'eventId' => $get('lottery_event_id'),
                            'includeEmployeeIds' => $get('include_employee_ids') ?? [],
                            'includeGroupIds' => $get('include_group_ids') ?? [],
                            'excludeEmployeeIds' => $get('exclude_employee_ids') ?? [],
                            'excludeGroupIds' => $get('exclude_group_ids') ?? [],
                            'excludePrizeIds' => $get('exclude_prize_ids') ?? [],
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
                                $get('exclude_prize_ids'),
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
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
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
