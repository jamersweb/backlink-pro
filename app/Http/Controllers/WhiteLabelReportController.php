<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class WhiteLabelReportController extends Controller
{
    public function index()
    {
        return Inertia::render('WhiteLabelReport/Index', [
            'reportHighlights' => [
                [
                    'title' => 'Own your brand experience',
                    'description' => 'Replace platform branding with your logo, company name and color palette for every client-facing report.',
                    'icon' => 'bi-palette',
                ],
                [
                    'title' => 'Send polished deliverables',
                    'description' => 'Present backlink progress, SEO wins and recommendations in a clean format your clients can forward with confidence.',
                    'icon' => 'bi-file-earmark-text',
                ],
                [
                    'title' => 'Keep delivery consistent',
                    'description' => 'Use one branded reporting workflow across campaigns so every account feels organized and premium.',
                    'icon' => 'bi-stars',
                ],
            ],
            'setupSteps' => [
                'Add your logo and primary brand colors',
                'Choose which report sections clients should see',
                'Preview export styling before sharing with clients',
            ],
        ]);
    }
}
