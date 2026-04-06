<template>
    <div class="stitch-home">
        <MarketingHead :meta="meta" />

        <header :class="['stitch-nav', { 'stitch-nav-dark': navOnDark } ]">
            <div class="stitch-shell">
                <div class="stitch-brand-wrap">
                    <a class="stitch-brand" href="/">
                        <span class="stitch-brand-mark">
                            <span class="stitch-brand-mark-core"></span>
                            <span class="stitch-brand-mark-ring"></span>
                        </span>
                        <span>BacklinkPro</span>
                    </a>

                    <nav class="stitch-links" aria-label="Primary">
                        <div class="stitch-nav-dropdown" v-for="group in navGroups" :key="group.label">
                            <button class="stitch-nav-trigger" type="button">
                                <span>{{ group.label }}</span>
                                <span class="stitch-nav-caret" aria-hidden="true"></span>
                            </button>

                            <div class="stitch-nav-menu">
                                <a
                                    v-for="item in group.items"
                                    :key="item.href"
                                    class="stitch-nav-menu-item"
                                    :href="item.href"
                                >
                                    <span class="stitch-nav-menu-label">{{ item.label }}</span>
                                    <span class="stitch-nav-menu-copy">{{ item.copy }}</span>
                                </a>
                            </div>
                        </div>
                    </nav>
                </div>

                <div class="stitch-actions">
                    <a class="stitch-text-link" href="/login">Sign In</a>
                    <a class="stitch-button stitch-button-light" href="/contact">Contact Sales</a>
                </div>
            </div>

            <div class="stitch-mobile-nav">
                <div class="stitch-shell">
                    <div class="stitch-mobile-groups">
                        <details class="stitch-mobile-dropdown" v-for="group in navGroups" :key="`${group.label}-mobile`">
                            <summary>
                                <span>{{ group.label }}</span>
                                <span class="stitch-nav-caret" aria-hidden="true"></span>
                            </summary>

                            <div class="stitch-mobile-menu">
                                <a
                                    v-for="item in group.items"
                                    :key="item.href"
                                    :href="item.href"
                                    class="stitch-mobile-menu-item"
                                >
                                    <span class="stitch-nav-menu-label">{{ item.label }}</span>
                                    <span class="stitch-nav-menu-copy">{{ item.copy }}</span>
                                </a>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section class="stitch-hero">
                <div class="stitch-hero-backdrop"></div>
                <div class="stitch-hero-product">
                    <div class="stitch-hero-window stitch-hero-window-main">
                        <div class="stitch-window-topbar">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="stitch-window-body">
                            <div class="stitch-window-sidebar">
                                <span class="is-active"></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>

                            <div class="stitch-window-content">
                                <div class="stitch-window-header">
                                    <div>
                                        <p class="stitch-window-kicker">Campaign control</p>
                                        <h3>Authority Growth Console</h3>
                                    </div>
                                    <div class="stitch-window-chip">Live sync</div>
                                </div>

                                <div class="stitch-window-metrics">
                                    <article v-for="metric in heroMetrics" :key="metric.label">
                                        <span>{{ metric.label }}</span>
                                        <strong>{{ metric.value }}</strong>
                                    </article>
                                </div>

                                <div class="stitch-window-chart">
                                    <div class="stitch-window-chart-line"></div>
                                </div>

                                <div class="stitch-window-table">
                                    <div v-for="row in heroRows" :key="row.site" class="stitch-window-row">
                                        <span>{{ row.site }}</span>
                                        <span>{{ row.status }}</span>
                                        <span>{{ row.metric }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="stitch-shell stitch-hero-inner">
                    <div class="stitch-status-pill">
                        <span class="stitch-status-dot"></span>
                        <span>Active Operations: 12,482</span>
                    </div>

                    <h1>
                        Build Quality Backlinks
                        <span>Without the Manual Grind.</span>
                    </h1>

                    <p class="stitch-hero-copy">
                        The autonomous link building engine for digital curators. Scale your SEO performance with safe,
                        trackable, and high-authority operations.
                    </p>

                    <div class="stitch-hero-actions">
                        <a class="stitch-button stitch-button-light stitch-button-lg" href="/contact">Contact Sales</a>
                    </div>

                    <div class="stitch-logo-row">
                        <span v-for="logo in logoNames" :key="logo">{{ logo }}</span>
                    </div>
                </div>
            </section>

            <section class="stitch-stats">
                <div class="stitch-shell stitch-stats-grid">
                    <div class="stitch-intro-copy">
                        <p>Scale your domain authority with precision in under two weeks.</p>
                    </div>

                    <div class="stitch-stat-card" v-for="item in statHighlights" :key="item.label">
                        <div class="stitch-stat-label">{{ item.label }}</div>
                        <div class="stitch-stat-value">{{ item.value }}</div>
                    </div>
                </div>
            </section>

            <section class="stitch-section stitch-section-audit">
                <div class="stitch-shell">
                    <div class="stitch-feature-band">
                        <div class="stitch-feature-copy">
                            <div class="stitch-section-label">Free SEO Audit Report</div>
                            <h2 class="stitch-section-heading">Turn one URL into a technical action plan.</h2>
                            <p>
                                Run a guided audit before you launch backlinks. Surface indexability issues,
                                on-page blockers, and quick wins so your campaigns start from a stronger base.
                            </p>

                            <div class="stitch-feature-points">
                                <article v-for="item in auditHighlights" :key="item.title">
                                    <h3>{{ item.title }}</h3>
                                    <p>{{ item.copy }}</p>
                                </article>
                            </div>

                            <div class="stitch-feature-actions">
                                <a class="stitch-button stitch-button-light" href="/seo-audit-report">Run Free SEO Audit</a>
                                <a class="stitch-button stitch-button-ghost" href="/product">See platform overview</a>
                            </div>
                        </div>

                        <div class="stitch-feature-visual">
                            <div class="stitch-audit-card">
                                <div class="stitch-audit-card-head">
                                    <span class="stitch-mini-label">Included in the report</span>
                                    <strong>SEO Audit Snapshot</strong>
                                </div>

                                <div class="stitch-audit-list">
                                    <div v-for="item in auditChecks" :key="item.title" class="stitch-audit-list-item">
                                        <span class="stitch-audit-dot"></span>
                                        <div>
                                            <h4>{{ item.title }}</h4>
                                            <p>{{ item.copy }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="stitch-audit-stats">
                                    <article v-for="item in auditStats" :key="item.label">
                                        <strong>{{ item.value }}</strong>
                                        <span>{{ item.label }}</span>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="product" class="stitch-section stitch-section-spacious">
                <div class="stitch-shell">
                    <div class="stitch-two-col">
                        <div>
                            <div class="stitch-section-label">Built for complexity</div>
                            <h2 class="stitch-display">
                                Link building is either slow, expensive, or dangerous.
                            </h2>
                        </div>

                        <div class="stitch-problem-list">
                            <article class="stitch-problem-item" v-for="item in problems" :key="item.title">
                                <div class="stitch-icon-chip">{{ item.icon }}</div>
                                <div>
                                    <h3>{{ item.title }}</h3>
                                    <p>{{ item.copy }}</p>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="stitch-demo-card">
                        <div class="stitch-demo-copy">
                            <div class="stitch-panel-icon">[]</div>
                            <h3>Control Console</h3>
                            <p>
                                The fastest way to build, govern, and scale your domain's backlink ecosystem.
                            </p>

                            <div class="stitch-demo-links">
                                <div v-for="item in consoleLinks" :key="item">{{ item }}</div>
                            </div>
                        </div>

                        <div class="stitch-demo-visual">
                            <img
                                alt="BacklinkPro control console dashboard preview"
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuCetG0pF3I6FuuqVs7Pe7Y7fTEFlb4J8xLeftvCtA1iyffPrfZ_gd4jErMIDszlYvH1Sy2CbIl5HtGGFhUYBhLE0kPsAuHtOGcpkiUq0-VdvOahWIibbm7gIWbAZm-IR4GFIixTo2bUXPEhqHr_MM406ZNAWKtNrSxhROPLo3IOkclA_Rx6CTes5L3GcQiYuqOmZNvm9bNrjv46eESke4-0_KbPztrGNVvqh6GSK-fc8obJDCVLmj0yK9yM9P_ON38pE4e8cb2l-l-9"
                            />

                            <div class="stitch-modal-mock">
                                <div class="stitch-modal-surface">
                                    <div class="stitch-mini-label">Create New Campaign</div>
                                    <div class="stitch-input-line"></div>
                                    <div class="stitch-input-block"></div>
                                    <div class="stitch-modal-actions">
                                        <span>Cancel</span>
                                        <span class="stitch-launch-chip">Launch Sprint</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="stitch-section stitch-section-campaign">
                <div class="stitch-shell">
                    <div class="stitch-campaign-shell">
                        <div class="stitch-campaign-head">
                            <div>
                                <div class="stitch-section-label">Create Campaign</div>
                                <h2 class="stitch-section-heading">Spin up backlink operations without spreadsheet chaos.</h2>
                            </div>
                            <p>
                                Build a campaign around one target page, define your guardrails, and let BacklinkPro
                                handle discovery, approvals, and reporting from one controlled workspace.
                            </p>
                        </div>

                        <div class="stitch-campaign-grid">
                            <article
                                v-for="item in campaignFlow"
                                :key="item.step"
                                class="stitch-campaign-card"
                            >
                                <span class="stitch-campaign-step">{{ item.step }}</span>
                                <h3>{{ item.title }}</h3>
                                <p>{{ item.copy }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <section id="how-it-works" class="stitch-section">
                <div class="stitch-shell">
                    <div class="stitch-two-col stitch-two-col-tight">
                        <div>
                            <div class="stitch-section-label">Smart Suggestions</div>
                            <h2 class="stitch-display">Improve as you go</h2>
                        </div>

                        <div class="stitch-mini-grid">
                            <article v-for="item in smartSignals" :key="item.title">
                                <div class="stitch-mini-icon">{{ item.icon }}</div>
                                <h3>{{ item.title }}</h3>
                                <p>{{ item.copy }}</p>
                            </article>
                        </div>
                    </div>

                    <div class="stitch-step-grid">
                        <article
                            v-for="step in steps"
                            :key="step.number"
                            class="stitch-step-card"
                            :class="{ 'stitch-step-card-featured': step.featured }"
                        >
                            <div class="stitch-step-number">{{ step.number }}</div>
                            <div>
                                <h3>{{ step.title }}</h3>
                                <p>{{ step.copy }}</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section id="proof" class="stitch-section">
                <div class="stitch-shell stitch-shell-narrow">
                    <h2 class="stitch-section-heading centered">How we stack up.</h2>

                    <div class="stitch-table-wrap">
                        <table class="stitch-table">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>Manual</th>
                                    <th>Agency</th>
                                    <th>BacklinkPro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in comparisonRows" :key="row.feature">
                                    <td>{{ row.feature }}</td>
                                    <td>{{ row.manual }}</td>
                                    <td>{{ row.agency }}</td>
                                    <td class="stitch-table-highlight">{{ row.platform }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="pricing" class="stitch-section stitch-surface-band">
                <div class="stitch-shell">
                    <div class="stitch-centered-copy">
                        <div class="stitch-section-label centered">Pricing</div>
                        <h2 class="stitch-section-heading">Precision Pricing</h2>
                        <p>Scalable plans for every stage of your digital growth.</p>
                    </div>

                    <div class="stitch-pricing-grid">
                        <article
                            v-for="plan in plans"
                            :key="plan.name"
                            class="stitch-plan-card"
                            :class="{ 'stitch-plan-card-featured': plan.featured }"
                        >
                            <div v-if="plan.featured" class="stitch-plan-badge">Most popular</div>

                            <div class="stitch-plan-head">
                                <h3>{{ plan.name }}</h3>
                                <p class="stitch-plan-tagline">{{ plan.tagline }}</p>
                                <div class="stitch-plan-price">
                                    <strong>{{ plan.price }}</strong>
                                    <span>{{ plan.period }}</span>
                                </div>
                                <p class="stitch-plan-annual">{{ plan.annual }}</p>
                            </div>

                            <div class="stitch-plan-limits">
                                <div v-for="limit in plan.limits" :key="`${plan.name}-${limit.label}`" class="stitch-plan-limit">
                                    <span>{{ limit.label }}</span>
                                    <strong>{{ limit.value }}</strong>
                                </div>
                            </div>

                            <div class="stitch-plan-types-wrap">
                                <h4>Backlink Types</h4>
                                <div class="stitch-plan-types">
                                    <span v-for="type in plan.types" :key="`${plan.name}-${type}`">{{ type }}</span>
                                </div>
                            </div>

                            <div class="stitch-plan-includes">
                                <h4>What You Get</h4>
                                <ul class="stitch-plan-list">
                                    <li v-for="feature in plan.features" :key="feature">{{ feature }}</li>
                                </ul>
                            </div>

                            <div class="stitch-plan-actions">
                                <a class="stitch-plan-action stitch-plan-action-primary" :href="plan.primaryHref">
                                    {{ plan.primaryCta }}
                                </a>
                                <a class="stitch-plan-action stitch-plan-action-secondary" :href="plan.secondaryHref">
                                    {{ plan.secondaryCta }}
                                </a>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section id="faq" class="stitch-section">
                <div class="stitch-shell stitch-shell-narrow">
                    <div class="stitch-centered-copy stitch-faq-head">
                        <div class="stitch-section-label centered">Support</div>
                        <h2 class="stitch-section-heading">Frequently Asked Questions</h2>
                        <p>
                            Everything you need to know about scaling your authority with BacklinkPro's autonomous engine.
                        </p>
                    </div>

                    <div class="stitch-faq-grid">
                        <details v-for="item in faqs" :key="item.question" class="stitch-faq-item">
                            <summary>
                                <span>{{ item.question }}</span>
                                <span class="stitch-faq-plus">+</span>
                            </summary>
                            <div class="stitch-faq-answer">{{ item.answer }}</div>
                        </details>
                    </div>
                </div>
            </section>

            <section class="stitch-cta-band">
                <div class="stitch-shell stitch-shell-narrow stitch-centered-copy">
                    <h2 class="stitch-cta-title">Ready to see BacklinkPro in action?</h2>
                    <p>Join 2,000+ SEO curators who have automated their authority growth with BacklinkPro.</p>
                    <a class="stitch-button stitch-button-dark stitch-button-lg" href="/contact">Contact Sales</a>
                </div>
            </section>
        </main>

        <footer class="stitch-footer">
            <div class="stitch-shell">
                <div class="stitch-footer-grid">
                    <div class="stitch-footer-brand">
                        <a class="stitch-brand" href="/">
                            <span class="stitch-brand-mark">B</span>
                            <span>BacklinkPro</span>
                        </a>

                        <div class="stitch-social-row">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>

                    <div class="stitch-footer-col" v-for="group in footerGroups" :key="group.title">
                        <h3>{{ group.title }}</h3>
                        <a v-for="link in group.links" :key="link.label" :href="link.href">{{ link.label }}</a>
                    </div>
                </div>

                <div class="stitch-footer-bottom">
                    <p>&copy; 2024 BacklinkPro, Inc.</p>
                    <div class="stitch-footer-badges">
                        <span>Fast</span>
                        <span>Secure</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import MarketingHead from '../../Components/Marketing/SEO/MarketingHead.vue';

defineProps({
    meta: {
        type: Object,
        required: true,
    },
});

const logoNames = ['POSTMAN', 'REPLY', 'DOORDASH', 'CAPITAL ONE', 'AFRIEX'];
const navOnDark = ref(false);

const syncNavTheme = () => {
    navOnDark.value = window.scrollY > 120;
};

const navGroups = [
    {
        label: 'Product',
        items: [
            { label: 'Platform Overview', href: '/product', copy: 'Explore the core backlink automation engine.' },
            { label: 'How It Works', href: '/how-it-works', copy: 'See the workflow from strategy to reporting.' },
            { label: 'Pricing', href: '/pricing', copy: 'Compare plans for startups, agencies, and teams.' },
            { label: 'Free Plan', href: '/free-plan', copy: 'Start with a guided backlink growth plan.' },
            { label: 'SEO Audit Report', href: '/seo-audit-report', copy: 'Review technical SEO findings, risks, and optimization priorities.' },
        ],
    },
    {
        label: 'Company',
        items: [
            { label: 'About Us', href: '/about', copy: 'Meet the team and our operating principles.' },
            { label: 'Case Studies', href: '/case-studies', copy: 'See proof from live authority growth campaigns.' },
            { label: 'Resources', href: '/resources', copy: 'Read guides, playbooks, and tactical breakdowns.' },
            { label: 'Contact', href: '/contact', copy: 'Talk with our team about your SEO goals.' },
        ],
    },
];

const heroMetrics = [
    { label: 'Ref domains won', value: '1,284' },
    { label: 'Avg DR uplift', value: '+27%' },
    { label: 'Safe placements', value: '98.4%' },
];

const heroRows = [
    { site: 'SaaS outreach sprint', status: 'Approved', metric: 'DR 72' },
    { site: 'Competitor mention watch', status: 'Reviewing', metric: '12 wins' },
    { site: 'Authority recovery', status: 'Live', metric: '+19 links' },
];

const statHighlights = [
    { label: 'Avg Traffic Boost', value: '142%' },
    { label: 'Niche Coverage', value: '94%' },
];

const auditHighlights = [
    { title: 'Spot blockers first', copy: 'Catch crawl, metadata, and technical issues before link velocity starts.' },
    { title: 'Give the team a brief', copy: 'Walk into campaign setup with a clearer page target and cleaner priorities.' },
    { title: 'Connect report to execution', copy: 'Move from audit insight to backlink action without leaving the platform.' },
];

const auditChecks = [
    { title: 'Technical health score', copy: 'Page readiness, crawlability, and page hygiene in one view.' },
    { title: 'Top-priority issues', copy: 'Missing metadata, slow assets, and weak page structure ranked by impact.' },
    { title: 'Actionable next steps', copy: 'A clear handoff your team can use before launching a backlink sprint.' },
];

const auditStats = [
    { value: 'Free', label: 'Forever' },
    { value: '0', label: 'Credit card' },
    { value: 'Fast', label: 'Results' },
];

const problems = [
    {
        icon: '01',
        title: 'The Manual Grind',
        copy: 'Endless outreach, tracking spreadsheets, and rejection emails that eat your time.',
    },
    {
        icon: '02',
        title: 'Agency Retainers',
        copy: 'Paying $5,000/month for a handful of links that might not even move the needle.',
    },
    {
        icon: '03',
        title: 'Cheap Automation',
        copy: 'Spammy tools that burn domains and trigger Google penalties with zero quality.',
    },
];

const consoleLinks = ['Connect Target Domains', 'Define Niche Parameters', 'Review Quality Control'];

const campaignFlow = [
    { step: '01', title: 'Choose target URL', copy: 'Select the page you want to grow and set the exact goal of the sprint.' },
    { step: '02', title: 'Set filters & risk rules', copy: 'Define DR ranges, niche fit, anchor preferences, and approval thresholds.' },
    { step: '03', title: 'Launch discovery', copy: 'BacklinkPro scans vetted opportunities and stages the best placements for review.' },
    { step: '04', title: 'Approve and track', copy: 'Push campaigns live, monitor wins, and review proof from the same dashboard.' },
];

const smartSignals = [
    { icon: 'P', title: 'Performance', copy: 'Real-time rank tracking for every link.' },
    { icon: 'C', title: 'Contextual', copy: 'Links that bridge your niche authority.' },
    { icon: 'A', title: 'Auto Clean', copy: 'Continuous monitoring of link health.' },
];

const steps = [
    { number: '01', title: 'Connect & Rules', copy: 'Define project parameters, target URLs, and strict filtering rules.' },
    { number: '02', title: 'Discover Opportunities', copy: 'Engine scans millions of curated domains for contextual relevance.', featured: true },
    { number: '03', title: 'AI Execution', copy: 'Automated placement with natural language for high-quality context.' },
    { number: '04', title: 'Approve & Track', copy: 'Full transparency. Approve every link and track rank changes live.' },
];

const comparisonRows = [
    { feature: 'Speed to First Link', manual: '2-4 Weeks', agency: '1 Month', platform: '48 Hours' },
    { feature: 'Average DR Range', manual: 'Variable', agency: 'High', platform: '30-80+' },
    { feature: 'Risk Management', manual: 'Manual', agency: 'Manual', platform: 'Automated QA' },
    { feature: 'Cost per Link', manual: '$$$ (Time)', agency: '$$$$', platform: '$ (Efficient)' },
];

const plans = [
    {
        name: 'Starter',
        price: '$79',
        period: '/mo',
        annual: 'Annual: $67 / month billed yearly',
        tagline: 'For small sites testing guardrailed workflows.',
        limits: [
            { label: 'Domains', value: '3' },
            { label: 'Projects', value: '1' },
            { label: 'Monthly Actions', value: '2,000' },
            { label: 'Team Seats', value: '1' },
        ],
        types: ['Comment', 'Profile'],
        features: [
            'Comment + Profile workflows',
            'Approvals (basic)',
            'Evidence logs',
            'Velocity caps + lists',
        ],
        primaryCta: 'Subscribe Now',
        primaryHref: '/register?plan=starter',
        secondaryCta: 'View Workflows',
        secondaryHref: '/workflows',
    },
    {
        name: 'Growth',
        price: '$199',
        period: '/mo',
        annual: 'Annual: $169 / month billed yearly',
        tagline: 'For teams scaling with tighter controls and more workflows.',
        limits: [
            { label: 'Domains', value: '20' },
            { label: 'Projects', value: '5' },
            { label: 'Monthly Actions', value: '8,000' },
            { label: 'Team Seats', value: '3' },
        ],
        types: ['Comment', 'Profile', 'Forum'],
        features: [
            'All Starter features',
            'Forum workflow',
            'Stronger approval gates (rules)',
            'CSV exports',
            'Weekly summaries',
        ],
        primaryCta: 'Subscribe Now',
        primaryHref: '/register?plan=growth',
        secondaryCta: 'See case studies',
        secondaryHref: '/case-studies',
        featured: true,
    },
    {
        name: 'Pro / Agency',
        price: '$499',
        period: '/mo',
        annual: 'Annual: $424 / month billed yearly',
        tagline: 'For agencies and multi-project operations with audit needs.',
        limits: [
            { label: 'Domains', value: '100' },
            { label: 'Projects', value: '20' },
            { label: 'Monthly Actions', value: '25,000' },
            { label: 'Team Seats', value: '10' },
        ],
        types: ['Comment', 'Profile', 'Forum', 'Guest'],
        features: [
            'All Growth features',
            'Guest workflow',
            'Advanced approvals + audit trail',
            'White-label exports',
            'Priority support',
        ],
        primaryCta: 'Subscribe Now',
        primaryHref: '/contact',
        secondaryCta: 'Security & Trust',
        secondaryHref: '/security',
    },
];

const faqs = [
    { question: 'How safe is automated link building?', answer: 'We prioritize safety through a strict vetting process, manual oversight, and adherence to search engine guidelines to ensure long-term site health.' },
    { question: 'How long until I see results?', answer: 'Most campaigns show initial indexing and movement within 3-6 weeks, with significant authority growth typically appearing after 3 months of consistent activity.' },
    { question: 'Can I control which links get placed?', answer: 'Yes, you have full transparency and granular control. You can set specific criteria, approve placements, and blacklist certain domains or niches.' },
    { question: 'How do you verify link placement?', answer: 'Our system provides real-time verification of every link, including live status checks, DR metrics, and detailed reporting in your dashboard.' },
    { question: 'What happens if a link gets removed?', answer: 'We offer a 12-month replacement guarantee. If a link is removed or becomes inactive, we automatically source a replacement of equal or higher value.' },
    { question: 'Do you integrate with SEO tools?', answer: 'Yes, we offer seamless integrations with major SEO platforms like Ahrefs, Semrush, and Google Search Console for comprehensive data tracking.' },
    { question: 'Can I use this for multiple projects?', answer: 'Absolutely. Our Pro and Enterprise plans are designed for multi-project management, allowing you to scale across different domains from a single dashboard.' },
    { question: 'What if I need to pause my campaign?', answer: 'You can pause or adjust your campaigns at any time. Your existing links remain live, and you can resume operations whenever you are ready.' },
];

const footerGroups = [
    { title: 'Product', links: [{ label: 'Agent Canvas', href: '/product' }, { label: 'Insights', href: '/product' }, { label: 'Voice Experience', href: '/product' }] },
    { title: 'Company', links: [{ label: 'Careers', href: '/about' }, { label: 'Contact', href: '/contact' }, { label: 'Trust Center', href: '/security' }] },
    { title: 'Resources', links: [{ label: 'News', href: '/resources' }, { label: 'Privacy Policy', href: '/privacy-policy' }, { label: 'Terms of Service', href: '/terms-of-service' }] },
];

onMounted(() => {
    syncNavTheme();
    window.addEventListener('scroll', syncNavTheme, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', syncNavTheme);
});
</script>

<style scoped>
.stitch-home {
    background:
        radial-gradient(circle at top, rgba(255, 86, 38, 0.14), transparent 24%),
        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.08), transparent 22%),
        #050505;
    color: #e5e5e5;
    font-family: Inter, sans-serif;
    letter-spacing: -0.01em;
}

.stitch-shell {
    width: min(100% - 3rem, 1440px);
    margin: 0 auto;
}

.stitch-shell-narrow {
    width: min(100% - 3rem, 1100px);
}

.stitch-nav {
    position: fixed;
    inset: 1.25rem 0 auto;
    z-index: 50;
}

.stitch-nav-dark .stitch-brand-wrap,
.stitch-nav-dark .stitch-text-link,
.stitch-nav-dark .stitch-mobile-dropdown {
    background: rgba(16, 18, 24, 0.54);
    box-shadow: 0 18px 44px rgba(0, 0, 0, 0.22);
}

.stitch-nav-dark .stitch-brand,
.stitch-nav-dark .stitch-nav-trigger,
.stitch-nav-dark .stitch-text-link,
.stitch-nav-dark .stitch-mobile-dropdown summary {
    color: rgba(255, 255, 255, 0.94);
}

.stitch-nav-dark .stitch-brand-mark {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.12));
}

.stitch-nav-dark .stitch-brand-mark-core,
.stitch-nav-dark .stitch-brand-mark-ring {
    background: #ffffff;
    border-color: #ffffff;
    border-left-color: transparent;
    border-bottom-color: transparent;
}

.stitch-nav-dark .stitch-nav-trigger:hover,
.stitch-nav-dark .stitch-text-link:hover {
    color: #ffffff;
}

.stitch-nav-dark .stitch-text-link:hover {
    background: rgba(255, 255, 255, 0.18);
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.2);
}

.stitch-nav-dark .stitch-nav-menu {
    background: rgba(16, 18, 24, 0.74);
    border-color: rgba(255, 255, 255, 0.14);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.28);
}

.stitch-nav-dark .stitch-nav-menu-item:hover,
.stitch-nav-dark .stitch-mobile-menu-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.stitch-nav-dark .stitch-nav-menu-label {
    color: #ffffff;
}

.stitch-nav-dark .stitch-nav-menu-copy {
    color: rgba(255, 255, 255, 0.68);
}

.stitch-nav .stitch-shell,
.stitch-brand-wrap,
.stitch-actions,
.stitch-links,
.stitch-brand,
.stitch-hero-actions,
.stitch-logo-row,
.stitch-modal-actions,
.stitch-footer-bottom,
.stitch-footer-badges,
.stitch-social-row {
    display: flex;
    align-items: center;
}

.stitch-nav .stitch-shell,
.stitch-footer-bottom {
    justify-content: space-between;
}

.stitch-nav .stitch-shell {
    padding: 0;
    gap: 1.5rem;
}

.stitch-brand-wrap {
    gap: 0.7rem;
    padding: 0.7rem 0.95rem;
    border-radius: 0.95rem;
    background: rgba(255, 255, 255, 0.16);
    border: none;
    backdrop-filter: blur(16px) saturate(140%);
    -webkit-backdrop-filter: blur(16px) saturate(140%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
}

.stitch-brand {
    gap: 0.75rem;
    color: rgba(15, 23, 42, 0.92);
    text-decoration: none;
    font-size: 0.92rem;
    font-weight: 700;
}

.stitch-brand-mark {
    width: 1.45rem;
    height: 1.45rem;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.74), rgba(255, 255, 255, 0.36));
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.stitch-brand-mark-core {
    width: 0.42rem;
    height: 0.42rem;
    border-radius: 999px;
    background: #314a61;
    transform: translateX(-0.13rem);
}

.stitch-brand-mark-ring {
    position: absolute;
    width: 0.82rem;
    height: 0.82rem;
    border: 0.12rem solid #314a61;
    border-left-color: transparent;
    border-bottom-color: transparent;
    border-radius: 999px;
    transform: rotate(18deg) translateX(0.08rem);
}

.stitch-links,
.stitch-actions {
    gap: 1rem;
}

.stitch-links {
    position: relative;
}

.stitch-nav-dropdown {
    position: relative;
}

.stitch-nav-trigger,
.stitch-text-link,
.stitch-footer-col a {
    color: rgba(15, 23, 42, 0.82);
    text-decoration: none;
    transition: color 0.2s ease, opacity 0.2s ease, background 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    font-family: "Manrope", Inter, sans-serif;
}

.stitch-nav-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    background: transparent;
    border: none;
    padding: 0.55rem 0.8rem;
    border-radius: 0.7rem;
    cursor: pointer;
}

.stitch-nav-trigger:hover,
.stitch-text-link:hover,
.stitch-footer-col a:hover {
    color: #020617;
}

.stitch-nav-caret {
    width: 0.42rem;
    height: 0.42rem;
    margin-top: -0.02rem;
    border-right: 1.5px solid currentColor;
    border-bottom: 1.5px solid currentColor;
    opacity: 0.62;
    line-height: 1;
    transform-origin: center;
    transform: rotate(45deg);
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.stitch-nav-dropdown:hover .stitch-nav-caret,
.stitch-mobile-dropdown[open] .stitch-nav-caret {
    transform: rotate(225deg);
}

.stitch-nav-menu {
    position: absolute;
    top: calc(100% + 0.72rem);
    left: 0;
    min-width: 11.75rem;
    max-width: 12.5rem;
    display: grid;
    gap: 0.18rem;
    padding: 0.38rem;
    border-radius: 0.95rem;
    background: rgba(255, 255, 255, 0.32);
    border: 1px solid rgba(255, 255, 255, 0.24);
    backdrop-filter: blur(18px) saturate(150%);
    -webkit-backdrop-filter: blur(18px) saturate(150%);
    box-shadow: 0 18px 48px rgba(15, 23, 42, 0.12);
    opacity: 0;
    visibility: hidden;
    transform: translateY(8px);
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
}

.stitch-nav-dropdown:hover .stitch-nav-menu,
.stitch-nav-dropdown:focus-within .stitch-nav-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.stitch-nav-menu-item,
.stitch-mobile-menu-item {
    display: grid;
    gap: 0;
    padding: 0.7rem 0.82rem;
    border-radius: 0.76rem;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.2s ease;
}

.stitch-nav-menu-item:hover,
.stitch-mobile-menu-item:hover {
    background: rgba(255, 255, 255, 0.22);
    transform: translateY(0);
}

.stitch-nav-menu-label {
    color: #111827;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    font-family: "Manrope", Inter, sans-serif;
}

.stitch-nav-menu-copy {
    display: none;
}

.stitch-text-link {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.95rem 1.3rem;
    border-radius: 0.95rem;
    background: rgba(255, 255, 255, 0.16);
    border: none;
    backdrop-filter: blur(16px) saturate(140%);
    -webkit-backdrop-filter: blur(16px) saturate(140%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
}

.stitch-text-link:hover {
    background: rgba(255, 255, 255, 0.34);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.14);
    transform: translateY(-1px);
}

.stitch-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 0.88rem 1.5rem;
    text-decoration: none;
    font-size: 0.92rem;
    font-weight: 700;
    transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.stitch-button:hover {
    transform: translateY(-1px);
}

.stitch-button-light {
    background: #ffffff;
    color: #020617;
    box-shadow: 0 14px 36px rgba(15, 23, 42, 0.14);
}

.stitch-button-light:hover {
    background: #0f172a;
    color: #ffffff;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
}

.stitch-button-dark {
    background: black;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.stitch-button-dark:hover {
    background: white;
    color: black;
}

.stitch-button-ghost {
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
    background: transparent;
}

.stitch-button-ghost:hover {
    background: white;
    color: black;
}

.stitch-button-lg {
    padding: 1.1rem 2.4rem;
    font-size: 1rem;
}

.stitch-hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10rem 0 5rem;
    overflow: hidden;
}

.stitch-hero-backdrop {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to bottom, rgba(5, 5, 5, 0.15), rgba(5, 5, 5, 0.95)),
        url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&q=80&w=2070')
            center / cover no-repeat;
}

.stitch-hero-backdrop::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(6, 11, 20, 0.08), rgba(6, 11, 20, 0.22));
}

.stitch-hero-product {
    position: absolute;
    inset: auto 0 2.5rem;
    pointer-events: none;
}

.stitch-hero-window {
    position: absolute;
    border-radius: 1.5rem;
    background: rgba(10, 16, 28, 0.52);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    box-shadow: 0 30px 100px rgba(0, 0, 0, 0.2);
}

.stitch-hero-window-main {
    width: min(58rem, calc(100% - 12rem));
    height: 18rem;
    left: 50%;
    bottom: 0;
    transform: translateX(-50%);
    overflow: hidden;
    opacity: 0.52;
}

.stitch-window-topbar {
    display: flex;
    gap: 0.45rem;
    padding: 1rem 1.2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.stitch-window-topbar span {
    width: 0.68rem;
    height: 0.68rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.2);
}

.stitch-window-body {
    display: grid;
    grid-template-columns: 4.8rem 1fr;
    height: calc(100% - 3.15rem);
}

.stitch-window-sidebar {
    display: grid;
    align-content: start;
    gap: 0.9rem;
    padding: 1.2rem 1rem;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
}

.stitch-window-sidebar span {
    width: 100%;
    height: 0.7rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
}

.stitch-window-sidebar .is-active {
    background: linear-gradient(90deg, rgba(47, 107, 255, 0.95), rgba(0, 214, 201, 0.88));
}

.stitch-window-content {
    padding: 1.4rem;
}

.stitch-window-header {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.stitch-window-kicker,
.stitch-hero-window-mini p {
    margin: 0;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.74rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-weight: 700;
}

.stitch-window-header h3,
.stitch-hero-window-mini strong {
    margin: 0.35rem 0 0;
    color: white;
    font-size: 1.25rem;
    font-weight: 700;
}

.stitch-window-chip {
    padding: 0.55rem 0.85rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.76rem;
    font-weight: 700;
}

.stitch-window-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.1rem;
}

.stitch-window-metrics article {
    padding: 1rem 1.1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.05);
}

.stitch-window-metrics span {
    display: block;
    color: rgba(255, 255, 255, 0.54);
    font-size: 0.72rem;
    margin-bottom: 0.45rem;
}

.stitch-window-metrics strong {
    color: white;
    font-size: 1.22rem;
}

.stitch-window-chart {
    height: 5.5rem;
    border-radius: 1.1rem;
    margin-bottom: 1rem;
    background:
        linear-gradient(180deg, rgba(47, 107, 255, 0.14), transparent),
        rgba(255, 255, 255, 0.04);
    position: relative;
    overflow: hidden;
}

.stitch-window-chart::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 100% 2.1rem, 4rem 100%;
}

.stitch-window-chart-line {
    position: absolute;
    inset: auto 1.4rem 1.2rem;
    height: 46%;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, rgba(0, 214, 201, 0.22), rgba(47, 107, 255, 0.45));
    clip-path: polygon(0 86%, 12% 72%, 24% 78%, 37% 42%, 50% 55%, 63% 24%, 76% 34%, 88% 12%, 100% 0, 100% 100%, 0 100%);
}

.stitch-window-table {
    display: grid;
    gap: 0.7rem;
}

.stitch-window-row {
    display: grid;
    grid-template-columns: 1.3fr 0.8fr 0.6fr;
    gap: 1rem;
    padding: 0.8rem 1rem;
    border-radius: 0.95rem;
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.76);
    font-size: 0.82rem;
}

.stitch-window-row span:last-child {
    color: #82f7ea;
    font-weight: 700;
}

.stitch-hero-inner {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 64rem;
}

.stitch-status-pill {
    display: inline-flex;
    gap: 0.65rem;
    align-items: center;
    padding: 0.65rem 1rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-weight: 800;
    color: rgba(255, 255, 255, 0.82);
    margin-bottom: 2rem;
}

.stitch-status-dot {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 999px;
    background: #ff5626;
    box-shadow: 0 0 18px rgba(255, 86, 38, 0.65);
}

.stitch-hero h1 {
    font-size: clamp(3rem, 8vw, 5.4rem);
    line-height: 0.98;
    letter-spacing: -0.05em;
    color: white;
    margin: 0;
    font-weight: 800;
}

.stitch-hero h1 span {
    display: block;
    color: rgba(255, 255, 255, 0.58);
    margin-top: 0.55rem;
}

.stitch-hero-copy {
    margin: 2rem auto 0;
    max-width: 42rem;
    color: rgba(255, 255, 255, 0.62);
    font-size: 1.15rem;
    line-height: 1.8;
}

.stitch-hero-actions {
    justify-content: center;
    margin: 2.5rem 0 4.25rem;
}

.stitch-logo-row {
    justify-content: center;
    flex-wrap: wrap;
    gap: 2rem 4rem;
    opacity: 0.45;
    font-weight: 800;
    font-size: 1.06rem;
    letter-spacing: 0.02em;
}

.stitch-stats,
.stitch-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.stitch-stats {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    padding: 4.5rem 0;
}

.stitch-stats-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr 1fr;
    gap: 2rem;
    align-items: end;
}

.stitch-intro-copy p {
    max-width: 16rem;
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.7;
    margin: 0;
}

.stitch-stat-card {
    border-left: 1px solid rgba(255, 255, 255, 0.08);
    padding-left: 2rem;
}

.stitch-stat-label,
.stitch-mini-label,
.stitch-footer-col h3 {
    font-size: 0.64rem;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    color: rgba(255, 255, 255, 0.34);
    font-weight: 800;
}

.stitch-stat-value {
    font-size: clamp(2.6rem, 5vw, 4rem);
    font-weight: 800;
    color: white;
    margin-top: 0.4rem;
}

.stitch-section {
    padding: 8rem 0;
}

#pricing.stitch-section {
    scroll-margin-top: 8.5rem;
    padding-top: 9rem;
}

.stitch-section-spacious {
    padding-top: 9rem;
}

.stitch-section-audit {
    padding-top: 6.5rem;
    padding-bottom: 2rem;
}

.stitch-section-campaign {
    padding-top: 2rem;
}

.stitch-feature-band,
.stitch-campaign-shell {
    border-radius: 2rem;
    border: 1px solid rgba(255, 110, 64, 0.14);
    background:
        radial-gradient(circle at top left, rgba(255, 110, 64, 0.12), transparent 34%),
        linear-gradient(180deg, rgba(23, 15, 14, 0.96), rgba(9, 9, 9, 0.98));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.stitch-feature-band {
    display: grid;
    grid-template-columns: minmax(0, 1.08fr) minmax(320px, 0.92fr);
    gap: 2rem;
    padding: 2rem;
}

.stitch-feature-copy {
    padding: 1rem;
}

.stitch-feature-copy .stitch-section-heading {
    max-width: 40rem;
}

.stitch-feature-copy > p,
.stitch-campaign-head p {
    max-width: 38rem;
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.8;
    font-size: 1rem;
}

.stitch-feature-points {
    display: grid;
    gap: 1rem;
    margin: 2rem 0;
}

.stitch-feature-points article {
    padding: 1rem 1.1rem;
    border-radius: 1rem;
    border: 1px solid rgba(255, 110, 64, 0.12);
    background: rgba(255, 255, 255, 0.02);
}

.stitch-feature-points h3,
.stitch-audit-list-item h4,
.stitch-campaign-card h3 {
    margin: 0 0 0.35rem;
    color: white;
    font-size: 1rem;
    font-weight: 700;
}

.stitch-feature-points p,
.stitch-audit-list-item p,
.stitch-campaign-card p {
    margin: 0;
    color: rgba(255, 255, 255, 0.55);
    line-height: 1.7;
}

.stitch-feature-actions {
    display: flex;
    gap: 0.85rem;
    flex-wrap: wrap;
    margin-top: 1.4rem;
}

.stitch-feature-visual {
    display: flex;
    align-items: stretch;
}

.stitch-audit-card {
    width: 100%;
    border-radius: 1.6rem;
    border: 1px solid rgba(255, 110, 64, 0.22);
    background: rgba(14, 10, 10, 0.78);
    padding: 1.5rem;
    display: grid;
    gap: 1rem;
}

.stitch-audit-card-head strong {
    display: block;
    color: white;
    font-size: 1.5rem;
    font-weight: 800;
    margin-top: 0.45rem;
}

.stitch-audit-list {
    display: grid;
    gap: 0.9rem;
}

.stitch-audit-list-item {
    display: flex;
    gap: 0.9rem;
    align-items: flex-start;
    padding: 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 110, 64, 0.14);
}

.stitch-audit-dot {
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 999px;
    background: #ff7a4f;
    box-shadow: 0 0 16px rgba(255, 122, 79, 0.45);
    margin-top: 0.45rem;
    flex: none;
}

.stitch-audit-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.85rem;
    margin-top: 0.35rem;
}

.stitch-audit-stats article {
    padding: 1rem 0.8rem;
    border-radius: 1rem;
    border: 1px solid rgba(255, 110, 64, 0.12);
    background: rgba(255, 255, 255, 0.02);
    text-align: center;
}

.stitch-audit-stats strong {
    display: block;
    color: white;
    font-size: 1.4rem;
    font-weight: 800;
}

.stitch-audit-stats span {
    display: block;
    margin-top: 0.35rem;
    color: rgba(255, 255, 255, 0.5);
    text-transform: uppercase;
    letter-spacing: 0.14em;
    font-size: 0.68rem;
    font-weight: 700;
}

.stitch-two-col {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 6rem;
    align-items: start;
    margin-bottom: 7rem;
}

.stitch-two-col-tight {
    margin-bottom: 5rem;
}

.stitch-section-label {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    color: #ff5626;
    font-size: 0.66rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-weight: 800;
    margin-bottom: 1.5rem;
}

.stitch-section-label::before {
    content: '';
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 0.18rem;
    background: #ff5626;
}

.stitch-section-label.centered {
    justify-content: center;
}

.stitch-display,
.stitch-section-heading,
.stitch-cta-title {
    color: white;
    letter-spacing: -0.04em;
    line-height: 1.04;
    margin: 0;
    font-weight: 800;
}

.stitch-display {
    font-size: clamp(2.6rem, 5vw, 4.2rem);
}

.stitch-section-heading {
    font-size: clamp(2.3rem, 4vw, 3.2rem);
    margin-bottom: 1rem;
}

.stitch-section-heading.centered {
    text-align: center;
    margin-bottom: 4rem;
}

.stitch-problem-list {
    display: grid;
    gap: 2.5rem;
}

.stitch-problem-item {
    display: flex;
    gap: 1.5rem;
}

.stitch-icon-chip,
.stitch-mini-icon,
.stitch-panel-icon {
    flex: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.75);
    font-weight: 700;
}

.stitch-icon-chip,
.stitch-mini-icon {
    width: 2.5rem;
    height: 2.5rem;
    font-size: 0.72rem;
}

.stitch-panel-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.9rem;
    color: #ff5626;
    margin-bottom: 1.5rem;
}

.stitch-problem-item h3,
.stitch-demo-copy h3,
.stitch-step-card h3,
.stitch-plan-head h3,
.stitch-footer-col h3,
.stitch-mini-grid h3 {
    color: white;
    margin: 0;
}

.stitch-problem-item h3,
.stitch-demo-copy h3,
.stitch-step-card h3,
.stitch-plan-head h3 {
    font-size: 1.15rem;
    font-weight: 700;
}

.stitch-problem-item p,
.stitch-demo-copy p,
.stitch-step-card p,
.stitch-mini-grid p,
.stitch-faq-answer,
.stitch-centered-copy p,
.stitch-footer-col a,
.stitch-footer-bottom p {
    color: rgba(255, 255, 255, 0.5);
    line-height: 1.75;
}

.stitch-demo-card,
.stitch-step-card,
.stitch-table-wrap,
.stitch-plan-card,
.stitch-faq-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
}

.stitch-demo-card {
    border-radius: 2rem;
    overflow: hidden;
    min-height: 38rem;
    display: grid;
    grid-template-columns: minmax(320px, 0.95fr) minmax(0, 1.5fr);
}

.stitch-demo-copy {
    padding: 3rem;
    display: flex;
    flex-direction: column;
}

.stitch-demo-links {
    margin-top: auto;
}

.stitch-demo-links div {
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    font-size: 0.95rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
}

.stitch-demo-visual {
    position: relative;
    overflow: hidden;
    background: #111111;
}

.stitch-demo-visual img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.82;
}

.stitch-demo-visual::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(10, 10, 10, 0.82), transparent 45%);
}

.stitch-modal-mock {
    position: absolute;
    inset: 50% auto auto 50%;
    transform: translate(-50%, -50%);
    width: min(80%, 32rem);
    z-index: 1;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
    border-radius: 1rem;
    padding: 1rem;
    box-shadow: 0 30px 70px rgba(0, 0, 0, 0.35);
}

.stitch-modal-surface {
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 0.8rem;
    padding: 1.5rem;
}

.stitch-mini-label {
    margin-bottom: 1rem;
}

.stitch-input-line,
.stitch-input-block {
    border-radius: 0.6rem;
    background: rgba(255, 255, 255, 0.05);
}

.stitch-input-line {
    height: 2rem;
    margin-bottom: 1rem;
}

.stitch-input-block {
    height: 6rem;
    margin-bottom: 1.5rem;
}

.stitch-modal-actions {
    justify-content: flex-end;
    gap: 0.6rem;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.stitch-modal-actions span {
    padding: 0.5rem 0.9rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.65);
}

.stitch-modal-actions .stitch-launch-chip {
    background: #ff5626;
    color: white;
    font-weight: 700;
}

.stitch-mini-grid,
.stitch-step-grid,
.stitch-pricing-grid,
.stitch-faq-grid,
.stitch-footer-grid {
    display: grid;
}

.stitch-campaign-shell {
    padding: 2rem;
}

.stitch-campaign-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 0.78fr);
    gap: 2rem;
    align-items: end;
    margin-bottom: 2rem;
}

.stitch-campaign-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.stitch-campaign-card {
    padding: 1.35rem;
    min-height: 14.5rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 110, 64, 0.15);
}

.stitch-campaign-step {
    display: inline-flex;
    margin-bottom: 1.25rem;
    color: rgba(255, 255, 255, 0.28);
    font-size: 2.1rem;
    font-weight: 800;
    letter-spacing: -0.05em;
}

.stitch-mini-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.5rem;
}

.stitch-mini-grid h3 {
    margin: 1rem 0 0.35rem;
    font-size: 0.82rem;
    letter-spacing: -0.03em;
    text-transform: uppercase;
}

.stitch-mini-grid p {
    font-size: 0.76rem;
    margin: 0;
}

.stitch-step-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}

.stitch-step-card {
    border-radius: 1.4rem;
    padding: 2.25rem;
    min-height: 20rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.stitch-step-card-featured {
    border-top: 2px solid #ff5626;
}

.stitch-step-number {
    font-size: 3.8rem;
    font-weight: 800;
    color: rgba(255, 255, 255, 0.08);
}

.stitch-centered-copy {
    text-align: center;
}

.stitch-table-wrap {
    border-radius: 1.4rem;
    overflow: hidden;
}

.stitch-table {
    width: 100%;
    border-collapse: collapse;
}

.stitch-table th,
.stitch-table td {
    padding: 1.7rem 1.8rem;
    text-align: left;
}

.stitch-table thead th {
    font-size: 0.66rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: rgba(255, 255, 255, 0.32);
    font-weight: 800;
}

.stitch-table tbody tr:not(:last-child) td,
.stitch-table thead th {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.stitch-table tbody td:first-child {
    color: white;
    font-weight: 600;
}

.stitch-table tbody td:not(:first-child) {
    color: rgba(255, 255, 255, 0.45);
}

.stitch-table-highlight {
    color: #ff5626 !important;
    font-weight: 800 !important;
}

.stitch-surface-band {
    background: #0a0a0a;
}

.stitch-pricing-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.5rem;
}

.stitch-plan-card {
    position: relative;
    border-radius: 1.2rem;
    padding: 1.15rem;
    display: flex;
    flex-direction: column;
    background:
        radial-gradient(circle at top, rgba(255, 110, 64, 0.08), transparent 42%),
        linear-gradient(180deg, rgba(18, 14, 14, 0.98), rgba(10, 10, 10, 0.99));
    border: 1px solid rgba(255, 110, 64, 0.32);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.stitch-plan-card-featured {
    border-color: rgba(255, 110, 64, 0.78);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.04),
        0 20px 55px rgba(255, 110, 64, 0.12);
}

.stitch-plan-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.85rem;
    left: auto;
    transform: none;
    background: rgba(255, 110, 64, 0.16);
    color: white;
    border-radius: 999px;
    padding: 0.3rem 0.65rem;
    font-size: 0.64rem;
    letter-spacing: -0.01em;
    font-weight: 700;
    z-index: 2;
    box-shadow: none;
    border: 1px solid rgba(255, 110, 64, 0.42);
}

.stitch-plan-head {
    margin-bottom: 1rem;
}

.stitch-plan-head h3,
.stitch-plan-types-wrap h4,
.stitch-plan-includes h4 {
    color: #ffffff;
    margin: 0;
    font-family: "Manrope", Inter, sans-serif;
}

.stitch-plan-head h3 {
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.stitch-plan-tagline {
    margin: 0.35rem 0 0;
    color: rgba(255, 234, 226, 0.78);
    font-size: 0.87rem;
    line-height: 1.45;
}

.stitch-plan-price {
    margin-top: 1rem;
    color: white;
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
    font-weight: 800;
}

.stitch-plan-price strong {
    font-size: 3rem;
    line-height: 0.95;
    color: #ffffff;
}

.stitch-plan-price span {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
}

.stitch-plan-annual {
    margin: 0.7rem 0 0;
    color: #ff8a65;
    font-size: 0.76rem;
    font-weight: 700;
}

.stitch-plan-limits {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
    margin-bottom: 1rem;
}

.stitch-plan-limit {
    padding: 0.75rem 0.85rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(255, 110, 64, 0.28);
    background: rgba(24, 16, 16, 0.82);
}

.stitch-plan-limit span {
    display: block;
    color: rgba(255, 194, 171, 0.88);
    font-size: 0.68rem;
    margin-bottom: 0.35rem;
}

.stitch-plan-limit strong {
    color: #ffffff;
    font-size: 1rem;
    font-weight: 800;
}

.stitch-plan-types-wrap,
.stitch-plan-includes {
    margin-bottom: 1rem;
}

.stitch-plan-types-wrap h4,
.stitch-plan-includes h4 {
    font-size: 0.98rem;
    font-weight: 800;
    margin-bottom: 0.7rem;
}

.stitch-plan-types {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.stitch-plan-types span {
    padding: 0.26rem 0.62rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.45);
    background: rgba(255, 110, 64, 0.12);
    color: #ffb199;
    font-size: 0.72rem;
    font-weight: 700;
}

.stitch-plan-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 0.7rem;
    flex: 1;
}

.stitch-plan-list li {
    position: relative;
    padding-left: 1rem;
    color: rgba(241, 245, 255, 0.92);
    line-height: 1.45;
    font-size: 0.95rem;
}

.stitch-plan-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0.47rem;
    width: 0.38rem;
    height: 0.38rem;
    border-radius: 999px;
    background: #ff6e40;
    box-shadow: 0 0 14px rgba(255, 110, 64, 0.35);
}

.stitch-plan-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.55rem;
    margin-top: auto;
    padding-top: 1rem;
}

.stitch-plan-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.65rem;
    border-radius: 0.55rem;
    text-decoration: none;
    font-size: 0.84rem;
    font-weight: 800;
    font-family: "Manrope", Inter, sans-serif;
    transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.stitch-plan-action:hover {
    transform: translateY(-1px);
}

.stitch-plan-action-primary {
    background: linear-gradient(180deg, #fff7f2, #ffe9de);
    border: 1px solid rgba(255, 214, 198, 0.7);
    color: #1a1210;
    box-shadow: 0 12px 28px rgba(255, 110, 64, 0.16);
}

.stitch-plan-action-primary:hover {
    background: linear-gradient(180deg, #ffefe7, #ffdccc);
    border-color: rgba(255, 181, 150, 0.9);
    color: #120c0b;
}

.stitch-plan-action-secondary {
    background: rgba(255, 110, 64, 0.1);
    border: 1px solid rgba(255, 110, 64, 0.3);
    color: #ffffff;
}

.stitch-plan-action-secondary:hover {
    background: rgba(255, 110, 64, 0.22);
}

.stitch-faq-head {
    margin-bottom: 3rem;
}

.stitch-faq-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.stitch-faq-item {
    border-radius: 1rem;
    overflow: hidden;
}

.stitch-faq-item summary {
    list-style: none;
    cursor: pointer;
    padding: 1.4rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    color: white;
    font-weight: 700;
}

.stitch-faq-item summary::-webkit-details-marker {
    display: none;
}

.stitch-faq-plus {
    color: #ff5626;
    font-size: 1.25rem;
    line-height: 1;
    transition: transform 0.2s ease;
}

.stitch-faq-item[open] .stitch-faq-plus {
    transform: rotate(45deg);
}

.stitch-faq-answer {
    padding: 0 1.5rem 1.5rem;
}

.stitch-cta-band {
    padding: 10rem 0;
    text-align: center;
    background: linear-gradient(to top, rgba(255, 86, 38, 0.1), transparent);
}

.stitch-cta-title {
    font-size: clamp(2.8rem, 5vw, 4.5rem);
    margin-bottom: 1.5rem;
}

.stitch-cta-band p {
    font-size: 1.15rem;
    max-width: 40rem;
    margin: 0 auto 2.5rem;
}

.stitch-footer {
    background: black;
    padding: 6rem 0 2rem;
}

.stitch-footer-grid {
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 2rem;
    padding-bottom: 3rem;
}

.stitch-footer-brand {
    max-width: 20rem;
}

.stitch-social-row {
    gap: 0.75rem;
    margin-top: 1.75rem;
    opacity: 0.45;
}

.stitch-social-row span {
    width: 2rem;
    height: 2rem;
    border-radius: 0.4rem;
    border: 1px solid rgba(255, 255, 255, 0.45);
    display: inline-block;
}

.stitch-footer-col {
    display: grid;
    align-content: start;
    gap: 1rem;
}

.stitch-footer-bottom {
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    font-size: 0.72rem;
}

.stitch-footer-badges {
    gap: 0.6rem;
}

.stitch-footer-badges span {
    border-radius: 999px;
    padding: 0.35rem 0.7rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.55);
}

.stitch-mobile-nav {
    display: none;
}

@media (max-width: 1100px) {
    .stitch-two-col,
    .stitch-demo-card,
    .stitch-stats-grid,
    .stitch-footer-grid,
    .stitch-pricing-grid,
    .stitch-feature-band,
    .stitch-campaign-head,
    .stitch-campaign-grid {
        grid-template-columns: 1fr;
    }

    .stitch-step-grid,
    .stitch-faq-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .stitch-mini-grid {
        grid-template-columns: 1fr;
    }

    .stitch-hero-window-main {
        width: min(52rem, calc(100% - 4rem));
        height: 16rem;
    }

    .stitch-stat-card {
        border-left: none;
        padding-left: 0;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .stitch-campaign-card {
        min-height: auto;
    }
}

@media (max-width: 820px) {
    .stitch-nav {
        inset: 0.9rem 0 auto;
    }

    .stitch-nav .stitch-shell {
        align-items: flex-start;
    }

    .stitch-links {
        display: none;
    }

    .stitch-brand-wrap,
    .stitch-actions {
        flex: 1;
    }

    .stitch-brand-wrap {
        padding: 0.75rem 0.9rem;
    }

    .stitch-actions {
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .stitch-text-link {
        display: none;
    }

    .stitch-button {
        padding: 0.9rem 1.2rem;
    }

    .stitch-mobile-nav {
        display: block;
        margin-top: 0.75rem;
    }

    .stitch-mobile-groups {
        display: grid;
        gap: 0.65rem;
    }

    .stitch-mobile-dropdown {
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.16);
        border: none;
        backdrop-filter: blur(16px) saturate(140%);
        -webkit-backdrop-filter: blur(16px) saturate(140%);
        overflow: hidden;
    }

    .stitch-mobile-dropdown summary {
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.95rem 1rem;
        color: #111827;
        font-weight: 600;
        cursor: pointer;
    }

    .stitch-mobile-dropdown summary::-webkit-details-marker {
        display: none;
    }

    .stitch-mobile-menu {
        display: grid;
        gap: 0.35rem;
        padding: 0 0.55rem 0.55rem;
    }

    .stitch-hero {
        min-height: auto;
        padding-top: 13rem;
    }

    .stitch-hero-product {
        inset: auto 0 1.25rem;
    }

    .stitch-hero-window-main {
        width: calc(100% - 2rem);
        height: 12.5rem;
        bottom: 0;
    }

    .stitch-window-body {
        grid-template-columns: 1fr;
    }

    .stitch-window-sidebar {
        display: none;
    }

    .stitch-window-content {
        padding: 1rem;
    }

    .stitch-window-header {
        align-items: center;
    }

    .stitch-window-header h3 {
        font-size: 1rem;
    }

    .stitch-window-chip {
        font-size: 0.68rem;
        padding: 0.5rem 0.7rem;
    }

    .stitch-window-metrics {
        grid-template-columns: 1fr;
    }

    .stitch-window-chart {
        height: 5rem;
    }

    .stitch-window-row {
        grid-template-columns: 1fr;
        gap: 0.3rem;
    }

    .stitch-section,
    .stitch-section-spacious,
    .stitch-cta-band {
        padding: 5.5rem 0;
    }

    .stitch-two-col,
    .stitch-demo-copy {
        gap: 2rem;
    }

    .stitch-step-grid,
    .stitch-faq-grid {
        grid-template-columns: 1fr;
    }

    .stitch-audit-stats {
        grid-template-columns: 1fr;
    }

    .stitch-table-wrap {
        overflow-x: auto;
    }

    .stitch-table {
        min-width: 42rem;
    }
}
</style>
