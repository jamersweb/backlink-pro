<template>
    <div class="competitors-repeater">
        <label class="block text-sm font-semibold text-text mb-2">
            Competitors (optional)
        </label>
        <p class="text-xs text-muted mb-4">Add up to 5 competitor URLs for analysis.</p>
        <div class="space-y-3">
            <div
                v-for="(competitor, idx) in competitors"
                :key="idx"
                class="flex gap-2"
            >
                <input
                    v-model="competitors[idx]"
                    type="url"
                    placeholder="https://competitor.com"
                    class="flex-1 px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    @input="updateCompetitors"
                />
                <button
                    v-if="competitors.length > 1"
                    type="button"
                    @click="removeCompetitor(idx)"
                    class="px-3 py-2 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                    aria-label="Remove competitor"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <button
                v-if="competitors.length < 5"
                type="button"
                @click="addCompetitor"
                class="text-sm text-primary hover:underline"
            >
                + Add competitor
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);

const competitors = ref(props.modelValue.length > 0 ? [...props.modelValue] : ['']);

const addCompetitor = () => {
    if (competitors.value.length < 5) {
        competitors.value.push('');
        updateCompetitors();
    }
};

const removeCompetitor = (idx) => {
    competitors.value.splice(idx, 1);
    updateCompetitors();
};

const updateCompetitors = () => {
    const filtered = competitors.value.filter(c => c && c.trim());
    emit('update:modelValue', filtered);
};

watch(() => props.modelValue, (newVal) => {
    if (newVal.length === 0 && competitors.value.length === 0) {
        competitors.value = [''];
    }
});
</script>
