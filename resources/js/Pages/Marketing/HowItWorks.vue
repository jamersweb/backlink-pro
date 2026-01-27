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
        <SideNav />

        <main id="main-content">
            <HowHero @demo-open="showDemoModal = true" />
            <WorkflowDiagram />
            <StepByStep :steps="steps" />
            <GuardrailsSection :guardrails="guardrails" />
            <EvidenceReporting />
            <WorkflowCards :workflows="workflows" />
            <IntegrationsTeaser />
            <FAQ :faqs="faq" />
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
import { useReveal } from '../../Composables/useReveal.js';
import AnnouncementBar from '../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../Components/Marketing/HeaderNav.vue';
import Footer from '../../Components/Marketing/Footer.vue';
import FinalCTA from '../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../Components/Marketing/StickyMobileCTA.vue';
import DemoModal from '../../Components/Marketing/DemoModal.vue';
import SideNav from '../../Components/Marketing/HowItWorks/SideNav.vue';
import HowHero from '../../Components/Marketing/HowItWorks/HowHero.vue';
import WorkflowDiagram from '../../Components/Marketing/HowItWorks/WorkflowDiagram.vue';
import StepByStep from '../../Components/Marketing/HowItWorks/StepByStep.vue';
import GuardrailsSection from '../../Components/Marketing/HowItWorks/GuardrailsSection.vue';
import EvidenceReporting from '../../Components/Marketing/HowItWorks/EvidenceReporting.vue';
import WorkflowCards from '../../Components/Marketing/HowItWorks/WorkflowCards.vue';
import IntegrationsTeaser from '../../Components/Marketing/HowItWorks/IntegrationsTeaser.vue';
import FAQ from '../../Components/Marketing/HowItWorks/FAQ.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    steps: {
        type: Array,
        required: true,
    },
    workflows: {
        type: Array,
        required: true,
    },
    guardrails: {
        type: Object,
        required: true,
    },
    faq: {
        type: Array,
        required: true,
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
