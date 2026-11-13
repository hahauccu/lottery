<?php

namespace Tests\Feature;

use App\Support\AnimationStyles;
use App\Support\LotteryGuides;
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

    public function test_library_and_articles_have_distinct_metadata_and_reachable_content(): void
    {
        $library = $this->get(route('guides.index'))->assertOk()->assertDontSee('lottery-config-data');
        $libraryXpath = $this->xpath($library->getContent());
        $titles = [];
        foreach (LotteryGuides::all() as $slug => $guide) {
            $url = route('guides.'.$slug);
            $this->assertGreaterThan(0, $libraryXpath->query('//a[@href="'.$url.'"]')->length);
            $response = $this->get($url)->assertOk()->assertDontSee('lottery-config-data');
            $xpath = $this->xpath($response->getContent());
            $titles[] = $xpath->evaluate('string(//title)');
            $this->assertSame(1, $xpath->query('//h1')->length);
            $this->assertSame($url, $xpath->evaluate('string(//link[@rel="canonical"]/@href)'));
            $this->assertSame('index,follow,max-image-preview:large', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
            $this->assertSame(0, $xpath->query('//iframe')->length);
            $this->assertGreaterThan(0, $xpath->query('//a[@href="'.route('guides.index').'"]')->length);
            foreach ($xpath->query('//a[starts-with(@href, "#")]') as $link) {
                $this->assertSame(1, $xpath->query('//*[@id="'.substr($link->getAttribute('href'), 1).'"]')->length);
            }
            foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
                $schema = json_decode($script->textContent, true, 512, JSON_THROW_ON_ERROR);
                if ($schema['@type'] === 'Article') {
                    $this->assertSame($url, $schema['mainEntityOfPage']);
                    $this->assertSame($xpath->evaluate('string(//title)'), $schema['headline']);
                    $this->assertArrayNotHasKey('dateModified', $schema);
                }
            }
        }
        $this->assertCount(count($titles), array_unique($titles));
        $this->get('/guides/not-a-guide')->assertNotFound();
        foreach (['/', '/faq', '/demo/lottery', '/demo/lottery/battle-top', '/demo/lottery/marble-race'] as $path) {
            $this->get($path)->assertOk()->assertSee(route('guides.index'));
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.route('guides.index').'</loc>', false);
    }

    public function test_downloads_are_valid_synthetic_csv_and_copy_targets_match_visible_scripts(): void
    {
        $csv = \League\Csv\Reader::createFromPath(public_path('examples/lottery-participants-sample.csv'));
        $csv->setHeaderOffset(0);
        $this->assertSame(['姓名', 'Email', '電話', '部門', '員工編號', '群組'], $csv->getHeader());
        $rows = iterator_to_array($csv->getRecords(), false);
        $this->assertCount(4, $rows);
        $this->assertCount(4, array_unique(array_column($rows, 'Email')));
        foreach ($rows as $row) {
            $this->assertStringEndsWith('@example.com', $row['Email']);
            $this->assertSame('', $row['電話']);
        }
        $host = $this->get(route('guides.lottery-host-script'))->assertOk();
        $xpath = $this->xpath($host->getContent());
        $this->assertSame(4, $xpath->query('//button[@data-copy-target]')->length);
        foreach ($xpath->query('//button[@data-copy-target]') as $button) {
            $id = $button->getAttribute('data-copy-target');
            $this->assertSame(1, $xpath->query('//blockquote[@id="'.$id.'"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'-status" and @role="status"]')->length);
        }
        $schedule = \League\Csv\Reader::createFromPath(public_path('examples/lottery-run-of-show.csv'));
        $schedule->setHeaderOffset(0);
        $this->assertCount(5, iterator_to_array($schedule->getRecords()));
        $host->assertSee(asset('examples/lottery-run-of-show.csv'));
        $this->get(route('guides.lottery-participant-import'))->assertOk()->assertSee(asset('examples/lottery-participants-sample.csv'));
    }

    public function test_walkthroughs_are_public_with_complete_text_real_images_and_series_links(): void
    {
        foreach (\App\Support\LotteryWalkthroughs::all() as $slug => $guide) {
            $response = $this->get(route('guides.'.$slug))->assertOk();
            $xpath = $this->xpath($response->getContent());
            foreach ($guide['steps'] as [$label, $text, $image]) {
                $response->assertSee($text);
                $this->assertFileExists(public_path('images/guides/lottery-flow/'.$image));
                $size = getimagesize(public_path('images/guides/lottery-flow/'.$image));
                $this->assertSame('image/jpeg', $size['mime']);
                $this->assertGreaterThan(0, $xpath->query('//img[@width="'.$size[0].'" and @height="'.$size[1].'"]')->length);
            }
            $this->assertSame(0, $xpath->query('//*[@data-tour-step and @hidden]')->length);
            foreach (\App\Support\LotteryWalkthroughs::series() as $otherSlug => $label) {
                $response->assertSee(route('guides.'.$otherSlug));
            }
            $response->assertDontSee('本機')->assertDontSee('_lac=');
            $this->get('/sitemap.xml')->assertSee('<loc>'.route('guides.'.$slug).'</loc>', false);
        }
    }

    public function test_public_analysis_sample_is_a_readable_workbook_with_statistics(): void
    {
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load(public_path('examples/lottery-analysis-sample.xlsx'));
        $this->assertSame(['總覽', '獎項統計', '部門比較', '機率比對', '圖表', 'PivotData'], $book->getSheetNames());
        $this->get(route('guides.lottery-analysis-report'))->assertOk()->assertSee(asset('examples/lottery-analysis-sample.xlsx'));
        $book->disconnectWorksheets();
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
