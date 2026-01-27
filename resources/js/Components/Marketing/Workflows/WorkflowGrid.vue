<template>
    <section class="workflow-grid py-20">
        <div class="marketing-container">
            <div v-if="filteredWorkflows.length === 0" class="text-center py-12">
                <p class="text-muted">No workflows match your filters.</p>
                <button @click="$emit('clear-filters')" class="btn-secondary mt-4">
                    Clear filters
                </button>
            </div>
            <div v-else class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <WorkflowCard
                    v-for="(workflow, idx) in filteredWorkflows"
                    :key="idx"
                    :workflow="workflow"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import WorkflowCard from './WorkflowCard.vue';

const props = defineProps({
    workflows: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['clear-filters']);

const filteredWorkflows = computed(() => {
    let results = [...props.workflows];

    // Search filter
    if (props.filters.search) {
        const query = props.filters.search.toLowerCase();
        results = results.filter(workflow =>
            workflow.name.toLowerCase().includes(query) ||
            workflow.summary.toLowerCase().includes(query) ||
            workflow.tags.some(tag => tag.toLowerCase().includes(query))
        );
    }

    // Risk filter
    if (props.filters.risk) {
        results = results.filter(workflow =>
            workflow.riskLevel.includes(props.filters.risk)
        );
    }

    // Tags filter
    if (props.filters.tags.length > 0) {
        results = results.filter(workflow =>
            props.filters.tags.some(tag => workflow.tags.includes(tag))
        );
    }

    // Sort
    if (props.filters.sort === 'popular') {
        // Keep original order (most popular first)
        results = results;
    } else if (props.filters.sort === 'risk') {
        // Sort by risk level (Low first)
        const riskOrder = {
            'Low': 1,
            'Low–Medium': 2,
            'Medium': 3,
            'Medium–High': 4,
            'High': 5,
        };
        results.sort((a, b) => {
            return (riskOrder[a.riskLevel] || 99) - (riskOrder[b.riskLevel] || 99);
        });
    } else if (props.filters.sort === 'setup') {
        // Sort by setup speed (Fast Setup tag first)
        results.sort((a, b) => {
            const aFast = a.tags.includes('Fast Setup') ? 0 : 1;
            const bFast = b.tags.includes('Fast Setup') ? 0 : 1;
            return aFast - bFast;
        });
    }

    return results;
});
</script>
