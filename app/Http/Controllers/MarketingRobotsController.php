<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class MarketingRobotsController extends Controller
{
    public function index()
    {
        $appUrl = config('marketing_site.urls.app_url');
        $sitemapUrl = rtrim($appUrl, '/') . '/sitemap.xml';

        $content = "User-agent: *\n";
        $content .= "Allow: /\n\n";
        $content .= "Sitemap: {$sitemapUrl}\n";

        return Response::make($content, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
