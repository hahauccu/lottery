<?php

namespace App\Filament\Resources\LotteryEventResource\Pages;

use App\Filament\Resources\LotteryEventResource;
use App\Jobs\SendPrizeNotifications;
use App\Models\PrizeWinner;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageClaims extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = LotteryEventResource::class;

    protected static string $view = 'filament.resources.lottery-event-resource.pages.manage-claims';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return '領獎管理 - '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_all_notifications')
                ->label('發送所有未通知')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('發送中獎通知')
                ->modalDescription(fn () => sprintf(
                    '將發送通知給 %d 位尚未收到通知的中獎者。確定要發送嗎？',
                    $this->getUnnotifiedCount()
                ))
                ->visible(fn () => $this->getUnnotifiedCount() > 0)
                ->action(function (): void {
                    $count = $this->getUnnotifiedCount();

                    foreach ($this->record->prizes as $prize) {
                        if ($prize->winners()->whereNull('notified_at')->count() > 0) {
                            SendPrizeNotifications::dispatch($prize->id);
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('通知已排程')
                        ->body("正在發送 {$count} 封通知信")
                        ->send();
                }),
            Action::make('scan_qr')
                ->label('掃描 QR Code 領獎')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->form([
                    TextInput::make('claim_token')
                        ->label('掃描或輸入領獎代碼')
                        ->placeholder('掃描 QR Code 或輸入代碼')
                        ->required()
                        ->autofocus(),
                ])
                ->action(function (array $data): void {
                    $token = trim($data['claim_token']);

                    $winner = PrizeWinner::where('claim_token', $token)
                        ->whereHas('prize', fn (Builder $q) => $q->where('lottery_event_id', $this->record->id))
                        ->with(['employee', 'prize'])
                        ->first();

                    if (! $winner) {
                        Notification::make()
                            ->danger()
                            ->title('找不到中獎者')
                            ->body('無效的領獎代碼或不屬於此活動')
                            ->send();

                        return;
                    }

                    if ($winner->isClaimed()) {
                        Notification::make()
                            ->warning()
                            ->title('已領獎')
                            ->body(sprintf(
                                '%s 已於 %s 領取「%s」',
                                $winner->employee->name,
                                $winner->claimed_at->format('Y/m/d H:i'),
                                $winner->prize->name
                            ))
                            ->send();

                        return;
                    }

                    $winner->update(['claimed_at' => now()]);

                    Notification::make()
                        ->success()
                        ->title('領獎成功')
                        ->body(sprintf(
                            '%s 已完成領取「%s」',
                            $winner->employee->name,
                            $winner->prize->name
                        ))
                        ->send();
                }),
            Action::make('back')
                ->label('返回活動')
                ->color('gray')
                ->url(LotteryEventResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PrizeWinner::query()
                ->whereHas('prize', fn (Builder $q) => $q->where('lottery_event_id', $this->record->id))
                ->with(['employee', 'prize']))
            ->columns([
                TextColumn::make('prize.name')
                    ->label('獎項')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sequence')
                    ->label('序號')
                    ->sortable()
                    ->prefix('#'),
                TextColumn::make('employee.name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('employee.department')
                    ->label('部門')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('won_at')
                    ->label('中獎時間')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('notified_at')
                    ->label('通知')
                    ->formatStateUsing(fn ($state) => $state ? '已通知' : '未通知')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('claimed_at')
                    ->label('領獎')
                    ->formatStateUsing(fn ($state) => $state ? '已領獎' : '待領獎')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->sortable(),
            ])
            ->defaultSort('won_at', 'desc')
            ->filters([
                SelectFilter::make('prize_id')
                    ->label('獎項')
                    ->options(fn () => $this->record->prizes()->orderBy('sort_order')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('claim_status')
                    ->label('領獎狀態')
                    ->options([
                        'claimed' => '已領獎',
                        'pending' => '待領獎',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value']) {
                            'claimed' => $query->whereNotNull('claimed_at'),
                            'pending' => $query->whereNull('claimed_at'),
                            default => $query,
                        };
                    }),
                SelectFilter::make('notification_status')
                    ->label('通知狀態')
                    ->options([
                        'notified' => '已通知',
                        'pending' => '未通知',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value']) {
                            'notified' => $query->whereNotNull('notified_at'),
                            'pending' => $query->whereNull('notified_at'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                TableAction::make('show_qr')
                    ->label('QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->modalHeading(fn (PrizeWinner $record) => "領獎 QR Code - {$record->employee->name}")
                    ->modalContent(fn (PrizeWinner $record) => view('filament.components.qr-code-modal', [
                        'winner' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('關閉'),
                TableAction::make('mark_claimed')
                    ->label('領獎')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PrizeWinner $record) => ! $record->isClaimed())
                    ->action(function (PrizeWinner $record): void {
                        $record->update(['claimed_at' => now()]);

                        Notification::make()
                            ->success()
                            ->title('已標記領獎')
                            ->body(sprintf('%s 已完成領獎', $record->employee->name))
                            ->send();
                    }),
                TableAction::make('unmark_claimed')
                    ->label('取消')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (PrizeWinner $record) => $record->isClaimed())
                    ->action(function (PrizeWinner $record): void {
                        $record->update(['claimed_at' => null]);

                        Notification::make()
                            ->success()
                            ->title('已取消領獎狀態')
                            ->send();
                    }),
            ]);
    }

    public function getWinnersCount(): int
    {
        return PrizeWinner::whereHas('prize', fn (Builder $q) => $q->where('lottery_event_id', $this->record->id))->count();
    }

    public function getNotifiedCount(): int
    {
        return PrizeWinner::whereHas('prize', fn (Builder $q) => $q->where('lottery_event_id', $this->record->id))
            ->whereNotNull('notified_at')
            ->count();
    }

    public function getUnnotifiedCount(): int
    {
        return PrizeWinner::whereHas('prize', fn (Builder $q) => $q->where('lottery_event_id', $this->record->id))
            ->whereNull('notified_at')
            ->count();
    }

    public function getClaimedCount(): int
    {
        return PrizeWinner::whereHas('prize', fn (Builder $q) => $q->where('lottery_event_id', $this->record->id))
            ->whereNotNull('claimed_at')
            ->count();
    }
}
