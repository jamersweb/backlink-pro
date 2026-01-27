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
            "@type": "CollectionPage",
            "name": "Solutions — BacklinkPro",
            "description": "{{ meta.description }}"
        }
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <AnnouncementBar />
        <HeaderNav />

        <main id="main-content">
            <SolutionsHero @demo-open="showDemoModal = true" />
            <div class="py-8 bg-surface2 border-b border-border">
                <div class="marketing-container">
                    <StageSelector :stages="stages" @update:stage="selectedStage = $event" />
                    <div class="mt-4">
                        <SegmentChips :segments="segments" @update:segments="selectedSegments = $event" />
                    </div>
                </div>
            </div>
            <FiltersBar :solutions="solutions" @update:filters="filters = $event" @clear-filters="clearFilters" />
            <SolutionGrid :solutions="solutions" :filters="filters" :selected-segments="selectedSegments" @clear-filters="clearFilters" />
            <CTAInline />
            <SolutionsFAQ :faqs="faqs" />
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
import SolutionsHero from '../../../Components/Marketing/Solutions/SolutionsHero.vue';
import StageSelector from '../../../Components/Marketing/Solutions/StageSelector.vue';
import SegmentChips from '../../../Components/Marketing/Solutions/SegmentChips.vue';
import FiltersBar from '../../../Components/Marketing/Solutions/FiltersBar.vue';
import SolutionGrid from '../../../Components/Marketing/Solutions/SolutionGrid.vue';
import CTAInline from '../../../Components/Marketing/Solutions/CTAInline.vue';
import SolutionsFAQ from '../../../Components/Marketing/Solutions/SolutionsFAQ.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    solutions: {
        type: Array,
        required: true,
    },
    segments: {
        type: Array,
        required: true,
    },
    stages: {
        type: Array,
        required: true,
    },
    faqs: {
        type: Array,
        required: true,
    },
});

const showDemoModal = ref(false);
const selectedStage = ref('Growth');
const selectedSegments = ref([]);
const filters = ref({
    search: '',
    tags: [],
    sort: 'relevant',
});

const clearFilters = () => {
    filters.value = {
        search: '',
        tags: [],
        sort: 'relevant',
    };
};

const { init } = useReveal();

onMounted(() => {
    init();
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
