<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'code' => 'lv1',
                'name' => '基本版',
                'max_employees' => 100,
                'price' => 3000,
                'duration_days' => 30,
                'description' => '適合 100 人以下的小型組織',
                'sort_order' => 1,
            ],
            [
                'code' => 'lv2',
                'name' => '進階版',
                'max_employees' => 200,
                'price' => 5000,
                'duration_days' => 30,
                'description' => '適合 200 人以下的中型組織',
                'sort_order' => 2,
            ],
            [
                'code' => 'lv3',
                'name' => '企業版',
                'max_employees' => 500,
                'price' => 8000,
                'duration_days' => 30,
                'description' => '適合 500 人以下的大型組織',
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );
        }
    }
}
