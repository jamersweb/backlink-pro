<template>
    <section class="filters-bar py-8 bg-surface2 sticky top-0 z-30 border-b border-border">
        <div class="marketing-container">
            <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
                <!-- Search -->
                <div class="flex-1 w-full md:w-auto">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search solutions..."
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>

                <!-- Sort -->
                <select
                    v-model="sortBy"
                    class="px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="relevant">Most relevant</option>
                    <option value="risk">Lowest risk</option>
                    <option value="setup">Fastest setup</option>
                </select>
            </div>

            <!-- Tags -->
            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="tag in availableTags"
                    :key="tag"
                    @click="toggleTag(tag)"
                    :aria-pressed="selectedTags.includes(tag)"
                    :class="[
                        'px-3 py-1 rounded-full text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                        selectedTags.includes(tag)
                            ? 'bg-primary text-white'
                            : 'bg-surface border border-border text-muted hover:text-text'
                    ]"
                >
                    {{ tag }}
                </button>
            </div>

            <!-- Active Filters -->
            <div v-if="hasActiveFilters" class="mt-4 flex items-center gap-2 flex-wrap">
                <span class="text-sm text-muted">Active filters:</span>
                <span
                    v-for="tag in selectedTags"
                    :key="tag"
                    class="px-2 py-1 bg-primary/20 text-primary text-xs rounded"
                >
                    {{ tag }}
                </span>
                <button
                    @click="clearFilters"
                    class="text-sm text-primary hover:underline"
                >
                    Clear all
                </button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    solutions: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:filters', 'clear-filters']);

const searchQuery = ref('');
const selectedTags = ref([]);
const sortBy = ref('relevant');

const availableTags = computed(() => {
    const tags = new Set();
    props.solutions.forEach(solution => {
        solution.tags.forEach(tag => tags.add(tag));
    });
    return Array.from(tags).sort();
});

const hasActiveFilters = computed(() => {
    return selectedTags.value.length > 0;
});

const toggleTag = (tag) => {
    const index = selectedTags.value.indexOf(tag);
    if (index > -1) {
        selectedTags.value.splice(index, 1);
    } else {
        selectedTags.value.push(tag);
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedTags.value = [];
    sortBy.value = 'relevant';
    emit('clear-filters');
};

// Debounce search
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        emitFilters();
    }, 200);
});

watch([selectedTags, sortBy], () => {
    emitFilters();
});

const emitFilters = () => {
    emit('update:filters', {
        search: searchQuery.value,
        tags: selectedTags.value,
        sort: sortBy.value,
    });
};

// Initial emit
emitFilters();
</script>
