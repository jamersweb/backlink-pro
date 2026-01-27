<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainMetaPage;
use App\Models\DomainMetaChange;
use App\Models\DomainAudit;
use App\Models\GscTopPage;
use App\Jobs\Meta\PublishMetaChangeJob;
use App\Services\Meta\Connectors\MetaConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DomainMetaController extends Controller
{
    /**
     * Show meta editor
     */
    public function index(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $connector = $domain->metaConnector;
        $pageId = $request->query('page_id');

        // Get pages list with draft/published status
        $pagesQuery = $domain->metaPages()->latest();
        $pages = $pagesQuery->paginate(20)->withQueryString();
        
        // Add draft/published flags to pages
        foreach ($pages->items() as $page) {
            $page->latest_draft = $page->latestDraft();
            $page->latest_published = $page->latestPublished();
        }

        // Get selected page details
        $selectedPage = null;
        if ($pageId) {
            $selectedPage = $domain->metaPages()
                ->with(['changes' => function($q) {
                    $q->latest()->limit(5);
                }])
                ->find($pageId);
        }

        // Check import options
        $latestAudit = DomainAudit::where('domain_id', $domain->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        $hasGscPages = GscTopPage::where('domain_id', $domain->id)
            ->exists();

        return Inertia::render('Domains/Meta/Index', [
            'domain' => $domain,
            'connector' => $connector,
            'pages' => $pages,
            'selectedPage' => $selectedPage,
            'importOptions' => [
                'auditAvailable' => $latestAudit !== null,
                'gscAvailable' => $hasGscPages,
                'connectorAvailable' => $connector && $connector->status === 'connected',
            ],
        ]);
    }

    /**
     * Import pages from various sources
     */
    public function importPages(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'source' => 'required|in:audit,gsc,connector,manual',
            'limit' => 'nullable|integer|min:1|max:500',
            'urls' => 'nullable|array',
        ]);

        $source = $validated['source'];
        $limit = $validated['limit'] ?? 100;
        $imported = 0;

        DB::beginTransaction();
        try {
            if ($source === 'audit') {
                $latestAudit = DomainAudit::where('domain_id', $domain->id)
                    ->where('status', 'completed')
                    ->latest()
                    ->first();

                if (!$latestAudit) {
                    return back()->with('error', 'No completed audit found');
                }

                $auditPages = $latestAudit->pages()
                    ->limit($limit)
                    ->get();

                foreach ($auditPages as $auditPage) {
                    DomainMetaPage::updateOrCreate(
                        [
                            'domain_id' => $domain->id,
                            'path' => $auditPage->path,
                        ],
                        [
                            'source' => DomainMetaPage::SOURCE_AUDIT,
                            'url' => $auditPage->url,
                            'resource_type' => DomainMetaPage::RESOURCE_TYPE_CUSTOM,
                            'title_current' => $auditPage->title,
                        ]
                    );
                    $imported++;
                }
            } elseif ($source === 'gsc') {
                $gscPages = GscTopPage::where('domain_id', $domain->id)
                    ->latest('date')
                    ->limit($limit)
                    ->get();

                foreach ($gscPages as $gscPage) {
                    DomainMetaPage::updateOrCreate(
                        [
                            'domain_id' => $domain->id,
                            'path' => parse_url($gscPage->page, PHP_URL_PATH),
                        ],
                        [
                            'source' => DomainMetaPage::SOURCE_GSC,
                            'url' => $gscPage->page,
                            'resource_type' => DomainMetaPage::RESOURCE_TYPE_CUSTOM,
                        ]
                    );
                    $imported++;
                }
            } elseif ($source === 'connector') {
                $connector = $domain->metaConnector;
                if (!$connector || $connector->status !== 'connected') {
                    return back()->with('error', 'Connector not connected');
                }

                $metaConnector = MetaConnectorFactory::make($connector->type);
                $resources = $metaConnector->listResources($domain, $connector);

                foreach (array_slice($resources, 0, $limit) as $resource) {
                    DomainMetaPage::updateOrCreate(
                        [
                            'domain_id' => $domain->id,
                            'resource_type' => $resource['resource_type'],
                            'external_id' => $resource['external_id'],
                        ],
                        [
                            'source' => DomainMetaPage::SOURCE_CONNECTOR,
                            'url' => $resource['url'],
                            'path' => $resource['path'],
                            'title_current' => $resource['title_current'],
                        ]
                    );
                    $imported++;
                }
            } elseif ($source === 'manual') {
                $urls = $validated['urls'] ?? [];
                foreach ($urls as $url) {
                    $path = parse_url($url, PHP_URL_PATH);
                    DomainMetaPage::updateOrCreate(
                        [
                            'domain_id' => $domain->id,
                            'path' => $path,
                        ],
                        [
                            'source' => DomainMetaPage::SOURCE_MANUAL,
                            'url' => $url,
                            'resource_type' => DomainMetaPage::RESOURCE_TYPE_CUSTOM,
                        ]
                    );
                    $imported++;
                }
            }

            DB::commit();
            return back()->with('success', "Imported {$imported} pages");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Save draft meta change
     */
    public function saveDraft(Request $request, Domain $domain, DomainMetaPage $page)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id() || $page->domain_id !== $domain->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:160',
            'og_title' => 'nullable|string|max:60',
            'og_description' => 'nullable|string|max:160',
            'og_image' => 'nullable|url|max:500',
            'canonical' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
        ]);

        $metaAfter = [
            'title' => $validated['title'] ?? '',
            'description' => $validated['description'] ?? '',
            'og_title' => $validated['og_title'] ?? '',
            'og_description' => $validated['og_description'] ?? '',
            'og_image' => $validated['og_image'] ?? '',
            'canonical' => $validated['canonical'] ?? '',
            'robots' => $validated['robots'] ?? 'index,follow',
        ];

        $metaBefore = $page->meta_current_json ?? $page->meta_published_json ?? [];

        DomainMetaChange::updateOrCreate(
            [
                'domain_id' => $domain->id,
                'page_id' => $page->id,
                'user_id' => Auth::id(),
                'status' => DomainMetaChange::STATUS_DRAFT,
            ],
            [
                'meta_before_json' => $metaBefore,
                'meta_after_json' => $metaAfter,
                'publish_target' => $domain->metaConnector?->type === 'custom_js' 
                    ? DomainMetaChange::PUBLISH_TARGET_SNIPPET 
                    : DomainMetaChange::PUBLISH_TARGET_CONNECTOR,
            ]
        );

        return back()->with('success', 'Draft saved');
    }

    /**
     * Publish meta change
     */
    public function publish(Request $request, Domain $domain, DomainMetaPage $page)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id() || $page->domain_id !== $domain->id) {
            abort(403);
        }

        // Check quota limits
        $user = Auth::user();
        try {
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->assertCan($user, 'meta.publish_per_month', 1);
        } catch (\App\Exceptions\QuotaExceededException $e) {
            return back()->withErrors([
                'quota' => $e->getMessage()
            ]);
        }

        // Get or create draft
        $draft = $page->latestDraft();
        
        if (!$draft) {
            // Create draft from request if no draft exists
            $validated = $request->validate([
                'title' => 'nullable|string|max:60',
                'description' => 'nullable|string|max:160',
                'og_title' => 'nullable|string|max:60',
                'og_description' => 'nullable|string|max:160',
                'og_image' => 'nullable|url|max:500',
                'canonical' => 'nullable|url|max:500',
                'robots' => 'nullable|string|max:100',
            ]);

            $metaAfter = [
                'title' => $validated['title'] ?? '',
                'description' => $validated['description'] ?? '',
                'og_title' => $validated['og_title'] ?? '',
                'og_description' => $validated['og_description'] ?? '',
                'og_image' => $validated['og_image'] ?? '',
                'canonical' => $validated['canonical'] ?? '',
                'robots' => $validated['robots'] ?? 'index,follow',
            ];

            $draft = DomainMetaChange::create([
                'domain_id' => $domain->id,
                'page_id' => $page->id,
                'user_id' => Auth::id(),
                'status' => DomainMetaChange::STATUS_QUEUED,
                'meta_before_json' => $page->meta_current_json ?? $page->meta_published_json ?? [],
                'meta_after_json' => $metaAfter,
                'publish_target' => $domain->metaConnector?->type === 'custom_js' 
                    ? DomainMetaChange::PUBLISH_TARGET_SNIPPET 
                    : DomainMetaChange::PUBLISH_TARGET_CONNECTOR,
            ]);
        } else {
            // Update existing draft to queued
            $draft->update(['status' => DomainMetaChange::STATUS_QUEUED]);
        }

        // Consume quota
        $quotaService = app(\App\Services\Usage\QuotaService::class);
        $quotaService->consume($user, 'meta.publish_per_month', 1, 'month', [
            'change_id' => $draft->id,
            'page_id' => $page->id,
            'domain_id' => $domain->id,
        ]);

        // Dispatch job
        PublishMetaChangeJob::dispatch($draft->id);

        return back()->with('success', 'Publishing...');
    }

    /**
     * Refresh current meta from connector
     */
    public function refreshPages(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $connector = $domain->metaConnector;
        if (!$connector || $connector->status !== 'connected') {
            return back()->with('error', 'Connector not connected');
        }

        $pageIds = $request->input('page_ids', []);
        $pages = $pageIds 
            ? $domain->metaPages()->whereIn('id', $pageIds)->get()
            : $domain->metaPages()->whereNotNull('external_id')->get();

        $metaConnector = MetaConnectorFactory::make($connector->type);
        $refreshed = 0;

        foreach ($pages as $page) {
            try {
                $meta = $metaConnector->fetchMeta($page, $connector);
                $page->update([
                    'meta_current_json' => $meta,
                    'title_current' => $meta['title'] ?? '',
                ]);
                $refreshed++;
            } catch (\Exception $e) {
                // Continue with other pages
            }
        }

        return back()->with('success', "Refreshed {$refreshed} pages");
    }
}
