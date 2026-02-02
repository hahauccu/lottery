<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\LotteryEvent;
use App\Models\Organization;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoLotterySeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrCreate(
            ['slug' => 'demo-org'],
            ['name' => '測試組織', 'slug' => 'demo-org']
        );

        if ($organization->name !== '測試組織') {
            $organization->update(['name' => '測試組織']);
        }

        $user = User::firstOrCreate(
            ['email' => 'test1@example.com'],
            [
                'name' => '測試使用者',
                'password' => Hash::make('11111111'),
                'email_verified_at' => now(),
            ]
        );

        $user->forceFill([
            'name' => '測試使用者',
            'password' => Hash::make('11111111'),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->organizations()->sync([$organization->id]);

        $event = LotteryEvent::firstOrNew(['brand_code' => 'DEMO01']);
        $event->fill([
            'organization_id' => $organization->id,
            'name' => '測試抽獎活動',
            'is_lottery_open' => true,
            'show_prizes_preview' => false,
        ]);
        $event->save();

        $prizeDefs = [
            [
                'name' => '特別獎',
                'winners_count' => 1,
                'draw_mode' => Prize::DRAW_MODE_ONE_BY_ONE,
                'animation_style' => 'big_treasure_chest',
                'lotto_hold_seconds' => 6,
                'allow_repeat_within_prize' => false,
                'sort_order' => 1,
            ],
            [
                'name' => '頭獎',
                'winners_count' => 1,
                'draw_mode' => Prize::DRAW_MODE_ONE_BY_ONE,
                'animation_style' => 'treasure_chest',
                'lotto_hold_seconds' => 6,
                'allow_repeat_within_prize' => false,
                'sort_order' => 2,
            ],
            [
                'name' => '二獎',
                'winners_count' => 2,
                'draw_mode' => Prize::DRAW_MODE_ALL_AT_ONCE,
                'animation_style' => 'red_packet',
                'lotto_hold_seconds' => 5,
                'allow_repeat_within_prize' => false,
                'sort_order' => 3,
            ],
            [
                'name' => '三獎',
                'winners_count' => 3,
                'draw_mode' => Prize::DRAW_MODE_ALL_AT_ONCE,
                'animation_style' => 'scratch_card',
                'lotto_hold_seconds' => 5,
                'allow_repeat_within_prize' => false,
                'sort_order' => 4,
            ],
            [
                'name' => '四獎',
                'winners_count' => 5,
                'draw_mode' => Prize::DRAW_MODE_ALL_AT_ONCE,
                'animation_style' => 'lotto_air',
                'lotto_hold_seconds' => 5,
                'allow_repeat_within_prize' => false,
                'sort_order' => 5,
            ],
        ];

        $prizeIds = [];
        foreach ($prizeDefs as $data) {
            $prize = Prize::updateOrCreate(
                [
                    'lottery_event_id' => $event->id,
                    'sort_order' => $data['sort_order'],
                ],
                array_merge($data, [
                    'lottery_event_id' => $event->id,
                    'sound_enabled' => true,
                ])
            );
            $prizeIds[] = $prize->id;
        }

        if ($prizeIds !== []) {
            $event->current_prize_id = $prizeIds[0];
            $event->save();
        }

        $departments = ['行政部', '人資部', '業務部', '研發部'];
        $names = [
            '陳冠廷',
            '林雅婷',
            '黃昱辰',
            '張怡君',
            '李柏翰',
            '王佳穎',
            '吳思妤',
            '劉宇翔',
            '蔡子涵',
            '楊婉晴',
            '許承翰',
            '鄭詩涵',
            '謝浩宇',
            '洪郁婷',
            '郭品妤',
            '邱睿哲',
            '曾靖雯',
            '廖哲偉',
            '賴家瑜',
            '周建宏',
            '陳雅雯',
            '林俊傑',
            '黃怡婷',
            '張冠宇',
            '李芷晴',
            '王博文',
            '吳佳玲',
            '劉俊佑',
            '蔡庭瑄',
            '楊怡伶',
        ];
        $now = now();
        $employees = [];

        for ($i = 1; $i <= 30; $i++) {
            $name = $names[$i - 1] ?? sprintf('員工%03d', $i);
            $employees[] = [
                'organization_id' => $organization->id,
                'name' => $name,
                'email' => sprintf('test1-emp-%03d@example.com', $i),
                'phone' => null,
                'department' => $departments[($i - 1) % count($departments)],
                'employee_no' => sprintf('EMP-%04d', $i),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Employee::upsert(
            $employees,
            ['organization_id', 'email'],
            ['name', 'phone', 'department', 'employee_no', 'updated_at']
        );

        $employeeRows = Employee::query()
            ->where('organization_id', $organization->id)
            ->get(['id', 'department']);

        $groupDefinitions = [
            [
                'name' => '全體員工',
                'filter' => static fn (Employee $employee): bool => true,
            ],
            [
                'name' => '行政組',
                'filter' => static fn (Employee $employee): bool => $employee->department === '行政部',
            ],
            [
                'name' => '人資組',
                'filter' => static fn (Employee $employee): bool => $employee->department === '人資部',
            ],
            [
                'name' => '業務組',
                'filter' => static fn (Employee $employee): bool => $employee->department === '業務部',
            ],
            [
                'name' => '研發組',
                'filter' => static fn (Employee $employee): bool => $employee->department === '研發部',
            ],
        ];

        foreach ($groupDefinitions as $definition) {
            $group = EmployeeGroup::updateOrCreate(
                ['organization_id' => $organization->id, 'name' => $definition['name']],
                ['organization_id' => $organization->id, 'name' => $definition['name']]
            );

            $memberIds = $employeeRows
                ->filter($definition['filter'])
                ->pluck('id')
                ->all();

            $group->employees()->sync($memberIds);
        }
    }
}
