<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionPlanResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    protected static ?string $model = SubscriptionPlan::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = '訂閱方案';

    protected static ?string $navigationGroup = '系統管理';

    protected static ?string $modelLabel = '訂閱方案';

    protected static ?string $pluralModelLabel = '訂閱方案';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('方案資訊')
                    ->schema([
                        TextInput::make('name')
                            ->label('方案名稱')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('方案代碼')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('唯一識別碼，如 lv1, lv2, lv3'),
                        TextInput::make('max_employees')
                            ->label('員工上限')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('price')
                            ->label('價格（元）')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('元'),
                        TextInput::make('duration_days')
                            ->label('有效天數')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('天'),
                        Textarea::make('description')
                            ->label('方案描述')
                            ->rows(2)
                            ->maxLength(1000),
                        TextInput::make('sort_order')
                            ->label('排序')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('啟用')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('方案名稱')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('代碼')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('max_employees')
                    ->label('員工上限')
                    ->suffix(' 人')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('價格')
                    ->money('TWD')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('有效天數')
                    ->suffix(' 天'),
                IconColumn::make('is_active')
                    ->label('啟用')
                    ->boolean(),
                TextColumn::make('subscriptions_count')
                    ->label('訂閱數')
                    ->counts('subscriptions'),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
