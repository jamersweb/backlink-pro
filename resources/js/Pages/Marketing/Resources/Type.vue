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
            "name": "{{ type.name }} Resources — BacklinkPro",
            "description": "{{ meta.description }}"
        }
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <AnnouncementBar />
        <HeaderNav />

        <main id="main-content">
            <section class="type-hero py-20">
                <div class="marketing-container">
                    <nav class="mb-8" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-2 text-sm text-muted">
                            <li><Link href="/resources" class="hover:text-text">Resources</Link></li>
                            <li>/</li>
                            <li class="text-text">{{ type.name }}</li>
                        </ol>
                    </nav>
                    <h1 class="text-4xl md:text-5xl font-bold mb-6 text-text" data-reveal>
                        {{ type.name }}
                    </h1>
                    <p class="text-xl text-muted mb-8" data-reveal>
                        {{ type.desc }}
                    </p>
                    <div class="marketing-card p-6 max-w-2xl" data-reveal>
                        <h2 class="text-lg font-semibold text-text mb-4">What you'll learn:</h2>
                        <ul class="space-y-2 text-muted">
                            <li>• Safe, repeatable processes with guardrails</li>
                            <li>• Approval workflows and evidence logging</li>
                            <li>• Monitoring and link health tracking</li>
                        </ul>
                    </div>
                </div>
            </section>

            <FiltersBar :topics="topics" @update:filters="filters = $event" @clear-filters="clearFilters" />

            <section class="resources-grid py-20">
                <div class="marketing-container">
                    <div v-if="filteredItems.length === 0" class="text-center py-12">
                        <p class="text-muted">No resources match your filters.</p>
                        <button @click="clearFilters" class="btn-secondary mt-4">
                            Clear filters
                        </button>
                    </div>
                    <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <ResourceCard
                            v-for="(item, idx) in filteredItems"
                            :key="idx"
                            :resource="item"
                        />
                    </div>
                </div>
            </section>

            <ResourcesCTAInline />
            <ResourcesFAQ :faqs="getFAQsForType(type.slug)" />
            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useReveal } from '../../../Composables/useReveal.js';
import AnnouncementBar from '../../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../../Components/Marketing/HeaderNav.vue';
import Footer from '../../../Components/Marketing/Footer.vue';
import FinalCTA from '../../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../../Components/Marketing/StickyMobileCTA.vue';
import FiltersBar from '../../../Components/Marketing/Resources/FiltersBar.vue';
import ResourceCard from '../../../Components/Marketing/Resources/ResourceCard.vue';
import ResourcesCTAInline from '../../../Components/Marketing/Resources/ResourcesCTAInline.vue';
import ResourcesFAQ from '../../../Components/Marketing/Resources/ResourcesFAQ.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    type: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    topics: {
        type: Array,
        required: true,
    },
});

const filters = ref({
    search: '',
    topics: [],
    sort: 'newest',
});

const clearFilters = () => {
    filters.value = {
        search: '',
        topics: [],
        sort: 'newest',
    };
};

const filteredItems = computed(() => {
    let results = [...props.items];

    // Search filter
    if (filters.value.search) {
        const query = filters.value.search.toLowerCase();
        results = results.filter(item =>
            item.title.toLowerCase().includes(query) ||
            item.excerpt.toLowerCase().includes(query) ||
            item.topics.some(topic => topic.toLowerCase().includes(query))
        );
    }

    // Topics filter
    if (filters.value.topics.length > 0) {
        results = results.filter(item =>
            filters.value.topics.some(topic => item.topics.includes(topic))
        );
    }

    // Sort
    if (filters.value.sort === 'newest') {
        results.sort((a, b) => new Date(b.date) - new Date(a.date));
    } else if (filters.value.sort === 'reading-time') {
        results.sort((a, b) => {
            const aTime = parseInt(a.readingTime);
            const bTime = parseInt(b.readingTime);
            return aTime - bTime;
        });
    }

    return results;
});

const getFAQsForType = (typeSlug) => {
    const faqs = {
        playbooks: [
            { q: 'What is a playbook?', a: 'A playbook is a step-by-step system for scaling link building safely with guardrails, approvals, and evidence logs.' },
            { q: 'Are playbooks templates?', a: 'No. Playbooks are processes and systems. Templates are reusable content pieces used within playbooks.' },
        ],
        templates: [
            { q: 'Are templates safe to use?', a: 'Yes. Our templates are designed to avoid spam signals and maintain natural language. They are personalized with context.' },
            { q: 'Can I customize templates?', a: 'Yes. Templates can be customized while maintaining safety principles and natural language.' },
        ],
        guides: [
            { q: 'What topics do guides cover?', a: 'Guides cover safety, approvals, evidence logging, monitoring, anchor distribution, velocity controls, and more.' },
            { q: 'Are guides updated regularly?', a: 'Yes. Guides are updated to reflect best practices and platform changes.' },
        ],
        tools: [
            { q: 'Are tools accurate?', a: 'Tools are demonstration placeholders. Actual calculations use more complex algorithms and real-time data.' },
            { q: 'Do tools require an account?', a: 'No. Tools are available to everyone. Full features require an account.' },
        ],
    };
    return faqs[typeSlug] || [];
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
