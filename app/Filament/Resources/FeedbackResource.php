<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedbackResource\Pages;
use App\Models\Feedback;
use App\Support\AnimationStyles;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = '意見回饋';

    protected static ?string $modelLabel = '意見回饋';

    protected static ?string $pluralModelLabel = '意見回饋';

    protected static ?string $navigationGroup = '系統管理';

    protected static ?int $navigationSort = 103;

    public static function canViewAny(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('status')->label('處理狀態')->options(Feedback::STATUSES)->required(),
            Textarea::make('admin_notes')->label('內部處理備註')->maxLength(3000)->rows(3)->columnSpanFull(),
            Select::make('category')->label('分類')->options(Feedback::CATEGORIES)->disabled(),
            TextInput::make('email')->label('回覆信箱')->disabled(),
            Select::make('animation_style')->label('動畫')->options(AnimationStyles::labels())->disabled(),
            TextInput::make('source_path')->label('來源頁面')->disabled(),
            TextInput::make('viewport_width')->label('視窗寬度')->disabled(),
            TextInput::make('viewport_height')->label('視窗高度')->disabled(),
            Textarea::make('message')->label('回饋內容')->rows(8)->disabled()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('編號')->sortable(),
            TextColumn::make('created_at')->label('收到時間')->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('category')->label('分類')->formatStateUsing(fn (string $state) => Feedback::CATEGORIES[$state])->badge(),
            TextColumn::make('message')->label('內容')->limit(45)->searchable()->wrap(),
            TextColumn::make('email')->label('回覆信箱')->searchable()->placeholder('未提供'),
            TextColumn::make('animation_style')->label('動畫')->formatStateUsing(fn (string $state) => AnimationStyles::labels()[$state] ?? $state)->placeholder('未指定'),
            TextColumn::make('status')->label('處理狀態')->formatStateUsing(fn (string $state) => Feedback::STATUSES[$state])->badge()
                ->color(fn (string $state) => $state === 'resolved' ? 'success' : 'warning'),
            TextColumn::make('notified_at')->label('通知寄出時間')->dateTime('Y-m-d H:i')->placeholder('未寄出')->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('處理狀態')->options(Feedback::STATUSES),
                SelectFilter::make('category')->label('分類')->options(Feedback::CATEGORIES),
            ])->actions([EditAction::make()->label('查看／處理')])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedback::route('/'),
            'edit' => Pages\EditFeedback::route('/{record}/edit'),
        ];
    }
}
