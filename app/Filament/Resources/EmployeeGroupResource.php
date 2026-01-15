<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeGroupResource\Pages;
use App\Models\EmployeeGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeGroupResource extends Resource
{
    protected static ?string $model = EmployeeGroup::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = '員工群組';

    protected static ?string $navigationGroup = '員工管理';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('群組名稱')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: EmployeeGroup::class,
                        column: 'name',
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule) => $rule->where('organization_id', Filament::getTenant()?->getKey())
                    ),
                Select::make('employees')
                    ->label('群組成員')
                    ->relationship(
                        'employees',
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
                    ->label('群組名稱')
                    ->searchable(),
                TextColumn::make('employees_count')
                    ->label('人數')
                    ->counts('employees'),
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
            'index' => Pages\ListEmployeeGroups::route('/'),
            'create' => Pages\CreateEmployeeGroup::route('/create'),
            'edit' => Pages\EditEmployeeGroup::route('/{record}/edit'),
        ];
    }
}
