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
            <PricingHero />

            <section class="py-20">
                <div class="marketing-container max-w-7xl mx-auto">
                    <BillingToggle
                        :cycles="billingCycles"
                        v-model="billingCycle"
                    />
                    <PlanCards
                        :plans="plans"
                        :billing-cycle="billingCycle"
                    />
                </div>
            </section>

            <WhichPlanPicker />

            <section class="py-20">
                <div class="marketing-container max-w-5xl mx-auto">
                    <GuaranteeBox :guarantee-box="guaranteeBox" />
                </div>
            </section>

            <FeatureComparison
                :plans="plans"
                :feature-groups="featureGroups"
                :matrix="matrix"
            />

            <AddOns :add-ons="addOns" />

            <PricingFAQ :faqs="faqs" />

            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useReveal } from '../../Composables/useReveal.js';
import AnnouncementBar from '../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../Components/Marketing/HeaderNav.vue';
import Footer from '../../Components/Marketing/Footer.vue';
import FinalCTA from '../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../Components/Marketing/StickyMobileCTA.vue';
import PricingHero from '../../Components/Marketing/Pricing/PricingHero.vue';
import BillingToggle from '../../Components/Marketing/Pricing/BillingToggle.vue';
import PlanCards from '../../Components/Marketing/Pricing/PlanCards.vue';
import WhichPlanPicker from '../../Components/Marketing/Pricing/WhichPlanPicker.vue';
import GuaranteeBox from '../../Components/Marketing/Pricing/GuaranteeBox.vue';
import FeatureComparison from '../../Components/Marketing/Pricing/FeatureComparison.vue';
import AddOns from '../../Components/Marketing/Pricing/AddOns.vue';
import PricingFAQ from '../../Components/Marketing/Pricing/PricingFAQ.vue';
import DisclosuresBar from '../../Components/Marketing/Pricing/DisclosuresBar.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    billingCycles: {
        type: Array,
        required: true,
    },
    plans: {
        type: Array,
        required: true,
    },
    featureGroups: {
        type: Array,
        required: true,
    },
    matrix: {
        type: Object,
        required: true,
    },
    addOns: {
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
    guaranteeBox: {
        type: Object,
        required: true,
    },
});

const billingCycle = ref('monthly');

const { init } = useReveal();

onMounted(() => {
    init();

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
