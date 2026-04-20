<?php

namespace App\Http\Controllers;

use App\Support\AnimationStyles;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            [
                'loc' => url('/'),
                'changefreq' => 'monthly',
                'priority' => '1.0',
            ],
            [
                'loc' => url('/demo/lottery'),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
        ];

        foreach (AnimationStyles::all() as $style) {
            $urls[] = [
                'loc' => url('/demo/lottery/'.$style['slug']),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
