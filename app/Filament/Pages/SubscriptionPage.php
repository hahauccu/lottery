<?php

namespace App\Filament\Pages;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Services\EcpayService;
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
                'pendingPayments' => collect(),
                'recentPayments' => collect(),
            ];
        }

        $subscription = $organization->activeSubscription;
        $plan = $subscription?->plan;
        $employeeCount = $organization->employees()->count();

        $service = app(SubscriptionService::class);
        $availablePlans = $service->getAvailablePlans($organization);

        // 取得最近的付款記錄
        $recentPayments = Payment::where('organization_id', $organization->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'organization' => $organization,
            'subscription' => $subscription,
            'plan' => $plan,
            'employeeCount' => $employeeCount,
            'availablePlans' => $availablePlans,
            'isTestMode' => $organization->isTestMode(),
            'recentPayments' => $recentPayments,
        ];
    }

    /**
     * 發起付款流程
     */
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

        // 價格為 0 時直接啟用（免費方案）
        if ($plan->price <= 0) {
            $service = app(SubscriptionService::class);
            $subscription = $service->purchaseSubscription($organization, $plan, '免費方案啟用');

            Notification::make()
                ->success()
                ->title('方案啟用成功')
                ->body("方案：{$plan->name}，到期日：{$subscription->expires_at->format('Y-m-d H:i')}")
                ->send();

            return;
        }

        // 建立付款單並取得綠界表單
        $ecpayService = app(EcpayService::class);
        $result = $ecpayService->createPayment($organization, $plan);

        // 記錄日誌
        \App\Services\EcpayLogger::log('checkout', [
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'payment_id' => $result['payment']->id,
            'merchant_trade_no' => $result['params']['MerchantTradeNo'],
            'amount' => $result['params']['TotalAmount'],
        ]);

        // 透過 JavaScript 提交表單到綠界
        $this->dispatch('submit-ecpay-form', formHtml: $result['form_html']);
    }
}
