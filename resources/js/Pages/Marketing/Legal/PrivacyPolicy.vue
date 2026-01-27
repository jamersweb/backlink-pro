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
            "name": "{{ meta.title }}",
            "description": "{{ meta.description }}",
            "dateModified": "{{ lastUpdated }}"
        }
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <AnnouncementBar />
        <HeaderNav />
        <ReadingProgress />
        <LegalDisclaimer />

        <main id="main-content" class="py-12 md:py-20">
            <LegalLayout>
                <template #hero>
                    <LegalHero :policy="policy" :last-updated="lastUpdated" />
                </template>

                <template #content>
                    <div class="legal-content">
                        <!-- Mobile TOC (shown above content) -->
                        <div class="lg:hidden mb-8">
                            <LegalTOC :sections="policy.sections" />
                        </div>
                        
                        <div class="space-y-8">
                            <LegalSection
                                v-for="(section, idx) in policy.sections"
                                :key="idx"
                                :section="section"
                            />
                        </div>
                        <div class="mt-12 pt-8 border-t border-border">
                            <h2 class="text-2xl font-bold mb-4 text-text">Contact</h2>
                            <p class="text-muted">
                                For privacy questions, contact us at
                                <a :href="`mailto:${contactEmail}`" class="text-primary hover:underline">
                                    {{ contactEmail }}
                                </a>
                            </p>
                        </div>
                    </div>
                </template>

                <template #toc>
                    <!-- Desktop TOC (shown in sidebar) -->
                    <LegalTOC :sections="policy.sections" />
                </template>
            </LegalLayout>
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
import StickyMobileCTA from '../../../Components/Marketing/StickyMobileCTA.vue';
import ReadingProgress from '../../../Components/Marketing/Legal/ReadingProgress.vue';
import LegalDisclaimer from '../../../Components/Marketing/Legal/LegalDisclaimer.vue';
import LegalLayout from '../../../Components/Marketing/Legal/LegalLayout.vue';
import LegalHero from '../../../Components/Marketing/Legal/LegalHero.vue';
import LegalTOC from '../../../Components/Marketing/Legal/LegalTOC.vue';
import LegalSection from '../../../Components/Marketing/Legal/LegalSection.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    policy: {
        type: Object,
        required: true,
    },
    lastUpdated: {
        type: String,
        required: true,
    },
    contactEmail: {
        type: String,
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

/* Print styles */
@media print {
    .marketing-dark {
        background: white;
    }

    .legal-content {
        color: #000;
    }

    .legal-content :deep(.text-muted) {
        color: #333;
    }

    .legal-content :deep(.text-text) {
        color: #000;
    }

    .legal-content :deep(.marketing-card) {
        border: 1px solid #ddd;
        box-shadow: none;
    }
}
</style>
