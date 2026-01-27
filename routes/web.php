<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserCampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GmailOAuthController;
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
use App\Http\Controllers\ProfileController;



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

// Marketing routes - Permission controlled access
// Users need 'marketing.view' permission (admins and users with this permission can access)
Route::middleware(['auth', 'permission:marketing.view'])->group(function () {
    Route::get('/', [MarketingController::class, 'home'])->name('marketing.home');
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
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/plans', [SubscriptionController::class, 'index'])->name('plans');

    // Additional Marketing pages
    Route::get('/about-page', [AboutController::class, 'index'])->name('about');
    Route::get('/features-page', [FeaturesController::class, 'index'])->name('features');
    Route::get('/contact-page', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact-page', [ContactController::class, 'store'])->name('contact.store');

    // Blog Routes - requires marketing.blog permission
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
});

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

    // Team Management
    Route::get('/team', [TeamController::class, 'show'])->name('team.show');
    Route::post('/team', [TeamController::class, 'update'])->name('team.update');
    Route::post('/team/invite', [TeamInvitationController::class, 'store'])->name('team.invite');
    Route::post('/team/invitations/{id}/revoke', [TeamInvitationController::class, 'revoke'])->name('team.invitations.revoke');
    Route::post('/team/members/{member}/role', [TeamMemberController::class, 'updateRole'])->name('team.members.role');
    Route::post('/team/members/{member}/remove', [TeamMemberController::class, 'remove'])->name('team.members.remove');
});


//user routes end

// Public Report Routes (no auth required)
Route::get('/r/{token}', [PublicReportController::class, 'show'])->name('public.report.show');
Route::post('/r/{token}/unlock', [PublicReportController::class, 'unlock'])->name('public.report.unlock');

// Team Invitation Acceptance (public route, auth optional)
Route::get('/team/invitations/{token}', [TeamInvitationController::class, 'accept'])->name('team.invitations.accept');
Route::post('/team/invitations/{token}/accept', [TeamInvitationController::class, 'accept'])->name('team.invitations.accept.post');

// Simple public test comment page for automation validation
Route::get('/test-comment', function () {
    return view('test-comment');
});

// Admin Routes are loaded from routes/admin.php via bootstrap/app.php