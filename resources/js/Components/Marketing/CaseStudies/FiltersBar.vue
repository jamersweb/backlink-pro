<template>
    <div class="filters-bar marketing-card p-6 mb-8" data-reveal>
        <!-- Search -->
        <div class="mb-6">
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Search case studies..."
                class="w-full px-4 py-3 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                @input="handleSearch"
            />
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <!-- Segment Filter -->
            <div>
                <label class="block text-xs font-semibold text-muted mb-2">Segment</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="segment in segments"
                        :key="segment"
                        @click="toggleSegment(segment)"
                        :aria-pressed="selectedSegments.includes(segment)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                            selectedSegments.includes(segment)
                                ? 'bg-primary text-white'
                                : 'bg-surface2 text-muted hover:text-text'
                        ]"
                    >
                        {{ segment }}
                    </button>
                </div>
            </div>

            <!-- Risk Mode Filter -->
            <div>
                <label class="block text-xs font-semibold text-muted mb-2">Risk Mode</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="mode in riskModes"
                        :key="mode"
                        @click="toggleRiskMode(mode)"
                        :aria-pressed="selectedRiskModes.includes(mode)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                            selectedRiskModes.includes(mode)
                                ? 'bg-primary text-white'
                                : 'bg-surface2 text-muted hover:text-text'
                        ]"
                    >
                        {{ mode }}
                    </button>
                </div>
            </div>

            <!-- Sort -->
            <div class="ml-auto">
                <label class="block text-xs font-semibold text-muted mb-2">Sort</label>
                <select
                    v-model="sortBy"
                    @change="handleSort"
                    class="px-3 py-1.5 rounded-lg bg-surface border border-border text-text text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="relevant">Most relevant</option>
                    <option value="shortest">Shortest time</option>
                    <option value="lowest">Lowest risk</option>
                </select>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    segments: {
        type: Array,
        required: true,
    },
    riskModes: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['filter', 'sort', 'search']);

const searchQuery = ref('');
const selectedSegments = ref([]);
const selectedRiskModes = ref([]);
const sortBy = ref('relevant');

let searchTimeout = null;

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        emit('search', searchQuery.value);
    }, 200);
};

const toggleSegment = (segment) => {
    const index = selectedSegments.value.indexOf(segment);
    if (index > -1) {
        selectedSegments.value.splice(index, 1);
    } else {
        selectedSegments.value.push(segment);
    }
    emit('filter', { segments: selectedSegments.value, riskModes: selectedRiskModes.value });
};

const toggleRiskMode = (mode) => {
    const index = selectedRiskModes.value.indexOf(mode);
    if (index > -1) {
        selectedRiskModes.value.splice(index, 1);
    } else {
        selectedRiskModes.value.push(mode);
    }
    emit('filter', { segments: selectedSegments.value, riskModes: selectedRiskModes.value });
};

const handleSort = () => {
    emit('sort', sortBy.value);
};

watch(() => props.segments, () => {
    selectedSegments.value = [];
}, { immediate: true });

watch(() => props.riskModes, () => {
    selectedRiskModes.value = [];
}, { immediate: true });
</script>
