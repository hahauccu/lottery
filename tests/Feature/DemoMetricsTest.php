<?php

namespace Tests\Feature;

use App\Filament\Pages\DemoMetricsPage;
use App\Models\DemoMetric;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DemoMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_are_aggregated_without_retaining_extra_personal_fields(): void
    {
        foreach ([1000, 2000] as $duration) {
            $this->postJson('/demo/metrics', [
                'event' => 'draw_complete', 'entry' => 'play', 'animation_style' => 'battle_top',
                'duration_ms' => $duration, 'email' => 'private@example.com', 'names' => ['私人姓名'], 'visitor_id' => 'discard',
            ])->assertNoContent()->assertHeader('X-Robots-Tag', 'noindex');
        }
        $metric = DemoMetric::sole();
        $this->assertSame(2, $metric->total);
        $this->assertSame(3000, $metric->duration_total_ms);
        $this->assertArrayNotHasKey('email', $metric->toArray());
        $this->assertArrayNotHasKey('names', $metric->toArray());
        $this->assertArrayNotHasKey('visitor_id', $metric->toArray());
    }

    public function test_invalid_events_and_inconsistent_sources_are_rejected(): void
    {
        $this->postJson('/demo/metrics', ['event' => 'unknown', 'entry' => 'unknown', 'animation_style' => 'unknown'])
            ->assertUnprocessable()->assertJsonValidationErrors(['event', 'entry', 'animation_style']);
        $this->postJson('/demo/metrics', ['event' => 'draw_start', 'entry' => 'home', 'animation_style' => 'battle_top'])->assertUnprocessable();
        $this->postJson('/demo/metrics', ['event' => 'draw_start', 'entry' => 'play', 'animation_style' => 'all'])->assertUnprocessable();
        $this->postJson('/demo/metrics', ['event' => 'page_view', 'entry' => 'intro', 'animation_style' => 'all'])->assertUnprocessable();
        $this->assertDatabaseCount('demo_metrics', 0);
    }

    public function test_days_styles_and_events_are_independent_counters(): void
    {
        $this->travelTo(now('Asia/Taipei')->startOfDay());
        DemoMetric::record('page_view', 'all', 'hub');
        DemoMetric::record('play_click', 'marble_race', 'hub');
        $this->travel(1)->days();
        DemoMetric::record('page_view', 'all', 'hub');
        $this->assertDatabaseCount('demo_metrics', 3);
    }

    public function test_metrics_are_only_enabled_on_public_demo_pages(): void
    {
        $this->get('/demo/lottery/battle-top')->assertOk()->assertSee('data-entry="intro"', false)->assertDontSee('lottery-config-data');
        $this->get('/demo/lottery/battle-top/play')->assertOk()->assertSee('data-entry="play"', false);
        $this->get('/demo/lottery/battle-top/play?embedded=1')->assertOk()->assertDontSee('id="demo-metrics"', false);
        $this->get('/demo/lottery')->assertOk()->assertSee('data-entry="hub"', false);
    }

    public function test_regular_tenant_cannot_access_metrics_and_admin_can_read_counts(): void
    {
        $tenant = Organization::create(['name' => '試玩統計測試']);
        $user = User::factory()->create(['is_admin' => false]);
        $user->organizations()->attach($tenant);
        $this->actingAs($user)->get(DemoMetricsPage::getUrl(['tenant' => $tenant]))->assertForbidden();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($tenant);
        Livewire::test(DemoMetricsPage::class)->assertForbidden();
        $user->update(['is_admin' => true]);
        $this->actingAs($user->fresh());
        DemoMetric::record('draw_complete', 'battle_top', 'play', 12000);
        $this->get(DemoMetricsPage::getUrl(['tenant' => $tenant]))->assertOk()->assertSee('完成動畫')->assertSee('事件次數');
    }

    public function test_default_configuration_restores_30_names_and_five_winners_after_custom_settings(): void
    {
        $this->postJson('/demo/lottery/battle-top/configure', ['names' => "甲\n乙\n丙", 'draw_count' => 1, 'draw_mode' => 'one_by_one'])->assertOk();
        $this->postJson('/demo/lottery/battle-top/configure', ['names' => '', 'draw_count' => 5, 'draw_mode' => 'all_at_once'])
            ->assertOk()->assertJsonCount(30, 'eligible_names')->assertJsonPath('current_prize.draw_mode', 'all_at_once');
        $this->postJson('/demo/lottery/battle-top/draw')->assertOk()->assertJsonCount(5, 'winners');
    }
}
