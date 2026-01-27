<template>
    <section class="filters-bar py-8 bg-surface2 sticky top-0 z-30 border-b border-border">
        <div class="marketing-container">
            <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
                <!-- Search -->
                <div class="flex-1 w-full md:w-auto">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search resources..."
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>

                <!-- Sort -->
                <select
                    v-model="sortBy"
                    class="px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="newest">Newest</option>
                    <option value="most-read">Most read</option>
                    <option value="reading-time">Reading time</option>
                </select>
            </div>

            <!-- Topics -->
            <div class="mt-4">
                <TopicChips :topics="topics" @update:topics="selectedTopics = $event" />
            </div>

            <!-- Active Filters -->
            <div v-if="hasActiveFilters" class="mt-4 flex items-center gap-2 flex-wrap">
                <span class="text-sm text-muted">Active filters:</span>
                <span
                    v-for="topic in selectedTopics"
                    :key="topic"
                    class="px-2 py-1 bg-primary/20 text-primary text-xs rounded"
                >
                    {{ topic }}
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
import TopicChips from './TopicChips.vue';

const props = defineProps({
    topics: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:filters', 'clear-filters']);

const searchQuery = ref('');
const selectedTopics = ref([]);
const sortBy = ref('newest');

const hasActiveFilters = computed(() => {
    return selectedTopics.value.length > 0;
});

const clearFilters = () => {
    searchQuery.value = '';
    selectedTopics.value = [];
    sortBy.value = 'newest';
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

watch([selectedTopics, sortBy], () => {
    emitFilters();
});

const emitFilters = () => {
    emit('update:filters', {
        search: searchQuery.value,
        topics: selectedTopics.value,
        sort: sortBy.value,
    });
};

// Initial emit
emitFilters();
</script>
