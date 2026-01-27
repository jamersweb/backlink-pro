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
            <PartnersHero :testimonials="testimonials" @scroll-to-form="scrollToForm" />
            <PartnerTypeCards :tiers="tiers" @select-tier="handleTierSelect" />
            <BenefitsGrid :benefits="benefits" />
            <HowItWorksSteps :steps="howItWorks" />
            <PartnerTiers :tiers="tiers" @select-tier="handleTierSelect" />
            <RequirementsBlock :requirements="requirements" />

            <section id="partner-apply-section" class="partner-apply-section py-20 bg-surface2 scroll-mt-24">
                <div class="marketing-container max-w-4xl mx-auto">
                    <PartnerApplyForm
                        :partner-types="partnerTypes"
                        :company-sizes="companySizes"
                        :client-counts="clientCounts"
                        :selected-tier="selectedTier"
                        @success="showSuccessToast = true"
                    />
                </div>
            </section>

            <PartnerFAQ :faqs="faqs" />
            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
        <SuccessToast
            :show="showSuccessToast"
            message="Thanks! Your application was received."
            @close="showSuccessToast = false"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useReveal } from '../../Composables/useReveal.js';
import AnnouncementBar from '../../Components/Marketing/AnnouncementBar.vue';
import HeaderNav from '../../Components/Marketing/HeaderNav.vue';
import Footer from '../../Components/Marketing/Footer.vue';
import FinalCTA from '../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../Components/Marketing/StickyMobileCTA.vue';
import PartnersHero from '../../Components/Marketing/Partners/PartnersHero.vue';
import PartnerTypeCards from '../../Components/Marketing/Partners/PartnerTypeCards.vue';
import BenefitsGrid from '../../Components/Marketing/Partners/BenefitsGrid.vue';
import HowItWorksSteps from '../../Components/Marketing/Partners/HowItWorksSteps.vue';
import PartnerTiers from '../../Components/Marketing/Partners/PartnerTiers.vue';
import RequirementsBlock from '../../Components/Marketing/Partners/RequirementsBlock.vue';
import PartnerApplyForm from '../../Components/Marketing/Partners/PartnerApplyForm.vue';
import PartnerFAQ from '../../Components/Marketing/Partners/PartnerFAQ.vue';
import DisclosuresBar from '../../Components/Marketing/Partners/DisclosuresBar.vue';
import SuccessToast from '../../Components/Marketing/Contact/SuccessToast.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    partnerTypes: {
        type: Array,
        required: true,
    },
    companySizes: {
        type: Array,
        required: true,
    },
    clientCounts: {
        type: Array,
        required: true,
    },
    benefits: {
        type: Array,
        required: true,
    },
    tiers: {
        type: Array,
        required: true,
    },
    requirements: {
        type: Array,
        required: true,
    },
    howItWorks: {
        type: Array,
        required: true,
    },
    faqs: {
        type: Array,
        required: true,
    },
    testimonials: {
        type: Array,
        required: true,
    },
    disclosures: {
        type: Array,
        required: true,
    },
});

const showSuccessToast = ref(false);
const selectedTier = ref('');

const scrollToForm = () => {
    const formSection = document.getElementById('partner-apply-section');
    if (formSection) {
        formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const handleTierSelect = (tierId) => {
    selectedTier.value = tierId;
    if (typeof window !== 'undefined') {
        localStorage.setItem('bp_partner_type', tierId);
    }
    scrollToForm();
};

const { init } = useReveal();

onMounted(() => {
    init();

    // Check for flash success message
    const page = usePage();
    if (page.props.flash?.success) {
        showSuccessToast.value = true;
        // Auto-hide after 5 seconds
        setTimeout(() => {
            showSuccessToast.value = false;
        }, 5000);
    }

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
