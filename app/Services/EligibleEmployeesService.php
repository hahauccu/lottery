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

    public function eligibleForPrize(LotteryEvent $event, array $includeEmployeeIds, array $includeGroupIds, array $excludeEmployeeIds, array $excludeGroupIds, bool $allowRepeatWithinPrize, ?int $currentPrizeId = null): Collection
    {
        $includeEmployeeIds = $this->normalizeIds($includeEmployeeIds);
        $includeGroupIds = $this->normalizeIds($includeGroupIds);
        $excludeEmployeeIds = $this->normalizeIds($excludeEmployeeIds);
        $excludeGroupIds = $this->normalizeIds($excludeGroupIds);

        // 若未選任何包含 → 可抽人數 = 0
        if (empty($includeEmployeeIds) && empty($includeGroupIds)) {
            return collect();
        }

        $includeGroupEmployeeIds = $this->employeeIdsForGroups($includeGroupIds, $event);
        $excludeGroupEmployeeIds = $this->employeeIdsForGroups($excludeGroupIds, $event);

        $includeIds = $this->mergeIds($includeEmployeeIds, $includeGroupEmployeeIds);
        $excludeIds = $this->mergeIds($excludeEmployeeIds, $excludeGroupEmployeeIds);

        $base = Employee::query()
            ->where('organization_id', $event->organization_id)
            ->whereIn('id', $includeIds)
            ->orderBy('name')
            ->get();

        if (! empty($excludeIds)) {
            $base = $base->whereNotIn('id', $excludeIds);
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
            $prize->allow_repeat_within_prize,
            $prize->id,
        );
    }

    private function employeeIdsForGroups(array $groupIds, ?LotteryEvent $event = null): array
    {
        if (empty($groupIds)) {
            return [];
        }

        $systemGroupService = app(SystemGroupService::class);

        $groups = EmployeeGroup::query()
            ->whereIn('id', $groupIds)
            ->with('employees')
            ->get();

        return $groups
            ->flatMap(function (EmployeeGroup $group) use ($systemGroupService) {
                if ($group->isSystemGroup()) {
                    return $systemGroupService->getMemberIdsForGroup($group);
                }

                return $group->employees->pluck('id');
            })
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
