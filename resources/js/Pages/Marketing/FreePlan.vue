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
            <FreePlanHero />

            <section class="py-20">
                <div class="marketing-container max-w-7xl mx-auto">
                    <div class="grid md:grid-cols-3 gap-8">
                        <!-- Left: Form -->
                        <div class="md:col-span-2">
                            <FreePlanForm
                                :segments="segments"
                                :risk-modes="riskModes"
                                :goals="goals"
                                @success="showSuccessToast = true"
                            />
                        </div>

                        <!-- Right: Trust Sidebar -->
                        <div>
                            <TrustSidebar />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Plan Preview (shown after form submission) -->
            <PlanPreview
                v-if="plan"
                :plan="plan"
                :workflows="workflows"
                :email="formEmail"
            />

            <FAQ :faqs="faqs" />
        </main>

        <Footer />
        <StickyMobileCTA />
        <SuccessToast
            :show="showSuccessToast"
            @close="showSuccessToast = false"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useReveal } from '../../Composables/useReveal.js';
import AnnouncementBar from '../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../Components/Marketing/HeaderNav.vue';
import Footer from '../../Components/Marketing/Footer.vue';
import StickyMobileCTA from '../../Components/Marketing/StickyMobileCTA.vue';
import FreePlanHero from '../../Components/Marketing/FreePlan/FreePlanHero.vue';
import FreePlanForm from '../../Components/Marketing/FreePlan/FreePlanForm.vue';
import TrustSidebar from '../../Components/Marketing/FreePlan/TrustSidebar.vue';
import PlanPreview from '../../Components/Marketing/FreePlan/PlanPreview.vue';
import FAQ from '../../Components/Marketing/FreePlan/FAQ.vue';
import SuccessToast from '../../Components/Marketing/FreePlan/SuccessToast.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    segments: {
        type: Array,
        required: true,
    },
    riskModes: {
        type: Array,
        required: true,
    },
    goals: {
        type: Array,
        required: true,
    },
    workflows: {
        type: Array,
        required: true,
    },
    faqs: {
        type: Array,
        required: true,
    },
});

const showSuccessToast = ref(false);

// Get plan from flash data
const page = usePage();
const plan = computed(() => {
    return page.props.flash?.plan || null;
});

// Extract email from form data if available (stored in session or from form)
const formEmail = computed(() => {
    // Try to get from flash data or form state
    return page.props.flash?.email || '';
});

const { init } = useReveal();

onMounted(() => {
    init();

    // Check for flash success message
    if (page.props.flash?.success) {
        showSuccessToast.value = true;
        setTimeout(() => {
            showSuccessToast.value = false;
        }, 5000);
    }

    // Add scroll-margin-top for anchor navigation
    const sections = document.querySelectorAll('section[id]');
    sections.forEach((section) => {
        section.style.scrollMarginTop = '120px';
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
