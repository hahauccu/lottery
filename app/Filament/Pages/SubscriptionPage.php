<?php

namespace App\Filament\Pages;

use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SubscriptionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = '訂閱管理';

    protected static ?string $title = '訂閱管理';

    protected static ?string $navigationGroup = '設定';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.subscription-page';

    public function getViewData(): array
    {
        $organization = Filament::getTenant();

        if (! $organization) {
            return [
                'organization' => null,
                'subscription' => null,
                'plan' => null,
                'employeeCount' => 0,
                'availablePlans' => collect(),
                'isTestMode' => true,
            ];
        }

        $subscription = $organization->activeSubscription;
        $plan = $subscription?->plan;
        $employeeCount = $organization->employees()->count();

        $service = app(SubscriptionService::class);
        $availablePlans = $service->getAvailablePlans($organization);

        return [
            'organization' => $organization,
            'subscription' => $subscription,
            'plan' => $plan,
            'employeeCount' => $employeeCount,
            'availablePlans' => $availablePlans,
            'isTestMode' => $organization->isTestMode(),
        ];
    }

    public function purchasePlan(int $planId): void
    {
        $organization = Filament::getTenant();

        if (! $organization) {
            Notification::make()
                ->danger()
                ->title('找不到組織資訊')
                ->send();

            return;
        }

        $plan = SubscriptionPlan::find($planId);

        if (! $plan || ! $plan->is_active) {
            Notification::make()
                ->danger()
                ->title('找不到方案或方案已停用')
                ->send();

            return;
        }

        $employeeCount = $organization->employees()->count();

        if ($employeeCount > $plan->max_employees) {
            Notification::make()
                ->danger()
                ->title('員工數超過方案上限')
                ->body("目前員工數 {$employeeCount} 人，方案上限 {$plan->max_employees} 人")
                ->send();

            return;
        }

        $service = app(SubscriptionService::class);
        $subscription = $service->purchaseSubscription($organization, $plan);

        Notification::make()
            ->success()
            ->title('購買成功')
            ->body("方案：{$plan->name}，到期日：{$subscription->expires_at->format('Y-m-d H:i')}")
            ->send();
    }
}
