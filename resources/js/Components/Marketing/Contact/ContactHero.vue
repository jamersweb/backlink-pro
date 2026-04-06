<template>
    <section class="contact-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div data-reveal>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                        Contact Sales
                    </h1>
                    <p class="text-xl text-muted mb-8 leading-relaxed">
                        Tell us about your site, growth goals, or team workflow. We will recommend the safest backlink setup for your stage.
                    </p>
                    <div class="flex flex-wrap gap-4 mb-6">
                        <a href="#contact-form-section" @click.prevent="scrollToSalesForm" class="btn-primary text-lg px-8 py-4">
                            Start sales request
                        </a>
                        <button @click="scrollToDemo" class="btn-secondary text-lg px-8 py-4">
                            Book demo
                        </button>
                    </div>
                    <p class="text-sm text-muted">
                        Human approvals • Evidence logs • Safe workflows • Monitoring
                    </p>
                </div>

                <div data-reveal>
                    <MiniProof :testimonials="testimonials" :logos="logos" />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import MiniProof from './MiniProof.vue';

defineProps({
    testimonials: {
        type: Array,
        default: () => [],
    },
    logos: {
        type: Array,
        default: () => [],
    },
});

const scrollToSalesForm = () => {
    const section = document.getElementById('contact-form-section');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const scrollToDemo = () => {
    const event = new CustomEvent('open-demo');
    window.dispatchEvent(event);

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
