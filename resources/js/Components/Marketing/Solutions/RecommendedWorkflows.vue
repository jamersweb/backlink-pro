<template>
    <section id="workflows" class="recommended-workflows py-20">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Recommended Workflows
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Workflows that work best for {{ solution.name }}.
            </p>

            <div class="grid md:grid-cols-3 gap-6">
                <Link
                    v-for="(workflow, idx) in solution.recommendedWorkflows"
                    :key="idx"
                    :href="`/workflows/${workflow.slug}`"
                    data-reveal
                    class="marketing-card p-6 block transition-all hover:shadow-xl hover:-translate-y-1 hover:border-primary/50"
                >
                    <h3 class="text-xl font-bold mb-3 text-text">{{ workflow.label }}</h3>
                    <p class="text-muted mb-4 text-sm">{{ workflow.reason }}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-muted">View workflow →</span>
                        <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">
                            Low Risk
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    solution: {
        type: Object,
        required: true,
    },
});
</script>
