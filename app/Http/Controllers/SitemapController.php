<?php

namespace App\Http\Controllers;

use App\Support\AnimationStyles;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $lastmod = $this->latestLastmod([
            public_path('build/manifest.json'),
            app_path('Http/Controllers/DemoLotteryController.php'),
            app_path('Http/Controllers/DemoLotteryTemplateController.php'),
            app_path('Http/Controllers/FaqController.php'),
            app_path('Support/AnimationStyles.php'),
            resource_path('views/demo/landing.blade.php'),
            resource_path('views/demo/templates/index.blade.php'),
            resource_path('views/demo/templates/show.blade.php'),
            resource_path('views/faq.blade.php'),
            resource_path('views/home.blade.php'),
            resource_path('views/lottery/show.blade.php'),
        ]);

        $urls = [
            [
                'loc' => url('/'),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '1.0',
            ],
            [
                'loc' => url('/demo/lottery'),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => url('/faq'),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => url('/demo/lottery/templates'),
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
        ];

        foreach (AnimationStyles::all() as $style) {
            $urls[] = [
                'loc' => url('/demo/lottery/'.$style['slug']),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function latestLastmod(array $paths): string
    {
        $latestTimestamp = collect($paths)
            ->filter(fn (string $path) => file_exists($path))
            ->map(fn (string $path) => filemtime($path))
            ->max();

        return Carbon::createFromTimestamp($latestTimestamp ?: time())->toAtomString();
    }
}
