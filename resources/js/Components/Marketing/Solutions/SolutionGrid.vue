<template>
    <section class="solution-grid py-20">
        <div class="marketing-container">
            <div v-if="filteredSolutions.length === 0" class="text-center py-12">
                <p class="text-muted">No solutions match your filters.</p>
                <button @click="$emit('clear-filters')" class="btn-secondary mt-4">
                    Clear filters
                </button>
            </div>
            <div v-else class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <SolutionCard
                    v-for="(solution, idx) in filteredSolutions"
                    :key="idx"
                    :solution="solution"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import SolutionCard from './SolutionCard.vue';

const props = defineProps({
    solutions: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    selectedSegments: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['clear-filters']);

const filteredSolutions = computed(() => {
    let results = [...props.solutions];

    // Reorder by selected segments (selected first)
    if (props.selectedSegments.length > 0) {
        results.sort((a, b) => {
            const aSelected = props.selectedSegments.includes(a.name);
            const bSelected = props.selectedSegments.includes(b.name);
            if (aSelected && !bSelected) return -1;
            if (!aSelected && bSelected) return 1;
            return 0;
        });
    }

    // Search filter
    if (props.filters.search) {
        const query = props.filters.search.toLowerCase();
        results = results.filter(solution =>
            solution.name.toLowerCase().includes(query) ||
            solution.summary.toLowerCase().includes(query) ||
            solution.tags.some(tag => tag.toLowerCase().includes(query))
        );
    }

    // Tags filter
    if (props.filters.tags.length > 0) {
        results = results.filter(solution =>
            props.filters.tags.some(tag => solution.tags.includes(tag))
        );
    }

    // Sort
    if (props.filters.sort === 'relevant') {
        // Keep segment-based order
        results = results;
    } else if (props.filters.sort === 'risk') {
        // Sort by risk (conservative tags first)
        results.sort((a, b) => {
            const aConservative = a.tags.includes('Conservative') ? 0 : 1;
            const bConservative = b.tags.includes('Conservative') ? 0 : 1;
            return aConservative - bConservative;
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
