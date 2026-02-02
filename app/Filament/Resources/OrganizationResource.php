<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = '組織管理';

    protected static ?string $navigationGroup = '系統管理';

    protected static ?string $modelLabel = '組織';

    protected static ?string $pluralModelLabel = '組織';

    protected static ?int $navigationSort = 101;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('組織資訊')
                    ->schema([
                        TextInput::make('name')
                            ->label('組織名稱')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('代碼')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('用於 URL，如 /org-slug/lottery'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('組織名稱')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('代碼')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('employees_count')
                    ->label('員工數')
                    ->counts('employees')
                    ->sortable(),
                TextColumn::make('activeSubscription.plan.name')
                    ->label('當前方案')
                    ->placeholder('無')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('activeSubscription.expires_at')
                    ->label('到期日')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('無')
                    ->color(fn ($record) => $record->activeSubscription?->expires_at?->isPast() ? 'danger' : null),
                IconColumn::make('is_test_mode')
                    ->label('測試模式')
                    ->state(fn (Organization $record): bool => $record->isTestMode())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),
                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('assignSubscription')
                    ->label('指派方案')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->form([
                        Select::make('subscription_plan_id')
                            ->label('選擇方案')
                            ->options(SubscriptionPlan::active()->ordered()->pluck('name', 'id'))
                            ->required()
                            ->helperText(fn (Organization $record) => '目前員工數：'.$record->employees()->count().' 人'),
                        Textarea::make('notes')
                            ->label('備註')
                            ->rows(2),
                    ])
                    ->action(function (Organization $record, array $data): void {
                        $plan = SubscriptionPlan::find($data['subscription_plan_id']);

                        if (! $plan) {
                            Notification::make()
                                ->danger()
                                ->title('找不到方案')
                                ->send();

                            return;
                        }

                        $service = app(SubscriptionService::class);
                        $subscription = $service->purchaseSubscription($record, $plan, $data['notes'] ?? null);

                        Notification::make()
                            ->success()
                            ->title('已指派方案')
                            ->body("方案：{$plan->name}，到期日：{$subscription->expires_at->format('Y-m-d H:i')}")
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
