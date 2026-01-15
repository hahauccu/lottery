<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\LotteryEvent;
use App\Models\Prize;
use App\Models\PrizeWinner;
use Illuminate\Support\Collection;

class EligibleEmployeesService
{
    public function eligibleForEvent(int $organizationId, array $includeEmployeeIds, array $includeGroupIds, array $excludeEmployeeIds, array $excludeGroupIds): Collection
    {
        $includeEmployeeIds = $this->normalizeIds($includeEmployeeIds);
        $includeGroupIds = $this->normalizeIds($includeGroupIds);
        $excludeEmployeeIds = $this->normalizeIds($excludeEmployeeIds);
        $excludeGroupIds = $this->normalizeIds($excludeGroupIds);

        $includeGroupEmployeeIds = $this->employeeIdsForGroups($includeGroupIds);
        $excludeGroupEmployeeIds = $this->employeeIdsForGroups($excludeGroupIds);

        $includeIds = $this->mergeIds($includeEmployeeIds, $includeGroupEmployeeIds);
        $excludeIds = $this->mergeIds($excludeEmployeeIds, $excludeGroupEmployeeIds);

        $query = Employee::query()->where('organization_id', $organizationId);

        if (! empty($includeIds)) {
            $query->whereIn('id', $includeIds);
        }

        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->orderBy('name')->get();
    }

    public function eligibleForPrize(LotteryEvent $event, array $includeEmployeeIds, array $includeGroupIds, array $excludeEmployeeIds, array $excludeGroupIds, array $excludePrizeIds, bool $allowRepeatWithinPrize, ?int $currentPrizeId = null): Collection
    {
        $base = $this->eligibleForEvent(
            $event->organization_id,
            $event->rules()->where('type', \App\Models\EventRule::TYPE_INCLUDE_EMPLOYEE)->pluck('ref_id')->all(),
            $event->rules()->where('type', \App\Models\EventRule::TYPE_INCLUDE_GROUP)->pluck('ref_id')->all(),
            $event->rules()->where('type', \App\Models\EventRule::TYPE_EXCLUDE_EMPLOYEE)->pluck('ref_id')->all(),
            $event->rules()->where('type', \App\Models\EventRule::TYPE_EXCLUDE_GROUP)->pluck('ref_id')->all()
        );

        $includeEmployeeIds = $this->normalizeIds($includeEmployeeIds);
        $includeGroupIds = $this->normalizeIds($includeGroupIds);
        $excludeEmployeeIds = $this->normalizeIds($excludeEmployeeIds);
        $excludeGroupIds = $this->normalizeIds($excludeGroupIds);
        $excludePrizeIds = $this->normalizeIds($excludePrizeIds);

        $includeGroupEmployeeIds = $this->employeeIdsForGroups($includeGroupIds);
        $excludeGroupEmployeeIds = $this->employeeIdsForGroups($excludeGroupIds);

        $includeIds = $this->mergeIds($includeEmployeeIds, $includeGroupEmployeeIds);
        $excludeIds = $this->mergeIds($excludeEmployeeIds, $excludeGroupEmployeeIds);

        if (! empty($includeIds)) {
            $base = $base->whereIn('id', $includeIds);
        }

        if (! empty($excludeIds)) {
            $base = $base->whereNotIn('id', $excludeIds);
        }

        if (! empty($excludePrizeIds)) {
            $excludedWinnerIds = PrizeWinner::query()
                ->whereIn('prize_id', $excludePrizeIds)
                ->pluck('employee_id')
                ->unique()
                ->values()
                ->all();

            if (! empty($excludedWinnerIds)) {
                $base = $base->whereNotIn('id', $excludedWinnerIds);
            }
        }

        if (! $allowRepeatWithinPrize && $currentPrizeId) {
            $currentWinnerIds = PrizeWinner::query()
                ->where('prize_id', $currentPrizeId)
                ->pluck('employee_id')
                ->unique()
                ->values()
                ->all();

            if (! empty($currentWinnerIds)) {
                $base = $base->whereNotIn('id', $currentWinnerIds);
            }
        }

        return $base->values();
    }

    public function eligibleForStoredPrize(Prize $prize): Collection
    {
        return $this->eligibleForPrize(
            $prize->lotteryEvent,
            $prize->rules()->where('type', \App\Models\PrizeRule::TYPE_INCLUDE_EMPLOYEE)->pluck('ref_id')->all(),
            $prize->rules()->where('type', \App\Models\PrizeRule::TYPE_INCLUDE_GROUP)->pluck('ref_id')->all(),
            $prize->rules()->where('type', \App\Models\PrizeRule::TYPE_EXCLUDE_EMPLOYEE)->pluck('ref_id')->all(),
            $prize->rules()->where('type', \App\Models\PrizeRule::TYPE_EXCLUDE_GROUP)->pluck('ref_id')->all(),
            $prize->rules()->where('type', \App\Models\PrizeRule::TYPE_EXCLUDE_PRIZE_WINNERS)->pluck('ref_id')->all(),
            $prize->allow_repeat_within_prize,
            $prize->id,
        );
    }

    private function employeeIdsForGroups(array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }

        return EmployeeGroup::query()
            ->whereIn('id', $groupIds)
            ->with('employees')
            ->get()
            ->flatMap(fn (EmployeeGroup $group) => $group->employees->pluck('id'))
            ->unique()
            ->values()
            ->all();
    }

    private function mergeIds(array ...$groups): array
    {
        return collect($groups)
            ->flatten()
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->flatten()
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
