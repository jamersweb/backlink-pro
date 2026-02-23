<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class FeatureFlagsController extends Controller
{
    /**
     * Show feature flags (read-only from config/env). Toggle via .env.
     */
    public function index()
    {
        $flags = config('features', []);
        $list = [];
        foreach ($flags as $key => $value) {
            $list[] = [
                'key' => $key,
                'enabled' => (bool) $value,
                'env_key' => 'FEATURE_' . strtoupper(str_replace(['-', ' '], '_', $key)),
            ];
        }

        return Inertia::render('Admin/FeatureFlags/Index', [
            'flags' => $list,
        ]);
    }
}
