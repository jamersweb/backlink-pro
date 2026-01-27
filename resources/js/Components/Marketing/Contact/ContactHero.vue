<template>
    <section class="contact-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Left: Text -->
                <div data-reveal>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                        Talk to BacklinkPro
                    </h1>
                    <p class="text-xl text-muted mb-8 leading-relaxed">
                        Sales, partnerships, or support—get guidance on safe backlink automation with approvals and evidence logs.
                    </p>
                    <div class="flex flex-wrap gap-4 mb-6">
                        <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                            Run Free Backlink Plan
                        </a>
                        <button @click="scrollToDemo" class="btn-secondary text-lg px-8 py-4">
                            Watch demo
                        </button>
                    </div>
                    <p class="text-sm text-muted">
                        No PBNs • Human approvals • Evidence logs • Monitoring
                    </p>
                </div>

                <!-- Right: MiniProof -->
                <div data-reveal>
                    <MiniProof :testimonials="testimonials" :logos="logos" />
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
    logos: {
        type: Array,
        default: () => [],
    },
});

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const scrollToDemo = () => {
    // Try to open demo modal first, otherwise scroll to demo tab
    const event = new CustomEvent('open-demo');
    window.dispatchEvent(event);
    
    // Also switch to demo tab if tabs exist
    setTimeout(() => {
        const demoTab = document.querySelector('[data-tab="demo"]');
        if (demoTab) {
            demoTab.click();
            const demoSection = document.getElementById('contact-form-section');
            if (demoSection) {
                demoSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }, 100);
};
</script>

<style scoped>
.contact-hero {
    min-height: 70vh;
    display: flex;
    align-items: center;
}
</style>
