<template>
    <div class="plan-cards grid md:grid-cols-3 gap-8 mb-16">
        <PlanCard
            v-for="plan in plans"
            :key="plan.id"
            :plan="plan"
            :billing-cycle="billingCycle"
        />
    </div>
</template>

<script setup>
import PlanCard from './PlanCard.vue';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
    billingCycle: {
        type: String,
        default: 'monthly',
    },
});
</script>
