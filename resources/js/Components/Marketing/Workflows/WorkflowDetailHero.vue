<template>
    <section class="workflow-detail-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <nav class="mb-6" aria-label="Breadcrumb">
                <a href="/workflows" class="text-primary hover:underline text-sm">
                    ← Workflows
                </a>
            </nav>
            <div class="max-w-4xl" data-reveal>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                    {{ item.title }}
                </h1>
                <p class="text-xl text-muted mb-8 leading-relaxed">
                    {{ item.excerpt }}
                </p>
                <SafetyProfile :profile="item.safetyProfile" />
                <div class="flex flex-wrap gap-4 mb-6">
                    <a :href="item.cta.primary.href" class="btn-primary text-lg px-8 py-4">
                        {{ item.cta.primary.label }}
                    </a>
                    <a :href="item.cta.secondary.href" class="btn-secondary text-lg px-8 py-4">
                        {{ item.cta.secondary.label }}
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
import SafetyProfile from './SafetyProfile.vue';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>

<style scoped>
.workflow-detail-hero {
    min-height: 50vh;
    display: flex;
    align-items: center;
}
</style>
