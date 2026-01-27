<template>
    <div class="stage-selector">
        <div class="flex gap-2 border-b border-border" role="tablist">
            <button
                v-for="stage in stages"
                :key="stage"
                @click="selectStage(stage)"
                :aria-selected="selectedStage === stage"
                :class="[
                    'px-4 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                    selectedStage === stage
                        ? 'border-b-2 border-primary text-primary'
                        : 'text-muted hover:text-text'
                ]"
                role="tab"
                :aria-controls="`stage-${stage}`"
            >
                {{ stage }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    stages: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:stage']);

const selectedStage = ref('Growth');

const selectStage = (stage) => {
    selectedStage.value = stage;
    if (typeof window !== 'undefined') {
        localStorage.setItem('bp_stage_selection', stage);
    }
    emit('update:stage', stage);
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('bp_stage_selection');
        if (saved && props.stages.includes(saved)) {
            selectedStage.value = saved;
            emit('update:stage', saved);
        }
    }
});
</script>
