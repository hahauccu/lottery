<?php

namespace App\Filament\Resources\LotteryEventResource\RelationManagers;

use App\Events\LotteryEventUpdated;
use App\Livewire\EligibleEmployeesPreview;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\Prize;
use App\Models\PrizeRule;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Livewire as LivewireComponent;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class PrizesRelationManager extends RelationManager
{
    protected static string $relationship = 'prizes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('基本資訊')
                    ->schema([
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
                            ->options(fn (?Prize $record) => $this->getOwnerRecord()
                                ->prizes()
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                ->orderBy('sort_order')
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
                            'eventId' => $this->getOwnerRecord()->getKey(),
                            'includeEmployeeIds' => $get('include_employee_ids') ?? [],
                            'includeGroupIds' => $get('include_group_ids') ?? [],
                            'excludeEmployeeIds' => $get('exclude_employee_ids') ?? [],
                            'excludeGroupIds' => $get('exclude_group_ids') ?? [],
                            'excludePrizeIds' => $get('exclude_prize_ids') ?? [],
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
                                $get('exclude_prize_ids'),
                                $get('allow_repeat_within_prize'),
                            ])))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('獎項名稱')
                    ->searchable(),
                TextColumn::make('winners_count')
                    ->label('中獎人數'),
                TextColumn::make('draw_mode')
                    ->label('抽獎模式')
                    ->formatStateUsing(fn (string $state) => $state === Prize::DRAW_MODE_ONE_BY_ONE ? '逐一抽出' : '一次全抽'),
                IconColumn::make('is_current')
                    ->label('目前')
                    ->boolean()
                    ->getStateUsing(fn (Prize $record): bool => (int) $this->getOwnerRecord()->current_prize_id === (int) $record->getKey()),
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
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('set_current')
                    ->label('設為目前獎項')
                    ->requiresConfirmation()
                    ->action(function (Prize $record): void {
                        $event = $this->getOwnerRecord();
                        $event->update(['current_prize_id' => $record->getKey()]);

                        event(new LotteryEventUpdated($event->refresh()));
                    }),
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, Prize $record): array {
                        return array_merge($data, $this->ruleStateFromRecord($record));
                    })
                    ->using(function (Prize $record, array $data): Prize {
                        $record->update($this->stripRuleFields($data));
                        $this->syncRules($record, $data);

                        return $record;
                    }),
                DeleteAction::make()
                    ->before(function (Prize $record): void {
                        $event = $this->getOwnerRecord();

                        if ((int) $event->current_prize_id !== (int) $record->getKey()) {
                            return;
                        }

                        $event->update(['current_prize_id' => null]);
                        event(new LotteryEventUpdated($event->refresh()));
                    }),
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
            'exclude_prize_ids',
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
            'exclude_prize_ids' => $record->rules()
                ->where('type', PrizeRule::TYPE_EXCLUDE_PRIZE_WINNERS)
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

        foreach (array_unique($state['exclude_prize_ids'] ?? []) as $id) {
            $rules[] = ['type' => PrizeRule::TYPE_EXCLUDE_PRIZE_WINNERS, 'ref_id' => $id];
        }

        return $rules;
    }
}
