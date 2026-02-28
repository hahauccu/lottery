<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignupTrialTest extends TestCase
{
    use RefreshDatabase;

    private function enableTrial(array $overrides = []): void
    {
        $defaults = [
            'enabled' => true,
            'start_at' => now()->subDay()->toDateTimeString(),
            'end_at' => now()->addDay()->toDateTimeString(),
            'plan_code' => 'lv1',
            'duration_days' => 7,
            'timezone' => 'Asia/Taipei',
        ];

        config(['subscription.signup_trial' => array_merge($defaults, $overrides)]);
    }

    private function createTrialPlan(array $overrides = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name' => '基本方案',
            'code' => 'lv1',
            'max_employees' => 100,
            'price' => 0,
            'duration_days' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'organization_name' => 'Test Org',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    private function registerAccountPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'organization_name' => 'Test Org',
        ], $overrides);
    }

    public function test_register_creates_trial_subscription_during_active_period(): void
    {
        $this->enableTrial();
        $this->createTrialPlan();

        $this->post('/register', $this->registerPayload());

        $org = Organization::first();
        $this->assertNotNull($org);

        $subscription = OrganizationSubscription::where('organization_id', $org->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertStringContains('signup_trial:auto:register', $subscription->notes);
        $this->assertTrue($subscription->expires_at->isFuture());
    }

    public function test_register_account_creates_trial_subscription_during_active_period(): void
    {
        $this->enableTrial();
        $this->createTrialPlan();

        $this->post('/register-account', $this->registerAccountPayload());

        $org = Organization::first();
        $this->assertNotNull($org);

        $subscription = OrganizationSubscription::where('organization_id', $org->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertStringContains('signup_trial:auto:register-account', $subscription->notes);
    }

    public function test_no_subscription_when_outside_active_period(): void
    {
        $this->enableTrial([
            'start_at' => now()->subDays(10)->toDateTimeString(),
            'end_at' => now()->subDay()->toDateTimeString(),
        ]);
        $this->createTrialPlan();

        $this->post('/register', $this->registerPayload());

        $org = Organization::first();
        $this->assertNotNull($org);
        $this->assertNull(
            OrganizationSubscription::where('organization_id', $org->id)->first()
        );
    }

    public function test_no_subscription_when_feature_disabled(): void
    {
        $this->enableTrial(['enabled' => false]);
        $this->createTrialPlan();

        $this->post('/register', $this->registerPayload());

        $this->assertDatabaseCount('organization_subscriptions', 0);
    }

    public function test_no_subscription_when_plan_not_found(): void
    {
        $this->enableTrial(['plan_code' => 'nonexistent']);
        $this->createTrialPlan(); // creates 'lv1', but config looks for 'nonexistent'

        $this->post('/register', $this->registerPayload());

        $org = Organization::first();
        $this->assertNotNull($org, '註冊應該成功');
        $this->assertDatabaseCount('organization_subscriptions', 0);
    }

    public function test_no_subscription_when_plan_is_inactive(): void
    {
        $this->enableTrial();
        $this->createTrialPlan(['is_active' => false]);

        $this->post('/register', $this->registerPayload());

        $org = Organization::first();
        $this->assertNotNull($org, '註冊應該成功');
        $this->assertDatabaseCount('organization_subscriptions', 0);
    }

    public function test_no_duplicate_subscription_if_org_already_has_one(): void
    {
        $this->enableTrial();
        $plan = $this->createTrialPlan();

        $this->post('/register', $this->registerPayload());

        $org = Organization::first();
        $this->assertDatabaseCount('organization_subscriptions', 1);

        // Manually call service again — should not create duplicate
        app(\App\Services\SignupTrialService::class)->grantIfEligible($org, 'manual');

        $this->assertDatabaseCount('organization_subscriptions', 1);
    }

    public function test_organization_is_not_test_mode_after_trial(): void
    {
        $this->enableTrial();
        $this->createTrialPlan();

        $this->post('/register', $this->registerPayload());

        $org = Organization::first();
        $this->assertNotNull($org);
        $this->assertFalse($org->isTestMode());
    }

    /**
     * Custom assertion: check string contains substring.
     */
    private function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack);
        $this->assertStringContainsString($needle, $haystack);
    }
}
