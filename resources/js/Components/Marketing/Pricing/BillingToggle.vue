<template>
    <div class="billing-toggle flex items-center justify-center gap-4 mb-12" data-reveal>
        <button
            v-for="cycle in cycles"
            :key="cycle.value"
            @click="selectCycle(cycle.value)"
            @keydown.enter="selectCycle(cycle.value)"
            @keydown.space.prevent="selectCycle(cycle.value)"
            :aria-pressed="selectedCycle === cycle.value"
            :class="[
                'px-6 py-3 rounded-lg font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-primary',
                selectedCycle === cycle.value
                    ? 'bg-primary text-white'
                    : 'bg-surface2 text-muted hover:text-text'
            ]"
        >
            {{ cycle.label }}
            <span
                v-if="cycle.badge && selectedCycle === cycle.value"
                class="ml-2 px-2 py-0.5 bg-white/20 text-xs rounded"
            >
                {{ cycle.badge }}
            </span>
        </button>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    cycles: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: String,
        default: 'monthly',
    },
});

const emit = defineEmits(['update:modelValue']);

const selectedCycle = ref(props.modelValue);

const selectCycle = (value) => {
    selectedCycle.value = value;
    emit('update:modelValue', value);
    if (typeof window !== 'undefined') {
        localStorage.setItem('bp_billing_cycle', value);
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('bp_billing_cycle');
        if (saved && props.cycles.find(c => c.value === saved)) {
            selectedCycle.value = saved;
            emit('update:modelValue', saved);
        }
    }
});
</script>
