<template>
    <MarketingLayout :meta="meta">
        <HeroSection @demo-modal-open="showDemoModal = true" />
        <ProofStrip :metrics="initialMetrics" :logos="logos" />
        <ProblemSolution />
        <HowItWorks />
        <FeaturePillars />
        <ProductCarousel />
        <SafetyTrust />
        <WorkflowsGrid />
        <CaseStudies />
        <ComparisonTable />
        <PricingTeaser />
        <FAQ />
        <FinalCTA />
        <DemoModal :is-open="showDemoModal" @close="showDemoModal = false" />
    </MarketingLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import MarketingLayout from '../../Layouts/MarketingLayout.vue';
import { useReveal } from '../../Composables/useReveal.js';
import HeroSection from '../../Components/Marketing/HeroSection.vue';
import ProofStrip from '../../Components/Marketing/ProofStrip.vue';
import ProblemSolution from '../../Components/Marketing/ProblemSolution.vue';
import HowItWorks from '../../Components/Marketing/HowItWorks.vue';
import FeaturePillars from '../../Components/Marketing/FeaturePillars.vue';
import ProductCarousel from '../../Components/Marketing/ProductCarousel.vue';
import SafetyTrust from '../../Components/Marketing/SafetyTrust.vue';
import WorkflowsGrid from '../../Components/Marketing/WorkflowsGrid.vue';
import CaseStudies from '../../Components/Marketing/CaseStudies.vue';
import ComparisonTable from '../../Components/Marketing/ComparisonTable.vue';
import PricingTeaser from '../../Components/Marketing/PricingTeaser.vue';
import FAQ from '../../Components/Marketing/FAQ.vue';
import FinalCTA from '../../Components/Marketing/FinalCTA.vue';
import DemoModal from '../../Components/Marketing/DemoModal.vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    initialMetrics: {
        type: Array,
        required: true,
    },
    logos: {
        type: Array,
        default: () => [],
    },
});

const showDemoModal = ref(false);

const { init } = useReveal();

onMounted(() => {
    init();
    
    // Add scroll-margin-top for anchor navigation
    const sections = document.querySelectorAll('section[id]');
    sections.forEach((section) => {
        section.style.scrollMarginTop = '80px';
    });
});
</script>

<style scoped>
/* Additional page-specific styles if needed */
</style>
