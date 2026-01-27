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
            "@type": "DefinedTermSet",
            "name": "BacklinkPro Glossary",
            "description": "Definitions for link building, safety, approvals, evidence logs, monitoring, and AI-driven SEO terms."
        }
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded">
            Skip to main content
        </a>

        <AnnouncementBar />
        <HeaderNav />

        <main id="main-content">
            <section class="glossary-hero py-20">
                <div class="marketing-container">
                    <h1 class="text-4xl md:text-5xl font-bold mb-6 text-text text-center" data-reveal>
                        Glossary
                    </h1>
                    <p class="text-xl text-muted mb-8 text-center max-w-3xl mx-auto" data-reveal>
                        Definitions for link building, safety, approvals, evidence logs, monitoring, and AI-driven SEO terms.
                    </p>

                    <!-- Search -->
                    <div class="max-w-2xl mx-auto mb-8" data-reveal>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search terms..."
                            class="w-full px-4 py-3 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                    </div>

                    <!-- Alphabet Jump Links -->
                    <div class="flex flex-wrap gap-2 justify-center mb-12" data-reveal>
                        <a
                            v-for="letter in alphabet"
                            :key="letter"
                            :href="`#letter-${letter}`"
                            @click.prevent="scrollToLetter(letter)"
                            class="px-3 py-1 rounded bg-surface border border-border text-muted hover:text-text hover:bg-surface2 transition-colors"
                        >
                            {{ letter }}
                        </a>
                    </div>
                </div>
            </section>

            <!-- Terms by Letter -->
            <section class="glossary-terms py-20">
                <div class="marketing-container max-w-4xl mx-auto">
                    <div
                        v-for="letter in alphabet"
                        :key="letter"
                        :id="`letter-${letter}`"
                        class="mb-12 scroll-mt-24"
                    >
                        <h2 class="text-3xl font-bold mb-6 text-text" data-reveal>
                            {{ letter }}
                        </h2>
                        <div class="space-y-4">
                            <div
                                v-for="(term, idx) in getTermsForLetter(letter)"
                                :key="idx"
                                data-reveal
                                class="marketing-card"
                            >
                                <button
                                    @click="toggleTerm(`${letter}-${idx}`)"
                                    @keydown.enter="toggleTerm(`${letter}-${idx}`)"
                                    @keydown.space.prevent="toggleTerm(`${letter}-${idx}`)"
                                    class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-primary rounded-lg p-2 -m-2"
                                    :aria-expanded="openTerms.includes(`${letter}-${idx}`)"
                                    :aria-controls="`term-${letter}-${idx}`"
                                >
                                    <h3 class="text-lg font-semibold text-text pr-4">{{ term.term }}</h3>
                                    <svg
                                        :class="[
                                            'w-6 h-6 text-muted flex-shrink-0 transition-transform',
                                            openTerms.includes(`${letter}-${idx}`) && 'rotate-180'
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div
                                    :id="`term-${letter}-${idx}`"
                                    v-show="openTerms.includes(`${letter}-${idx}`)"
                                    class="mt-4 pt-4 border-t border-border text-muted"
                                >
                                    <p>{{ term.def }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

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

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    terms: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref('');
const openTerms = ref([]);

const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

const filteredTerms = computed(() => {
    if (!searchQuery.value) return props.terms;
    const query = searchQuery.value.toLowerCase();
    return props.terms.filter(term =>
        term.term.toLowerCase().includes(query) ||
        term.def.toLowerCase().includes(query)
    );
});

const getTermsForLetter = (letter) => {
    return filteredTerms.value.filter(term =>
        term.term.charAt(0).toUpperCase() === letter
    );
};

const toggleTerm = (id) => {
    const index = openTerms.value.indexOf(id);
    if (index > -1) {
        openTerms.value.splice(index, 1);
    } else {
        openTerms.value.push(id);
    }
};

const scrollToLetter = (letter) => {
    const element = document.getElementById(`letter-${letter}`);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const { init } = useReveal();

onMounted(() => {
    init();

    // Add scroll-margin-top for anchor navigation
    const sections = document.querySelectorAll('[id^="letter-"]');
    sections.forEach((section) => {
        section.style.scrollMarginTop = '100px';
    });
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
