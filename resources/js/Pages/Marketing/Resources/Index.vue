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
            "name": "Resources — BacklinkPro",
            "description": "{{ meta.description }}"
        }
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <AnnouncementBar />
        <HeaderNav />

        <main id="main-content">
            <ResourcesHero @demo-open="showDemoModal = true" />
            <ResourceTypeCards :types="types" :all-items="allItems || []" />
            <FeaturedResources :featured="featured" />
            <ResourcesList :resources="latest" />
            <ResourcesCTAInline />
            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
        <DemoModal :is-open="showDemoModal" @close="showDemoModal = false" />
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useReveal } from '../../../Composables/useReveal.js';
import AnnouncementBar from '../../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../../Components/Marketing/HeaderNav.vue';
import Footer from '../../../Components/Marketing/Footer.vue';
import FinalCTA from '../../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../../Components/Marketing/StickyMobileCTA.vue';
import DemoModal from '../../../Components/Marketing/DemoModal.vue';
import ResourcesHero from '../../../Components/Marketing/Resources/ResourcesHero.vue';
import ResourceTypeCards from '../../../Components/Marketing/Resources/ResourceTypeCards.vue';
import FeaturedResources from '../../../Components/Marketing/Resources/FeaturedResources.vue';
import ResourcesList from '../../../Components/Marketing/Resources/ResourcesList.vue';
import ResourcesCTAInline from '../../../Components/Marketing/Resources/ResourcesCTAInline.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        required: true,
    },
    featured: {
        type: Array,
        required: true,
    },
    latest: {
        type: Array,
        required: true,
    },
    topics: {
        type: Array,
        required: true,
    },
    allItems: {
        type: Array,
        default: () => [],
    },
});

import { ref } from 'vue';

const showDemoModal = ref(false);

const allItems = computed(() => {
    return props.allItems || [...props.featured, ...props.latest];
});

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
