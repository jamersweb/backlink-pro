<template>
    <section class="related-solutions py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                Related Solutions
            </h2>

            <div v-if="related.length === 0" class="text-center text-muted">
                <p>No related solutions available.</p>
            </div>
            <div v-else class="grid md:grid-cols-3 gap-6">
                <SolutionCard
                    v-for="(solution, idx) in related"
                    :key="idx"
                    :solution="solution"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import SolutionCard from './SolutionCard.vue';

const props = defineProps({
    related: {
        type: Array,
        default: () => [],
    },
});
</script>
