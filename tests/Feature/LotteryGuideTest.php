<?php

namespace Tests\Feature;

use App\Support\AnimationStyles;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_is_crawlable_server_rendered_content_with_consistent_metadata_and_links(): void
    {
        $url = route('guides.year-end-lottery');
        $response = $this->get($url)->assertOk()->assertDontSee('lottery-config-data');
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame($url, $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
        $this->assertSame($url, $xpath->evaluate('string(//meta[@property="og:url"]/@content)'));
        $this->assertSame('index,follow,max-image-preview:large', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
        $this->assertSame(0, $xpath->query('//script[@src] | //iframe')->length);
        foreach ($xpath->query('//a[starts-with(@href, "#")]') as $link) {
            $id = substr($link->getAttribute('href'), 1);
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length, 'Missing or duplicate section: '.$id);
        }
        foreach (AnimationStyles::all() as $style) {
            $this->assertGreaterThan(0, $xpath->query('//a[@href="'.route('demo.lottery.style', ['slug' => $style['slug']]).'"]')->length);
        }
        $this->assertSame(1, $xpath->query('//a[@href="'.route('register-account').'"]')->length);
        $schemas = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $schema = json_decode($script->textContent, true, 512, JSON_THROW_ON_ERROR);
            $schemas[$schema['@type']] = $schema;
        }
        $this->assertSame($url, $schemas['Article']['mainEntityOfPage']);
        $this->assertSame($xpath->evaluate('string(//title)'), $schemas['Article']['headline']);
        $this->assertSame($xpath->evaluate('string(//meta[@name="description"]/@content)'), $schemas['Article']['description']);
        $this->assertSame($url, $schemas['BreadcrumbList']['itemListElement'][1]['item']);
        $this->assertArrayNotHasKey('dateModified', $schemas['Article']);
    }

    public function test_guide_has_inbound_links_from_public_pages_and_sitemap_without_changing_play_pages(): void
    {
        $url = route('guides.year-end-lottery');
        foreach (['/', '/demo/lottery', '/faq', ...array_map(fn ($style) => '/demo/lottery/'.$style['slug'], AnimationStyles::all())] as $path) {
            $response = $this->get($path)->assertOk();
            $this->assertGreaterThan(0, $this->xpath($response->getContent())->query('//a[@href="'.$url.'"]')->length, $path);
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.$url.'</loc>', false)->assertDontSee('<lastmod>', false);
        $this->get('/demo/lottery/marble-race/play')->assertOk()->assertDontSee('id="planning-tips"', false)->assertSee('noindex, follow');
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
