<template>
    <section class="resources-list py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Latest Resources
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <ResourceCard
                    v-for="(resource, idx) in resources"
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
    resources: {
        type: Array,
        required: true,
    },
});
</script>
