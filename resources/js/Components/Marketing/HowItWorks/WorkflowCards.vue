<template>
    <section id="workflows" class="workflow-cards py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Four Powerful Workflows
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Choose the right workflow for each opportunity.
            </p>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(workflow, idx) in workflows"
                    :key="idx"
                    data-reveal
                    class="marketing-card"
                >
                    <div class="text-4xl mb-4">{{ getIcon(workflow.slug) }}</div>
                    <h3 class="text-xl font-bold mb-3 text-text">{{ workflow.title }}</h3>
                    <div class="space-y-3 mb-6 text-sm">
                        <div>
                            <span class="font-semibold text-text">Best for:</span>
                            <span class="text-muted ml-2">{{ workflow.bestFor }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-text">Timeline:</span>
                            <span class="text-muted ml-2">{{ workflow.timeline }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-text">Controls:</span>
                            <span class="text-muted ml-2">{{ workflow.controls }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-text">Risk:</span>
                            <span
                                :class="[
                                    'ml-2 px-2 py-1 rounded text-xs font-semibold',
                                    getRiskClass(workflow.risk)
                                ]"
                            >
                                {{ workflow.risk }}
                            </span>
                        </div>
                    </div>
                    <Link :href="`/workflows/${workflow.slug}`" class="text-primary hover:underline font-semibold text-sm">
                        View workflow →
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    workflows: {
        type: Array,
        required: true,
    },
});

const getIcon = (slug) => {
    const icons = {
        comment: '💬',
        profile: '👤',
        forum: '📝',
        guest: '✍️',
    };
    return icons[slug] || '🔗';
};

const getRiskClass = (risk) => {
    const classes = {
        low: 'bg-success/20 text-success',
        medium: 'bg-warning/20 text-warning',
        high: 'bg-danger/20 text-danger',
    };
    return classes[risk] || 'bg-muted/20 text-muted';
};
</script>
