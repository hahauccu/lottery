<?php

namespace Tests\Feature;

use App\Filament\Resources\FeedbackResource;
use App\Filament\Resources\FeedbackResource\Pages\EditFeedback;
use App\Mail\FeedbackReceivedMail;
use App\Models\Feedback;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['feedback.notification_email' => 'support@example.com']);
    }

    private function input(array $extra = []): array
    {
        return array_merge([
            'category' => 'animation_stuck',
            'message' => '手機抽獎時第三顆球停止移動，等待後仍未顯示結果。',
            'source_path' => '/demo/lottery/lotto-air/play',
            'animation_style' => 'lotto_air',
            'viewport_width' => 390,
            'viewport_height' => 844,
        ], $extra);
    }

    private function tenantUser(bool $admin): array
    {
        $user = User::factory()->create(['is_admin' => $admin]);
        $organization = Organization::create(['name' => '回饋測試組織']);
        $user->organizations()->attach($organization);

        return [$user, $organization];
    }

    public function test_public_form_accepts_anonymous_feedback_and_notifies_support(): void
    {
        $this->get('/feedback?from=/demo/lottery/lotto-air/play&style=lotto_air')
            ->assertOk()->assertSee('意見回饋與聯絡我們')
            ->assertSee('value="lotto_air" selected', false)
            ->assertSee('name="source_path" value="/demo/lottery/lotto-air/play"', false)
            ->assertSee('<link rel="canonical" href="'.route('feedback.index').'">', false);

        $this->post('/feedback', $this->input(['status' => 'resolved', 'admin_notes' => '不能由訪客設定']))
            ->assertRedirect(route('feedback.index'))->assertSessionHas('status');
        $feedback = Feedback::sole();
        $this->assertNull($feedback->email);
        $this->assertSame('pending', $feedback->status);
        $this->assertNull($feedback->admin_notes);
        $this->assertSame(390, $feedback->viewport_width);
        $this->assertNotNull($feedback->notified_at);
        Mail::assertSent(FeedbackReceivedMail::class, fn ($mail) => $mail->hasTo('support@example.com'));
    }

    public function test_reply_email_is_optional_and_used_only_when_supplied(): void
    {
        $this->post('/feedback', $this->input(['email' => 'tester@example.com']))->assertSessionHasNoErrors();
        $mail = new FeedbackReceivedMail(Feedback::sole());
        $mail->build();
        $this->assertTrue($mail->hasReplyTo('tester@example.com'));
        $mail = new FeedbackReceivedMail(new Feedback($this->input()));
        $mail->build();
        $this->assertSame([], $mail->replyTo);
    }

    public function test_notification_failure_does_not_lose_the_feedback(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('mail unavailable'));
        $this->post('/feedback', $this->input())->assertRedirect(route('feedback.index'))->assertSessionHas('status');
        $this->assertDatabaseCount('feedback', 1);
        $this->assertNull(Feedback::sole()->notified_at);
    }

    public function test_validation_rejects_bad_content_and_private_or_external_source_urls(): void
    {
        $this->postJson('/feedback', $this->input([
            'message' => '短', 'email' => 'invalid', 'category' => 'invalid',
            'source_path' => 'https://example.com/?token=secret',
            'animation_style' => 'invalid', 'viewport_width' => 99999,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['message', 'email', 'category', 'source_path', 'animation_style', 'viewport_width']);
        $this->postJson('/feedback', $this->input(['source_path' => '/demo/lottery/lotto-air/play?_lac=secret']))
            ->assertUnprocessable()->assertJsonValidationErrors('source_path');
        $this->assertDatabaseCount('feedback', 0);
        Mail::assertNothingSent();
    }

    public function test_unknown_prefill_is_discarded_and_feedback_is_never_echoed_publicly(): void
    {
        Feedback::create($this->input(['email' => 'private@example.com', 'message' => '此訊息是私密意見不應出現在公開頁面']));
        $this->get('/feedback?from=https://example.com/&style=unknown')->assertOk()
            ->assertDontSee('private@example.com')->assertDontSee('此訊息是私密意見不應出現在公開頁面')
            ->assertSee('name="source_path" value=""', false);
    }

    public function test_honeypot_and_rate_limit_prevent_unlimited_submissions(): void
    {
        $this->postJson('/feedback', $this->input(['website' => 'spam']))->assertUnprocessable()->assertJsonValidationErrors('website');
        for ($i = 0; $i < 4; $i++) {
            $this->post('/feedback', $this->input())->assertRedirect();
        }
        $this->postJson('/feedback', $this->input())->assertTooManyRequests();
        $this->assertDatabaseCount('feedback', 4);
    }

    public function test_feedback_mail_escapes_submitted_html(): void
    {
        $feedback = Feedback::create($this->input(['message' => '<script>alert("test")</script>這是測試內容']));
        $html = (new FeedbackReceivedMail($feedback))->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_non_admin_tenant_cannot_read_or_edit_global_feedback(): void
    {
        [$user, $tenant] = $this->tenantUser(false);
        $feedback = Feedback::create($this->input());
        $this->actingAs($user)->get(FeedbackResource::getUrl('index', tenant: $tenant))->assertForbidden();
        $this->get(FeedbackResource::getUrl('edit', ['record' => $feedback], tenant: $tenant))->assertForbidden();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($tenant);
        Livewire::test(EditFeedback::class, ['record' => $feedback->getRouteKey()])->assertForbidden();
    }

    public function test_system_admin_can_review_and_resolve_feedback_without_changing_original_content(): void
    {
        [$user, $tenant] = $this->tenantUser(true);
        $feedback = Feedback::create($this->input());
        $this->actingAs($user)->get(FeedbackResource::getUrl('index', tenant: $tenant))->assertOk()->assertSee('手機抽獎時');
        $this->get(FeedbackResource::getUrl('edit', ['record' => $feedback], tenant: $tenant))->assertOk();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::setTenant($tenant);
        Livewire::test(EditFeedback::class, ['record' => $feedback->getRouteKey()])
            ->fillForm(['status' => 'resolved', 'admin_notes' => '已確認並修復'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('resolved', $feedback->fresh()->status);
        $this->assertSame('已確認並修復', $feedback->fresh()->admin_notes);
        $this->assertSame($this->input()['message'], $feedback->fresh()->message);
    }

    public function test_public_entry_points_and_sitemap_include_feedback(): void
    {
        $this->get('/')->assertSee('意見回饋／聯絡我們');
        $this->get('/demo/lottery')->assertSee('意見回饋');
        $this->get('/demo/lottery/battle-top/play')->assertSee('意見回饋／問題回報');
        $this->get('/sitemap.xml')->assertSee('<loc>'.route('feedback.index').'</loc>', false);
    }
}
