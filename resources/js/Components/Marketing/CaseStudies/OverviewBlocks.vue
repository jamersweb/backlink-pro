<template>
    <section id="overview" class="overview-blocks py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                Overview
            </h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div
                    v-for="(block, idx) in blocks"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <h3 class="text-xl font-bold mb-4 text-text">{{ block.title }}</h3>
                    <p class="text-muted mb-4">{{ block.content }}</p>
                    <ul v-if="block.items" class="space-y-2 text-sm text-muted">
                        <li
                            v-for="(item, iIdx) in block.items"
                            :key="iIdx"
                            class="flex items-start gap-2"
                        >
                            <span class="text-primary">•</span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    caseStudy: {
        type: Object,
        required: true,
    },
});

const blocks = computed(() => [
    {
        title: 'Starting Point',
        content: props.caseStudy.overview.startingPoint,
    },
    {
        title: 'Goal',
        content: props.caseStudy.overview.goal,
    },
    {
        title: 'Constraints',
        content: null,
        items: props.caseStudy.overview.constraints,
    },
]);
</script>
