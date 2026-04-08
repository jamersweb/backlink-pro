<template>
    <div class="marketing-dark min-h-screen marketing-linked-page workflows-page">
        <Head>
            <title>{{ meta.title }}</title>
            <meta name="description" :content="meta.description" />
            <meta property="og:title" :content="meta.og.title" />
            <meta property="og:description" :content="meta.og.description" />
            <meta property="og:image" :content="meta.og.image" />
            <meta property="og:type" content="website" />
        </Head>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <HeaderNav />

        <main id="main-content">
            <WorkflowsHero />

            <section class="py-20">
                <div class="marketing-container max-w-7xl mx-auto">
                    <WorkflowCards :items="items" />
                </div>
            </section>

            <WorkflowComparison :items="items" :comparison="comparison" />

            <section class="workflow-safety-band py-20 bg-surface2">
                <div class="marketing-container max-w-6xl mx-auto">
                    <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                        How we keep it safe
                    </h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="marketing-card workflow-safety-card" data-reveal>
                            <span class="workflow-safety-kicker">Approval Gate</span>
                            <h3 class="text-lg font-bold mb-3 text-text">Approvals gate risk</h3>
                            <p class="text-sm text-muted mb-4">Manual approvals step in whenever actions move beyond your preferred threshold or confidence range.</p>
                            <a href="/security" class="text-primary hover:underline text-sm font-semibold">
                                Learn more ->
                            </a>
                        </div>

                        <div class="marketing-card workflow-safety-card" data-reveal>
                            <span class="workflow-safety-kicker">Evidence Trail</span>
                            <h3 class="text-lg font-bold mb-3 text-text">Evidence logs per action</h3>
                            <p class="text-sm text-muted mb-4">Each workflow keeps proof artifacts, timestamps, and placement records visible for teams and clients.</p>
                            <a href="/product" class="text-primary hover:underline text-sm font-semibold">
                                View product ->
                            </a>
                        </div>

                        <div class="marketing-card workflow-safety-card" data-reveal>
                            <span class="workflow-safety-kicker">Control Layer</span>
                            <h3 class="text-lg font-bold mb-3 text-text">Velocity caps to prevent spikes</h3>
                            <p class="text-sm text-muted mb-4">Teams can control pacing across domains, projects, and platforms so outreach never turns into noisy bursts.</p>
                            <a href="/security" class="text-primary hover:underline text-sm font-semibold">
                                See guardrails ->
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <WorkflowsFAQ :faqs="faqs" />

            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useReveal } from '../../../Composables/useReveal.js';
import HeaderNav from '../../../Components/Marketing/HeaderNav.vue';
import Footer from '../../../Components/Marketing/Footer.vue';
import FinalCTA from '../../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../../Components/Marketing/StickyMobileCTA.vue';
import WorkflowsHero from '../../../Components/Marketing/Workflows/WorkflowsHero.vue';
import WorkflowCards from '../../../Components/Marketing/Workflows/WorkflowCards.vue';
import WorkflowComparison from '../../../Components/Marketing/Workflows/WorkflowComparison.vue';
import WorkflowsFAQ from '../../../Components/Marketing/Workflows/WorkflowsFAQ.vue';
import '../shared-linked-page-theme.css';

defineProps({
    meta: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    comparison: {
        type: Object,
        required: true,
    },
    faqs: {
        type: Array,
        required: true,
    },
    disclosures: {
        type: Array,
        required: true,
    },
});

const { init } = useReveal();

onMounted(() => {
    init();
});
</script>

<style scoped>
.workflow-safety-card {
    padding: 1.5rem;
    border-radius: 1.5rem;
}

.workflow-safety-kicker {
    display: inline-flex;
    align-items: center;
    margin-bottom: 0.85rem;
    padding: 0.32rem 0.62rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.2);
    background: rgba(255, 110, 64, 0.08);
    color: rgba(255, 174, 143, 0.92);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

.focus\:not-sr-only:focus {
    position: static;
    width: auto;
    height: auto;
    padding: inherit;
    margin: inherit;
    overflow: visible;
    clip: auto;
    white-space: normal;
}
</style>
