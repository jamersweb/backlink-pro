<template>
    <div class="budget-email-row grid md:grid-cols-2 gap-4">
        <div>
            <label for="monthly_budget" class="block text-sm font-semibold text-text mb-2">
                Monthly Budget (optional)
            </label>
            <input
                id="monthly_budget"
                v-model.number="budget"
                type="number"
                min="0"
                max="200000"
                placeholder="e.g., 500"
                class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                @input="updateBudget"
            />
        </div>
        <div>
            <label for="email" class="block text-sm font-semibold text-text mb-2">
                Email (optional)
            </label>
            <input
                id="email"
                v-model="email"
                type="email"
                placeholder="your@email.com"
                class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                @input="updateEmail"
            />
            <p class="text-xs text-muted mt-1">We'll send your plan to this address</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    budget: {
        type: Number,
        default: null,
    },
    email: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:budget', 'update:email']);

const budget = ref(props.budget);
const email = ref(props.email);

const updateBudget = () => {
    emit('update:budget', budget.value ? parseInt(budget.value) : null);
};

const updateEmail = () => {
    emit('update:email', email.value);
};
</script>
