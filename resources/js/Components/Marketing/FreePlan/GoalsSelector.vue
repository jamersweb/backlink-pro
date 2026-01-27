<template>
    <div class="goals-selector">
        <label class="block text-sm font-semibold text-text mb-4">
            Goals <span class="text-danger">*</span>
        </label>
        <div class="flex flex-wrap gap-3" role="group" aria-label="Goals">
            <button
                v-for="goal in goals"
                :key="goal.value"
                type="button"
                @click="toggleGoal(goal.value)"
                @keydown.enter="toggleGoal(goal.value)"
                @keydown.space.prevent="toggleGoal(goal.value)"
                :aria-pressed="selectedGoals.includes(goal.value)"
                :class="[
                    'px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                    selectedGoals.includes(goal.value)
                        ? 'bg-primary text-white'
                        : 'bg-surface2 border border-border text-muted hover:text-text'
                ]"
            >
                {{ goal.label }}
            </button>
        </div>
        <p v-if="error" class="text-xs text-danger mt-2">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    goals: {
        type: Array,
        required: true,
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const selectedGoals = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const toggleGoal = (value) => {
    const current = [...selectedGoals.value];
    const index = current.indexOf(value);
    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(value);
    }
    selectedGoals.value = current;
};
</script>
