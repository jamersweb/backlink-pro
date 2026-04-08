<template>
    <section class="product-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="max-w-3xl mx-auto text-center" data-reveal>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                    Platform Overview
                </h1>
                <p class="text-xl text-muted mb-10 leading-relaxed">
                    Explore the full BacklinkPro workspace for approvals, evidence logs, workflows, and reporting built around safe backlink growth.
                </p>
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
defineProps({
    badges: {
        type: Array,
        required: true,
    },
});
</script>

<style scoped>
.product-hero {
    min-height: 60vh;
    display: flex;
    align-items: center;
}
</style>
