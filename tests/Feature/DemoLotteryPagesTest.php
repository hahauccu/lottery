<?php

namespace Tests\Feature;

use App\Models\DemoLotteryTemplate;
use App\Support\AnimationStyles;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoLotteryPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function animationStyles(): array
    {
        return array_map(fn (array $style) => [$style], AnimationStyles::all());
    }

    #[DataProvider('animationStyles')]
    public function test_animation_url_is_an_indexable_introduction_page(array $style): void
    {
        $introUrl = route('demo.lottery.style', ['slug' => $style['slug']]);
        $playUrl = route('demo.lottery.play', ['slug' => $style['slug']]);
        $response = $this->get($introUrl);

        $response->assertOk()
            ->assertSee($style['label'].'抽獎動畫')
            ->assertSee('開始設定參數試玩')
            ->assertSee('此動畫 Demo 免費（NT$0），不需登入。')
            ->assertSessionMissing('demo_lottery_'.$style['key'])
            ->assertDontSee('lottery-config-data');

        $xpath = $this->xpath($response->getContent());
        $this->assertSame($introUrl, $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame($style['seoTitle'], $xpath->evaluate('string(//title)'));
        $this->assertSame($style['seoDescription'], $xpath->evaluate('string(//meta[@name="description"]/@content)'));
        $this->assertSame('index,follow,max-image-preview:large', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(0, $xpath->query('//script[@src]')->length);
        $this->assertFileExists(public_path('images/previews/'.$style['key'].'.svg'));
        $this->assertSame(url('/images/previews/'.$style['key'].'.svg'), $xpath->evaluate('string(//meta[@property="og:image"]/@content)'));

        $schemas = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $schema = json_decode($script->textContent, true, 512, JSON_THROW_ON_ERROR);
            $schemas[$schema['@type']] = $schema;
        }
        $this->assertSame($introUrl, $schemas['WebPage']['url']);
        $this->assertSame($introUrl, $schemas['Product']['url']);
        $this->assertSame('0', $schemas['Product']['offers']['price']);
        $this->assertSame('TWD', $schemas['Product']['offers']['priceCurrency']);
        $this->assertSame($playUrl, $schemas['Product']['offers']['url']);
        $this->assertArrayNotHasKey('priceValidUntil', $schemas['Product']['offers']);
        $this->assertArrayNotHasKey('aggregateRating', $schemas['Product']);
        $this->assertSame($introUrl, $schemas['BreadcrumbList']['itemListElement'][2]['item']);
    }

    #[DataProvider('animationStyles')]
    public function test_play_url_is_a_noindex_interactive_page(array $style): void
    {
        $response = $this->get('/demo/lottery/'.$style['slug'].'/play');

        $response->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false)
            ->assertSee('lottery-config-data', false)
            ->assertSee('demoStyleToolbar', false)
            ->assertDontSee('<aside class="demo-seo-links">', false);

        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//meta[@name="robots"]')->length);
        $this->assertSame(0, $xpath->query('//link[@rel="canonical"]')->length);
        $this->assertSame(0, $xpath->query('//script[@type="application/ld+json"]')->length);
        $this->assertSame(route('demo.lottery.play', ['slug' => $style['slug']]), $xpath->evaluate('string(//meta[@property="og:url"]/@content)'));
        $this->assertNotEmpty($xpath->evaluate('string(//meta[@property="og:image"]/@content)'));
    }

    public function test_home_page_does_not_embed_animation_iframes(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertSee('查看介紹', false)
            ->assertSee('立即試玩', false);
    }

    public function test_landing_keeps_its_search_topic_and_links_to_both_introduction_and_play_pages(): void
    {
        $response = $this->get('/demo/lottery');
        $response->assertOk()->assertSee('抽獎動畫｜7 種尾牙抽獎、線上抽獎動畫免費體驗');
        $xpath = $this->xpath($response->getContent());

        foreach (AnimationStyles::all() as $style) {
            $intro = route('demo.lottery.style', ['slug' => $style['slug']]);
            $play = route('demo.lottery.play', ['slug' => $style['slug']]);
            $this->assertGreaterThan(0, $xpath->query('//a[@href="'.$intro.'"]')->length);
            $this->assertSame(1, $xpath->query('//a[@href="'.$play.'" and contains(., "立即試玩")]')->length);
        }
    }

    public function test_legacy_embedded_url_preserves_preview_mode(): void
    {
        $this->get('/demo/lottery/battle-top?embedded=1')
            ->assertRedirect(route('demo.lottery.play', ['slug' => 'battle-top', 'embedded' => 1]));
        $this->get('/demo/lottery/battle-top/play?embedded=1')
            ->assertOk()->assertSee('is-embedded-preview')
            ->assertSee('lottery-config-data')
            ->assertDontSee('demoStyleToolbar');
    }

    public function test_payload_responses_stay_out_of_search_and_share_the_configured_session(): void
    {
        $this->postJson('/demo/lottery/battle-top/configure', [
            'names' => "甲\n乙\n丙",
            'draw_count' => 2,
            'draw_mode' => 'all_at_once',
        ])->assertOk();

        foreach (['/demo/lottery/battle-top', '/demo/lottery/battle-top/play'] as $path) {
            $this->get($path.'?payload=1')->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex')
                ->assertJsonPath('eligible_names', ['甲', '乙', '丙']);
            $this->getJson($path)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
        }

        $response = $this->postJson('/demo/lottery/battle-top/draw')->assertOk()->assertJsonCount(2, 'winners');
        $names = array_column($response->json('winners'), 'employee_name');
        $this->assertCount(2, array_unique($names));
        $this->assertEmpty(array_diff($names, ['甲', '乙', '丙']));
        $this->getJson('/demo/lottery/battle-top/play?payload=1')->assertJsonCount(1, 'eligible_names');
    }

    public function test_applying_a_shared_template_opens_play_with_the_template_options(): void
    {
        $template = DemoLotteryTemplate::create([
            'token' => 'seo-template',
            'title' => '午餐抽籤',
            'options' => ['便當', '麵食'],
            'animation_style' => 'battle_top',
            'draw_count' => 1,
            'draw_mode' => 'all_at_once',
            'is_public' => true,
        ]);

        $this->postJson(route('demo.lottery.templates.apply', $template))
            ->assertOk()->assertJsonPath('redirectUrl', route('demo.lottery.play', ['slug' => 'battle-top']));
        $this->getJson('/demo/lottery/battle-top/play?payload=1')
            ->assertOk()->assertJsonPath('eligible_names', ['便當', '麵食']);
    }

    public function test_unknown_styles_return_404_on_both_page_types(): void
    {
        $this->get('/demo/lottery/unknown-animation')->assertNotFound();
        $this->get('/demo/lottery/unknown-animation/play')->assertNotFound();
    }

    public function test_real_event_display_remains_excluded_from_search(): void
    {
        $html = view('lottery.show', [
            'payload' => [],
            'event' => (object) ['name' => '正式活動'],
            'currentPrize' => null,
            'currentWinners' => collect(),
            'bgUrl' => null,
            'isDemo' => false,
        ])->render();

        $xpath = $this->xpath($html);
        $this->assertSame('noindex, nofollow', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
        $this->assertSame(0, $xpath->query('//script[@type="application/ld+json"]')->length);
    }

    public function test_sitemap_lists_introduction_pages_but_not_play_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertDontSee('<lastmod>', false);

        $xml = simplexml_load_string($response->getContent());
        $urls = array_map(fn ($url) => (string) $url->loc, iterator_to_array($xml->url, false));
        $this->assertCount(11, $urls);
        foreach (AnimationStyles::all() as $style) {
            $this->assertContains(route('demo.lottery.style', ['slug' => $style['slug']]), $urls);
            $this->assertNotContains(route('demo.lottery.play', ['slug' => $style['slug']]), $urls);
        }
    }

    private function xpath(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
