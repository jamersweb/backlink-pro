<template>
    <section class="product-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="max-w-4xl mx-auto text-center" data-reveal>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                    What's inside BacklinkPro
                </h1>
                <p class="text-xl text-muted mb-8 leading-relaxed">
                    A guardrailed link engine with approvals, evidence logs, and monitoring—so automation stays controllable.
                </p>
                <div class="flex flex-wrap gap-4 justify-center mb-8">
                    <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                        Run Free Backlink Plan
                    </a>
                    <a href="/how-it-works" class="btn-secondary text-lg px-8 py-4">
                        How it works
                    </a>
                    <a href="/pricing" class="btn-secondary text-lg px-8 py-4">
                        View pricing
                    </a>
                </div>
                <div class="flex flex-wrap gap-3 justify-center">
                    <span
                        v-for="(badge, idx) in badges"
                        :key="idx"
                        class="px-4 py-2 bg-surface2 border border-border rounded-full text-sm text-muted"
                    >
                        {{ badge }}
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    badges: {
        type: Array,
        required: true,
    },
});

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<style scoped>
.product-hero {
    min-height: 60vh;
    display: flex;
    align-items: center;
}
</style>
