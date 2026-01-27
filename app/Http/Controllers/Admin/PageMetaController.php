<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageMetaController extends Controller
{
    /**
     * Display all page metas
     */
    public function index(Request $request)
    {
        $query = PageMeta::query()->with('updatedByUser');

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('page_name', 'like', '%' . $request->search . '%')
                    ->orWhere('page_key', 'like', '%' . $request->search . '%')
                    ->orWhere('url_path', 'like', '%' . $request->search . '%')
                    ->orWhere('meta_title', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $pages = $query->orderBy('page_name')->paginate(20)->withQueryString();

        return Inertia::render('Admin/PageMetas/Index', [
            'pages' => $pages,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Admin/PageMetas/Create', [
            'schemaTemplates' => PageMeta::getSchemaTemplates(),
        ]);
    }

    /**
     * Store new page meta
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_key' => 'required|string|max:100|unique:page_metas,page_key',
            'page_name' => 'required|string|max:255',
            'route_name' => 'nullable|string|max:255',
            'url_path' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|string|max:50',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|string|max:500',
            'schema_json' => 'nullable|array',
            'content_json' => 'nullable|array',
            'is_active' => 'boolean',
            'is_indexable' => 'boolean',
            'is_followable' => 'boolean',
            'canonical_url' => 'nullable|string|max:500',
        ]);

        $validated['updated_by'] = auth()->id();

        PageMeta::create($validated);

        return redirect()->route('admin.page-metas.index')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $page = PageMeta::findOrFail($id);

        return Inertia::render('Admin/PageMetas/Edit', [
            'page' => $page,
            'schemaTemplates' => PageMeta::getSchemaTemplates(),
        ]);
    }

    /**
     * Update page meta
     */
    public function update(Request $request, $id)
    {
        $page = PageMeta::findOrFail($id);

        $validated = $request->validate([
            'page_key' => 'required|string|max:100|unique:page_metas,page_key,' . $id,
            'page_name' => 'required|string|max:255',
            'route_name' => 'nullable|string|max:255',
            'url_path' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|string|max:50',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|string|max:500',
            'schema_json' => 'nullable|array',
            'content_json' => 'nullable|array',
            'is_active' => 'boolean',
            'is_indexable' => 'boolean',
            'is_followable' => 'boolean',
            'canonical_url' => 'nullable|string|max:500',
        ]);

        $validated['updated_by'] = auth()->id();

        $page->update($validated);

        return redirect()->route('admin.page-metas.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Delete page meta
     */
    public function destroy($id)
    {
        $page = PageMeta::findOrFail($id);
        $page->delete();

        return redirect()->route('admin.page-metas.index')
            ->with('success', 'Page deleted successfully.');
    }

    /**
     * Toggle page active status
     */
    public function toggleStatus($id)
    {
        $page = PageMeta::findOrFail($id);
        $page->update([
            'is_active' => !$page->is_active,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Page status updated.');
    }

    /**
     * Duplicate a page meta
     */
    public function duplicate($id)
    {
        $original = PageMeta::findOrFail($id);

        $newPage = $original->replicate();
        $newPage->page_key = $original->page_key . '_copy_' . time();
        $newPage->page_name = $original->page_name . ' (Copy)';
        $newPage->updated_by = auth()->id();
        $newPage->save();

        return redirect()->route('admin.page-metas.edit', $newPage->id)
            ->with('success', 'Page duplicated successfully.');
    }

    /**
     * Bulk import default pages
     */
    public function importDefaults()
    {
        $defaults = $this->getDefaultPages();
        $imported = 0;

        foreach ($defaults as $page) {
            if (!PageMeta::where('page_key', $page['page_key'])->exists()) {
                $page['updated_by'] = auth()->id();
                PageMeta::create($page);
                $imported++;
            }
        }

        return back()->with('success', "Imported {$imported} default pages.");
    }

    /**
     * Get default marketing pages
     */
    private function getDefaultPages(): array
    {
        return [
            [
                'page_key' => 'home',
                'page_name' => 'Homepage',
                'route_name' => 'marketing.home',
                'url_path' => '/',
                'meta_title' => 'Build Quality Backlinks Without Manual Grind | BacklinkPro',
                'meta_description' => 'AI-powered backlink automation with guardrails. Risk scoring, human approvals, evidence logs, and link monitoring. No PBNs. Start your free plan.',
                'meta_keywords' => 'backlinks, SEO, link building, automation, AI',
                'og_title' => 'BacklinkPro - Automated Link Building with Guardrails',
                'og_description' => 'AI selects the safest action, executes workflows, and tracks every link with evidence.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'pricing',
                'page_name' => 'Pricing',
                'route_name' => 'marketing.pricing',
                'url_path' => '/pricing',
                'meta_title' => 'Pricing — BacklinkPro',
                'meta_description' => 'Choose a plan for guardrailed backlink workflows with approvals, evidence logs, and monitoring.',
                'meta_keywords' => 'pricing, plans, backlinks, SEO pricing',
                'og_title' => 'Pricing — BacklinkPro',
                'og_description' => 'Choose a plan for guardrailed backlink workflows with approvals, evidence logs, and monitoring.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'about',
                'page_name' => 'About',
                'route_name' => 'marketing.about',
                'url_path' => '/about',
                'meta_title' => 'About — BacklinkPro',
                'meta_description' => 'BacklinkPro is a guardrailed link engine: approvals, evidence logs, and monitoring for safe backlink automation.',
                'meta_keywords' => 'about, company, backlinks, SEO',
                'og_title' => 'About — BacklinkPro',
                'og_description' => 'BacklinkPro is a guardrailed link engine: approvals, evidence logs, and monitoring for safe backlink automation.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'product',
                'page_name' => 'Product',
                'route_name' => 'marketing.product',
                'url_path' => '/product',
                'meta_title' => 'Product — BacklinkPro',
                'meta_description' => 'See what\'s inside BacklinkPro: projects, analyzer, guardrails, approvals, evidence logs, and monitoring.',
                'meta_keywords' => 'product, features, backlinks, automation',
                'og_title' => 'Product — BacklinkPro',
                'og_description' => 'See what\'s inside BacklinkPro: projects, analyzer, guardrails, approvals, evidence logs, and monitoring.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'how-it-works',
                'page_name' => 'How It Works',
                'route_name' => 'marketing.how',
                'url_path' => '/how-it-works',
                'meta_title' => 'How BacklinkPro Works — Guardrailed Link Engine',
                'meta_description' => 'See the exact workflow: opportunity discovery, AI decisioning, execution, approvals, evidence logs, and reporting.',
                'meta_keywords' => 'how it works, workflow, backlinks, automation',
                'og_title' => 'How BacklinkPro Works — Guardrailed Link Engine',
                'og_description' => 'See the exact workflow: opportunity discovery, AI decisioning, execution, approvals, evidence logs, and reporting.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'workflows',
                'page_name' => 'Workflows',
                'route_name' => 'marketing.workflows.index',
                'url_path' => '/workflows',
                'meta_title' => 'Workflows — BacklinkPro',
                'meta_description' => 'Comment, profile, forum, and guest workflows with guardrails, approvals, and evidence logs.',
                'meta_keywords' => 'workflows, comment, profile, forum, guest post',
                'og_title' => 'Workflows — BacklinkPro',
                'og_description' => 'Comment, profile, forum, and guest workflows with guardrails, approvals, and evidence logs.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'case-studies',
                'page_name' => 'Case Studies',
                'route_name' => 'marketing.caseStudies.index',
                'url_path' => '/case-studies',
                'meta_title' => 'Case Studies — BacklinkPro',
                'meta_description' => 'Real workflows with guardrails, approvals, and evidence logs. Outcomes vary by niche and moderation.',
                'meta_keywords' => 'case studies, results, backlinks, SEO',
                'og_title' => 'Case Studies — BacklinkPro',
                'og_description' => 'Real workflows with guardrails, approvals, and evidence logs. Outcomes vary by niche and moderation.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'resources',
                'page_name' => 'Resources',
                'route_name' => 'marketing.resources.index',
                'url_path' => '/resources',
                'meta_title' => 'Resources — BacklinkPro',
                'meta_description' => 'Playbooks, templates, and tools for safe link building with guardrails, approvals, evidence logs, and monitoring.',
                'meta_keywords' => 'resources, guides, templates, backlinks',
                'og_title' => 'Resources — BacklinkPro',
                'og_description' => 'Playbooks, templates, and tools for safe link building with guardrails, approvals, evidence logs, and monitoring.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'blog',
                'page_name' => 'Blog',
                'route_name' => 'blog.index',
                'url_path' => '/blog',
                'meta_title' => 'Blog — BacklinkPro',
                'meta_description' => 'Latest articles on link building, SEO, and backlink automation with guardrails.',
                'meta_keywords' => 'blog, articles, SEO, link building',
                'og_title' => 'Blog — BacklinkPro',
                'og_description' => 'Latest articles on link building, SEO, and backlink automation with guardrails.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'contact',
                'page_name' => 'Contact',
                'route_name' => 'marketing.contact',
                'url_path' => '/contact',
                'meta_title' => 'Contact / Book a Demo — BacklinkPro',
                'meta_description' => 'Talk to sales, request a demo, or ask about partnerships. Guardrailed backlink automation with approvals and evidence logs.',
                'meta_keywords' => 'contact, demo, sales, support',
                'og_title' => 'Contact / Book a Demo — BacklinkPro',
                'og_description' => 'Talk to sales, request a demo, or ask about partnerships. Guardrailed backlink automation with approvals and evidence logs.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'partners',
                'page_name' => 'Partners',
                'route_name' => 'marketing.partners',
                'url_path' => '/partners',
                'meta_title' => 'Partners / Agency Program — BacklinkPro',
                'meta_description' => 'Refer, resell, or partner with BacklinkPro. Guardrailed link automation with approvals, evidence logs, and monitoring.',
                'meta_keywords' => 'partners, agency, referral, reseller',
                'og_title' => 'Partners / Agency Program — BacklinkPro',
                'og_description' => 'Refer, resell, or partner with BacklinkPro. Guardrailed link automation with approvals, evidence logs, and monitoring.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'security',
                'page_name' => 'Security',
                'route_name' => 'marketing.security',
                'url_path' => '/security',
                'meta_title' => 'Security & Trust — BacklinkPro',
                'meta_description' => 'Security-by-design: approvals, audit trails, evidence logs, and guardrails for safe backlink automation.',
                'meta_keywords' => 'security, trust, safety, compliance',
                'og_title' => 'Security & Trust — BacklinkPro',
                'og_description' => 'Security-by-design: approvals, audit trails, evidence logs, and guardrails for safe backlink automation.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'privacy-policy',
                'page_name' => 'Privacy Policy',
                'route_name' => 'marketing.privacy',
                'url_path' => '/privacy-policy',
                'meta_title' => 'Privacy Policy — BacklinkPro',
                'meta_description' => 'How BacklinkPro collects, uses, and protects information.',
                'meta_keywords' => 'privacy policy, data protection',
                'og_title' => 'Privacy Policy — BacklinkPro',
                'og_description' => 'How BacklinkPro collects, uses, and protects information.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'terms',
                'page_name' => 'Terms of Service',
                'route_name' => 'marketing.terms',
                'url_path' => '/terms',
                'meta_title' => 'Terms of Service — BacklinkPro',
                'meta_description' => 'Terms governing use of BacklinkPro website and services.',
                'meta_keywords' => 'terms of service, legal',
                'og_title' => 'Terms of Service — BacklinkPro',
                'og_description' => 'Terms governing use of BacklinkPro website and services.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'solutions',
                'page_name' => 'Solutions',
                'route_name' => 'marketing.solutions.index',
                'url_path' => '/solutions',
                'meta_title' => 'Solutions — BacklinkPro',
                'meta_description' => 'Backlink automation with guardrails for SaaS, Ecommerce, Local businesses, and Agencies.',
                'meta_keywords' => 'solutions, SaaS, ecommerce, local, agency',
                'og_title' => 'Solutions — BacklinkPro',
                'og_description' => 'Backlink automation with guardrails for SaaS, Ecommerce, Local businesses, and Agencies.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
            [
                'page_key' => 'free-plan',
                'page_name' => 'Free Backlink Plan',
                'route_name' => 'marketing.freePlan',
                'url_path' => '/free-backlink-plan',
                'meta_title' => 'Free Backlink Plan — BacklinkPro',
                'meta_description' => 'Generate a safe backlink plan with guardrails: workflow mix, action schedule, and evidence logging approach.',
                'meta_keywords' => 'free plan, backlink plan, generator',
                'og_title' => 'Free Backlink Plan — BacklinkPro',
                'og_description' => 'Generate a safe backlink plan with guardrails: workflow mix, action schedule, and evidence logging approach.',
                'og_type' => 'website',
                'is_active' => true,
                'is_indexable' => true,
                'is_followable' => true,
            ],
        ];
    }
}
