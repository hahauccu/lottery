<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\LotteryEvent;
use App\Models\Prize;
use App\Models\PrizeWinner;
use Illuminate\Support\Collection;

class SystemGroupService
{
    public const KEY_ALL_EMPLOYEES = 'all_employees';

    public const KEY_EVENT_WINNERS = 'event_winners';

    public const KEY_PRIZE_WINNERS = 'prize_winners';

    /**
     * 確保活動有基礎系統群組（全體員工、已中獎員工）
     */
    public function ensureEventSystemGroups(LotteryEvent $event): void
    {
        $this->findOrCreateGroup(
            $event->organization_id,
            $event->id,
            null,
            self::KEY_ALL_EMPLOYEES,
            '全體員工'
        );

        $this->findOrCreateGroup(
            $event->organization_id,
            $event->id,
            null,
            self::KEY_EVENT_WINNERS,
            '已中獎員工'
        );
    }

    /**
     * 同步獎項中獎者群組
     */
    public function syncPrizeWinnersGroup(Prize $prize): void
    {
        $event = $prize->lotteryEvent;

        $this->findOrCreateGroup(
            $event->organization_id,
            $event->id,
            $prize->id,
            self::KEY_PRIZE_WINNERS,
            "{$prize->name}中獎員工"
        );
    }

    /**
     * 取得活動的所有系統群組
     */
    public function getSystemGroupsForEvent(LotteryEvent $event): Collection
    {
        return EmployeeGroup::query()
            ->where('organization_id', $event->organization_id)
            ->where('lottery_event_id', $event->id)
            ->whereNotNull('system_key')
            ->orderByRaw("CASE system_key
                WHEN 'all_employees' THEN 1
                WHEN 'event_winners' THEN 2
                ELSE 3
            END")
            ->orderBy('name')
            ->get();
    }

    /**
     * 動態計算系統群組成員
     */
    public function getMembersForGroup(EmployeeGroup $group): Collection
    {
        if (! $this->isSystemGroup($group)) {
            return $group->employees;
        }

        return match ($group->system_key) {
            self::KEY_ALL_EMPLOYEES => $this->getAllEmployees($group),
            self::KEY_EVENT_WINNERS => $this->getEventWinners($group),
            self::KEY_PRIZE_WINNERS => $this->getPrizeWinners($group),
            default => collect(),
        };
    }

    /**
     * 取得系統群組的成員 ID
     */
    public function getMemberIdsForGroup(EmployeeGroup $group): array
    {
        return $this->getMembersForGroup($group)->pluck('id')->all();
    }

    /**
     * 判斷是否為系統群組
     */
    public function isSystemGroup(EmployeeGroup $group): bool
    {
        return $group->system_key !== null;
    }

    /**
     * 取得組織全體員工
     */
    private function getAllEmployees(EmployeeGroup $group): Collection
    {
        return Employee::query()
            ->where('organization_id', $group->organization_id)
            ->get();
    }

    /**
     * 取得活動所有中獎者
     */
    private function getEventWinners(EmployeeGroup $group): Collection
    {
        if (! $group->lottery_event_id) {
            return collect();
        }

        $winnerIds = PrizeWinner::query()
            ->whereHas('prize', fn ($q) => $q->where('lottery_event_id', $group->lottery_event_id))
            ->pluck('employee_id')
            ->unique();

        return Employee::query()
            ->whereIn('id', $winnerIds)
            ->get();
    }

    /**
     * 取得獎項中獎者
     */
    private function getPrizeWinners(EmployeeGroup $group): Collection
    {
        if (! $group->prize_id) {
            return collect();
        }

        $winnerIds = PrizeWinner::query()
            ->where('prize_id', $group->prize_id)
            ->pluck('employee_id')
            ->unique();

        return Employee::query()
            ->whereIn('id', $winnerIds)
            ->get();
    }

    /**
     * 尋找或建立系統群組
     */
    private function findOrCreateGroup(
        int $organizationId,
        int $lotteryEventId,
        ?int $prizeId,
        string $systemKey,
        string $name
    ): EmployeeGroup {
        return EmployeeGroup::firstOrCreate(
            [
                'organization_id' => $organizationId,
                'lottery_event_id' => $lotteryEventId,
                'system_key' => $systemKey,
                'prize_id' => $prizeId,
            ],
            ['name' => $name]
        );
    }
}
