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

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "Security & Trust — BacklinkPro",
            "description": "{{ meta.description }}"
        }
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <AnnouncementBar />
        <HeaderNav />
        <SecuritySideNav />
        <DisclosureBar :disclosures="disclosures" />

        <main id="main-content">
            <SecurityHero />
            <TrustHighlights :trust-points="trustPoints" />

            <!-- Render sections dynamically -->
            <template v-for="(section, idx) in sections" :key="idx">
                <!-- Overview section -->
                <SecuritySection v-if="section.id === 'overview'" :section="section" />

                <!-- Data handling section -->
                <DataHandling v-else-if="section.id === 'data'" :section="section" />

                <!-- Access control section -->
                <SecuritySection v-else-if="section.id === 'access'" :section="section" />

                <!-- Automation guardrails section -->
                <GuardrailsBlock v-else-if="section.id === 'automation'" :section="section" />

                <!-- Reliability section -->
                <ReliabilityBlock v-else-if="section.id === 'reliability'" :section="section" />

                <!-- Vulnerability reporting section -->
                <VulnDisclosure v-else-if="section.id === 'vuln'" :section="section" />
            </template>

            <SecurityFAQ :faqs="faqs" />
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
import SecurityHero from '../../Components/Marketing/Security/SecurityHero.vue';
import TrustHighlights from '../../Components/Marketing/Security/TrustHighlights.vue';
import SecuritySideNav from '../../Components/Marketing/Security/SecuritySideNav.vue';
import SecuritySection from '../../Components/Marketing/Security/SecuritySection.vue';
import DataHandling from '../../Components/Marketing/Security/DataHandling.vue';
import GuardrailsBlock from '../../Components/Marketing/Security/GuardrailsBlock.vue';
import ReliabilityBlock from '../../Components/Marketing/Security/ReliabilityBlock.vue';
import VulnDisclosure from '../../Components/Marketing/Security/VulnDisclosure.vue';
import SecurityFAQ from '../../Components/Marketing/Security/SecurityFAQ.vue';
import DisclosureBar from '../../Components/Marketing/Security/DisclosureBar.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    sections: {
        type: Array,
        required: true,
    },
    faqs: {
        type: Array,
        required: true,
    },
    trustPoints: {
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
