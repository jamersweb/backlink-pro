<template>
    <div class="marketing-dark min-h-screen">
        <Head>
            <title>{{ meta.title }}</title>
            <meta name="description" :content="meta.description" />
            <meta property="og:title" :content="meta.og.title" />
            <meta property="og:description" :content="meta.og.description" />
            <meta property="og:image" :content="meta.og.image" />
            <meta property="og:type" content="article" />
        </Head>

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "{{ solution.name }} Solution — BacklinkPro",
            "description": "{{ meta.description }}",
            "about": {
                "@type": "SoftwareApplication",
                "name": "BacklinkPro",
                "applicationCategory": "SEO Tool"
            }
        }
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <AnnouncementBar />
        <HeaderNav />
        <SolutionSideNav />

        <main id="main-content">
            <SolutionHero :solution="solution" @demo-open="showDemoModal = true" />
            <SolutionOverview :solution="solution" />
            <GoalsSection :solution="solution" />
            <RecommendedWorkflows :solution="solution" />
            <PresetsSection :solution="solution" />
            <ControlsTrust :solution="solution" />
            <ProofEvidence :solution="solution" />
            <SolutionFAQ :faqs="faqs" />
            <RelatedSolutions :related="related" />
            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
        <DemoModal :is-open="showDemoModal" @close="showDemoModal = false" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useReveal } from '../../../Composables/useReveal.js';
import AnnouncementBar from '../../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../../Components/Marketing/HeaderNav.vue';
import Footer from '../../../Components/Marketing/Footer.vue';
import FinalCTA from '../../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../../Components/Marketing/StickyMobileCTA.vue';
import DemoModal from '../../../Components/Marketing/DemoModal.vue';
import SolutionSideNav from '../../../Components/Marketing/Solutions/SolutionSideNav.vue';
import SolutionHero from '../../../Components/Marketing/Solutions/SolutionHero.vue';
import SolutionOverview from '../../../Components/Marketing/Solutions/SolutionOverview.vue';
import GoalsSection from '../../../Components/Marketing/Solutions/GoalsSection.vue';
import RecommendedWorkflows from '../../../Components/Marketing/Solutions/RecommendedWorkflows.vue';
import PresetsSection from '../../../Components/Marketing/Solutions/PresetsSection.vue';
import ControlsTrust from '../../../Components/Marketing/Solutions/ControlsTrust.vue';
import ProofEvidence from '../../../Components/Marketing/Solutions/ProofEvidence.vue';
import SolutionFAQ from '../../../Components/Marketing/Solutions/SolutionFAQ.vue';
import RelatedSolutions from '../../../Components/Marketing/Solutions/RelatedSolutions.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    solution: {
        type: Object,
        required: true,
    },
    related: {
        type: Array,
        default: () => [],
    },
    faqs: {
        type: Array,
        default: () => [],
    },
});

const showDemoModal = ref(false);

const { init } = useReveal();

onMounted(() => {
    init();

    // Add scroll-margin-top for anchor navigation
    const sections = document.querySelectorAll('section[id]');
    sections.forEach((section) => {
        section.style.scrollMarginTop = '100px';
    });

    // Handle hash navigation on mount
    if (window.location.hash) {
        setTimeout(() => {
            const element = document.querySelector(window.location.hash);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
