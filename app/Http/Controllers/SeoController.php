<?php

namespace App\Http\Controllers;

use App\Models\Projet;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $sitemapUrl = route('sitemap');

        return response(
            "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api\nDisallow: /devis/*/pdf\nDisallow: /attestations/\n\nSitemap: {$sitemapUrl}\n",
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('services'), 'changefreq' => 'monthly', 'priority' => '0.9'],
            ['loc' => route('gallery'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => route('public.devis'), 'changefreq' => 'monthly', 'priority' => '0.9'],
            ['loc' => route('contact'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('reviews'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];

        $serviceCategories = app(PublicController::class)->serviceCategories();
        foreach ($serviceCategories as $slug => $service) {
            $urls[] = [
                'loc' => route('services.detail', ['slug' => $slug]),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        if (Schema::hasTable('projets')) {
            $latestProject = Projet::query()
                ->where('is_visible_public', true)
                ->latest('updated_at')
                ->first(['updated_at']);

            if ($latestProject?->updated_at) {
                $urls[2]['lastmod'] = $latestProject->updated_at->toAtomString();
            }
        }

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
