<?php

namespace App\Services\Backlinks;

use App\Models\Setting;
use App\Services\Backlinks\Providers\DataForSeoBacklinkProvider;

class BacklinkProviderFactory
{
    /**
     * Create a backlink provider instance
     */
    public static function make(): BacklinkProviderInterface
    {
        $provider = Setting::get('backlink_provider') ?: config('services.backlinks.provider', 'dataforseo');

        switch ($provider) {
            case 'dataforseo':
                return new DataForSeoBacklinkProvider();
            default:
                throw new \Exception("Unknown backlink provider: {$provider}");
        }
    }
}


