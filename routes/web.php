<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserCampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GmailOAuthController;
use App\Http\Controllers\GoogleGa4Controller;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\BacklinkController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\DomainAuditController;
use App\Http\Controllers\DomainGoogleIntegrationController;
use App\Http\Controllers\GoogleSeoOAuthController;
use App\Http\Controllers\DomainBacklinksController;
use App\Http\Controllers\DomainMetaController;
use App\Http\Controllers\DomainMetaConnectorController;
use App\Http\Controllers\DomainConnectorController;
use App\Http\Controllers\MetaSnippetController;
use App\Http\Controllers\SnippetAgentController;
use App\Http\Controllers\DomainSnippetController;
use App\Http\Controllers\ContentDashboardController;
use App\Http\Controllers\ContentBriefController;
use App\Http\Controllers\KeywordMapController;
use App\Http\Controllers\RankTrackingController;
use App\Http\Controllers\DomainInsightsController;
use App\Http\Controllers\DomainTaskController;
use App\Http\Controllers\DomainPlannerController;
use App\Http\Controllers\DomainSetupWizardController;
use App\Http\Controllers\AutomationCampaignController;
use App\Http\Controllers\AutomationJobController;
use App\Http\Controllers\PlanUsageController;
use App\Http\Controllers\DomainReportsController;
use App\Http\Controllers\PublicReportController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\SiteAccountController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditExportController;
use Inertia\Inertia;



// Authentication Routes
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
// login and logout routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Password Reset Routes
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

// Email Verification Routes (accessible without verified middleware)
use App\Http\Controllers\Auth\EmailVerificationController;
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->middleware(['auth'])
    ->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');
// Authentication Routes end

// Health check endpoint (for monitoring)
use App\Http\Controllers\HealthController;
Route::get('/health', [HealthController::class, 'check'])->name('health');

// Marketing Controllers
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MarketingSitemapController;
use App\Http\Controllers\MarketingRobotsController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\FeaturesController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\BlogController;

// SEO routes (public - needed for search engines)
Route::get('/sitemap.xml', [MarketingSitemapController::class, 'index'])->name('marketing.sitemap');
Route::get('/robots.txt', [MarketingRobotsController::class, 'index'])->name('marketing.robots');

// Marketing routes - Public access (no authentication required)
// These pages are the public-facing marketing website
Route::get('/', [MarketingController::class, 'home'])->name('marketing.home');
Route::get('/seo-audit-report', [MarketingController::class, 'seoAuditReport'])->name('marketing.seo-audit-report');
Route::get('/how-it-works', [MarketingController::class, 'howItWorks'])->name('marketing.how');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('marketing.pricing');
Route::get('/case-studies', [MarketingController::class, 'caseStudiesIndex'])->name('marketing.caseStudies.index');
Route::get('/case-studies/{slug}', [MarketingController::class, 'caseStudiesShow'])->name('marketing.caseStudies.show');
Route::get('/workflows', [MarketingController::class, 'workflowsIndex'])->name('marketing.workflows.index');
Route::get('/workflows/{slug}', [MarketingController::class, 'workflowsShow'])->name('marketing.workflows.show');
Route::get('/solutions', [MarketingController::class, 'solutionsIndex'])->name('marketing.solutions.index');
Route::get('/solutions/{slug}', [MarketingController::class, 'solutionsShow'])->name('marketing.solutions.show');
Route::get('/resources', [MarketingController::class, 'resourcesIndex'])->name('marketing.resources.index');
Route::get('/resources/{type}', [MarketingController::class, 'resourcesType'])->name('marketing.resources.type');
Route::get('/resources/{type}/{slug}', [MarketingController::class, 'resourcesShow'])->name('marketing.resources.show');
Route::get('/glossary', [MarketingController::class, 'glossaryIndex'])->name('marketing.glossary.index');
Route::get('/security', [MarketingController::class, 'security'])->name('marketing.security');
Route::get('/contact', [MarketingController::class, 'contact'])->name('marketing.contact');
Route::post('/contact', [MarketingController::class, 'contactSubmit'])
    ->middleware(['throttle:10,1'])
    ->name('marketing.contact.submit');
Route::get('/partners', [MarketingController::class, 'partners'])->name('marketing.partners');
Route::post('/partners/apply', [MarketingController::class, 'partnerApply'])
    ->middleware(['throttle:10,1'])
    ->name('marketing.partners.apply');
Route::get('/about', [MarketingController::class, 'about'])->name('marketing.about');
Route::get('/privacy-policy', [MarketingController::class, 'privacy'])->name('marketing.privacy');
Route::get('/terms', [MarketingController::class, 'terms'])->name('marketing.terms');
Route::get('/product', [MarketingController::class, 'product'])->name('marketing.product');
Route::get('/features', [MarketingController::class, 'product'])->name('marketing.features');
Route::get('/free-backlink-plan', [MarketingController::class, 'freePlan'])->name('marketing.freePlan');
Route::post('/free-backlink-plan', [MarketingController::class, 'freePlanSubmit'])
    ->middleware(['throttle:15,1'])
    ->name('marketing.freePlan.submit');
Route::get('/plans', [SubscriptionController::class, 'index'])->name('plans');

// Additional Marketing pages
Route::get('/about-page', [AboutController::class, 'index'])->name('about');
Route::get('/features-page', [FeaturesController::class, 'index'])->name('features');
Route::get('/contact-page', [ContactController::class, 'index'])->name('contact');
Route::post('/contact-page', [ContactController::class, 'store'])
    ->middleware(['throttle:10,1'])
    ->name('contact.store');

// Blog Routes - Public access
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// SEO Audit Routes - Public (guest + auth)
Route::get('/audit', [AuditController::class, 'create'])->name('audit.create');
Route::post('/audit', [AuditController::class, 'store'])->name('audit.store');
Route::get('/audit/{audit}', [AuditController::class, 'show'])->name('audit.show');
Route::get('/audit/{audit}/status', [AuditController::class, 'status'])->name('audit.status');
Route::get('/audit/{audit}/pagespeed', [AuditController::class, 'pagespeed'])->name('audit.pagespeed');
Route::post('/audit/{audit}/pagespeed/run', [AuditController::class, 'runPagespeed'])->name('audit.pagespeed.run');
Route::get('/audit/{audit}/crux', [AuditController::class, 'crux'])->name('audit.crux');
Route::post('/audit/{audit}/crux/run', [AuditController::class, 'runCrux'])->name('audit.crux.run');
Route::get('/audit/{audit}/export/pdf', [AuditExportController::class, 'pdf'])->name('audit.export.pdf');
Route::get('/audit/{audit}/export/pdf-v2', [AuditExportController::class, 'pdfV2'])->name('audit.export.pdf.v2');
Route::get('/audit/{audit}/export/pages.csv', [AuditExportController::class, 'pagesCsv'])->name('audit.export.pages.csv');
Route::get('/audit/{audit}/export/issues.csv', [AuditExportController::class, 'issuesCsv'])->name('audit.export.issues.csv');
Route::get('/audit/{audit}/export/links.csv', [AuditExportController::class, 'linksCsv'])->name('audit.export.links.csv');
Route::get('/audit/{audit}/export/broken-links.csv', [AuditExportController::class, 'brokenLinksCsv'])->name('audit.export.broken-links.csv');
Route::get('/audit/{audit}/export/lighthouse.json', [AuditExportController::class, 'lighthouseJson'])->name('audit.export.lighthouse.json');
Route::get('/audit/{audit}/export/assets.csv', [AuditExportController::class, 'assetsCsv'])->name('audit.export.assets.csv');

Route::get('/Backlink/auditreport', function (Illuminate\Http\Request $request) {
    $auditId = $request->query('audit_id');
    $url = $request->query('url');

    if ($auditId) {
        return redirect("/audit/{$auditId}");
    }

    if ($url) {
        return redirect('/audit?url=' . urlencode($url));
    }

    return redirect('/audit');
})->name('backlink.auditreport');

// AI endpoints for public audits (if authenticated)
Route::middleware(['auth'])->group(function() {
    Route::prefix('orgs/{organization}/audits/{audit}')->name('orgs.audits.ai.')->group(function() {
        Route::post('/ai/chat', [\App\Http\Controllers\AiController::class, 'chat'])->name('chat');
        Route::get('/ai/chat/{questionHash}', [\App\Http\Controllers\AiController::class, 'getChatAnswer'])->name('chat.answer');
        Route::post('/ai/snippets', [\App\Http\Controllers\AiController::class, 'generateSnippets'])->name('snippets.generate');
        Route::get('/ai/snippets/{fingerprint}', [\App\Http\Controllers\AiController::class, 'getSnippets'])->name('snippets.get');
        Route::get('/ai/summary', [\App\Http\Controllers\AiController::class, 'getSummary'])->name('summary');
        
        // Competitor benchmarking
        Route::get('/competitors', [\App\Http\Controllers\CompetitorController::class, 'index'])->name('competitors.index');
        Route::post('/competitors', [\App\Http\Controllers\CompetitorController::class, 'store'])->name('competitors.store');
        Route::get('/competitors/{competitorRun}', [\App\Http\Controllers\CompetitorController::class, 'show'])->name('competitors.show');
    });
});

// Affiliate tracking middleware (public)
Route::middleware([\App\Http\Middleware\TrackAffiliateReferral::class])->group(function () {
    Route::get('/', [MarketingController::class, 'home'])->name('marketing.home');
});

// Home redirect (authenticated users go to dashboard)
Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth');

// Stripe webhook (no CSRF protection)
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])->name('stripe.webhook');
//user routes
//dashboard route

Route::middleware(['auth', 'verified'])->group(function(){

    // dashboard stays at /dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gmail OAuth routes
    Route::prefix('gmail')
        ->name('gmail.')
        ->group(function() {
            Route::get('/', [GmailOAuthController::class, 'index'])->name('index');
            Route::prefix('oauth')
                ->group(function() {
                    Route::get('/connect', [GmailOAuthController::class, 'connect'])->name('connect');
                    Route::get('/callback', [GmailOAuthController::class, 'callback'])->name('callback');
                    Route::post('/disconnect/{id}', [GmailOAuthController::class, 'disconnect'])->name('disconnect');
                });
        });

    // Google GA4 OAuth routes
    Route::get('/auth/google/redirect', [GoogleGa4Controller::class, 'redirect'])->name('google.ga4.redirect');
    Route::get('/auth/google/callback', [GoogleGa4Controller::class, 'callback'])->name('google.ga4.callback');
    Route::post('/auth/google/disconnect', [GoogleGa4Controller::class, 'disconnect'])->name('google.ga4.disconnect');
    Route::get('/ga4/properties', [GoogleGa4Controller::class, 'properties'])->name('ga4.properties');
    Route::get('/ga4/summary', [GoogleGa4Controller::class, 'summary'])->name('ga4.summary');

    // Subscription routes
    Route::prefix('subscription')
        ->name('subscription.')
        ->group(function() {
            Route::get('/manage', [SubscriptionController::class, 'manage'])->name('manage');
            Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
            Route::post('/resume', [SubscriptionController::class, 'resume'])->name('resume');
            Route::get('/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('checkout');
            Route::get('/success', [SubscriptionController::class, 'success'])->name('success');
            Route::get('/cancel-page', [SubscriptionController::class, 'cancelPage'])->name('cancel-page');
        });

  Route::prefix('campaign')
     ->name('user-campaign.')
     ->group(function(){
        Route::get('/',     [UserCampaignController::class,'index'])->name('index');
        Route::get('create',[UserCampaignController::class,'create'])->name('create');
        Route::post('/',    [UserCampaignController::class,'store'])->name('store');
        Route::get('{campaign}',       [UserCampaignController::class,'show'])->name('show');
        Route::get('{campaign}/edit',  [UserCampaignController::class,'edit'])->name('edit');
        Route::put('{campaign}',       [UserCampaignController::class,'update'])->name('update');
        Route::delete('{campaign}',    [UserCampaignController::class,'destroy'])->name('destroy');
        Route::post('{campaign}/pause', [UserCampaignController::class, 'pause'])->name('pause');
        Route::post('{campaign}/resume', [UserCampaignController::class, 'resume'])->name('resume');
        Route::get('{campaign}/backlinks', [BacklinkController::class, 'index'])->name('backlinks');
     });
  
  Route::get('/campaigns/export', [UserCampaignController::class, 'export'])->name('campaigns.export');
  Route::get('/reports/export', [ReportsController::class, 'export'])->name('reports.export');

    // Backlinks Management (all user's backlinks)
    Route::prefix('backlinks')
        ->name('backlinks.')
        ->group(function() {
            Route::get('/', [BacklinkController::class, 'all'])->name('index');
            Route::get('/export', [BacklinkController::class, 'export'])->name('export');
            Route::post('/{id}/recheck', [BacklinkController::class, 'recheck'])->name('recheck');
        });

    // Domain Management
    Route::resource('domains', DomainController::class);
    
    // Domain Audits (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.audits.')->group(function() {
        Route::get('/audits', [DomainAuditController::class, 'index'])->name('index');
        Route::post('/audits', [DomainAuditController::class, 'store'])->name('store');
        Route::get('/audits/{audit}', [DomainAuditController::class, 'show'])->name('show');
        Route::get('/audits/{audit}/export', [DomainAuditController::class, 'export'])->name('export');
        
        // AI endpoints
        Route::prefix('audits/{audit}')->name('ai.')->group(function() {
            Route::post('/ai/chat', [\App\Http\Controllers\AiController::class, 'chat'])->name('chat');
            Route::get('/ai/chat/{questionHash}', [\App\Http\Controllers\AiController::class, 'getChatAnswer'])->name('chat.answer');
            Route::post('/ai/snippets', [\App\Http\Controllers\AiController::class, 'generateSnippets'])->name('snippets.generate');
            Route::get('/ai/snippets/{fingerprint}', [\App\Http\Controllers\AiController::class, 'getSnippets'])->name('snippets.get');
            Route::get('/ai/summary', [\App\Http\Controllers\AiController::class, 'getSummary'])->name('summary');
            
            // Competitor benchmarking
            Route::get('/competitors', [\App\Http\Controllers\CompetitorController::class, 'index'])->name('competitors.index');
            Route::post('/competitors', [\App\Http\Controllers\CompetitorController::class, 'store'])->name('competitors.store');
            Route::get('/competitors/{competitorRun}', [\App\Http\Controllers\CompetitorController::class, 'show'])->name('competitors.show');
        });
    });

    // Domain Backlinks (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.backlinks.')->group(function() {
        Route::get('/backlinks', [DomainBacklinksController::class, 'index'])->name('index');
        Route::post('/backlinks', [DomainBacklinksController::class, 'store'])->name('store');
        Route::get('/backlinks/{run}', [DomainBacklinksController::class, 'show'])->name('show');
        Route::get('/backlinks/{run}/export', [DomainBacklinksController::class, 'export'])->name('export');
    });

    // Domain Meta Editor (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.meta.')->group(function() {
        Route::get('/meta', [DomainMetaController::class, 'index'])->name('index');
        Route::post('/meta/pages/import', [DomainMetaController::class, 'importPages'])->name('pages.import');
        Route::post('/meta/pages/refresh', [DomainMetaController::class, 'refreshPages'])->name('pages.refresh');
        Route::post('/meta/pages/{page}/save', [DomainMetaController::class, 'saveDraft'])->name('pages.save');
        Route::post('/meta/pages/{page}/publish', [DomainMetaController::class, 'publish'])->name('pages.publish');
        Route::post('/meta/connect', [DomainMetaConnectorController::class, 'connectOrUpdate'])->name('connect');
        Route::post('/meta/test', [DomainMetaConnectorController::class, 'test'])->name('test');
        Route::post('/meta/disconnect', [DomainMetaConnectorController::class, 'disconnect'])->name('disconnect');
        
        // New connector routes
        Route::get('/meta/connector', [DomainConnectorController::class, 'index'])->name('connector.index');
        Route::post('/meta/connector', [DomainConnectorController::class, 'store'])->name('connector.store');
        Route::post('/meta/connector/test', [DomainConnectorController::class, 'test'])->name('connector.test');
        Route::delete('/meta/connector', [DomainConnectorController::class, 'destroy'])->name('connector.destroy');
    });

    // Domain Content (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.content.')->group(function() {
        Route::get('/content', [ContentDashboardController::class, 'index'])->name('index');
        Route::post('/content/opportunities/refresh', [ContentDashboardController::class, 'refreshOpportunities'])->name('opportunities.refresh');
        Route::post('/content/opportunities/{opportunity}/ignore', [ContentDashboardController::class, 'ignoreOpportunity'])->name('opportunities.ignore');
        
        Route::get('/content/briefs', [ContentBriefController::class, 'index'])->name('briefs.index');
        Route::get('/content/briefs/create', [ContentBriefController::class, 'create'])->name('briefs.create');
        Route::post('/content/briefs', [ContentBriefController::class, 'store'])->name('briefs.store');
        Route::get('/content/briefs/{brief}', [ContentBriefController::class, 'show'])->name('briefs.show');
        Route::post('/content/briefs/{brief}', [ContentBriefController::class, 'updateStatus'])->name('briefs.updateStatus');
        Route::get('/content/briefs/{brief}/export', [ContentBriefController::class, 'exportMarkdown'])->name('briefs.export');
        Route::post('/content/briefs/{brief}/send-to-meta', [ContentBriefController::class, 'sendToMetaEditor'])->name('briefs.sendToMeta');
        
        Route::get('/content/keyword-map', [KeywordMapController::class, 'index'])->name('keywordMap.index');
        Route::post('/content/keyword-map', [KeywordMapController::class, 'store'])->name('keywordMap.store');
        Route::post('/content/keyword-map/{keywordMap}/delete', [KeywordMapController::class, 'destroy'])->name('keywordMap.destroy');
    });

    // Domain Rank Tracking (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.rank.')->group(function() {
        Route::get('/rank-tracking', [RankTrackingController::class, 'index'])->name('index');
        Route::post('/rank-tracking/keywords', [RankTrackingController::class, 'storeKeyword'])->name('keywords.store');
        Route::post('/rank-tracking/keywords/sync', [RankTrackingController::class, 'syncFromSources'])->name('keywords.sync');
        Route::post('/rank-tracking/keywords/{keyword}/toggle', [RankTrackingController::class, 'toggleKeyword'])->name('keywords.toggle');
        Route::post('/rank-tracking/run', [RankTrackingController::class, 'runNow'])->name('run');
        Route::get('/rank-tracking/checks/{check}', [RankTrackingController::class, 'showCheck'])->name('checks.show');
    });

    // Public Meta Snippet endpoints (no auth)
    Route::get('/snippet/{snippetKey}.js', [MetaSnippetController::class, 'script'])->name('meta.snippet.script');
    Route::get('/snippet/{snippetKey}/meta', [MetaSnippetController::class, 'metaJson'])->name('meta.snippet.meta');
    
    // Public Snippet Agent endpoints (no auth, rate limited)
    Route::post('/snippet/{key}/ping', [SnippetAgentController::class, 'ping'])->name('snippet.ping');
    Route::post('/snippet/{key}/event', [SnippetAgentController::class, 'event'])->name('snippet.event');
    Route::post('/snippet/{key}/perf', [SnippetAgentController::class, 'perf'])->name('snippet.perf');
    Route::get('/snippet/{key}/commands', [SnippetAgentController::class, 'commands'])->name('snippet.commands');
    Route::post('/snippet/{key}/commands/{command}/ack', [SnippetAgentController::class, 'ackCommand'])->name('snippet.commands.ack');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/{notification}/archive', [NotificationController::class, 'archive'])->name('notifications.archive');
    Route::post('/notifications/{notification}/snooze', [NotificationController::class, 'snooze'])->name('notifications.snooze');
    Route::post('/notifications/mute', [NotificationController::class, 'mute'])->name('notifications.mute');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');

    // Domain Insights (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.insights.')->group(function() {
        Route::get('/insights', [DomainInsightsController::class, 'index'])->name('index');
        Route::post('/insights/run', [DomainInsightsController::class, 'runNow'])->name('run');
        Route::post('/insights/create-campaign-from-lost-links', [DomainInsightsController::class, 'createCampaignFromLostLinks'])->name('createCampaignFromLostLinks');
        Route::post('/alerts/{alert}/read', [DomainInsightsController::class, 'markAlertRead'])->name('alerts.read');
        Route::post('/tasks/{task}/status', [DomainTaskController::class, 'updateStatus'])->name('tasks.status');
        Route::post('/tasks/create', [DomainTaskController::class, 'createManual'])->name('tasks.create');
    });

    // Domain Planner (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.planner.')->group(function() {
        Route::get('/planner', [DomainPlannerController::class, 'index'])->name('index');
        Route::post('/planner/generate', [DomainPlannerController::class, 'generate'])->name('generate');
        Route::post('/planner/apply', [DomainPlannerController::class, 'apply'])->name('apply');
        Route::post('/planner/{plan}/archive', [DomainPlannerController::class, 'archive'])->name('archive');
    });

    // Domain Setup Wizard (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.setup.')->group(function() {
        Route::get('/setup', [DomainSetupWizardController::class, 'show'])->name('show');
        Route::post('/setup/audit/start', [DomainSetupWizardController::class, 'startAudit'])->name('audit.start');
        Route::get('/setup/google/connect', [DomainSetupWizardController::class, 'connectGoogle'])->name('google.connect');
        Route::post('/setup/google/select', [DomainSetupWizardController::class, 'saveGoogleSelection'])->name('google.select');
        Route::post('/setup/backlinks/start', [DomainSetupWizardController::class, 'startBacklinks'])->name('backlinks.start');
        Route::post('/setup/meta/connect', [DomainSetupWizardController::class, 'saveMetaConnector'])->name('meta.connect');
        Route::post('/setup/insights/run', [DomainSetupWizardController::class, 'runInsights'])->name('insights.run');
        Route::post('/setup/report/create', [DomainSetupWizardController::class, 'createReport'])->name('report.create');
        Route::post('/setup/complete', [DomainSetupWizardController::class, 'complete'])->name('complete');
    });

    // Domain Automation (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.automation.')->group(function() {
        Route::get('/automation', [AutomationCampaignController::class, 'index'])->name('index');
        Route::get('/automation/create', [AutomationCampaignController::class, 'create'])->name('create');
        Route::post('/automation', [AutomationCampaignController::class, 'store'])->name('store');
        Route::get('/automation/{campaign}', [AutomationCampaignController::class, 'show'])->name('show');
        Route::post('/automation/{campaign}/start', [AutomationCampaignController::class, 'start'])->name('start');
        Route::post('/automation/{campaign}/pause', [AutomationCampaignController::class, 'pause'])->name('pause');
        Route::post('/automation/{campaign}/resume', [AutomationCampaignController::class, 'resume'])->name('resume');
        Route::post('/automation/{campaign}/stop', [AutomationCampaignController::class, 'stop'])->name('stop');
        Route::post('/automation/{campaign}/targets/import-csv', [AutomationCampaignController::class, 'importCsv'])->name('targets.importCsv');
        Route::post('/automation/{campaign}/targets/import-backlinks-run', [AutomationCampaignController::class, 'importBacklinksRun'])->name('targets.importBacklinksRun');
        Route::post('/automation/{campaign}/targets/add-manual', [AutomationCampaignController::class, 'addManual'])->name('targets.addManual');
        Route::post('/automation/jobs/{job}/retry', [AutomationCampaignController::class, 'retryJob'])->name('jobs.retry');
        Route::post('/automation/jobs/{job}/skip', [AutomationCampaignController::class, 'skipJob'])->name('jobs.skip');
    });

    // Worker API (for Python worker to update job results)
    Route::prefix('domains/{domain}')->name('domains.automation.worker.')->group(function() {
        Route::post('/automation/jobs/{job}/result', [AutomationJobController::class, 'updateResult'])->name('jobs.result');
    });

    // Domain Reports (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.reports.')->group(function() {
        Route::get('/reports', [DomainReportsController::class, 'index'])->name('index');
        Route::post('/reports', [DomainReportsController::class, 'store'])->name('store');
        Route::post('/reports/{report}/revoke', [DomainReportsController::class, 'revoke'])->name('revoke');
        Route::post('/reports/{report}/refresh', [DomainReportsController::class, 'refresh'])->name('refresh');
    });

    // Domain Google Integrations (nested under domains)
    Route::prefix('domains/{domain}')->name('domains.integrations.')->group(function() {
        Route::get('/integrations/google', [DomainGoogleIntegrationController::class, 'index'])->name('google');
        Route::post('/integrations/google/save', [DomainGoogleIntegrationController::class, 'saveSelection'])->name('google.save');
        Route::post('/integrations/google/sync-now', [DomainGoogleIntegrationController::class, 'syncNow'])->name('google.sync-now');
        Route::post('/integrations/google/disconnect', [DomainGoogleIntegrationController::class, 'disconnect'])->name('google.disconnect');
        Route::get('/integrations/google/connect', [GoogleSeoOAuthController::class, 'connect'])->name('google.connect');
    });

    // SEO OAuth callback (separate route)
    Route::get('/seo/google/callback', [GoogleSeoOAuthController::class, 'callback'])->name('seo.google.callback');

    // Site Account Management
    Route::resource('site-accounts', SiteAccountController::class);

    // Settings
    Route::prefix('settings')
        ->name('settings.')
        ->group(function() {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
            Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
            Route::get('/plan-usage', [PlanUsageController::class, 'index'])->name('plan-usage');
            
            // Notification settings
            Route::get('/notifications', [NotificationSettingsController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/rules', [NotificationSettingsController::class, 'store'])->name('notifications.store');
            
            // Webhook settings
            Route::get('/notifications/webhooks', [WebhookController::class, 'index'])->name('notifications.webhooks.index');
            Route::post('/notifications/webhooks', [WebhookController::class, 'store'])->name('notifications.webhooks.store');
            Route::post('/notifications/webhooks/{endpoint}/toggle', [WebhookController::class, 'toggle'])->name('notifications.webhooks.toggle');
            Route::delete('/notifications/webhooks/{endpoint}', [WebhookController::class, 'destroy'])->name('notifications.webhooks.destroy');
        });

    // Activity Feed
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

    // Reports/Analytics
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

    // Help & Documentation
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    // SEO Audit Share (auth only)
    Route::post('/audit/{audit}/share', [AuditController::class, 'share'])->name('audit.share');

    // Team Management
    Route::get('/team', [TeamController::class, 'show'])->name('team.show');
    Route::post('/team', [TeamController::class, 'update'])->name('team.update');
    Route::post('/team/invite', [TeamInvitationController::class, 'store'])->name('team.invite');
    Route::post('/team/invitations/{id}/revoke', [TeamInvitationController::class, 'revoke'])->name('team.invitations.revoke');
    Route::post('/team/members/{member}/role', [TeamMemberController::class, 'updateRole'])->name('team.members.role');
    Route::post('/team/members/{member}/remove', [TeamMemberController::class, 'remove'])->name('team.members.remove');

    // Organization Management
    Route::prefix('orgs')->name('orgs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\OrganizationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\OrganizationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\OrganizationController::class, 'store'])->name('store');
        
        Route::prefix('{organization}')->group(function () {
            // Settings
            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/branding', [\App\Http\Controllers\OrgSettingsController::class, 'branding'])->name('branding');
                Route::post('/branding', [\App\Http\Controllers\OrgSettingsController::class, 'updateBranding'])->name('branding.update');
                Route::get('/domains', [\App\Http\Controllers\OrgSettingsController::class, 'domains'])->name('domains');
                Route::post('/domains', [\App\Http\Controllers\OrgSettingsController::class, 'addDomain'])->name('domains.add');
                Route::delete('/domains/{domain}', [\App\Http\Controllers\OrgSettingsController::class, 'removeDomain'])->name('domains.remove');
                Route::get('/pagespeed', [\App\Http\Controllers\OrgSettingsController::class, 'pagespeed'])->name('pagespeed');
                Route::post('/pagespeed', [\App\Http\Controllers\OrgSettingsController::class, 'updatePagespeed'])->name('pagespeed.update');
                Route::post('/pagespeed/verify', [\App\Http\Controllers\OrgSettingsController::class, 'verifyPagespeed'])->name('pagespeed.verify');
            });
            
            // API Keys
            Route::get('/api-keys', [\App\Http\Controllers\ApiKeyController::class, 'index'])->name('api-keys.index');
            Route::post('/api-keys', [\App\Http\Controllers\ApiKeyController::class, 'store'])->name('api-keys.store');
            Route::delete('/api-keys/{apiKey}', [\App\Http\Controllers\ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
            Route::get('/api-keys/{apiKey}/widget-snippet', [\App\Http\Controllers\ApiKeyController::class, 'widgetSnippet'])->name('api-keys.widget-snippet');
            
            // Leads
            Route::get('/leads', [\App\Http\Controllers\LeadsController::class, 'index'])->name('leads.index');
            Route::get('/leads/{lead}', [\App\Http\Controllers\LeadsController::class, 'show'])->name('leads.show');
            Route::put('/leads/{lead}', [\App\Http\Controllers\LeadsController::class, 'update'])->name('leads.update');
            
            // Audits
            Route::get('/audits', [\App\Http\Controllers\AuditController::class, 'index'])->name('audits.index');
            
            // Billing
            Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
            Route::get('/billing/plans', [\App\Http\Controllers\BillingController::class, 'plans'])->name('billing.plans');
            Route::post('/billing/checkout', [\App\Http\Controllers\BillingController::class, 'checkout'])->name('billing.checkout');
            Route::get('/billing/success', [\App\Http\Controllers\BillingController::class, 'success'])->name('billing.success');
            Route::get('/billing/portal', [\App\Http\Controllers\BillingController::class, 'portal'])->name('billing.portal');
            
            // Usage
            Route::get('/usage', [\App\Http\Controllers\UsageController::class, 'index'])->name('usage.index');
            
            // Team Invitations
            Route::post('/invites', [\App\Http\Controllers\InvitationController::class, 'store'])->name('invites.store');
            Route::delete('/invites/{invitation}', [\App\Http\Controllers\InvitationController::class, 'revoke'])->name('invites.revoke');
            
            // Service Requests
            Route::get('/service-requests', [\App\Http\Controllers\ServiceRequestController::class, 'index'])->name('service-requests.index');
            Route::post('/service-requests', [\App\Http\Controllers\ServiceRequestController::class, 'store'])->name('service-requests.store');
            Route::get('/service-requests/{serviceRequest}', [\App\Http\Controllers\ServiceRequestController::class, 'show'])->name('service-requests.show');
            Route::post('/service-requests/{serviceRequest}/quote', [\App\Http\Controllers\ServiceRequestController::class, 'quote'])->name('service-requests.quote');
            Route::post('/service-requests/{serviceRequest}/checkout', [\App\Http\Controllers\ServiceRequestController::class, 'checkout'])->name('service-requests.checkout');
            Route::post('/service-requests/{serviceRequest}/messages', [\App\Http\Controllers\ServiceRequestController::class, 'addMessage'])->name('service-requests.messages');
            
            // Analytics
            Route::get('/insights', [\App\Http\Controllers\AnalyticsController::class, 'insights'])->name('insights');
            
            // Google Integration
            Route::get('/integrations/google', [\App\Http\Controllers\GoogleIntegrationController::class, 'index'])->name('integrations.google');
            Route::get('/integrations/google/connect', [\App\Http\Controllers\GoogleIntegrationController::class, 'connect'])->name('integrations.google.connect');
            Route::get('/integrations/google/callback', [\App\Http\Controllers\GoogleIntegrationController::class, 'callback'])->name('integrations.google.callback');
            Route::post('/integrations/google/disconnect', [\App\Http\Controllers\GoogleIntegrationController::class, 'disconnect'])->name('integrations.google.disconnect');
            Route::post('/integrations/google/gsc-site', [\App\Http\Controllers\GoogleIntegrationController::class, 'selectGscSite'])->name('integrations.google.gsc-site');
            Route::post('/integrations/google/ga4-property', [\App\Http\Controllers\GoogleIntegrationController::class, 'selectGa4Property'])->name('integrations.google.ga4-property');
            
            // SEO Dashboard
            Route::get('/seo/dashboard', [\App\Http\Controllers\SeoController::class, 'dashboard'])->name('seo.dashboard');
            Route::get('/seo/gsc-queries', [\App\Http\Controllers\SeoController::class, 'gscQueries'])->name('seo.gsc-queries');
            Route::get('/seo/ga4-pages', [\App\Http\Controllers\SeoController::class, 'ga4Pages'])->name('seo.ga4-pages');
            Route::get('/seo/reports', [\App\Http\Controllers\SeoController::class, 'reports'])->name('seo.reports');
            Route::get('/seo/reports/{report}/download', [\App\Http\Controllers\SeoController::class, 'downloadReport'])->name('seo.reports.download');
            
            // Rank Tracking
            Route::get('/seo/rankings', [\App\Http\Controllers\RankTrackingController::class, 'index'])->name('seo.rankings.index');
            Route::post('/seo/rank-projects', [\App\Http\Controllers\RankTrackingController::class, 'createProject'])->name('seo.rank-projects.create');
            Route::get('/seo/rank-projects/{project}', [\App\Http\Controllers\RankTrackingController::class, 'showProject'])->name('seo.rank-projects.show');
            Route::post('/seo/rank-projects/{project}/keywords', [\App\Http\Controllers\RankTrackingController::class, 'addKeywords'])->name('seo.rank-projects.keywords');
            Route::post('/seo/rank-projects/{project}/run-checks', [\App\Http\Controllers\RankTrackingController::class, 'runChecks'])->name('seo.rank-projects.run-checks');
            
            // SEO Alerts
            Route::get('/seo/alerts', [\App\Http\Controllers\SeoAlertController::class, 'index'])->name('seo.alerts.index');
            Route::post('/seo/alerts/rules', [\App\Http\Controllers\SeoAlertController::class, 'storeRule'])->name('seo.alerts.rules.store');
            Route::post('/seo/alerts/rules/{rule}/toggle', [\App\Http\Controllers\SeoAlertController::class, 'toggleRule'])->name('seo.alerts.rules.toggle');
            
            // Fix Automation
            Route::get('/audits/{audit}/fix-automation', [\App\Http\Controllers\FixAutomationController::class, 'index'])->name('audits.fix-automation');
            Route::post('/audits/{audit}/fix-automation/candidates', [\App\Http\Controllers\FixAutomationController::class, 'generateCandidates'])->name('audits.fix-automation.candidates');
            Route::post('/audits/{audit}/fix-automation/candidates/{candidate}/patch', [\App\Http\Controllers\FixAutomationController::class, 'generatePatch'])->name('audits.fix-automation.patch');
            Route::get('/audits/{audit}/fix-automation/patches/{patch}/download', [\App\Http\Controllers\FixAutomationController::class, 'downloadPatch'])->name('audits.fix-automation.download');
            Route::post('/audits/{audit}/fix-automation/patches/{patch}/pr', [\App\Http\Controllers\FixAutomationController::class, 'openPR'])->name('audits.fix-automation.pr');
            Route::post('/repos/connect/github', [\App\Http\Controllers\FixAutomationController::class, 'connectGithub'])->name('repos.connect.github');
            Route::get('/repos/list', [\App\Http\Controllers\FixAutomationController::class, 'listRepos'])->name('repos.list');
            Route::post('/repos/select', [\App\Http\Controllers\FixAutomationController::class, 'selectRepo'])->name('repos.select');
            
            // Backlink Campaigns
            Route::get('/backlinks/campaigns', [\App\Http\Controllers\BacklinkCampaignController::class, 'index'])->name('backlinks.campaigns.index');
            Route::post('/audits/{audit}/backlinks/campaigns', [\App\Http\Controllers\BacklinkCampaignController::class, 'create'])->name('backlinks.campaigns.create');
            Route::get('/backlinks/campaigns/{campaign}', [\App\Http\Controllers\BacklinkCampaignController::class, 'show'])->name('backlinks.campaigns.show');
            
            // Monitoring
            Route::get('/monitoring', [\App\Http\Controllers\MonitoringController::class, 'index'])->name('monitoring.index');
            Route::post('/monitoring', [\App\Http\Controllers\MonitoringController::class, 'store'])->name('monitoring.store');
            Route::get('/monitoring/{monitor}', [\App\Http\Controllers\MonitoringController::class, 'show'])->name('monitoring.show');
            Route::post('/monitoring/{monitor}/run', [\App\Http\Controllers\MonitoringController::class, 'run'])->name('monitoring.run');
            Route::post('/monitoring/{monitor}/toggle', [\App\Http\Controllers\MonitoringController::class, 'toggle'])->name('monitoring.toggle');
            
            // Google Integration
            Route::get('/integrations/google', [\App\Http\Controllers\GoogleIntegrationController::class, 'index'])->name('integrations.google');
            Route::get('/integrations/google/connect', [\App\Http\Controllers\GoogleIntegrationController::class, 'connect'])->name('integrations.google.connect');
            Route::get('/integrations/google/callback', [\App\Http\Controllers\GoogleIntegrationController::class, 'callback'])->name('integrations.google.callback');
            Route::post('/integrations/google/disconnect', [\App\Http\Controllers\GoogleIntegrationController::class, 'disconnect'])->name('integrations.google.disconnect');
            Route::post('/integrations/google/gsc-site', [\App\Http\Controllers\GoogleIntegrationController::class, 'selectGscSite'])->name('integrations.google.gsc-site');
            Route::post('/integrations/google/ga4-property', [\App\Http\Controllers\GoogleIntegrationController::class, 'selectGa4Property'])->name('integrations.google.ga4-property');
            
            // SEO Dashboard
            Route::get('/seo/dashboard', [\App\Http\Controllers\SeoController::class, 'dashboard'])->name('seo.dashboard');
            Route::get('/seo/gsc-queries', [\App\Http\Controllers\SeoController::class, 'gscQueries'])->name('seo.gsc-queries');
            Route::get('/seo/ga4-pages', [\App\Http\Controllers\SeoController::class, 'ga4Pages'])->name('seo.ga4-pages');
            Route::get('/seo/reports', [\App\Http\Controllers\SeoController::class, 'reports'])->name('seo.reports');
            Route::get('/seo/reports/{report}/download', [\App\Http\Controllers\SeoController::class, 'downloadReport'])->name('seo.reports.download');
            
            // Rank Tracking
            Route::get('/seo/rankings', [\App\Http\Controllers\RankTrackingController::class, 'index'])->name('seo.rankings.index');
            Route::post('/seo/rank-projects', [\App\Http\Controllers\RankTrackingController::class, 'createProject'])->name('seo.rank-projects.create');
            Route::get('/seo/rank-projects/{project}', [\App\Http\Controllers\RankTrackingController::class, 'showProject'])->name('seo.rank-projects.show');
            Route::post('/seo/rank-projects/{project}/keywords', [\App\Http\Controllers\RankTrackingController::class, 'addKeywords'])->name('seo.rank-projects.keywords');
            Route::post('/seo/rank-projects/{project}/run-checks', [\App\Http\Controllers\RankTrackingController::class, 'runChecks'])->name('seo.rank-projects.run-checks');
            
            // SEO Alerts
            Route::get('/seo/alerts', [\App\Http\Controllers\SeoAlertController::class, 'index'])->name('seo.alerts.index');
            Route::post('/seo/alerts/rules', [\App\Http\Controllers\SeoAlertController::class, 'storeRule'])->name('seo.alerts.rules.store');
            Route::post('/seo/alerts/rules/{rule}/toggle', [\App\Http\Controllers\SeoAlertController::class, 'toggleRule'])->name('seo.alerts.rules.toggle');
        });
    });
    
    // Affiliate Routes
    Route::prefix('affiliate')->name('affiliate.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AffiliateController::class, 'index'])->name('index');
        Route::get('/links', [\App\Http\Controllers\AffiliateController::class, 'links'])->name('links');
        Route::get('/commissions', [\App\Http\Controllers\AffiliateController::class, 'commissions'])->name('commissions');
        Route::get('/payouts', [\App\Http\Controllers\AffiliateController::class, 'payouts'])->name('payouts');
        Route::post('/payouts/method', [\App\Http\Controllers\AffiliateController::class, 'updatePayoutMethod'])->name('payouts.method');
        Route::get('/apply', [\App\Http\Controllers\AffiliateController::class, 'apply'])->name('apply');
        Route::post('/apply', [\App\Http\Controllers\AffiliateController::class, 'store'])->name('store');
    });
    
    // Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/retention', [\App\Http\Controllers\AnalyticsController::class, 'retention'])->name('retention');
    });
});


//user routes end

// Public Report Routes (no auth required)
Route::get('/r/{token}', [PublicReportController::class, 'show'])->name('public.report.show');
Route::post('/r/{token}/unlock', [PublicReportController::class, 'unlock'])->name('public.report.unlock');

// Widget JS endpoint
Route::get('/widget.js', [\App\Http\Controllers\WidgetController::class, 'js'])->name('widget.js');

// Stripe webhook (no CSRF protection)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->middleware('throttle:60,1')
    ->name('stripe.webhook');

// Team Invitation Acceptance (public route, auth optional)
Route::get('/team/invitations/{token}', [TeamInvitationController::class, 'accept'])->name('team.invitations.accept');
Route::post('/team/invitations/{token}/accept', [TeamInvitationController::class, 'accept'])->name('team.invitations.accept.post');

// Organization Invitations (public)
Route::get('/invites/{token}', [\App\Http\Controllers\InvitationController::class, 'show'])->name('invitations.accept');
Route::post('/invites/{token}/accept', [\App\Http\Controllers\InvitationController::class, 'accept'])->name('invitations.accept.post');

// Simple public test comment page for automation validation
Route::get('/test-comment', function () {
    return view('test-comment');
});

// Admin Routes are loaded from routes/admin.php via bootstrap/app.php
