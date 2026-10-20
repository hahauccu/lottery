<?php

namespace Tests\Feature;

use App\Events\DanmakuSent;
use App\Events\LotteryEventUpdated;
use App\Events\PrizeWinnersUpdated;
use App\Models\Employee;
use App\Models\LotteryEvent;
use App\Models\Organization;
use App\Models\Prize;
use App\Models\PrizeRule;
use App\Support\AccessCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LotteryBroadcastIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $code): array
    {
        $organization = Organization::create(['name' => '測試公司'.$code]);
        $event = LotteryEvent::create(['organization_id' => $organization->id, 'name' => $code.'測試活動',
            'brand_code' => $code, 'draw_starts_at' => now(), 'is_lottery_open' => true, 'danmaku_enabled' => true]);
        $prize = Prize::create(['lottery_event_id' => $event->id, 'name' => $code.'測試獎項', 'winners_count' => 2,
            'draw_mode' => 'one_by_one', 'animation_style' => 'lotto_air', 'lotto_hold_seconds' => 5]);
        $employees = collect(range(1, 3))->map(function ($i) use ($organization, $prize, $code) {
            $employee = Employee::create(['organization_id' => $organization->id, 'name' => $code.'員工'.$i, 'email' => "person{$i}@example.test"]);
            PrizeRule::create(['prize_id' => $prize->id, 'type' => PrizeRule::TYPE_INCLUDE_EMPLOYEE, 'ref_id' => $employee->id]);

            return $employee;
        });
        $event->update(['current_prize_id' => $prize->id]);

        return compact('event', 'prize', 'employees');
    }

    private function draw(array $company, string $run = 'same-run-id')
    {
        $code = $company['event']->brand_code;

        return $this->postJson('/'.$code.'/draw', ['run_id' => $run], ['X-Operator-Token' => AccessCodeGenerator::generate($code)]);
    }

    public function test_different_companies_can_draw_with_the_same_run_id_without_sharing_results_or_dedup_keys(): void
    {
        Event::fake([PrizeWinnersUpdated::class]);
        $a = $this->company('AUDITA');
        $b = $this->company('AUDITB');
        $this->draw($a)->assertOk()->assertJsonPath('prize_id', $a['prize']->id)->assertJsonCount(1, 'winners');
        $this->draw($b)->assertOk()->assertJsonPath('prize_id', $b['prize']->id)->assertJsonCount(1, 'winners');
        $this->draw($a)->assertStatus(409);
        $this->assertSame(1, $a['prize']->winners()->count());
        $this->assertSame(1, $b['prize']->winners()->count());
        foreach ([$a, $b] as $company) {
            $this->assertContains($company['prize']->winners()->sole()->employee_id, $company['employees']->pluck('id')->all());
            Event::assertDispatched(PrizeWinnersUpdated::class, function ($broadcast) use ($company) {
                $payload = $broadcast->broadcastWith();

                return $broadcast->broadcastOn()->name === 'lottery.'.$company['event']->brand_code
                    && $payload['prize_id'] === $company['prize']->id
                    && collect($payload['all_prizes'])->pluck('id')->all() === [$company['prize']->id]
                    && collect($payload['eligible_names'])->every(fn ($name) => str_starts_with($name, $company['event']->brand_code));
            });
        }
    }

    public function test_holding_one_event_draw_lock_does_not_block_the_other_company(): void
    {
        Event::fake([PrizeWinnersUpdated::class]);
        $a = $this->company('AUDITA');
        $b = $this->company('AUDITB');
        $lock = Cache::lock('draw:'.$a['event']->id, 10);
        $this->assertTrue($lock->get());
        try {
            $this->draw($a)->assertStatus(423)->assertJsonPath('message', 'drawing_in_progress');
            $this->draw($b)->assertOk()->assertJsonCount(1, 'winners');
            $this->assertSame(0, $a['prize']->winners()->count());
        } finally {
            $lock->release();
        }
        $this->draw($a)->assertOk();
    }

    public function test_operator_token_and_drawing_heartbeat_are_scoped_to_each_event(): void
    {
        $a = $this->company('AUDITA');
        $b = $this->company('AUDITB');
        $this->postJson('/AUDITB/draw', ['run_id' => 'test'], ['X-Operator-Token' => AccessCodeGenerator::generate('AUDITA')])->assertForbidden();
        foreach (['AUDITA', 'AUDITB'] as $code) {
            $this->postJson('/'.$code.'/drawing-state', ['is_drawing' => true, 'run_id' => 'shared-run'], ['X-Operator-Token' => AccessCodeGenerator::generate($code)])->assertOk();
        }
        $this->postJson('/AUDITA/drawing-state', ['is_drawing' => false, 'run_id' => 'shared-run'], ['X-Operator-Token' => AccessCodeGenerator::generate('AUDITA')])->assertOk();
        $this->assertNull(Cache::get('lottery-drawing:AUDITA'));
        $this->assertSame('shared-run', Cache::get('lottery-drawing:AUDITB'));
    }

    public function test_event_updates_and_danmaku_use_their_own_brand_channel_and_payload(): void
    {
        $a = $this->company('AUDITA');
        $b = $this->company('AUDITB');
        foreach ([$a, $b] as $company) {
            $event = $company['event']->fresh();
            $broadcast = new LotteryEventUpdated($event);
            $payload = $broadcast->broadcastWith();
            $this->assertSame('lottery.'.$event->brand_code, $broadcast->broadcastOn()->name);
            $this->assertSame($event->id, $payload['event']['id']);
            $this->assertSame($company['employees']->pluck('name')->sort()->values()->all(), collect($payload['eligible_names'])->sort()->values()->all());
            $this->assertSame([$company['prize']->id], collect($payload['all_prizes'])->pluck('id')->all());
            $danmaku = new DanmakuSent($event->brand_code, '測試員工', $event->brand_code.'加油', 'test-id');
            $this->assertSame($broadcast->broadcastOn()->name, $danmaku->broadcastOn()->name);
            $this->assertSame($event->brand_code.'加油', $danmaku->broadcastWith()['message']);
        }
    }
}
