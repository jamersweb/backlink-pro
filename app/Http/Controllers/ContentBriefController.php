<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\ContentBrief;
use App\Models\DomainMetaPage;
use App\Models\DomainMetaChange;
use App\Services\Content\BriefGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ContentBriefController extends Controller
{
    protected $generator;

    public function __construct(BriefGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * List briefs
     */
    public function index(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $briefs = ContentBrief::where('domain_id', $domain->id)
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Domains/Content/Briefs/Index', [
            'domain' => $domain,
            'briefs' => $briefs,
        ]);
    }

    /**
     * Show create form
     */
    public function create(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $opportunityId = $request->query('opportunity_id');
        $opportunity = null;
        $prefill = [];

        if ($opportunityId) {
            $opportunity = \App\Models\KeywordOpportunity::where('id', $opportunityId)
                ->where('domain_id', $domain->id)
                ->first();
            
            if ($opportunity) {
                $prefill = [
                    'primary_keyword' => $opportunity->query,
                    'target_url' => $opportunity->page_url,
                    'target_type' => $opportunity->page_url ? 'existing_page' : 'new_page',
                ];
            }
        }

        return Inertia::render('Domains/Content/Briefs/Create', [
            'domain' => $domain,
            'opportunity' => $opportunity,
            'prefill' => $prefill,
        ]);
    }

    /**
     * Store new brief
     */
    public function store(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'primary_keyword' => 'required|string|max:255',
            'target_type' => 'required|in:existing_page,new_page',
            'target_url' => 'nullable|url',
            'suggested_slug' => 'nullable|string|max:255',
        ]);

        // Generate brief
        $briefData = $this->generator->generate(
            $domain,
            $validated['primary_keyword'],
            $validated['target_url'] ?? null,
            $validated['target_type']
        );

        // Create brief
        $brief = ContentBrief::create([
            'domain_id' => $domain->id,
            'user_id' => Auth::id(),
            'title' => $briefData['title'],
            'primary_keyword' => $validated['primary_keyword'],
            'secondary_keywords_json' => $briefData['secondary_keywords'],
            'target_type' => $validated['target_type'],
            'target_url' => $validated['target_url'] ?? null,
            'suggested_slug' => $validated['suggested_slug'] ?? Str::slug($validated['primary_keyword']),
            'intent' => $briefData['intent'],
            'outline_json' => $briefData['outline_json'],
            'faq_json' => $briefData['faq_json'],
            'internal_links_json' => $briefData['internal_links_json'],
            'meta_suggestion_json' => $briefData['meta_suggestion_json'],
            'status' => ContentBrief::STATUS_DRAFT,
        ]);

        // Create keyword map entry
        if ($brief->target_url) {
            \App\Models\KeywordMap::updateOrCreate(
                [
                    'domain_id' => $domain->id,
                    'keyword' => $brief->primary_keyword,
                ],
                [
                    'url' => $brief->target_url,
                    'source' => \App\Models\KeywordMap::SOURCE_BRIEF,
                ]
            );
        }

        // Update opportunity status if created from opportunity
        if ($request->has('opportunity_id')) {
            $opportunity = \App\Models\KeywordOpportunity::where('id', $request->opportunity_id)
                ->where('domain_id', $domain->id)
                ->first();
            if ($opportunity) {
                $opportunity->update(['status' => \App\Models\KeywordOpportunity::STATUS_BRIEF_CREATED]);
            }
        }

        // Create meta editor draft if existing page
        if ($brief->target_type === ContentBrief::TARGET_TYPE_EXISTING_PAGE && $brief->target_url) {
            $this->createMetaDraft($domain, $brief);
        }

        return redirect()->route('domains.content.briefs.show', [$domain, $brief])
            ->with('success', 'Content brief created');
    }

    /**
     * Show brief
     */
    public function show(Domain $domain, ContentBrief $brief)
    {
        Gate::authorize('domain.view', $domain);

        if ($brief->domain_id !== $domain->id || $brief->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('Domains/Content/Briefs/Show', [
            'domain' => $domain,
            'brief' => $brief,
        ]);
    }

    /**
     * Update brief status
     */
    public function updateStatus(Domain $domain, ContentBrief $brief, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        if ($brief->domain_id !== $domain->id || $brief->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,writing,published,archived',
            'outline_json' => 'nullable|array',
            'meta_suggestion_json' => 'nullable|array',
        ]);

        $updateData = ['status' => $validated['status']];

        if (isset($validated['outline_json'])) {
            $updateData['outline_json'] = $validated['outline_json'];
        }

        if (isset($validated['meta_suggestion_json'])) {
            $updateData['meta_suggestion_json'] = $validated['meta_suggestion_json'];
        }

        $brief->update($updateData);

        return back()->with('success', 'Brief updated');
    }

    /**
     * Export brief as markdown
     */
    public function exportMarkdown(Domain $domain, ContentBrief $brief)
    {
        Gate::authorize('domain.view', $domain);

        if ($brief->domain_id !== $domain->id || $brief->user_id !== Auth::id()) {
            abort(403);
        }

        $markdown = "# {$brief->title}\n\n";
        $markdown .= "**Primary Keyword:** {$brief->primary_keyword}\n";
        $markdown .= "**Intent:** {$brief->intent}\n";
        $markdown .= "**Status:** {$brief->status}\n\n";

        if ($brief->secondary_keywords_json) {
            $markdown .= "## Secondary Keywords\n\n";
            foreach ($brief->secondary_keywords_json as $keyword) {
                $markdown .= "- {$keyword}\n";
            }
            $markdown .= "\n";
        }

        $markdown .= "## Outline\n\n";
        foreach ($brief->outline_json as $section) {
            $level = $section['level'] ?? 1;
            $heading = $section['heading'] ?? '';
            $content = $section['content'] ?? '';
            
            $markdown .= str_repeat('#', $level + 1) . " {$heading}\n\n";
            if ($content) {
                $markdown .= "{$content}\n\n";
            }

            if (isset($section['subsections'])) {
                foreach ($section['subsections'] as $subsection) {
                    $subLevel = $subsection['level'] ?? 3;
                    $subHeading = $subsection['heading'] ?? '';
                    $subContent = $subsection['content'] ?? '';
                    
                    $markdown .= str_repeat('#', $subLevel + 1) . " {$subHeading}\n\n";
                    if ($subContent) {
                        $markdown .= "{$subContent}\n\n";
                    }
                }
            }
        }

        if ($brief->faq_json) {
            $markdown .= "## FAQs\n\n";
            foreach ($brief->faq_json as $faq) {
                $markdown .= "### {$faq['question']}\n\n";
                $markdown .= "{$faq['answer']}\n\n";
            }
        }

        if ($brief->internal_links_json) {
            $markdown .= "## Internal Links\n\n";
            foreach ($brief->internal_links_json as $link) {
                $markdown .= "- [{$link['anchor']}]({$link['url']})\n";
            }
            $markdown .= "\n";
        }

        if ($brief->meta_suggestion_json) {
            $markdown .= "## Meta Suggestions\n\n";
            $markdown .= "**Title:** {$brief->meta_suggestion_json['title']}\n\n";
            $markdown .= "**Description:** {$brief->meta_suggestion_json['description']}\n\n";
        }

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => 'attachment; filename="brief-' . Str::slug($brief->title) . '.md"',
        ]);
    }

    /**
     * Send meta suggestions to Meta Editor
     */
    public function sendToMetaEditor(Domain $domain, ContentBrief $brief)
    {
        Gate::authorize('domain.view', $domain);

        if ($brief->domain_id !== $domain->id || $brief->user_id !== Auth::id()) {
            abort(403);
        }

        if ($brief->target_type !== ContentBrief::TARGET_TYPE_EXISTING_PAGE || !$brief->target_url) {
            return back()->with('error', 'Can only send meta for existing pages');
        }

        $this->createMetaDraft($domain, $brief);

        return back()->with('success', 'Meta draft created in Meta Editor');
    }

    /**
     * Create meta editor draft from brief
     */
    protected function createMetaDraft(Domain $domain, ContentBrief $brief)
    {
        if (!$brief->meta_suggestion_json || !$brief->target_url) {
            return;
        }

        $path = parse_url($brief->target_url, PHP_URL_PATH) ?? '/';
        if ($path === '') {
            $path = '/';
        }

        // Find or create meta page
        $metaPage = DomainMetaPage::firstOrCreate(
            [
                'domain_id' => $domain->id,
                'path' => $path,
            ],
            [
                'url' => $brief->target_url,
                'source' => DomainMetaPage::SOURCE_MANUAL,
                'title_current' => $brief->title,
            ]
        );

        // Create meta change draft
        DomainMetaChange::create([
            'domain_id' => $domain->id,
            'page_id' => $metaPage->id,
            'user_id' => Auth::id(),
            'status' => DomainMetaChange::STATUS_DRAFT,
            'meta_before_json' => $metaPage->meta_current_json ?? $metaPage->meta_published_json ?? [],
            'meta_after_json' => [
                'title' => $brief->meta_suggestion_json['title'] ?? '',
                'description' => $brief->meta_suggestion_json['description'] ?? '',
                'og_title' => $brief->meta_suggestion_json['title'] ?? '',
                'og_description' => $brief->meta_suggestion_json['description'] ?? '',
                'robots' => 'index,follow',
            ],
            'publish_target' => $domain->metaConnector ? DomainMetaChange::PUBLISH_TARGET_CONNECTOR : DomainMetaChange::PUBLISH_TARGET_SNIPPET,
        ]);
    }
}
