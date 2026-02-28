<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SignupTrialService
{
    public function grantIfEligible(Organization $org, string $source): ?OrganizationSubscription
    {
        $config = config('subscription.signup_trial');

        if (! $config['enabled']) {
            return null;
        }

        $tz = $config['timezone'] ?? 'Asia/Taipei';
        $now = Carbon::now($tz);

        if ($config['start_at'] && $now->lt(Carbon::parse($config['start_at'], $tz))) {
            return null;
        }

        if ($config['end_at'] && $now->gt(Carbon::parse($config['end_at'], $tz))) {
            return null;
        }

        $plan = SubscriptionPlan::where('code', $config['plan_code'])
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            Log::warning('試用方案不存在或已停用', ['plan_code' => $config['plan_code']]);

            return null;
        }

        if ($org->activeSubscription) {
            return null;
        }

        return OrganizationSubscription::create([
            'organization_id' => $org->id,
            'subscription_plan_id' => $plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays($config['duration_days']),
            'status' => 'active',
            'notes' => "signup_trial:auto:{$source}",
        ]);
    }
}
