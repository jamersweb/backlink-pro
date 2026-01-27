<template>
    <section class="case-study-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <nav class="mb-6" aria-label="Breadcrumb">
                <a href="/case-studies" class="text-primary hover:underline text-sm">
                    ← Case Studies
                </a>
            </nav>
            <div class="max-w-4xl" data-reveal>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                    {{ item.title }}
                </h1>
                <div class="flex flex-wrap gap-2 mb-6">
                    <span class="px-3 py-1 bg-primary/20 text-primary text-sm rounded">{{ item.segment }}</span>
                    <span class="px-3 py-1 bg-success/20 text-success text-sm rounded">{{ item.riskMode }}</span>
                    <span class="px-3 py-1 bg-surface2 text-muted text-sm rounded">{{ item.duration }}</span>
                </div>
                <div class="flex flex-wrap gap-4 mb-6">
                    <a href="/free-backlink-plan" class="btn-primary text-lg px-8 py-4">
                        {{ item.cta.primary }}
                    </a>
                    <a href="/contact" class="btn-secondary text-lg px-8 py-4">
                        {{ item.cta.secondary }}
                    </a>
                </div>
                <p class="text-sm text-warning italic">
                    Outcomes vary. No guaranteed links.
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>

<style scoped>
.case-study-hero {
    min-height: 50vh;
    display: flex;
    align-items: center;
}
</style>
