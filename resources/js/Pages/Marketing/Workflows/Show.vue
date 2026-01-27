<template>
    <div class="marketing-dark min-h-screen">
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

        <AnnouncementBar />
        <HeaderNav />
        <DisclosuresBar :disclosures="disclosures" />

        <main id="main-content">
            <WorkflowDetailHero :item="item" />

            <div class="marketing-container max-w-7xl mx-auto">
                <div class="flex gap-8">
                    <!-- Main Content -->
                    <div class="flex-1">
                        <WorkflowSteps :steps="item.steps" />
                        <GuardrailsBlock :guardrails="item.guardrails" />
                        <TemplatePreview :templates="item.templates" />
                        <EvidencePackMock :evidence-pack="item.evidencePack" />
                        <BestForBlock :best-for="item.bestFor" :not-for="item.notFor" />
                    </div>

                    <!-- Side Nav (Desktop only) -->
                    <SideNav />
                </div>
            </div>

            <section id="faq" class="py-20 scroll-mt-24">
                <div class="marketing-container max-w-4xl mx-auto">
                    <WorkflowsFAQ :faqs="faqs" />
                </div>
            </section>

            <RelatedWorkflows :related="related" />

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
import AnnouncementBar from '../../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../../Components/Marketing/HeaderNav.vue';
import Footer from '../../../Components/Marketing/Footer.vue';
import FinalCTA from '../../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../../Components/Marketing/StickyMobileCTA.vue';
import WorkflowDetailHero from '../../../Components/Marketing/Workflows/WorkflowDetailHero.vue';
import SideNav from '../../../Components/Marketing/Workflows/SideNav.vue';
import WorkflowSteps from '../../../Components/Marketing/Workflows/WorkflowSteps.vue';
import GuardrailsBlock from '../../../Components/Marketing/Workflows/GuardrailsBlock.vue';
import TemplatePreview from '../../../Components/Marketing/Workflows/TemplatePreview.vue';
import EvidencePackMock from '../../../Components/Marketing/Workflows/EvidencePackMock.vue';
import BestForBlock from '../../../Components/Marketing/Workflows/BestForBlock.vue';
import RelatedWorkflows from '../../../Components/Marketing/Workflows/RelatedWorkflows.vue';
import WorkflowsFAQ from '../../../Components/Marketing/Workflows/WorkflowsFAQ.vue';
import DisclosuresBar from '../../../Components/Marketing/Workflows/DisclosuresBar.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    item: {
        type: Object,
        required: true,
    },
    related: {
        type: Array,
        required: true,
    },
    disclosures: {
        type: Array,
        required: true,
    },
    faqs: {
        type: Array,
        required: true,
    },
});

const { init } = useReveal();

onMounted(() => {
    init();

    // Add scroll-margin-top for anchor navigation
    const sections = document.querySelectorAll('section[id]');
    sections.forEach((section) => {
        section.style.scrollMarginTop = '120px';
    });

    // Handle hash navigation on mount
    if (window.location.hash) {
        setTimeout(() => {
            const element = document.querySelector(window.location.hash);
            if (element) {
                const offset = 120;
                const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                const offsetPosition = elementPosition - offset;
                window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
            }
        }, 100);
    }
});
</script>

<style scoped>
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
