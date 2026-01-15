<?php

namespace App\Filament\Resources;

use App\Events\LotteryEventUpdated;
use App\Filament\Resources\LotteryEventResource\Pages;
use App\Livewire\EligibleEmployeesPreview;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\EventRule;
use App\Models\LotteryEvent;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Livewire as LivewireComponent;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;

class LotteryEventResource extends Resource
{
    protected static ?string $model = LotteryEvent::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = '抽獎活動';

    protected static ?string $navigationGroup = '抽獎管理';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('基本資訊')
                    ->schema([
                        TextInput::make('name')
                            ->label('活動名稱')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('brand_code')
                            ->label('活動代碼')
                            ->helperText('建立後自動產生，可用於前台 URL')
                            ->disabled()
                            ->dehydrated(),
                        Toggle::make('is_lottery_open')
                            ->label('開放抽獎')
                            ->default(false),
                        Select::make('current_prize_id')
                            ->label('目前抽獎')
                            ->options(fn (?LotteryEvent $record) => $record?->prizes()->orderBy('name')->pluck('name', 'id')->all() ?? [])
                            ->searchable()
                            ->preload()
                            ->disabled(fn (?LotteryEvent $record) => $record === null)
                            ->helperText('選擇後前台會自動切換顯示'),
                        FileUpload::make('default_bg_image_path')
                            ->label('預設背景圖')
                            ->disk('public')
                            ->directory('lottery/backgrounds')
                            ->image()
                            ->imagePreviewHeight('120')
                            ->maxSize(4096),
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
                        LivewireComponent::make(EligibleEmployeesPreview::class, fn (Get $get) => [
                            'context' => 'event',
                            'organizationId' => Filament::getTenant()?->getKey(),
                            'includeEmployeeIds' => $get('include_employee_ids') ?? [],
                            'includeGroupIds' => $get('include_group_ids') ?? [],
                            'excludeEmployeeIds' => $get('exclude_employee_ids') ?? [],
                            'excludeGroupIds' => $get('exclude_group_ids') ?? [],
                        ])
                            ->key(fn (Get $get) => 'event-preview-'.md5(json_encode([
                                $get('include_employee_ids'),
                                $get('include_group_ids'),
                                $get('exclude_employee_ids'),
                                $get('exclude_group_ids'),
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
                    ->label('活動名稱')
                    ->searchable(),
                TextColumn::make('brand_code')
                    ->label('代碼')
                    ->copyable()
                    ->copyMessage('已複製')
                    ->toggleable(),
                ToggleColumn::make('is_lottery_open')
                    ->label('開放抽獎')
                    ->afterStateUpdated(function (LotteryEvent $record): void {
                        event(new LotteryEventUpdated($record->refresh()));
                    }),
                TextColumn::make('currentPrize.name')
                    ->label('目前獎項')
                    ->toggleable(),
                TextColumn::make('prizes_count')
                    ->label('獎項數')
                    ->counts('prizes'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('setCurrentPrize')
                    ->label('切換獎項')
                    ->modalHeading('切換目前抽獎')
                    ->form([
                        Select::make('current_prize_id')
                            ->label('目前獎項')
                            ->options(fn (LotteryEvent $record) => $record->prizes()->orderBy('name')->pluck('name', 'id')->all())
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->fillForm(fn (LotteryEvent $record) => [
                        'current_prize_id' => $record->current_prize_id,
                    ])
                    ->action(function (LotteryEvent $record, array $data): void {
                        $record->update(['current_prize_id' => $data['current_prize_id']]);
                        event(new LotteryEventUpdated($record->refresh()));
                    }),
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
            ->when($tenant, fn (Builder $query) => $query->where('organization_id', $tenant->getKey()));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLotteryEvents::route('/'),
            'create' => Pages\CreateLotteryEvent::route('/create'),
            'edit' => Pages\EditLotteryEvent::route('/{record}/edit'),
        ];
    }
}
