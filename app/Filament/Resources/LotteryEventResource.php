<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LotteryEventResource\Pages;
use App\Filament\Resources\LotteryEventResource\RelationManagers\PrizesRelationManager;
use App\Models\LotteryEvent;
use App\Support\LotteryBroadcaster;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        Toggle::make('show_prizes_preview')
                            ->label('顯示獎項預覽')
                            ->helperText('啟用後前台會顯示所有獎項資訊（無當前獎項時自動顯示，或手動開啟）')
                            ->default(false),
                        Toggle::make('danmaku_enabled')
                            ->label('啟用彈幕')
                            ->helperText('啟用後員工可在中獎清單頁面發送彈幕至抽獎頁面')
                            ->default(false),
                        FileUpload::make('default_bg_image_path')
                            ->label('預設背景圖')
                            ->disk('public')
                            ->directory(function (?LotteryEvent $record) {
                                $slug = Filament::getTenant()?->slug
                                    ?? $record?->organization?->slug;

                                return $slug
                                    ? 'lottery/'.$slug
                                    : 'lottery/pending';
                            })
                            ->image()
                            ->imagePreviewHeight('120')
                            ->maxSize(4096),
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
                        LotteryBroadcaster::dispatchUpdate($record->refresh());
                    }),
                ToggleColumn::make('show_prizes_preview')
                    ->label('顯示獎項預覽')
                    ->afterStateUpdated(function (LotteryEvent $record): void {
                        LotteryBroadcaster::dispatchUpdate($record->refresh());
                    }),
                ToggleColumn::make('danmaku_enabled')
                    ->label('彈幕')
                    ->afterStateUpdated(function (LotteryEvent $record): void {
                        LotteryBroadcaster::dispatchUpdate($record->refresh());
                    }),
                TextColumn::make('currentPrize.name')
                    ->label('目前獎項')
                    ->toggleable(),
                TextColumn::make('prizes_count')
                    ->label('獎項數')
                    ->counts('prizes'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('claims')
                    ->label('領獎管理')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->url(fn (LotteryEvent $record) => static::getUrl('claims', ['record' => $record])),
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

    public static function getRelations(): array
    {
        return [
            PrizesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLotteryEvents::route('/'),
            'create' => Pages\CreateLotteryEvent::route('/create'),
            'edit' => Pages\EditLotteryEvent::route('/{record}/edit'),
            'analysis' => Pages\AnalyzeLotteryEvent::route('/{record}/analysis'),
            'claims' => Pages\ManageClaims::route('/{record}/claims'),
        ];
    }
}
