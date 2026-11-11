<?php

namespace Tests\Feature;

use App\Events\LotteryEventUpdated;
use App\Events\PrizeWinnersUpdated;
use App\Filament\Resources\LotteryEventResource\Pages\AnalyzeLotteryEvent;
use App\Jobs\RunLotteryAnalysis;
use App\Models\Employee;
use App\Models\EmployeeGroup;
use App\Models\LotteryAnalysisRun;
use App\Models\LotteryEvent;
use App\Models\Organization;
use App\Models\Prize;
use App\Models\PrizeRule;
use App\Models\PrizeWinner;
use App\Models\User;
use App\Services\LotteryAnalysisService;
use App\Services\LotteryDrawService;
use App\Services\PrizeRedrawService;
use App\Support\AccessCodeGenerator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LotteryFeatureClaimsTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        Event::fake([LotteryEventUpdated::class, PrizeWinnersUpdated::class]);
        $organization = Organization::create(['name' => '功能驗證公司', 'slug' => 'feature-audit']);
        $user = User::factory()->create();
        $user->organizations()->attach($organization);
        $event = LotteryEvent::create(['organization_id' => $organization->id, 'name' => '合成活動',
            'brand_code' => 'FEATURETEST', 'draw_starts_at' => now(), 'is_lottery_open' => true]);
        $prize = Prize::create(['lottery_event_id' => $event->id, 'name' => '合成幸運獎', 'winners_count' => 2,
            'draw_mode' => Prize::DRAW_MODE_ALL_AT_ONCE, 'animation_style' => 'lotto_air', 'allow_repeat_within_prize' => false]);
        $employees = collect(range(1, 4))->map(fn ($i) => Employee::create([
            'organization_id' => $organization->id, 'name' => '測試'.$i, 'email' => "feature{$i}@example.com",
            'department' => $i <= 2 ? '甲部門' : '乙部門',
        ]));
        $group = EmployeeGroup::create(['organization_id' => $organization->id, 'name' => '合成參加組']);
        $group->employees()->attach($employees->pluck('id')->all());
        PrizeRule::create(['prize_id' => $prize->id, 'type' => PrizeRule::TYPE_INCLUDE_GROUP, 'ref_id' => $group->id]);
        $event->update(['current_prize_id' => $prize->id]);

        return compact('organization', 'user', 'event', 'prize', 'employees');
    }

    public function test_static_rule_analysis_counts_exposures_and_does_not_change_formal_winners_or_send_mail(): void
    {
        $data = $this->fixture();
        Mail::fake();
        PrizeRule::create(['prize_id' => $data['prize']->id, 'type' => PrizeRule::TYPE_EXCLUDE_EMPLOYEE,
            'ref_id' => $data['employees']->last()->id]);
        $winner = app(LotteryDrawService::class)->drawCurrentPrize($data['event'])->first();
        $before = PrizeWinner::orderBy('id')->get()->toArray();
        $result = app(LotteryAnalysisService::class)->analyze($data['event'], 200);

        $this->assertSame(200, $result['iterations']);
        $this->assertSame(600, $result['summary']['eligible_exposures']);
        $this->assertSame(400, $result['summary']['total_wins']);
        $this->assertEquals(3, $result['prizes'][0]['eligible_avg']);
        $this->assertEqualsWithDelta(2 / 3, $result['prizes'][0]['win_rate'], 0.000001);
        $departments = collect($result['departments'])->keyBy('department');
        $this->assertSame(400, $departments['甲部門']['eligible_exposures']);
        $this->assertSame(200, $departments['乙部門']['eligible_exposures']);
        $this->assertSame(400, $departments->sum('win_count'));
        $this->assertEqualsWithDelta(1, $departments->sum('win_pct'), 0.000001);
        $this->assertSame($before, PrizeWinner::orderBy('id')->get()->toArray());
        $this->assertNull($winner->fresh()->notified_at);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_analysis_form_accepts_one_thousand_but_rejects_ten_thousand(): void
    {
        $data = $this->fixture();
        $this->actingAs($data['user']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($data['organization']);
        Queue::fake();
        $page = Livewire::test(AnalyzeLotteryEvent::class, ['record' => $data['event']->getRouteKey()]);
        $page->set('iterations', 10000)->call('startAnalysis')->assertHasErrors(['iterations' => 'max']);
        $this->assertDatabaseCount('lottery_analysis_runs', 0);
        Queue::assertNothingPushed();
        $page->set('iterations', 1000)->call('startAnalysis')->assertHasNoErrors();
        $this->assertDatabaseHas('lottery_analysis_runs', ['iterations' => 1000, 'status' => 'queued']);
        Queue::assertPushed(RunLotteryAnalysis::class);
    }

    public function test_completed_analysis_download_contains_readable_excel_sheets_and_is_scoped_to_organization(): void
    {
        $data = $this->fixture();
        $run = LotteryAnalysisRun::create(['lottery_event_id' => $data['event']->id, 'iterations' => 20,
            'status' => 'queued', 'progress' => 0]);
        $url = route('admin.lottery-analysis.download', $run);
        $this->actingAs($data['user'])->get($url)->assertStatus(409);
        (new RunLotteryAnalysis($run->id))->handle(app(LotteryAnalysisService::class));
        $this->assertSame('completed', $run->fresh()->status);
        $this->assertSame(100, $run->fresh()->progress);
        $response = $this->get($url)->assertOk()->assertDownload('analysis-FEATURETEST-'.$run->id.'.xlsx');
        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(['總覽', '獎項統計', '部門比較', '機率比對', '圖表', 'PivotData'], $spreadsheet->getSheetNames());
        $this->assertSame('合成幸運獎', $spreadsheet->getSheetByName('獎項統計')->getCell('A2')->getValue());
        $spreadsheet->disconnectWorksheets();
        $csv = $this->get($url.'?format=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('prize_summary', $csv);
        $this->assertStringContainsString('department_summary', $csv);
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->assertDatabaseCount('prize_winners', 0);
    }

    public function test_release_keeps_original_record_and_only_a_later_draw_fills_the_vacancy(): void
    {
        $data = $this->fixture();
        $draw = app(LotteryDrawService::class);
        $original = $draw->drawCurrentPrize($data['event'])->first();
        $service = app(PrizeRedrawService::class);
        $released = $service->releaseWinners($data['prize'], [$original->id], $data['user']->id);
        $this->assertCount(1, $released);
        $this->assertDatabaseCount('prize_winners', 2);
        $this->assertSame(1, $data['prize']->winners()->count());
        $this->assertSame('absent', $original->fresh()->release_reason);
        $this->assertSame($data['user']->id, $original->fresh()->released_by_user_id);
        $this->assertNotNull($original->fresh()->released_at);
        Event::assertDispatched(LotteryEventUpdated::class);

        $replacement = $draw->drawCurrentPrize($data['event'])->sole();
        $this->assertSame($original->id, $replacement->replacement_for_winner_id);
        $this->assertSame($original->sequence, $replacement->sequence);
        $this->assertNotSame($original->employee_id, $replacement->employee_id);
        $this->assertSame(2, $data['prize']->winners()->count());
        $this->assertDatabaseCount('prize_winners', 3);
        $this->get($original->getClaimUrl())->assertOk()->assertViewIs('claim.released');
    }

    public function test_claimed_released_or_other_prize_records_cannot_be_released_and_drawing_blocks_release(): void
    {
        $data = $this->fixture();
        $winners = app(LotteryDrawService::class)->drawCurrentPrize($data['event']);
        $winners[0]->update(['claimed_at' => now()]);
        $winners[1]->update(['released_at' => now()]);
        $otherPrize = Prize::create(['lottery_event_id' => $data['event']->id, 'name' => '其他獎', 'winners_count' => 1]);
        $otherWinner = PrizeWinner::create(['prize_id' => $otherPrize->id, 'employee_id' => $data['employees']->first()->id,
            'sequence' => 1, 'won_at' => now()]);
        $service = app(PrizeRedrawService::class);
        $this->assertCount(0, $service->releaseWinners($data['prize'], [...$winners->modelKeys(), $otherWinner->id], $data['user']->id));
        $this->assertNull($winners[0]->fresh()->released_at);
        $this->assertNull($otherWinner->fresh()->released_at);
        Cache::put('lottery-drawing:FEATURETEST', 'test-run', 15);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('前台正在抽獎中');
        $service->releaseWinners($data['prize'], [$winners[0]->id], $data['user']->id);
    }

    public function test_prize_switch_requires_ack_before_frontend_draw_and_ack_does_not_draw(): void
    {
        $data = $this->fixture();
        $headers = ['X-Operator-Token' => AccessCodeGenerator::generate('FEATURETEST')];
        $data['event']->update(['is_prize_switching' => true]);
        $this->postJson('/FEATURETEST/draw', ['run_id' => 'feature-run'], $headers)
            ->assertStatus(423)->assertJsonPath('message', 'prize_switching');
        $this->postJson('/FEATURETEST/switch-ack', ['prize_id' => $data['prize']->id + 1], $headers)
            ->assertOk()->assertJsonPath('message', 'prize_mismatch');
        $this->assertTrue($data['event']->fresh()->is_prize_switching);
        $this->postJson('/FEATURETEST/switch-ack', ['prize_id' => $data['prize']->id], $headers)
            ->assertOk()->assertJsonPath('message', 'ack_ok');
        $this->assertFalse($data['event']->fresh()->is_prize_switching);
        $this->assertDatabaseCount('prize_winners', 0);
        $this->postJson('/FEATURETEST/draw', ['run_id' => 'feature-run'], $headers)->assertOk()->assertJsonCount(2, 'winners');
        $data['event']->update(['is_lottery_open' => false]);
        $this->postJson('/FEATURETEST/draw', ['run_id' => 'closed-run'], $headers)->assertForbidden();
    }
}
