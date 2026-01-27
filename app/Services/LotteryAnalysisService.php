<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\LotteryEvent;
use App\Models\Prize;
use App\Models\PrizeRule;
use Illuminate\Support\Collection;

class LotteryAnalysisService
{
    public function analyze(LotteryEvent $event, int $iterations, ?callable $onProgress = null): array
    {
        $iterations = max(1, $iterations);

        $prizes = $event->prizes()->with('rules')->get();
        $employees = Employee::query()
            ->where('organization_id', $event->organization_id)
            ->orderBy('name')
            ->get(['id', 'department']);

        $employeeDeptById = $employees
            ->mapWithKeys(fn (Employee $employee) => [$employee->id => $this->normalizeDepartment($employee->department)])
            ->all();

        $groupEmployeeMap = $this->buildGroupEmployeeMap($prizes);
        $prizeContexts = $this->buildPrizeContexts($prizes, $employees->pluck('id')->all(), $employeeDeptById, $groupEmployeeMap);

        $prizeStats = [];
        foreach ($prizeContexts as $context) {
            $prizeStats[$context['id']] = [
                'total_wins' => 0,
                'eligible_exposures' => 0,
            ];
        }

        $deptExposure = [];
        $deptWins = [];

        $lastProgress = 0;

        for ($iteration = 1; $iteration <= $iterations; $iteration++) {
            $winnersByPrizeId = [];

            foreach ($prizeContexts as $context) {
                $excludedSet = $this->buildExcludedSet($context['exclude_prize_ids'], $winnersByPrizeId);
                $eligibleIds = $this->filterEligibleIds($context['eligible_ids'], $excludedSet);
                $eligibleDeptCounts = $this->applyExcludedToDeptCounts($context['dept_counts'], $context['dept_by_id'], $excludedSet);

                $eligibleCount = array_sum($eligibleDeptCounts);
                $prizeStats[$context['id']]['eligible_exposures'] += $eligibleCount;

                foreach ($eligibleDeptCounts as $dept => $count) {
                    $deptExposure[$dept] = ($deptExposure[$dept] ?? 0) + $count;
                }

                if ($eligibleCount <= 0 || $context['winners_count'] <= 0) {
                    continue;
                }

                $winners = $this->pickWinners($eligibleIds, $context['winners_count'], $context['allow_repeat']);

                foreach ($winners as $winnerId) {
                    $prizeStats[$context['id']]['total_wins']++;
                    $dept = $context['dept_by_id'][$winnerId] ?? $this->normalizeDepartment(null);
                    $deptWins[$dept] = ($deptWins[$dept] ?? 0) + 1;
                    $winnersByPrizeId[$context['id']][$winnerId] = true;
                }
            }

            if ($onProgress) {
                $progress = (int) floor(($iteration / $iterations) * 100);
                if ($progress >= $lastProgress + 5) {
                    $lastProgress = $progress;
                    $onProgress($progress);
                }
            }
        }

        $prizeResults = [];
        $totalWins = 0;
        $totalEligibleExposure = 0;

        foreach ($prizeContexts as $context) {
            $stat = $prizeStats[$context['id']];
            $eligibleExposure = $stat['eligible_exposures'];
            $totalEligibleExposure += $eligibleExposure;
            $totalWins += $stat['total_wins'];

            $prizeResults[] = [
                'id' => $context['id'],
                'name' => $context['name'],
                'draw_mode' => $context['draw_mode'],
                'allow_repeat_within_prize' => $context['allow_repeat'],
                'winners_count' => $context['winners_count'],
                'eligible_avg' => $iterations > 0 ? $eligibleExposure / $iterations : 0,
                'eligible_exposures' => $eligibleExposure,
                'total_wins' => $stat['total_wins'],
                'win_rate' => $eligibleExposure > 0 ? $stat['total_wins'] / $eligibleExposure : 0,
            ];
        }

        $deptKeys = collect(array_keys($deptExposure))
            ->merge(array_keys($deptWins))
            ->unique()
            ->values()
            ->all();

        $departmentResults = [];
        foreach ($deptKeys as $dept) {
            $eligibleExposure = $deptExposure[$dept] ?? 0;
            $winCount = $deptWins[$dept] ?? 0;
            $eligiblePct = $totalEligibleExposure > 0 ? $eligibleExposure / $totalEligibleExposure : 0;
            $winPct = $totalWins > 0 ? $winCount / $totalWins : 0;

            $departmentResults[] = [
                'department' => $dept,
                'eligible_exposures' => $eligibleExposure,
                'eligible_pct' => $eligiblePct,
                'win_count' => $winCount,
                'win_pct' => $winPct,
                'delta_pct' => $winPct - $eligiblePct,
            ];
        }

        usort($departmentResults, fn (array $a, array $b) => $b['win_count'] <=> $a['win_count']);

        return [
            'iterations' => $iterations,
            'generated_at' => now()->toDateTimeString(),
            'summary' => [
                'total_wins' => $totalWins,
                'eligible_exposures' => $totalEligibleExposure,
            ],
            'prizes' => $prizeResults,
            'departments' => $departmentResults,
        ];
    }

    private function buildPrizeContexts(Collection $prizes, array $allEmployeeIds, array $employeeDeptById, array $groupEmployeeMap): array
    {
        $contexts = [];

        foreach ($prizes as $prize) {
            $rules = $prize->rules;
            $includeEmployeeIds = $this->ruleIds($rules, PrizeRule::TYPE_INCLUDE_EMPLOYEE);
            $includeGroupIds = $this->ruleIds($rules, PrizeRule::TYPE_INCLUDE_GROUP);
            $excludeEmployeeIds = $this->ruleIds($rules, PrizeRule::TYPE_EXCLUDE_EMPLOYEE);
            $excludeGroupIds = $this->ruleIds($rules, PrizeRule::TYPE_EXCLUDE_GROUP);
            $excludePrizeIds = $this->ruleIds($rules, PrizeRule::TYPE_EXCLUDE_PRIZE_WINNERS);

            $includeIds = $this->mergeIds($includeEmployeeIds, $this->idsForGroups($includeGroupIds, $groupEmployeeMap));
            $excludeIds = $this->mergeIds($excludeEmployeeIds, $this->idsForGroups($excludeGroupIds, $groupEmployeeMap));

            $eligibleIds = $this->applyIncludeExclude($allEmployeeIds, $includeIds, $excludeIds);

            $deptCounts = [];
            $deptById = [];
            foreach ($eligibleIds as $id) {
                $dept = $employeeDeptById[$id] ?? $this->normalizeDepartment(null);
                $deptById[$id] = $dept;
                $deptCounts[$dept] = ($deptCounts[$dept] ?? 0) + 1;
            }

            $contexts[] = [
                'id' => $prize->id,
                'name' => $prize->name,
                'draw_mode' => $prize->draw_mode,
                'allow_repeat' => (bool) $prize->allow_repeat_within_prize,
                'winners_count' => (int) $prize->winners_count,
                'exclude_prize_ids' => $excludePrizeIds,
                'eligible_ids' => $eligibleIds,
                'dept_counts' => $deptCounts,
                'dept_by_id' => $deptById,
            ];
        }

        return $contexts;
    }

    private function buildGroupEmployeeMap(Collection $prizes): array
    {
        $groupIds = $prizes
            ->flatMap(fn (Prize $prize) => $prize->rules->whereIn('type', [
                PrizeRule::TYPE_INCLUDE_GROUP,
                PrizeRule::TYPE_EXCLUDE_GROUP,
            ])->pluck('ref_id'))
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($groupIds === []) {
            return [];
        }

        return EmployeeGroup::query()
            ->whereIn('id', $groupIds)
            ->with(['employees:id'])
            ->get()
            ->mapWithKeys(fn (EmployeeGroup $group) => [
                $group->id => $group->employees->pluck('id')->all(),
            ])
            ->all();
    }

    private function ruleIds(Collection $rules, string $type): array
    {
        return $rules
            ->where('type', $type)
            ->pluck('ref_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function applyIncludeExclude(array $allEmployeeIds, array $includeIds, array $excludeIds): array
    {
        $eligible = $allEmployeeIds;

        if ($includeIds !== []) {
            $includeSet = array_flip($includeIds);
            $eligible = array_values(array_filter($eligible, fn ($id) => isset($includeSet[$id])));
        }

        if ($excludeIds !== []) {
            $excludeSet = array_flip($excludeIds);
            $eligible = array_values(array_filter($eligible, fn ($id) => ! isset($excludeSet[$id])));
        }

        return $eligible;
    }

    private function idsForGroups(array $groupIds, array $groupEmployeeMap): array
    {
        if ($groupIds === []) {
            return [];
        }

        $ids = [];
        foreach ($groupIds as $groupId) {
            foreach ($groupEmployeeMap[$groupId] ?? [] as $employeeId) {
                $ids[] = (int) $employeeId;
            }
        }

        return $this->normalizeIds($ids);
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

    private function buildExcludedSet(array $excludePrizeIds, array $winnersByPrizeId): array
    {
        if ($excludePrizeIds === []) {
            return [];
        }

        $excluded = [];
        foreach ($excludePrizeIds as $prizeId) {
            foreach ($winnersByPrizeId[$prizeId] ?? [] as $winnerId => $value) {
                $excluded[$winnerId] = true;
            }
        }

        return $excluded;
    }

    private function filterEligibleIds(array $eligibleIds, array $excludedSet): array
    {
        if ($excludedSet === []) {
            return $eligibleIds;
        }

        return array_values(array_filter($eligibleIds, fn ($id) => ! isset($excludedSet[$id])));
    }

    private function applyExcludedToDeptCounts(array $deptCounts, array $deptById, array $excludedSet): array
    {
        if ($excludedSet === []) {
            return $deptCounts;
        }

        $adjusted = $deptCounts;
        foreach ($excludedSet as $id => $value) {
            $dept = $deptById[$id] ?? null;
            if ($dept === null || ! isset($adjusted[$dept])) {
                continue;
            }

            $adjusted[$dept] = max(0, $adjusted[$dept] - 1);
            if ($adjusted[$dept] === 0) {
                unset($adjusted[$dept]);
            }
        }

        return $adjusted;
    }

    private function pickWinners(array $eligibleIds, int $winnersCount, bool $allowRepeat): array
    {
        if ($winnersCount <= 0 || $eligibleIds === []) {
            return [];
        }

        if ($allowRepeat) {
            $max = count($eligibleIds) - 1;
            $winners = [];
            for ($i = 0; $i < $winnersCount; $i++) {
                $winners[] = $eligibleIds[random_int(0, $max)];
            }

            return $winners;
        }

        $count = min($winnersCount, count($eligibleIds));
        $shuffled = $eligibleIds;
        shuffle($shuffled);

        return array_slice($shuffled, 0, $count);
    }

    private function normalizeDepartment(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : '未設定';
    }
}
