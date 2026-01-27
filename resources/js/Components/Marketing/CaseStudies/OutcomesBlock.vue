<template>
    <section id="outcomes" class="outcomes-block py-20 scroll-mt-24" data-reveal>
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-text">Outcomes</h2>
            <div class="marketing-card p-6 mb-6 bg-warning/10 border border-warning/20">
                <p class="text-sm text-muted italic">{{ outcomes.note }}</p>
            </div>
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div
                    v-for="(metric, idx) in outcomes.metrics"
                    :key="idx"
                    class="marketing-card p-6 text-center"
                >
                    <div class="text-2xl font-bold text-primary mb-2">{{ metric.value }}</div>
                    <div class="text-sm text-muted">{{ metric.label }}</div>
                </div>
            </div>
            <div v-if="outcomes.charts && outcomes.charts.length > 0" class="marketing-card p-6 mb-6">
                <h3 class="text-lg font-bold mb-4 text-text">{{ outcomes.charts[0].label }}</h3>
                <Sparkline :data="outcomes.charts[0].data" />
            </div>
            <div class="marketing-card p-6 bg-surface2">
                <h3 class="text-lg font-bold mb-3 text-text">Understanding Variance</h3>
                <p class="text-sm text-muted mb-3">
                    Outcomes vary based on:
                </p>
                <ul class="space-y-2 text-sm text-muted">
                    <li class="flex items-start gap-2">
                        <span class="text-primary">•</span>
                        <span><strong>Moderation cycles:</strong> Some sites approve quickly, others take weeks or months.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-primary">•</span>
                        <span><strong>Niche authority:</strong> Established sites see faster results than new domains.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-primary">•</span>
                        <span><strong>Target quality:</strong> Whitelist-only approaches may have slower velocity but higher approval rates.</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>

<script setup>
import Sparkline from './Sparkline.vue';

const props = defineProps({
    outcomes: {
        type: Object,
        required: true,
    },
});
</script>
