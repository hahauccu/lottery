<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LotteryEvent;
use App\Models\Organization;
use App\Models\Prize;
use App\Models\PrizeWinner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryWinnerExportTest extends TestCase
{
    use RefreshDatabase;

    private function createEventWithWinner(): array
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::factory()->create();
        $user->organizations()->attach($org);

        $event = LotteryEvent::create([
            'organization_id' => $org->id,
            'name' => '年終抽獎',
            'brand_code' => 'test-download',
            'draw_starts_at' => now(),
        ]);

        $prize = Prize::create([
            'lottery_event_id' => $event->id,
            'name' => '特獎',
            'winners_count' => 3,
            'draw_mode' => 'all_at_once',
            'animation_style' => 'slot',
            'sort_order' => 1,
        ]);

        $employee = Employee::create([
            'organization_id' => $org->id,
            'name' => '王小明',
            'email' => 'wang@test.com',
            'department' => '工程部',
        ]);

        $winner = PrizeWinner::create([
            'prize_id' => $prize->id,
            'employee_id' => $employee->id,
            'sequence' => 1,
            'won_at' => now(),
        ]);

        return compact('org', 'user', 'event', 'prize', 'employee', 'winner');
    }

    public function test_unauthorized_user_gets_403(): void
    {
        $data = $this->createEventWithWinner();

        // 不同組織的使用者
        $otherUser = User::factory()->create();
        $otherOrg = Organization::create(['name' => 'Other Org', 'slug' => 'other-org']);
        $otherUser->organizations()->attach($otherOrg);

        $response = $this->actingAs($otherUser)
            ->get(route('admin.lottery-winners.download', $data['event']));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $data = $this->createEventWithWinner();

        $response = $this->get(route('admin.lottery-winners.download', $data['event']));

        $response->assertRedirect('/login');
    }

    public function test_authorized_user_can_download_csv(): void
    {
        $data = $this->createEventWithWinner();

        $response = $this->actingAs($data['user'])
            ->get(route('admin.lottery-winners.download', $data['event']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContains('王小明', $content);
        $this->assertStringContains('特獎', $content);
        $this->assertStringContains('工程部', $content);
    }

    public function test_csv_includes_redraw_records(): void
    {
        $data = $this->createEventWithWinner();

        // 原始中獎者被釋出
        $data['winner']->update([
            'released_at' => now(),
            'release_reason' => 'absent',
        ]);

        // 補抽者
        $replacement = Employee::create([
            'organization_id' => $data['org']->id,
            'name' => '陳小華',
            'email' => 'chen@test.com',
            'department' => '業務部',
        ]);

        PrizeWinner::create([
            'prize_id' => $data['prize']->id,
            'employee_id' => $replacement->id,
            'sequence' => 1,
            'won_at' => now(),
            'replacement_for_winner_id' => $data['winner']->id,
        ]);

        $response = $this->actingAs($data['user'])
            ->get(route('admin.lottery-winners.download', $data['event']));

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // 兩筆紀錄都在
        $this->assertStringContains('王小明', $content);
        $this->assertStringContains('陳小華', $content);

        // 狀態欄位
        $this->assertStringContains('已釋出', $content);
        $this->assertStringContains('原始中獎', $content);
        $this->assertStringContains('重抽補位', $content);
        $this->assertStringContains('不在場重抽', $content);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that string contains '{$needle}'."
        );
    }
}
