<template>
    <section class="resources-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="max-w-4xl mx-auto text-center" data-reveal>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                    Resources to scale backlinks safely.
                </h1>
                <p class="text-xl text-muted mb-8 leading-relaxed">
                    Playbooks, templates, and tools—built around approvals, evidence logs, and monitoring.
                </p>
                <div class="flex flex-wrap gap-4 justify-center mb-6">
                    <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                        Run Free Backlink Plan
                    </a>
                    <button @click="$emit('demo-open')" class="btn-secondary text-lg px-8 py-4">
                        Watch demo
                    </button>
                </div>
                <p class="text-sm text-muted">
                    No PBNs • Human approvals • Full evidence
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
defineEmits(['demo-open']);

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<style scoped>
.resources-hero {
    min-height: 70vh;
    display: flex;
    align-items: center;
}
</style>
