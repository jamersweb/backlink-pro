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
            <ContactHero :testimonials="testimonials" :logos="logos" />

            <section id="contact-form-section" class="contact-form-section py-20">
                <div class="marketing-container">
                    <ContactTabs @update:tab="activeTab = $event" />

                    <!-- Contact Tab Content -->
                    <div v-show="activeTab === 'contact'" class="mt-8">
                        <div class="grid md:grid-cols-3 gap-8">
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
import AnnouncementBar from '../../Components/Marketing/AnnouncementBar.vue';
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
