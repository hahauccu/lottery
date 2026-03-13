<?php

namespace App\Livewire;

use App\Models\Prize;
use App\Models\PrizeWinner;
use App\Services\PrizeRedrawService;
use Filament\Notifications\Notification;
use Livewire\Component;

class ReprizeSelector extends Component
{
    public int $prizeId;

    public string $search = '';

    public array $selected = [];

    public bool $confirming = false;

    public function mount(int $prizeId): void
    {
        $this->prizeId = $prizeId;
    }

    public function getWinnersProperty()
    {
        $query = PrizeWinner::query()
            ->where('prize_id', $this->prizeId)
            ->with('employee')
            ->orderByRaw('released_at IS NOT NULL ASC')
            ->orderBy('sequence');

        if ($this->search !== '') {
            $search = $this->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function toggleSelect(int $winnerId): void
    {
        if (in_array($winnerId, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$winnerId]));
        } else {
            $this->selected[] = $winnerId;
        }
        $this->confirming = false;
    }

    public function startConfirm(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $this->confirming = true;
    }

    public function cancelConfirm(): void
    {
        $this->confirming = false;
    }

    public function executeRelease(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $prize = Prize::find($this->prizeId);
        if (! $prize) {
            return;
        }

        try {
            $released = app(PrizeRedrawService::class)->releaseWinners(
                $prize,
                $this->selected,
                auth()->id(),
                'absent'
            );

            $count = $released->count();

            Notification::make()
                ->success()
                ->title("已釋出 {$count} 位中獎者")
                ->body('前台已同步更新，可進行補抽')
                ->send();

            $this->selected = [];
            $this->confirming = false;

            $this->dispatch('close-modal', id: 'reprize-modal');
        } catch (\RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('釋出失敗')
                ->body($e->getMessage())
                ->send();

            $this->confirming = false;
        }
    }

    public function getSelectedWinnersProperty()
    {
        if (empty($this->selected)) {
            return collect();
        }

        return PrizeWinner::query()
            ->whereIn('id', $this->selected)
            ->with('employee')
            ->orderBy('sequence')
            ->get();
    }

    public function render()
    {
        return view('livewire.reprize-selector', [
            'winners' => $this->winners,
            'selectedWinners' => $this->selectedWinners,
        ]);
    }
}
