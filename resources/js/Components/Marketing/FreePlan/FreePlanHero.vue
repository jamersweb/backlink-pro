<template>
    <section class="free-plan-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Left: Text -->
                <div data-reveal>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                        Generate your free backlink plan
                    </h1>
                    <p class="text-xl text-muted mb-8 leading-relaxed">
                        Get a safe workflow mix, weekly schedule, and guardrails preset—built around approvals and evidence logs.
                    </p>
                    <p class="text-sm text-muted mb-6">
                        No PBNs • Human approvals • Evidence logs • Monitoring
                    </p>
                    <button @click="scrollToForm" class="btn-primary text-lg px-8 py-4">
                        Get started
                    </button>
                </div>

                <!-- Right: Evidence Sample Card -->
                <div data-reveal>
                    <EvidenceSampleCard />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import EvidenceSampleCard from './EvidenceSampleCard.vue';

const scrollToForm = () => {
    const form = document.getElementById('free-plan-form');
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<style scoped>
.free-plan-hero {
    min-height: 70vh;
    display: flex;
    align-items: center;
}
</style>
