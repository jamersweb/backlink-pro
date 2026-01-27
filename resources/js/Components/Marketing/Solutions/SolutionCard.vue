<template>
    <Link
        :href="`/solutions/${solution.slug}`"
        class="solution-card marketing-card p-6 block transition-all hover:shadow-xl hover:-translate-y-1 hover:border-primary/50"
        data-reveal
    >
        <div class="flex items-start justify-between mb-4">
            <h3 class="text-xl font-bold text-text flex-1">{{ solution.name }}</h3>
            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded font-semibold">
                {{ solution.name }}
            </span>
        </div>

        <p class="text-muted mb-4 text-sm">{{ solution.summary }}</p>

        <!-- Mini Bullets -->
        <div class="mb-4 space-y-2">
            <div class="text-xs text-muted">
                <strong class="text-text">Best for:</strong> {{ solution.whoFor[0] }}
            </div>
            <div class="text-xs text-muted">
                <strong class="text-text">Primary goal:</strong> {{ solution.goals[0] }}
            </div>
            <div class="text-xs text-muted">
                <strong class="text-text">Recommended preset:</strong> {{ solution.presets[1].name }}
            </div>
        </div>

        <!-- Recommended Workflows -->
        <div class="mb-4">
            <div class="text-xs text-muted mb-2">Recommended workflows:</div>
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="(workflow, idx) in displayWorkflows"
                    :key="idx"
                    class="px-2 py-0.5 bg-surface2 text-muted text-xs rounded"
                >
                    {{ workflow.label }}
                </span>
            </div>
        </div>

        <div class="text-primary hover:underline font-semibold text-sm">
            View solution →
        </div>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    solution: {
        type: Object,
        required: true,
    },
});

const displayWorkflows = computed(() => {
    return props.solution.recommendedWorkflows.slice(0, 2);
});
</script>
