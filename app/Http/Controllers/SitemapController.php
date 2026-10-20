<?php

namespace App\Http\Controllers;

use App\Support\AnimationStyles;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        // 未追蹤每頁的內容更新日期；省略 lastmod，避免部署時間被誤認為內容更新。
        $urls = [
            [
                'loc' => route('guides.year-end-lottery'),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ],
            [
                'loc' => route('feedback.index'),
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
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
            [
                'loc' => url('/faq'),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => url('/demo/lottery/templates'),
                'changefreq' => 'weekly',
                'priority' => '0.7',
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
