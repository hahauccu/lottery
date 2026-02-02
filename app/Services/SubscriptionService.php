<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class SubscriptionService
{
    public function hasValidSubscription(Organization $organization): bool
    {
        return $organization->hasValidSubscription();
    }

    public function getRequiredPlan(Organization $organization): ?SubscriptionPlan
    {
        $employeeCount = $organization->employees()->count();

        return SubscriptionPlan::query()
            ->active()
            ->where('max_employees', '>=', $employeeCount)
            ->ordered()
            ->first();
    }

    public function createSubscription(
        Organization $organization,
        SubscriptionPlan $plan,
        ?string $notes = null
    ): OrganizationSubscription {
        $now = Carbon::now();
        // 從隔天開始算完整天數：今天購買，明天開始生效，享有完整 duration_days 天
        $startsAt = $now->copy()->addDay()->startOfDay();
        $expiresAt = $startsAt->copy()->addDays($plan->duration_days);

        return OrganizationSubscription::create([
            'organization_id' => $organization->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'notes' => $notes,
        ]);
    }

    public function extendSubscription(
        Organization $organization,
        SubscriptionPlan $plan,
        ?string $notes = null
    ): OrganizationSubscription {
        $activeSubscription = $organization->activeSubscription;
        $now = Carbon::now();

        // 續訂：從現有到期日延長，若已過期則從明天開始
        if ($activeSubscription && $activeSubscription->expires_at->isFuture()) {
            $baseDate = $activeSubscription->expires_at;
        } else {
            $baseDate = $now->copy()->addDay()->startOfDay();
        }

        return OrganizationSubscription::create([
            'organization_id' => $organization->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => $now,
            'expires_at' => $baseDate->copy()->addDays($plan->duration_days),
            'status' => 'active',
            'notes' => $notes,
        ]);
    }

    public function purchaseSubscription(
        Organization $organization,
        SubscriptionPlan $plan,
        ?string $notes = null
    ): OrganizationSubscription {
        $activeSubscription = $organization->activeSubscription;

        if ($activeSubscription) {
            return $this->extendSubscription($organization, $plan, $notes);
        }

        return $this->createSubscription($organization, $plan, $notes);
    }

    public function cancelSubscription(OrganizationSubscription $subscription): void
    {
        $subscription->update(['status' => 'cancelled']);
    }

    public function getAvailablePlans(Organization $organization): \Illuminate\Database\Eloquent\Collection
    {
        $employeeCount = $organization->employees()->count();

        return SubscriptionPlan::query()
            ->active()
            ->where('max_employees', '>=', $employeeCount)
            ->ordered()
            ->get();
    }
}
