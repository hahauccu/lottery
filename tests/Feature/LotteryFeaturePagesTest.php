<?php

namespace Tests\Feature;

use App\Support\LotteryFeatures;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryFeaturePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_hub_and_details_have_crawlable_content_metadata_and_bidirectional_links(): void
    {
        $hub = $this->get(route('features.index'))->assertOk();
        $titles = [];
        $descriptions = [];
        foreach (['index' => null, ...LotteryFeatures::all()] as $slug => $feature) {
            $url = route('features.'.$slug);
            $html = $this->get($url)->assertOk()->assertDontSee('lottery-config-data')->getContent();
            $xpath = $this->xpath($html);
            $titles[] = $xpath->evaluate('string(//title)');
            $descriptions[] = $xpath->evaluate('string(//meta[@name="description"]/@content)');
            $this->assertSame(1, $xpath->query('//h1')->length);
            $this->assertSame($url, $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
            $this->assertSame($url, $xpath->evaluate('string(//meta[@property="og:url"]/@content)'));
            $this->assertSame('index,follow,max-image-preview:large', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
            $this->assertSame(0, $xpath->query('//iframe | //script[@src]')->length);
            foreach ($xpath->query('//a[starts-with(@href, "#")]') as $link) {
                $this->assertSame(1, $xpath->query('//*[@id="'.substr($link->getAttribute('href'), 1).'"]')->length);
            }
            foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
                $schema = json_decode($script->textContent, true, 512, JSON_THROW_ON_ERROR);
                if ($schema['@type'] === 'BreadcrumbList') {
                    $this->assertSame($url, array_last($schema['itemListElement'])['item']);
                } else {
                    $this->assertSame($url, $schema['url']);
                    $this->assertSame(end($titles), $schema['name']);
                    $this->assertSame(end($descriptions), $schema['description']);
                }
                $this->assertArrayNotHasKey('aggregateRating', $schema);
            }
            if ($feature) {
                $hub->assertSee($url);
                foreach (LotteryFeatures::all() as $relatedSlug => $related) {
                    if ($slug !== $relatedSlug) {
                        $this->assertGreaterThan(0, $xpath->query('//a[@href="'.route('features.'.$relatedSlug).'"]')->length);
                    }
                }
                $this->assertGreaterThan(0, $xpath->query('//a[@href="'.route('guides.'.$feature['guide']).'"]')->length);
                $this->get(route('guides.'.$feature['guide']))->assertOk()->assertSee($url);
            }
            $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.$url.'</loc>', false);
        }
        $this->assertCount(4, array_unique($titles));
        $this->assertCount(4, array_unique($descriptions));
        foreach (['/', '/faq', '/demo/lottery', '/guides'] as $path) {
            $this->get($path)->assertOk()->assertSee(route('features.index'));
        }
        $this->get('/features/not-a-feature')->assertNotFound();
        $this->get(route('features.lottery-analysis'))->assertSee('1,000')->assertSee('系統群組與動態資格尚未完整支援');
        $this->get(route('features.lottery-control'))->assertSee('正式抽獎仍由前台觸發');
        $this->get(route('features.absent-redraw'))->assertSee('釋出不會自動產生替補者');
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
