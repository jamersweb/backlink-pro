<template>
    <div class="marketing-dark min-h-screen contact-sales-page marketing-linked-page">
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
            <ContactHero :testimonials="testimonials" :logos="logos" />

            <section id="contact-form-section" class="contact-form-section py-20">
                <div class="marketing-container">
                    <ContactTabs @update:tab="activeTab = $event" />

                    <!-- Contact Tab Content -->
                    <div v-show="activeTab === 'contact'" class="mt-8">
                        <div class="contact-sales-grid grid md:grid-cols-3 gap-8">
                            <!-- Left: Form -->
                            <div class="md:col-span-2">
                                <ContactForm
                                    :inquiry-types="inquiryTypes"
                                    :segments="segments"
                                    :budgets="budgets"
                                    @success="showSuccessToast = true"
                                />
                            </div>

                            <!-- Right: Sidebar -->
                            <div class="space-y-6">
                                <TrustSidebar />
                                <ResponseExpectations />
                            </div>
                        </div>
                    </div>

                    <!-- Demo Tab Content -->
                    <div v-show="activeTab === 'demo'" class="mt-8">
                        <DemoBookingPanel
                            :demo-embed-url="demoEmbedUrl"
                            @scroll-to-form="scrollToForm"
                        />
                    </div>
                </div>
            </section>

            <ContactFAQ :faqs="contactFAQs" />
            <FinalCTA />
        </main>

        <Footer />
        <StickyMobileCTA />
        <SuccessToast :show="showSuccessToast" @close="showSuccessToast = false" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useReveal } from '../../Composables/useReveal.js';
import HeaderNav from '../../Components/Marketing/HeaderNav.vue';
import Footer from '../../Components/Marketing/Footer.vue';
import FinalCTA from '../../Components/Marketing/FinalCTA.vue';
import StickyMobileCTA from '../../Components/Marketing/StickyMobileCTA.vue';
import ContactHero from '../../Components/Marketing/Contact/ContactHero.vue';
import ContactTabs from '../../Components/Marketing/Contact/ContactTabs.vue';
import ContactForm from '../../Components/Marketing/Contact/ContactForm.vue';
import DemoBookingPanel from '../../Components/Marketing/Contact/DemoBookingPanel.vue';
import TrustSidebar from '../../Components/Marketing/Contact/TrustSidebar.vue';
import ResponseExpectations from '../../Components/Marketing/Contact/ResponseExpectations.vue';
import ContactFAQ from '../../Components/Marketing/Contact/ContactFAQ.vue';
import SuccessToast from '../../Components/Marketing/Contact/SuccessToast.vue';
import './shared-linked-page-theme.css';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
    inquiryTypes: {
        type: Array,
        required: true,
    },
    segments: {
        type: Array,
        required: true,
    },
    budgets: {
        type: Array,
        required: true,
    },
    demoEmbedUrl: {
        type: String,
        default: null,
    },
    logos: {
        type: Array,
        default: () => [],
    },
    testimonials: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('contact');
const showSuccessToast = ref(false);

const contactFAQs = [
    {
        q: 'Do you guarantee backlinks?',
        a: 'No. Placements depend on moderation and relevance. We focus on safe workflows, approvals, evidence logging, and monitoring transparency.',
    },
    {
        q: 'What is an "action"?',
        a: 'An action is a single placement attempt (comment, profile, forum post, guest pitch). Each action is logged with evidence, requires approval (if above threshold), and is monitored for live/lost status.',
    },
    {
        q: 'Can my team approve actions?',
        a: 'Yes. An approval queue can gate actions above a risk threshold. You can configure auto-approval for low-risk actions or review each one manually.',
    },
    {
        q: 'Is this safe for long-term SEO?',
        a: 'Yes. All workflows include risk thresholds, approval gates, velocity controls, and evidence logging. We avoid spam tactics and PBNs.',
    },
    {
        q: 'What do you log as evidence?',
        a: 'Every action includes: placement URL, screenshot or HTML snippet, timestamp, and operator/audit trail. Full transparency for teams and compliance.',
    },
    {
        q: 'Do you offer agency/reseller plans?',
        a: 'Yes. The Agency solution includes multi-client management, roles/permissions, audit trails, and white-label reporting (Pro plan).',
    },
];

const scrollToForm = () => {
    activeTab.value = 'contact';
    const formSection = document.getElementById('contact-form-section');
    if (formSection) {
        formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
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

    // Listen for demo open event
    window.addEventListener('open-demo', () => {
        activeTab.value = 'demo';
        const formSection = document.getElementById('contact-form-section');
        if (formSection) {
            formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

<style scoped>
.contact-sales-page {
    background:
        radial-gradient(circle at top, rgba(255, 110, 64, 0.14), transparent 30%),
        radial-gradient(circle at top right, rgba(255, 110, 64, 0.1), transparent 24%),
        linear-gradient(180deg, #090909 0%, #050505 100%);
}

.contact-form-section {
    position: relative;
}

.contact-form-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0)),
        radial-gradient(circle at center, rgba(255, 110, 64, 0.06), transparent 42%);
    pointer-events: none;
}

.contact-sales-grid {
    position: relative;
    z-index: 1;
}

:deep(.contact-hero) {
    min-height: 72vh;
    display: flex;
    align-items: center;
    background:
        radial-gradient(circle at 15% 18%, rgba(255, 110, 64, 0.18), transparent 24%),
        linear-gradient(180deg, rgba(12, 10, 10, 0.72), rgba(6, 6, 6, 0.92));
}

:deep(.contact-hero .text-text) {
    color: #fff7f2 !important;
    letter-spacing: -0.04em;
}

:deep(.contact-hero .text-muted) {
    color: rgba(255, 236, 227, 0.72) !important;
}

:deep(.contact-tabs > div) {
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}

:deep(.contact-tabs .text-primary),
:deep(.contact-tabs .border-primary) {
    color: #ff8a65 !important;
    border-color: #ff8a65 !important;
}

:deep(.marketing-card) {
    background:
        linear-gradient(180deg, rgba(22, 18, 18, 0.94), rgba(10, 10, 10, 0.98)) !important;
    border: 1px solid rgba(255, 110, 64, 0.18) !important;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.03),
        0 24px 60px rgba(0, 0, 0, 0.28);
    backdrop-filter: blur(18px);
}

:deep(.contact-form input),
:deep(.contact-form select),
:deep(.contact-form textarea) {
    background: rgba(255, 255, 255, 0.04) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #fff7f2 !important;
    border-radius: 0.9rem;
    min-height: 3rem;
}

:deep(.contact-form input::placeholder),
:deep(.contact-form textarea::placeholder) {
    color: rgba(255, 236, 227, 0.38) !important;
}

:deep(.contact-form select:focus),
:deep(.contact-form input:focus),
:deep(.contact-form textarea:focus) {
    border-color: rgba(255, 138, 101, 0.78) !important;
    box-shadow: 0 0 0 4px rgba(255, 110, 64, 0.12) !important;
}

:deep(.contact-form .btn-primary),
:deep(.demo-booking-panel .btn-primary),
:deep(.contact-hero .btn-primary) {
    background: linear-gradient(180deg, #fff7f2, #ffe7db) !important;
    color: #16100d !important;
    border: 1px solid rgba(255, 224, 208, 0.72);
    box-shadow: 0 18px 40px rgba(255, 110, 64, 0.18);
}

:deep(.contact-form .btn-secondary),
:deep(.demo-booking-panel .btn-secondary),
:deep(.contact-hero .btn-secondary) {
    background: rgba(255, 110, 64, 0.1) !important;
    border: 1px solid rgba(255, 110, 64, 0.28) !important;
    color: #fff7f2 !important;
}

:deep(.text-success),
:deep(.text-primary) {
    color: #ff8a65 !important;
}

:deep(.text-danger) {
    color: #ff9f80 !important;
}

:deep(.bg-primary\/10) {
    background: rgba(255, 110, 64, 0.1) !important;
}

:deep(.border-primary\/20) {
    border-color: rgba(255, 110, 64, 0.2) !important;
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
