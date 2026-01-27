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
            "@type": "Article",
            "name": "{{ item.title }}",
            "description": "{{ meta.description }}",
            "datePublished": "{{ item.date }}",
            "author": {
                "@type": "Organization",
                "name": "BacklinkPro"
            }
        }
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <AnnouncementBar />
        <HeaderNav />
        <ReadingProgress />
        <ArticleSideNav :sections="item.sections" />

        <main id="main-content">
            <ArticleLayout :item="item">
                <ArticleSections :sections="item.sections" />

                <!-- Tool Widget -->
                <div v-if="item.toolWidget">
                    <ToolWidgetRiskCalculator v-if="item.toolWidget === 'riskScoreCalculator'" />
                    <ToolWidgetAnchorMixPlanner v-if="item.toolWidget === 'anchorMixPlanner'" />
                    <ToolWidgetVelocityPlanner v-if="item.toolWidget === 'velocityPlanner'" />
                </div>

                <!-- CTA -->
                <div class="mt-12 flex flex-wrap gap-4 justify-center" data-reveal>
                    <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                        {{ item.cta.primary }}
                    </a>
                    <Link href="/pricing" class="btn-secondary text-lg px-8 py-4">
                        {{ item.cta.secondary }}
                    </Link>
                </div>
            </ArticleLayout>

            <RelatedResources :related="related" />
            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useReveal } from '../../../Composables/useReveal.js';
import AnnouncementBar from '../../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../../Components/Marketing/HeaderNav.vue';
import Footer from '../../../Components/Marketing/Footer.vue';
import FinalCTA from '../../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../../Components/Marketing/StickyMobileCTA.vue';
import ReadingProgress from '../../../Components/Marketing/Resources/ReadingProgress.vue';
import ArticleSideNav from '../../../Components/Marketing/Resources/ArticleSideNav.vue';
import ArticleLayout from '../../../Components/Marketing/Resources/ArticleLayout.vue';
import ArticleSections from '../../../Components/Marketing/Resources/ArticleSections.vue';
import ToolWidgetRiskCalculator from '../../../Components/Marketing/Resources/ToolWidgetRiskCalculator.vue';
import ToolWidgetAnchorMixPlanner from '../../../Components/Marketing/Resources/ToolWidgetAnchorMixPlanner.vue';
import ToolWidgetVelocityPlanner from '../../../Components/Marketing/Resources/ToolWidgetVelocityPlanner.vue';
import RelatedResources from '../../../Components/Marketing/Resources/RelatedResources.vue';

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
        default: () => [],
    },
});

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const { init } = useReveal();

onMounted(() => {
    init();

    // Add scroll-margin-top for anchor navigation
    const sections = document.querySelectorAll('[id^="section-"]');
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
