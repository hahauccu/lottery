<?php

namespace App\Http\Controllers;

use App\Support\AnimationStyles;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $manifest = public_path('build/manifest.json');
        $lastmod = file_exists($manifest)
            ? Carbon::createFromTimestamp(filemtime($manifest))->toAtomString()
            : Carbon::now()->toAtomString();

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
}
