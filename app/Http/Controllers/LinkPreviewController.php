<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class LinkPreviewController extends Controller
{
    public function preview(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $html = Http::timeout(5)->get($request->url)->body();

        $crawler = new Crawler($html);

        return response()->json([
            'url' => $request->url,
            'title' => $crawler->filter('meta[property="og:title"]')->attr('content') ?? '',
            'desc' => $crawler->filter('meta[property="og:description"]')->attr('content') ?? '',
            'image' => $crawler->filter('meta[property="og:image"]')->attr('content') ?? ''
        ]);
    }
}
