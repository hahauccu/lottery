<?php

namespace App\Livewire;

use App\Models\LotteryEvent;
use App\Services\EligibleEmployeesService;
use Illuminate\Support\Collection;
use Livewire\Component;

class EligibleEmployeesPreview extends Component
{
    public string $context = 'event';

    public ?int $organizationId = null;

    public ?int $eventId = null;

    public array $includeEmployeeIds = [];

    public array $includeGroupIds = [];

    public array $excludeEmployeeIds = [];

    public array $excludeGroupIds = [];

    public bool $allowRepeatWithinPrize = false;

    public ?int $currentPrizeId = null;

    public int $page = 1;

    public int $perPage = 50;

    public function updated($name): void
    {
        if ($name !== 'page') {
            $this->page = 1;
        }
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function render()
    {
        $employees = $this->eligibleEmployees();
        $total = $employees->count();
        $start = ($this->page - 1) * $this->perPage;
        $items = $employees->slice($start, $this->perPage)->values();
        $hasMore = $total > ($start + $this->perPage);

        return view('livewire.eligible-employees-preview', [
            'items' => $items,
            'total' => $total,
            'hasMore' => $hasMore,
        ]);
    }

    private function eligibleEmployees(): Collection
    {
        if (! $this->organizationId) {
            return collect();
        }

        $service = app(EligibleEmployeesService::class);

        if ($this->context === 'prize') {
            if (! $this->eventId) {
                return collect();
            }

            $event = LotteryEvent::find($this->eventId);

            if (! $event) {
                return collect();
            }

            return $service->eligibleForPrize(
                $event,
                $this->includeEmployeeIds,
                $this->includeGroupIds,
                $this->excludeEmployeeIds,
                $this->excludeGroupIds,
                $this->allowRepeatWithinPrize,
                $this->currentPrizeId
            );
        }

        return $service->eligibleForEvent(
            $this->organizationId,
            $this->includeEmployeeIds,
            $this->includeGroupIds,
            $this->excludeEmployeeIds,
            $this->excludeGroupIds
        );
    }
}
