<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = '員工';

    protected static ?string $navigationGroup = '員工管理';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: Employee::class,
                        column: 'email',
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule) => $rule->where('organization_id', Filament::getTenant()?->getKey())
                    ),
                TextInput::make('phone')
                    ->label('手機')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('department')
                    ->label('部門')
                    ->maxLength(255),
                TextInput::make('employee_no')
                    ->label('員工編號')
                    ->maxLength(100),
                Select::make('groups')
                    ->label('群組')
                    ->relationship(
                        'groups',
                        'name',
                        fn (Builder $query) => $query->where('organization_id', Filament::getTenant()?->getKey())
                    )
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('手機')
                    ->toggleable(),
                TextColumn::make('department')
                    ->label('部門')
                    ->toggleable(),
                TextColumn::make('employee_no')
                    ->label('員工編號')
                    ->toggleable(),
                TextColumn::make('groups_count')
                    ->label('群組數')
                    ->counts('groups'),
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
            ->when($tenant, fn (Builder $query) => $query->where('organization_id', $tenant->getKey()));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
