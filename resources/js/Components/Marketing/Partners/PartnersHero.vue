<template>
    <section class="partners-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Left: Text -->
                <div data-reveal>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                        Partner with BacklinkPro
                    </h1>
                    <p class="text-xl text-muted mb-8 leading-relaxed">
                        Referral or reseller—bring safe backlink automation to your audience with approvals and evidence logs.
                    </p>
                    <div class="flex flex-wrap gap-4 mb-6">
                        <button @click="scrollToForm" class="btn-primary text-lg px-8 py-4">
                            Apply to Partner Program
                        </button>
                        <button @click="scrollToDemo" class="btn-secondary text-lg px-8 py-4">
                            Book a partner call
                        </button>
                        <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-secondary text-lg px-8 py-4">
                            Run Free Backlink Plan
                        </a>
                    </div>
                    <p class="text-sm text-muted">
                        No PBNs • Human approvals • Evidence logs • Monitoring
                    </p>
                </div>

                <!-- Right: MiniProof -->
                <div data-reveal>
                    <MiniProof :testimonials="testimonials" />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import MiniProof from './MiniProof.vue';

const props = defineProps({
    testimonials: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['scroll-to-form']);

const scrollToForm = () => {
    emit('scroll-to-form');
    const formSection = document.getElementById('partner-apply-section');
    if (formSection) {
        formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const scrollToDemo = () => {
    // Try to open contact page or scroll to demo section
    const contactLink = document.querySelector('a[href="/contact"]');
    if (contactLink) {
        window.location.href = '/contact?tab=demo';
    } else {
        // Fallback: scroll to form
        scrollToForm();
    }
};

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<style scoped>
.partners-hero {
    min-height: 70vh;
    display: flex;
    align-items: center;
}
</style>
