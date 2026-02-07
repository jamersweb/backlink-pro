<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PageMetaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketingController extends Controller
{
    protected PageMetaService $pageMetaService;

    public function __construct(PageMetaService $pageMetaService)
    {
        $this->pageMetaService = $pageMetaService;
    }

    /**
     * Get meta data for a page with fallback defaults
     */
    protected function getPageMeta(string $pageKey, array $defaults): array
    {
        return $this->pageMetaService->getMeta($pageKey, $defaults);
    }

    public function home()
    {
        $meta = $this->getPageMeta('home', [
            'title' => 'Build Quality Backlinks Without Manual Grind | BacklinkPro',
            'description' => 'AI-powered backlink automation with guardrails. Risk scoring, human approvals, evidence logs, and link monitoring. No PBNs. Start your free plan.',
            'og' => [
                'title' => 'BacklinkPro - Automated Link Building with Guardrails',
                'description' => 'AI selects the safest action, executes workflows, and tracks every link with evidence.',
                'image' => asset('images/og-image.jpg'),
            ],
        ]);

        return Inertia::render('Marketing/Home', [
            'meta' => $meta,
            'initialMetrics' => [
                ['label' => 'Links Placed', 'value' => 125000, 'suffix' => '+'],
                ['label' => 'Hours Saved/Month', 'value' => 45000, 'suffix' => '+'],
                ['label' => 'Avg Approval Rate', 'value' => 87, 'suffix' => '%'],
                ['label' => 'Projects Supported', 'value' => 3200, 'suffix' => '+'],
            ],
            'logos' => [
                ['name' => 'Company 1', 'src' => '/images/logos/logo-1.svg'],
                ['name' => 'Company 2', 'src' => '/images/logos/logo-2.svg'],
                ['name' => 'Company 3', 'src' => '/images/logos/logo-3.svg'],
                ['name' => 'Company 4', 'src' => '/images/logos/logo-4.svg'],
                ['name' => 'Company 5', 'src' => '/images/logos/logo-5.svg'],
            ],
        ]);
    }

    public function seoAuditReport()
    {
        return Inertia::render('Marketing/SeoAuditReport', [
            'meta' => [
                'title' => 'SEO Audit Report - Comprehensive Website Analysis | BacklinkPro',
                'description' => 'Get a complete SEO audit report with performance metrics, security headers, multi-page crawl analysis, and actionable insights.',
                'og' => [
                    'title' => 'SEO Audit Report - Comprehensive Website Analysis | BacklinkPro',
                    'description' => 'Get a complete SEO audit report with performance metrics, security headers, multi-page crawl analysis, and actionable insights.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
        ]);
    }

    public function howItWorks()
    {
        return Inertia::render('Marketing/HowItWorks', [
            'meta' => [
                'title' => 'How BacklinkPro Works — Guardrailed Link Engine',
                'description' => 'See the exact workflow: opportunity discovery, AI decisioning, execution, approvals, evidence logs, and reporting.',
                'og' => [
                    'title' => 'How BacklinkPro Works — Guardrailed Link Engine',
                    'description' => 'See the exact workflow: opportunity discovery, AI decisioning, execution, approvals, evidence logs, and reporting.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'steps' => [
                [
                    'id' => 1,
                    'title' => 'Connect Project & Rules',
                    'summary' => 'Configure your domain, target pages, keywords, and safety rules.',
                    'bullets' => [
                        'Target pages and keywords',
                        'Categories and niches',
                        'Anchor distribution (brand/partial/exact)',
                        'Link velocity (rate limits)',
                        'Blacklist/whitelist domains',
                    ],
                    'uiImage' => 'settings-preview',
                ],
                [
                    'id' => 2,
                    'title' => 'Discover & Score Opportunities',
                    'summary' => 'AI scans the web for relevant link opportunities and scores each one.',
                    'bullets' => [
                        'Prospecting from multiple sources',
                        'Topical relevance filtering',
                        'Spam signal detection',
                        'Indexing and visibility checks',
                        'Quality scoring algorithm',
                    ],
                    'uiImage' => 'opportunities-preview',
                ],
                [
                    'id' => 3,
                    'title' => 'AI Chooses Workflow + Executes',
                    'summary' => 'Based on risk score and relevance, AI selects the best action and executes.',
                    'bullets' => [
                        'Risk score evaluation',
                        'Action selection (comment/profile/forum/guest)',
                        'Template-based content generation',
                        'Form-fill automation',
                        'Human override available',
                    ],
                    'uiImage' => 'action-selection-preview',
                ],
                [
                    'id' => 4,
                    'title' => 'Approvals + Evidence + Monitoring',
                    'summary' => 'Review, approve, and track every link with full evidence logs.',
                    'bullets' => [
                        'Approval queue workflow',
                        'Evidence logs with screenshots',
                        'Link monitoring and health checks',
                        'Real-time alerts for changes',
                        'Comprehensive reporting',
                    ],
                    'uiImage' => 'approval-queue-preview',
                ],
            ],
            'workflows' => [
                [
                    'slug' => 'comment',
                    'title' => 'Comment Backlinks',
                    'bestFor' => 'Blog posts, articles',
                    'timeline' => '1-3 days',
                    'controls' => 'Templates, filters',
                    'risk' => 'low',
                ],
                [
                    'slug' => 'profile',
                    'title' => 'Profile Backlinks',
                    'bestFor' => 'Forums, communities',
                    'timeline' => '2-5 days',
                    'controls' => 'Bio templates, rules',
                    'risk' => 'low',
                ],
                [
                    'slug' => 'forum',
                    'title' => 'Forum Backlinks',
                    'bestFor' => 'Active forums',
                    'timeline' => '3-7 days',
                    'controls' => 'Topic filters, rules',
                    'risk' => 'medium',
                ],
                [
                    'slug' => 'guest',
                    'title' => 'Guest Posts',
                    'bestFor' => 'High-authority blogs',
                    'timeline' => '7-14 days',
                    'controls' => 'Pitch templates, rules',
                    'risk' => 'medium',
                ],
            ],
            'guardrails' => [
                'do' => [
                    'Human-in-the-loop approvals',
                    'Risk scoring thresholds',
                    'Whitelists/blacklists',
                    'Anchor distribution rules',
                    'Velocity controls (rate limits)',
                    'Evidence required per placement',
                ],
                'never' => [
                    'PBNs (Private Blog Networks)',
                    'Hacked or compromised sites',
                    'Spam blasts',
                    'Irrelevant site dumping',
                    'Hidden links',
                ],
            ],
            'faq' => [
                [
                    'question' => 'Is this safe?',
                    'answer' => 'Yes. Every action goes through risk scoring and human approval. We never use PBNs, hacked sites, or spam tactics.',
                ],
                [
                    'question' => 'Do you guarantee links?',
                    'answer' => 'We guarantee the execution of approved actions. Some placements may be removed by site owners, which we monitor and alert you about.',
                ],
                [
                    'question' => 'Can I control anchor text?',
                    'answer' => 'Yes. You can set anchor distribution rules (brand/partial/exact match) and approve each anchor before placement.',
                ],
                [
                    'question' => 'How are sites selected?',
                    'answer' => 'Sites are selected based on topical relevance, quality signals, indexing status, and your blacklist/whitelist rules.',
                ],
                [
                    'question' => 'What do approvals do?',
                    'answer' => 'Approvals let you review each opportunity before execution. You can approve, reject, or request changes.',
                ],
                [
                    'question' => 'How do you verify a placement?',
                    'answer' => 'We capture evidence logs with screenshots, HTML snippets, and URLs for every placement attempt.',
                ],
                [
                    'question' => 'How fast will I see results?',
                    'answer' => 'Most users see their first approved links within 1-2 weeks. Full campaign results typically appear after 1-3 months.',
                ],
                [
                    'question' => 'Does it work for SaaS/local/ecom?',
                    'answer' => 'Yes. The system adapts to your industry and niche. You can configure industry-specific rules and templates.',
                ],
                [
                    'question' => 'What if a link is removed?',
                    'answer' => 'Our monitoring system detects removed links and alerts you immediately. You can then choose to re-attempt or mark as unavailable.',
                ],
            ],
        ]);
    }

    public function solutionsIndex()
    {
        return Inertia::render('Marketing/Solutions/Index', [
            'meta' => [
                'title' => 'Solutions — BacklinkPro',
                'description' => 'Backlink automation with guardrails for SaaS, Ecommerce, Local businesses, and Agencies.',
                'og' => [
                    'title' => 'Solutions — BacklinkPro',
                    'description' => 'Backlink automation with guardrails for SaaS, Ecommerce, Local businesses, and Agencies.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'solutions' => config('marketing_solutions.solutions', []),
            'segments' => config('marketing_solutions.segments', []),
            'stages' => config('marketing_solutions.stages', []),
            'faqs' => config('marketing_solutions.index_faq', []),
        ]);
    }

    public function solutionsShow(string $slug)
    {
        $items = collect(config('marketing_solutions.solutions', []));
        $solution = $items->firstWhere('slug', $slug);
        
        if (!$solution) {
            abort(404);
        }

        $related = $items->where('slug', '!=', $slug)->take(3)->values()->all();

        return Inertia::render('Marketing/Solutions/Show', [
            'meta' => [
                'title' => $solution['seo']['title'] ?? ('Solution — ' . ($solution['name'] ?? 'BacklinkPro')),
                'description' => $solution['seo']['description'] ?? $solution['summary'],
                'og' => [
                    'title' => $solution['seo']['title'] ?? ('Solution — ' . ($solution['name'] ?? 'BacklinkPro')),
                    'description' => $solution['seo']['description'] ?? $solution['summary'],
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'solution' => $solution,
            'related' => $related,
            'faqs' => $solution['faq'] ?? [],
        ]);
    }

    public function resourcesIndex()
    {
        $items = collect(config('marketing_resources.items', []));
        $featuredSlugs = config('marketing_resources.featured', []);
        $featured = $items->filter(fn($item) => in_array($item['slug'], $featuredSlugs))->values()->all();
        $latest = $items->take(6)->values()->all();
        $allItems = $items->values()->all();

        return Inertia::render('Marketing/Resources/Index', [
            'meta' => [
                'title' => 'Resources — BacklinkPro',
                'description' => 'Playbooks, templates, and tools for safe link building with guardrails, approvals, evidence logs, and monitoring.',
                'og' => [
                    'title' => 'Resources — BacklinkPro',
                    'description' => 'Playbooks, templates, and tools for safe link building with guardrails, approvals, evidence logs, and monitoring.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'types' => config('marketing_resources.types', []),
            'featured' => $featured,
            'latest' => $latest,
            'allItems' => $allItems,
            'topics' => config('marketing_resources.topics', []),
        ]);
    }

    public function resourcesType(string $type)
    {
        $types = collect(config('marketing_resources.types', []));
        $typeData = $types->firstWhere('slug', $type);
        
        if (!$typeData) {
            abort(404);
        }

        $items = collect(config('marketing_resources.items', []))
            ->where('type', $type)
            ->values()
            ->all();

        return Inertia::render('Marketing/Resources/Type', [
            'meta' => [
                'title' => ucfirst($type) . ' Resources — BacklinkPro',
                'description' => 'Browse BacklinkPro ' . $type . ' resources: guides, playbooks, templates, and tools.',
                'og' => [
                    'title' => ucfirst($type) . ' Resources — BacklinkPro',
                    'description' => 'Browse BacklinkPro ' . $type . ' resources: guides, playbooks, templates, and tools.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'type' => $typeData,
            'items' => $items,
            'topics' => config('marketing_resources.topics', []),
        ]);
    }

    public function resourcesShow(string $type, string $slug)
    {
        $item = collect(config('marketing_resources.items', []))
            ->firstWhere(fn($i) => $i['type'] === $type && $i['slug'] === $slug);
        
        if (!$item) {
            abort(404);
        }

        $related = collect(config('marketing_resources.items', []))
            ->where('type', $type)
            ->where('slug', '!=', $slug)
            ->take(3)
            ->values()
            ->all();

        return Inertia::render('Marketing/Resources/Show', [
            'meta' => [
                'title' => $item['seo']['title'] ?? ($item['title'] . ' — BacklinkPro'),
                'description' => $item['seo']['description'] ?? $item['excerpt'],
                'og' => [
                    'title' => $item['seo']['title'] ?? ($item['title'] . ' — BacklinkPro'),
                    'description' => $item['seo']['description'] ?? $item['excerpt'],
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'item' => $item,
            'related' => $related,
        ]);
    }

    public function glossaryIndex()
    {
        $terms = config('marketing_resources.glossary', []);

        return Inertia::render('Marketing/Resources/Glossary', [
            'meta' => [
                'title' => 'Glossary — BacklinkPro',
                'description' => 'Definitions for link building, safety, approvals, evidence logs, monitoring, and AI-driven SEO terms.',
                'og' => [
                    'title' => 'Glossary — BacklinkPro',
                    'description' => 'Definitions for link building, safety, approvals, evidence logs, monitoring, and AI-driven SEO terms.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'terms' => $terms,
        ]);
    }

    public function security()
    {
        return Inertia::render('Marketing/Security', [
            'meta' => [
                'title' => 'Security & Trust — BacklinkPro',
                'description' => 'Security-by-design: approvals, audit trails, evidence logs, and guardrails for safe backlink automation.',
                'og' => [
                    'title' => 'Security & Trust — BacklinkPro',
                    'description' => 'Security-by-design: approvals, audit trails, evidence logs, and guardrails for safe backlink automation.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'sections' => config('marketing_security.sections', []),
            'faqs' => config('marketing_security.faqs', []),
            'trustPoints' => config('marketing_security.trust_points', []),
            'disclosures' => config('marketing_security.disclosures', []),
        ]);
    }

    public function contact()
    {
        return Inertia::render('Marketing/Contact', [
            'meta' => [
                'title' => 'Contact / Book a Demo — BacklinkPro',
                'description' => 'Talk to sales, request a demo, or ask about partnerships. Guardrailed backlink automation with approvals and evidence logs.',
                'og' => [
                    'title' => 'Contact / Book a Demo — BacklinkPro',
                    'description' => 'Talk to sales, request a demo, or ask about partnerships. Guardrailed backlink automation with approvals and evidence logs.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'inquiryTypes' => [
                ['value' => 'sales', 'label' => 'Sales'],
                ['value' => 'support', 'label' => 'Support'],
                ['value' => 'partnership', 'label' => 'Partnership'],
            ],
            'segments' => [
                ['value' => 'saas', 'label' => 'SaaS'],
                ['value' => 'ecommerce', 'label' => 'Ecommerce'],
                ['value' => 'local', 'label' => 'Local'],
                ['value' => 'agency', 'label' => 'Agency/Reseller'],
            ],
            'budgets' => [
                ['value' => '<200', 'label' => '< $200/mo'],
                ['value' => '200-500', 'label' => '$200–$500/mo'],
                ['value' => '500-1500', 'label' => '$500–$1,500/mo'],
                ['value' => '1500-5000', 'label' => '$1,500–$5,000/mo'],
                ['value' => '>5000', 'label' => '> $5,000/mo'],
            ],
            'demoEmbedUrl' => null,
            'logos' => [],
            'testimonials' => [
                ['quote' => '"Approvals + evidence logs made automation feel safe."', 'name' => '(Placeholder)', 'role' => 'SEO Lead'],
            ],
        ]);
    }

    public function contactSubmit(Request $request)
    {
        $validated = $request->validate([
            'inquiry_type' => 'required|in:sales,support,partnership',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'company' => 'nullable|string|max:190',
            'website' => 'nullable|string|max:255',
            'segment' => 'nullable|in:saas,ecommerce,local,agency',
            'budget' => 'nullable|string|max:50',
            'message' => 'required|string|max:4000',
            'preferred_contact' => 'nullable|in:email,call,whatsapp',
            'utm' => 'nullable|array',
            'utm.source' => 'nullable|string|max:100',
            'utm.medium' => 'nullable|string|max:100',
            'utm.campaign' => 'nullable|string|max:100',
            'utm.term' => 'nullable|string|max:100',
            'utm.content' => 'nullable|string|max:100',
            'hp' => 'nullable|string|max:10',
        ]);

        // Honeypot: if filled, silently succeed (anti-bot)
        if (!empty($validated['hp'])) {
            return back(303)->with('success', 'Thanks! We received your request.');
        }

        // Persist lead
        $lead = \App\Models\MarketingContactRequest::create([
            'inquiry_type' => $validated['inquiry_type'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company' => $validated['company'] ?? null,
            'website' => $validated['website'] ?? null,
            'segment' => $validated['segment'] ?? null,
            'budget' => $validated['budget'] ?? null,
            'message' => $validated['message'],
            'preferred_contact' => $validated['preferred_contact'] ?? 'email',
            'utm_json' => $validated['utm'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 1000),
            'status' => 'new',
        ]);

        // Optional: email notify admin (safe fallback if mail not configured)
        try {
            \Log::info('New marketing contact lead', ['id' => $lead->id, 'email' => $lead->email, 'type' => $lead->inquiry_type]);
        } catch (\Throwable $e) {
            \Log::error('Lead notify failed', ['error' => $e->getMessage()]);
        }

        return back(303)->with('success', 'Thanks! We received your request.');
    }

    public function partners()
    {
        return Inertia::render('Marketing/Partners', [
            'meta' => [
                'title' => 'Partners / Agency Program — BacklinkPro',
                'description' => 'Refer, resell, or partner with BacklinkPro. Guardrailed link automation with approvals, evidence logs, and monitoring.',
                'og' => [
                    'title' => 'Partners / Agency Program — BacklinkPro',
                    'description' => 'Refer, resell, or partner with BacklinkPro. Guardrailed link automation with approvals, evidence logs, and monitoring.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'partnerTypes' => [
                ['value' => 'referral', 'label' => 'Referral Partner'],
                ['value' => 'reseller', 'label' => 'Agency / Reseller'],
                ['value' => 'integration', 'label' => 'Integration Partner'],
            ],
            'companySizes' => [
                ['value' => 'solo', 'label' => 'Solo'],
                ['value' => '2-10', 'label' => '2–10'],
                ['value' => '11-50', 'label' => '11–50'],
                ['value' => '50+', 'label' => '50+'],
            ],
            'clientCounts' => [
                ['value' => '0-5', 'label' => '0–5'],
                ['value' => '6-20', 'label' => '6–20'],
                ['value' => '21-50', 'label' => '21–50'],
                ['value' => '50+', 'label' => '50+'],
            ],
            'benefits' => config('marketing_partners.benefits'),
            'tiers' => config('marketing_partners.tiers'),
            'requirements' => config('marketing_partners.requirements'),
            'howItWorks' => config('marketing_partners.how_it_works'),
            'faqs' => config('marketing_partners.faqs'),
            'testimonials' => config('marketing_partners.testimonials'),
            'disclosures' => config('marketing_partners.disclosures'),
        ]);
    }

    public function partnerApply(Request $request)
    {
        $validated = $request->validate([
            'partner_type' => 'required|in:referral,reseller,integration',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'company' => 'nullable|string|max:190',
            'website' => 'nullable|string|max:255',
            'company_size' => 'nullable|in:solo,2-10,11-50,50+',
            'client_count' => 'nullable|in:0-5,6-20,21-50,50+',
            'regions' => 'nullable|string|max:190',
            'message' => 'required|string|max:4000',
            'utm' => 'nullable|array',
            'utm.source' => 'nullable|string|max:100',
            'utm.medium' => 'nullable|string|max:100',
            'utm.campaign' => 'nullable|string|max:100',
            'utm.term' => 'nullable|string|max:100',
            'utm.content' => 'nullable|string|max:100',
            'hp' => 'nullable|string|max:10',
        ]);

        if (!empty($validated['hp'])) {
            return back(303)->with('success', 'Thanks! Your application was received.');
        }

        $app = \App\Models\PartnerApplication::create([
            'partner_type' => $validated['partner_type'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company' => $validated['company'] ?? null,
            'website' => $validated['website'] ?? null,
            'company_size' => $validated['company_size'] ?? null,
            'client_count' => $validated['client_count'] ?? null,
            'regions' => $validated['regions'] ?? null,
            'message' => $validated['message'],
            'utm_json' => $validated['utm'] ?? null,
            'status' => 'new',
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 1000),
        ]);

        try {
            \Log::info('New partner application', ['id' => $app->id, 'email' => $app->email, 'type' => $app->partner_type]);
        } catch (\Throwable $e) {
            \Log::error('Partner apply log failed', ['error' => $e->getMessage()]);
        }

        return back(303)->with('success', 'Thanks! Your application was received.');
    }

    public function about()
    {
        return Inertia::render('Marketing/About', [
            'meta' => [
                'title' => 'About — BacklinkPro',
                'description' => 'BacklinkPro is a guardrailed link engine: approvals, evidence logs, and monitoring for safe backlink automation.',
                'og' => [
                    'title' => 'About — BacklinkPro',
                    'description' => 'BacklinkPro is a guardrailed link engine: approvals, evidence logs, and monitoring for safe backlink automation.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'principles' => config('marketing_about.principles'),
            'timeline' => config('marketing_about.timeline'),
            'values' => config('marketing_about.values'),
            'metrics' => config('marketing_about.metrics'),
            'faqs' => config('marketing_about.faqs'),
            'disclosures' => config('marketing_about.disclosures'),
        ]);
    }

    public function privacy()
    {
        return Inertia::render('Marketing/Legal/PrivacyPolicy', [
            'meta' => [
                'title' => 'Privacy Policy — BacklinkPro',
                'description' => 'How BacklinkPro collects, uses, and protects information. (Template—review before launch).',
                'og' => [
                    'title' => 'Privacy Policy — BacklinkPro',
                    'description' => 'How BacklinkPro collects, uses, and protects information. (Template—review before launch).',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'policy' => config('marketing_legal.privacy'),
            'lastUpdated' => config('marketing_legal.last_updated'),
            'contactEmail' => config('marketing_legal.privacy_contact_email'),
        ]);
    }

    public function terms()
    {
        return Inertia::render('Marketing/Legal/TermsOfService', [
            'meta' => [
                'title' => 'Terms of Service — BacklinkPro',
                'description' => 'Terms governing use of BacklinkPro website and services. (Template—review before launch).',
                'og' => [
                    'title' => 'Terms of Service — BacklinkPro',
                    'description' => 'Terms governing use of BacklinkPro website and services. (Template—review before launch).',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'terms' => config('marketing_legal.terms'),
            'lastUpdated' => config('marketing_legal.last_updated'),
            'supportEmail' => config('marketing_legal.support_contact_email'),
        ]);
    }

    public function product()
    {
        return Inertia::render('Marketing/Product', [
            'meta' => [
                'title' => 'Product — BacklinkPro',
                'description' => 'See what\'s inside BacklinkPro: projects, analyzer, guardrails, approvals, evidence logs, and monitoring.',
                'og' => [
                    'title' => 'Product — BacklinkPro',
                    'description' => 'See what\'s inside BacklinkPro: projects, analyzer, guardrails, approvals, evidence logs, and monitoring.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'heroBadges' => config('marketing_product.hero_badges'),
            'modules' => config('marketing_product.modules'),
            'workflows' => config('marketing_product.workflows'),
            'guardrails' => config('marketing_product.guardrails'),
            'reports' => config('marketing_product.reports'),
            'faqs' => config('marketing_product.faqs'),
            'disclosures' => config('marketing_product.disclosures'),
        ]);
    }

    public function freePlan()
    {
        return Inertia::render('Marketing/FreePlan', [
            'meta' => [
                'title' => 'Free Backlink Plan — BacklinkPro',
                'description' => 'Generate a safe backlink plan with guardrails: workflow mix, action schedule, and evidence logging approach.',
                'og' => [
                    'title' => 'Free Backlink Plan — BacklinkPro',
                    'description' => 'Generate a safe backlink plan with guardrails: workflow mix, action schedule, and evidence logging approach.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'segments' => [
                ['value' => 'saas', 'label' => 'SaaS'],
                ['value' => 'ecommerce', 'label' => 'Ecommerce'],
                ['value' => 'local', 'label' => 'Local'],
                ['value' => 'agency', 'label' => 'Agency'],
            ],
            'riskModes' => [
                ['value' => 'conservative', 'label' => 'Conservative (lowest risk)'],
                ['value' => 'balanced', 'label' => 'Balanced'],
                ['value' => 'growth', 'label' => 'Growth (higher velocity)'],
            ],
            'goals' => [
                ['value' => 'authority', 'label' => 'Increase authority'],
                ['value' => 'rankings', 'label' => 'Support rankings'],
                ['value' => 'product-pages', 'label' => 'Support money/product pages'],
                ['value' => 'content-clusters', 'label' => 'Support content clusters'],
                ['value' => 'brand', 'label' => 'Strengthen brand signals'],
            ],
            'workflows' => [
                ['value' => 'comment', 'label' => 'Comment'],
                ['value' => 'profile', 'label' => 'Profile'],
                ['value' => 'forum', 'label' => 'Forum'],
                ['value' => 'guest', 'label' => 'Guest'],
            ],
            'faqs' => [
                [
                    'q' => 'Do you guarantee backlinks?',
                    'a' => 'No. Outcomes vary by niche and moderation. We focus on guardrails, approvals, evidence logging, and transparency.',
                ],
                [
                    'q' => 'What is an "action"?',
                    'a' => 'One executed workflow step tracked with evidence. It is not a guaranteed link.',
                ],
                [
                    'q' => 'How do approvals work?',
                    'a' => 'Actions above your risk threshold require manual approval before execution. You can review and approve/reject each one.',
                ],
                [
                    'q' => 'Is this safe?',
                    'a' => 'Yes. All workflows include risk thresholds, velocity caps, whitelists/blacklists, and approval gates. We avoid spam tactics and PBNs.',
                ],
                [
                    'q' => 'What evidence is logged?',
                    'a' => 'Every action includes: placement URL, screenshot or HTML snippet (where available), timestamp, and status (live/lost/pending).',
                ],
                [
                    'q' => 'Can I change risk mode later?',
                    'a' => 'Yes. You can adjust risk thresholds, velocity caps, and approval settings at any time in your project settings.',
                ],
                [
                    'q' => 'Which plan should I buy?',
                    'a' => 'Start with the plan that matches your estimated weekly actions. You can upgrade or downgrade as needed.',
                ],
            ],
        ]);
    }

    public function freePlanSubmit(Request $request)
    {
        $validated = $request->validate([
            'website' => 'required|url|max:255',
            'segment' => 'required|in:saas,ecommerce,local,agency',
            'risk_mode' => 'required|in:conservative,balanced,growth',
            'goals' => 'required|array|min:1',
            'goals.*' => 'in:authority,rankings,product-pages,content-clusters,brand',
            'target_pages' => 'nullable|array|max:5',
            'target_pages.*' => 'nullable|url|max:255',
            'competitors' => 'nullable|array|max:5',
            'competitors.*' => 'nullable|url|max:255',
            'monthly_budget' => 'nullable|integer|min:0|max:200000',
            'email' => 'nullable|email|max:190',
            'utm' => 'nullable|array',
            'utm.source' => 'nullable|string|max:100',
            'utm.medium' => 'nullable|string|max:100',
            'utm.campaign' => 'nullable|string|max:100',
            'utm.term' => 'nullable|string|max:100',
            'utm.content' => 'nullable|string|max:100',
            'hp' => 'nullable|string|max:10',
        ]);

        if (!empty($validated['hp'])) {
            $plan = $this->generatePlanPreview($validated);
            return back(303)->with('plan', $plan)->with('success', 'Plan generated.');
        }

        $req = \App\Models\FreePlanRequest::create([
            'website' => $validated['website'],
            'segment' => $validated['segment'],
            'risk_mode' => $validated['risk_mode'],
            'goals' => $validated['goals'],
            'target_pages' => $validated['target_pages'] ?? [],
            'competitors' => $validated['competitors'] ?? [],
            'monthly_budget' => $validated['monthly_budget'] ?? null,
            'email' => $validated['email'] ?? null,
            'utm_json' => $validated['utm'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 1000),
            'status' => 'new',
        ]);

        $plan = $this->generatePlanPreview($validated);

        \Log::info('Free plan generated', ['id' => $req->id, 'website' => $req->website, 'segment' => $req->segment]);

        return back(303)
            ->with('plan', $plan)
            ->with('email', $validated['email'] ?? '')
            ->with('success', 'Plan generated.');
    }

    private function generatePlanPreview(array $input): array
    {
        // Simple deterministic heuristics (placeholder until AI model)
        $risk = $input['risk_mode'];
        $segment = $input['segment'];
        $goalsCount = count($input['goals'] ?? []);
        $targets = count($input['target_pages'] ?? []);
        $budget = (int)($input['monthly_budget'] ?? 0);

        // Base weekly actions by risk
        $base = $risk === 'conservative' ? 40 : ($risk === 'balanced' ? 70 : 110);
        $base += min(30, $goalsCount * 10);
        $base += min(20, $targets * 5);
        if ($segment === 'agency') {
            $base += 20;
        }

        // Workflow mix by segment + risk
        $mix = [
            'comment' => $risk === 'growth' ? 35 : ($risk === 'balanced' ? 40 : 45),
            'profile' => $risk === 'growth' ? 15 : ($risk === 'balanced' ? 20 : 25),
            'forum' => $risk === 'growth' ? 35 : ($risk === 'balanced' ? 25 : 20),
            'guest' => $risk === 'growth' ? 15 : ($risk === 'balanced' ? 15 : 10),
        ];
        if ($segment === 'ecommerce') {
            $mix['profile'] += 5;
            $mix['forum'] -= 5;
        }
        if ($segment === 'saas') {
            $mix['guest'] += 5;
            $mix['comment'] -= 5;
        }
        if ($segment === 'local') {
            $mix['comment'] += 5;
            $mix['guest'] -= 5;
        }

        // Normalize to 100
        $sum = array_sum($mix);
        foreach ($mix as $k => $v) {
            $mix[$k] = (int)round($v * 100 / $sum);
        }

        // Anchor presets
        $anchor = $risk === 'conservative'
            ? ['brand' => 70, 'partial' => 25, 'exact' => 5]
            : ($risk === 'balanced' ? ['brand' => 55, 'partial' => 35, 'exact' => 10] : ['brand' => 45, 'partial' => 40, 'exact' => 15]);

        // Schedule: 4 weeks
        $weekly = [
            ['week' => 1, 'actions' => (int)round($base * 0.8), 'focus' => 'Setup + relevance filters + first approvals'],
            ['week' => 2, 'actions' => (int)round($base * 1.0), 'focus' => 'Execute mixed workflows + evidence logging'],
            ['week' => 3, 'actions' => (int)round($base * 1.1), 'focus' => 'Increase velocity slightly if approvals clean'],
            ['week' => 4, 'actions' => (int)round($base * 1.1), 'focus' => 'Monitoring pass + prune risky targets'],
        ];

        $guardrails = [
            'riskThreshold' => $risk === 'conservative' ? 'High threshold (strict)' : ($risk === 'balanced' ? 'Standard threshold' : 'Moderate threshold'),
            'velocityCap' => $risk === 'growth' ? 'Higher cap with monitoring' : 'Conservative cap',
            'approvalMode' => $risk === 'growth' ? 'Manual above threshold' : 'Manual for medium+ risk',
            'lists' => 'Whitelist/Blacklist enabled',
        ];

        $nextSteps = [
            'Add 3–5 target pages (money + supporting content).',
            'Choose a conservative anchor mix and lock it.',
            'Enable approvals for anything above your risk threshold.',
            'Start with lower velocity for 7 days, then ramp if evidence is clean.',
            'Review "lost/pending" placements weekly and adjust targets.',
        ];

        return [
            'summary' => [
                'weeklyActions' => $base,
                'riskMode' => $risk,
                'segment' => $segment,
            ],
            'workflowMix' => $mix,
            'weeklySchedule' => $weekly,
            'anchorMix' => $anchor,
            'guardrails' => $guardrails,
            'nextSteps' => $nextSteps,
            'disclosures' => [
                'Outcomes vary by niche, authority, and moderation. No guaranteed backlinks.',
                'This plan is a preview based on heuristics; replace with your AI decisioning later.',
            ],
        ];
    }

    public function pricing()
    {
        // Fetch plans from database
        $dbPlans = Plan::active()->public()->ordered()->get();
        
        // Convert plans to marketing format
        $plans = $dbPlans->map(fn($plan) => $plan->toMarketingArray())->values()->toArray();
        
        // Build feature matrix from database plans
        $matrix = [];
        foreach ($dbPlans as $plan) {
            $matrix[$plan->code] = [
                'projects' => true,
                'guardrails' => true,
                'approvals' => $plan->features_json['approvals'] ?? true,
                'evidence' => $plan->features_json['evidence_logs'] ?? true,
                'comment' => $plan->features_json['comment_workflow'] ?? false,
                'profile' => $plan->features_json['profile_workflow'] ?? false,
                'forum' => $plan->features_json['forum_workflow'] ?? false,
                'guest' => $plan->features_json['guest_workflow'] ?? false,
                'monitoring' => $plan->features_json['monitoring'] ?? false,
                'exports' => $plan->features_json['exports'] ?? false,
                'weekly' => $plan->features_json['weekly_summaries'] ?? false,
                'seats' => (string) ($plan->limits_json['team_seats'] ?? '1'),
                'roles' => $plan->features_json['roles_permissions'] ?? false,
                'audit' => $plan->features_json['audit_trail'] ?? false,
            ];
        }
        
        return Inertia::render('Marketing/Pricing', [
            'meta' => [
                'title' => 'Pricing — BacklinkPro',
                'description' => 'Choose a plan for guardrailed backlink workflows with approvals, evidence logs, and monitoring.',
                'og' => [
                    'title' => 'Pricing — BacklinkPro',
                    'description' => 'Choose a plan for guardrailed backlink workflows with approvals, evidence logs, and monitoring.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'billingCycles' => config('marketing_pricing.billing_cycles'),
            'plans' => $plans,
            'featureGroups' => config('marketing_pricing.feature_groups'),
            'matrix' => $matrix,
            'addOns' => config('marketing_pricing.add_ons'),
            'faqs' => config('marketing_pricing.faqs'),
            'disclosures' => config('marketing_pricing.disclosures'),
            'guaranteeBox' => config('marketing_pricing.guarantee_box'),
        ]);
    }

    public function caseStudiesIndex()
    {
        return Inertia::render('Marketing/CaseStudies/Index', [
            'meta' => [
                'title' => 'Case Studies — BacklinkPro',
                'description' => 'Real workflows with guardrails, approvals, and evidence logs. Outcomes vary by niche and moderation.',
                'og' => [
                    'title' => 'Case Studies — BacklinkPro',
                    'description' => 'Real workflows with guardrails, approvals, and evidence logs. Outcomes vary by niche and moderation.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'items' => config('marketing_case_studies.items'),
            'filters' => config('marketing_case_studies.filters'),
            'faqs' => config('marketing_case_studies.faqs'),
        ]);
    }

    public function caseStudiesShow(string $slug)
    {
        $items = collect(config('marketing_case_studies.items'));
        $item = $items->firstWhere('slug', $slug);

        abort_if(!$item, 404);

        $related = $items->where('slug', '!=', $slug)->take(3)->values();

        return Inertia::render('Marketing/CaseStudies/Show', [
            'meta' => [
                'title' => $item['seo']['title'] ?? ($item['title'] . ' — BacklinkPro'),
                'description' => $item['seo']['description'] ?? $item['excerpt'],
                'og' => [
                    'title' => $item['seo']['title'] ?? ($item['title'] . ' — BacklinkPro'),
                    'description' => $item['seo']['description'] ?? $item['excerpt'],
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'item' => $item,
            'related' => $related,
        ]);
    }

    public function workflowsIndex()
    {
        return Inertia::render('Marketing/Workflows/Index', [
            'meta' => [
                'title' => 'Workflows — BacklinkPro',
                'description' => 'Comment, profile, forum, and guest workflows with guardrails, approvals, and evidence logs. Outcomes vary.',
                'og' => [
                    'title' => 'Workflows — BacklinkPro',
                    'description' => 'Comment, profile, forum, and guest workflows with guardrails, approvals, and evidence logs. Outcomes vary.',
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'items' => config('marketing_workflows.items'),
            'comparison' => config('marketing_workflows.comparison'),
            'faqs' => config('marketing_workflows.faqs'),
            'disclosures' => config('marketing_workflows.disclosures'),
        ]);
    }

    public function workflowsShow(string $slug)
    {
        $items = collect(config('marketing_workflows.items'));
        $item = $items->firstWhere('slug', $slug);

        abort_if(!$item, 404);

        $related = $items->where('slug', '!=', $slug)->take(3)->values();

        return Inertia::render('Marketing/Workflows/Show', [
            'meta' => [
                'title' => $item['seo']['title'] ?? ($item['title'] . ' Workflow — BacklinkPro'),
                'description' => $item['seo']['description'] ?? $item['excerpt'],
                'og' => [
                    'title' => $item['seo']['title'] ?? ($item['title'] . ' Workflow — BacklinkPro'),
                    'description' => $item['seo']['description'] ?? $item['excerpt'],
                    'image' => asset('images/og-image.jpg'),
                ],
            ],
            'item' => $item,
            'related' => $related,
            'disclosures' => config('marketing_workflows.disclosures'),
            'faqs' => config('marketing_workflows.faqs'),
        ]);
    }

    public function sitemap()
    {
        $urls = [
            url('/'),
            url('/product'),
            url('/how-it-works'),
            url('/pricing'),
            url('/workflows'),
            url('/case-studies'),
            url('/resources'),
            url('/security'),
            url('/partners'),
            url('/about'),
            url('/contact'),
            url('/free-backlink-plan'),
            url('/privacy-policy'),
            url('/terms'),
        ];

        // Add workflow detail pages
        foreach (config('marketing_workflows.items', []) as $workflow) {
            $urls[] = url('/workflows/' . $workflow['slug']);
        }

        // Add case study detail pages
        foreach (config('marketing_case_studies.items', []) as $caseStudy) {
            $urls[] = url('/case-studies/' . $caseStudy['slug']);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.8</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
