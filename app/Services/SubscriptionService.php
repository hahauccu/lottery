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

        return OrganizationSubscription::create([
            'organization_id' => $organization->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => $now,
            'expires_at' => $now->copy()->addDays($plan->duration_days),
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

        $baseDate = $activeSubscription && $activeSubscription->expires_at->isFuture()
            ? $activeSubscription->expires_at
            : Carbon::now();

        return OrganizationSubscription::create([
            'organization_id' => $organization->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => Carbon::now(),
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
