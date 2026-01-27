<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BacklinkPlanLead;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Get all active public plans for pricing page.
     */
    public function index()
    {
        $plans = Plan::active()->public()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $plans->map(fn($plan) => $plan->toMarketingArray()),
        ]);
    }

    /**
     * Get a specific plan by code.
     */
    public function show(string $code)
    {
        $plan = Plan::where('code', $code)->active()->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $plan->toMarketingArray(),
        ]);
    }

    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:255',
            'industry' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Mock preview data - replace with actual logic later
        $url = $request->input('url');
        $domain = parse_url($url, PHP_URL_HOST);
        
        // Simulate analysis
        $opportunities = rand(15, 45);
        $riskScore = rand(20, 85);
        $estLinks = [
            'min' => max(5, floor($opportunities * 0.3)),
            'max' => min(30, floor($opportunities * 0.7)),
        ];

        $topOps = [
            [
                'domain' => 'example-blog.com',
                'reason' => 'High DA blog accepting guest posts in your niche',
            ],
            [
                'domain' => 'tech-forum.org',
                'reason' => 'Active forum with relevant discussion threads',
            ],
            [
                'domain' => 'industry-news.net',
                'reason' => 'News site with comment sections',
            ],
        ];

        $preview = [
            'anchors' => ['brand', 'keyword', 'natural'],
            'categories' => ['Tech', 'Business', 'Marketing'],
            'notes' => 'Initial analysis shows strong opportunity for quality backlink acquisition.',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'opportunities' => $opportunities,
                'riskScore' => $riskScore,
                'estLinks' => $estLinks,
                'topOps' => $topOps,
                'preview' => $preview,
            ],
        ]);
    }

    public function lead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'url' => 'required|url|max:255',
            'industry' => 'nullable|string|max:100',
            'previewJson' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        BacklinkPlanLead::create([
            'email' => $request->input('email'),
            'url' => $request->input('url'),
            'industry' => $request->input('industry'),
            'preview_json' => $request->input('previewJson'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Check your inbox for your free backlink plan.',
        ]);
    }
}
