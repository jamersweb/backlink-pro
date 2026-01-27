<template>
    <section class="related-resources py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                Related Resources
            </h2>

            <div v-if="related.length === 0" class="text-center text-muted">
                <p>No related resources available.</p>
            </div>
            <div v-else class="grid md:grid-cols-3 gap-6">
                <ResourceCard
                    v-for="(resource, idx) in related"
                    :key="idx"
                    :resource="resource"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import ResourceCard from './ResourceCard.vue';

const props = defineProps({
    related: {
        type: Array,
        default: () => [],
    },
});
</script>
