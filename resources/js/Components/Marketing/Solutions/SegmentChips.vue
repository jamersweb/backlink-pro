<template>
    <div class="segment-chips flex flex-wrap gap-2">
        <button
            v-for="segment in segments"
            :key="segment"
            @click="toggleSegment(segment)"
            :aria-pressed="selectedSegments.includes(segment)"
            :class="[
                'px-4 py-2 rounded-full text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                selectedSegments.includes(segment)
                    ? 'bg-primary text-white'
                    : 'bg-surface border border-border text-muted hover:text-text'
            ]"
        >
            {{ segment }}
        </button>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    segments: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:segments']);

const selectedSegments = ref([]);

const toggleSegment = (segment) => {
    const index = selectedSegments.value.indexOf(segment);
    if (index > -1) {
        selectedSegments.value.splice(index, 1);
    } else {
        selectedSegments.value.push(segment);
    }
    saveToLocalStorage();
    emit('update:segments', selectedSegments.value);
};

const saveToLocalStorage = () => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('bp_segment_selection', JSON.stringify(selectedSegments.value));
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('bp_segment_selection');
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed)) {
                    selectedSegments.value = parsed.filter(s => props.segments.includes(s));
                    emit('update:segments', selectedSegments.value);
                }
            } catch (e) {
                // Invalid JSON, ignore
            }
        }
    }
});
</script>
