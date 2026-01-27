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
            <AboutHero />
            <MetricsStrip :metrics="metrics" />
            <MissionStory />
            <PrinciplesGrid :principles="principles" />
            <ValuesBlock :values="values" />
            <TimelineRoadmap :timeline="timeline" />
            <TrustCallout />
            <AboutFAQ :faqs="faqs" />
            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useReveal } from '../../Composables/useReveal.js';
import AnnouncementBar from '../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../Components/Marketing/HeaderNav.vue';
import Footer from '../../Components/Marketing/Footer.vue';
import FinalCTA from '../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../Components/Marketing/StickyMobileCTA.vue';
import AboutHero from '../../Components/Marketing/About/AboutHero.vue';
import MetricsStrip from '../../Components/Marketing/About/MetricsStrip.vue';
import MissionStory from '../../Components/Marketing/About/MissionStory.vue';
import PrinciplesGrid from '../../Components/Marketing/About/PrinciplesGrid.vue';
import ValuesBlock from '../../Components/Marketing/About/ValuesBlock.vue';
import TimelineRoadmap from '../../Components/Marketing/About/TimelineRoadmap.vue';
import TrustCallout from '../../Components/Marketing/About/TrustCallout.vue';
import AboutFAQ from '../../Components/Marketing/About/AboutFAQ.vue';
import DisclosuresBar from '../../Components/Marketing/About/DisclosuresBar.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    principles: {
        type: Array,
        required: true,
    },
    timeline: {
        type: Array,
        required: true,
    },
    values: {
        type: Array,
        required: true,
    },
    metrics: {
        type: Array,
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
