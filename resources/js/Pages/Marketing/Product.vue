<template>
    <div class="marketing-dark min-h-screen product-overview-page">
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

        <HeaderNav />

        <main id="main-content">
            <ProductHero :badges="heroBadges" />

            <div class="marketing-container product-overview-shell max-w-7xl mx-auto">
                <ProductSideNav />

                <div class="product-overview-grid">
                    <ModuleTabs :modules="modules" />
                    <WorkflowStrip :workflows="workflows" />
                    <GuardrailsBlock :guardrails="guardrails" />
                    <ReportsPreview :reports="reports" />
                </div>
            </div>
        </main>

        <Footer />
        <StickyMobileCTA />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useReveal } from '../../Composables/useReveal.js';
import HeaderNav from '../../Components/Marketing/HeaderNav.vue';
import Footer from '../../Components/Marketing/Footer.vue';
import StickyMobileCTA from '../../Components/Marketing/StickyMobileCTA.vue';
import ProductHero from '../../Components/Marketing/Product/ProductHero.vue';
import ProductSideNav from '../../Components/Marketing/Product/ProductSideNav.vue';
import ModuleTabs from '../../Components/Marketing/Product/ModuleTabs.vue';
import WorkflowStrip from '../../Components/Marketing/Product/WorkflowStrip.vue';
import GuardrailsBlock from '../../Components/Marketing/Product/GuardrailsBlock.vue';
import ReportsPreview from '../../Components/Marketing/Product/ReportsPreview.vue';

defineProps({
    meta: {
        type: Object,
        required: true,
    },
    heroBadges: {
        type: Array,
        required: true,
    },
    modules: {
        type: Array,
        required: true,
    },
    workflows: {
        type: Array,
        required: true,
    },
    guardrails: {
        type: Object,
        required: true,
    },
    reports: {
        type: Array,
        required: true,
    },
    faqs: {
        type: Array,
        required: false,
        default: () => [],
    },
    disclosures: {
        type: Array,
        required: false,
        default: () => [],
    },
});

const { init } = useReveal();

onMounted(() => {
    init();

    const sections = document.querySelectorAll('section[id]');
    sections.forEach((section) => {
        section.style.scrollMarginTop = '120px';
    });

    if (window.location.hash) {
        setTimeout(() => {
            const element = document.querySelector(window.location.hash);
            if (element) {
                const offset = 120;
                const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                const offsetPosition = elementPosition - offset;
                window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
            }
        }, 100);
    }
});
</script>

<style scoped>
.product-overview-page {
    background:
        radial-gradient(circle at top, rgba(255, 110, 64, 0.15), transparent 30%),
        radial-gradient(circle at top right, rgba(255, 110, 64, 0.08), transparent 22%),
        linear-gradient(180deg, #080808 0%, #050505 100%);
}

.product-overview-shell {
    position: relative;
    z-index: 1;
    padding-top: 1.35rem;
    padding-bottom: 5rem;
}

.product-overview-grid {
    display: grid;
    gap: 0;
}

:deep(.header-nav) {
    background: linear-gradient(180deg, rgba(5, 5, 5, 0.78), rgba(5, 5, 5, 0)) !important;
}

:deep(.product-hero) {
    min-height: 52vh;
    display: flex;
    align-items: center;
    background:
        radial-gradient(circle at 18% 20%, rgba(255, 110, 64, 0.18), transparent 22%),
        linear-gradient(180deg, rgba(14, 11, 11, 0.78), rgba(6, 6, 6, 0.94));
}

:deep(.product-hero .marketing-container) {
    max-width: 74rem !important;
}

:deep(.product-hero .text-text) {
    color: #fff7f2 !important;
    letter-spacing: -0.04em;
}

:deep(.product-hero .text-muted) {
    color: rgba(255, 240, 232, 0.7) !important;
}

:deep(.product-hero .bg-surface2) {
    background: rgba(255, 110, 64, 0.1) !important;
}

:deep(.product-hero .border-border) {
    border-color: rgba(255, 110, 64, 0.16) !important;
}

:deep(.marketing-card),
:deep(.workflow-strip .marketing-card),
:deep(.reports-preview .marketing-card),
:deep(.product-side-nav .marketing-card),
:deep(.module-tabs .marketing-card) {
    background:
        linear-gradient(180deg, rgba(22, 18, 18, 0.94), rgba(10, 10, 10, 0.98)) !important;
    border: 1px solid rgba(255, 110, 64, 0.18) !important;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.03),
        0 24px 60px rgba(0, 0, 0, 0.22);
    backdrop-filter: blur(16px);
}

:deep(.module-tabs),
:deep(.guardrails-block) {
    padding-top: 5.5rem !important;
    padding-bottom: 5.5rem !important;
}

:deep(.workflow-strip),
:deep(.reports-preview) {
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.015), rgba(255, 255, 255, 0)),
        radial-gradient(circle at top, rgba(255, 110, 64, 0.07), transparent 38%) !important;
    padding-top: 5.5rem !important;
    padding-bottom: 5.5rem !important;
}

:deep(.module-tabs h2),
:deep(.workflow-strip h2),
:deep(.guardrails-block h2),
:deep(.reports-preview h2),
:deep(.product-side-nav h3) {
    color: #fff7f2 !important;
    letter-spacing: -0.04em;
}

:deep(.module-tabs button),
:deep(.workflow-strip h3),
:deep(.reports-preview h3),
:deep(.product-side-nav h3) {
    font-family: "Manrope", Inter, sans-serif;
}

:deep(.module-tabs .bg-primary),
:deep(.guardrails-block .bg-primary\/10) {
    background: rgba(255, 110, 64, 0.14) !important;
}

:deep(.module-tabs .text-white),
:deep(.module-tabs .text-primary),
:deep(.workflow-strip .text-primary),
:deep(.reports-preview .text-primary),
:deep(.product-side-nav .text-primary) {
    color: #ff8a65 !important;
}

:deep(.text-success) {
    color: #ff9d7b !important;
}

:deep(.text-danger) {
    color: #ffb198 !important;
}

:deep(.text-muted) {
    color: rgba(255, 240, 232, 0.58) !important;
}

:deep(.text-text) {
    color: #fff7f2 !important;
}

:deep(.workflow-strip a.btn-secondary),
:deep(.reports-preview a.btn-secondary),
:deep(.product-hero a.btn-secondary) {
    background: rgba(255, 110, 64, 0.1) !important;
    border: 1px solid rgba(255, 110, 64, 0.28) !important;
    color: #fff7f2 !important;
}

:deep(.product-hero a.btn-primary) {
    background: linear-gradient(180deg, #fff7f2, #ffe7db) !important;
    color: #16100d !important;
    border: 1px solid rgba(255, 224, 208, 0.72);
    box-shadow: 0 18px 40px rgba(255, 110, 64, 0.18);
}

:deep(.border-border) {
    border-color: rgba(255, 110, 64, 0.16) !important;
}

@media (max-width: 900px) {
    .product-overview-shell {
        padding-top: 1rem;
    }
}

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
