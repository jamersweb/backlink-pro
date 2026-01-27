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

        <main id="main-content">
            <CaseStudiesHero />

            <section class="py-20">
                <div class="marketing-container max-w-7xl mx-auto">
                    <FiltersBar
                        :segments="filters.segments"
                        :risk-modes="filters.riskModes"
                        @filter="handleFilter"
                        @sort="handleSort"
                        @search="handleSearch"
                    />
                    <CaseStudyGrid
                        :items="items"
                        :filtered-items="filteredItems"
                    />
                </div>
            </section>

            <ProofCallouts />

            <CaseStudiesFAQ :faqs="faqs" />

            <div class="py-8 bg-warning/10 border-y border-warning/20">
                <div class="marketing-container">
                    <p class="text-center text-sm text-muted">
                        Outcomes vary. No guaranteed links. This case study uses directional metrics and time windows.
                    </p>
                </div>
            </div>

            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useReveal } from '../../../Composables/useReveal.js';
import AnnouncementBar from '../../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../../Components/Marketing/HeaderNav.vue';
import Footer from '../../../Components/Marketing/Footer.vue';
import FinalCTA from '../../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../../Components/Marketing/StickyMobileCTA.vue';
import CaseStudiesHero from '../../../Components/Marketing/CaseStudies/CaseStudiesHero.vue';
import FiltersBar from '../../../Components/Marketing/CaseStudies/FiltersBar.vue';
import CaseStudyGrid from '../../../Components/Marketing/CaseStudies/CaseStudyGrid.vue';
import ProofCallouts from '../../../Components/Marketing/CaseStudies/ProofCallouts.vue';
import CaseStudiesFAQ from '../../../Components/Marketing/CaseStudies/CaseStudiesFAQ.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    faqs: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref('');
const selectedSegments = ref([]);
const selectedRiskModes = ref([]);
const sortBy = ref('relevant');

const filteredItems = computed(() => {
    let result = [...props.items];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(item => {
            return item.title.toLowerCase().includes(query) ||
                   item.excerpt.toLowerCase().includes(query) ||
                   item.segment.toLowerCase().includes(query);
        });
    }

    // Segment filter
    if (selectedSegments.value.length > 0) {
        result = result.filter(item => selectedSegments.value.includes(item.segment));
    }

    // Risk mode filter
    if (selectedRiskModes.value.length > 0) {
        result = result.filter(item => selectedRiskModes.value.includes(item.riskMode));
    }

    // Sort
    if (sortBy.value === 'shortest') {
        result.sort((a, b) => {
            const aWeeks = parseInt(a.duration) || 999;
            const bWeeks = parseInt(b.duration) || 999;
            return aWeeks - bWeeks;
        });
    } else if (sortBy.value === 'lowest') {
        const riskOrder = { 'Conservative': 1, 'Balanced': 2, 'Growth': 3 };
        result.sort((a, b) => {
            return (riskOrder[a.riskMode] || 999) - (riskOrder[b.riskMode] || 999);
        });
    }

    return result;
});

const handleFilter = (filters) => {
    selectedSegments.value = filters.segments;
    selectedRiskModes.value = filters.riskModes;
};

const handleSort = (sort) => {
    sortBy.value = sort;
};

const handleSearch = (query) => {
    searchQuery.value = query;
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
