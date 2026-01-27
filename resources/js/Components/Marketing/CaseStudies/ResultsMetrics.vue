<template>
    <section id="results" class="results-metrics py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Results & Metrics
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Measurable outcomes with full transparency.
            </p>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto mb-12">
                <div
                    v-for="(metric, idx) in caseStudy.results.metrics"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6 text-center"
                >
                    <div class="text-3xl font-bold mb-2 text-primary">{{ metric.value }}</div>
                    <div class="text-sm text-muted">{{ metric.label }}</div>
                </div>
            </div>

            <!-- How We Measure -->
            <div class="max-w-3xl mx-auto" data-reveal>
                <div class="marketing-card p-6 bg-surface2">
                    <h3 class="font-semibold text-text mb-3 flex items-center gap-2">
                        How We Measure
                        <button
                            @click="showMeasurementTooltip = !showMeasurementTooltip"
                            class="w-5 h-5 rounded-full bg-surface border border-border flex items-center justify-center text-xs text-muted hover:text-text focus:outline-none focus:ring-2 focus:ring-primary"
                            aria-label="Measurement methodology"
                        >
                            i
                        </button>
                    </h3>
                    <div v-if="showMeasurementTooltip" class="mt-3 p-4 bg-surface rounded border border-border text-sm text-muted">
                        <p class="mb-2">
                            <strong class="text-text">Actions executed:</strong> Total workflow steps completed with evidence logs.
                        </p>
                        <p class="mb-2">
                            <strong class="text-text">Placements verified:</strong> Links confirmed live at time of verification. 
                            Some may be removed by site owners over time.
                        </p>
                        <p>
                            <strong class="text-text">Monitoring status:</strong> Ongoing link health checks. 
                            Status may change as sites update content.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    caseStudy: {
        type: Object,
        required: true,
    },
});

const showMeasurementTooltip = ref(false);
</script>
