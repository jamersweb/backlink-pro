<template>
    <div
        :class="[
            'plan-card marketing-card p-8 transition-all hover:scale-105 hover:ring-2 hover:ring-primary/50',
            plan.highlight && 'ring-2 ring-primary'
        ]"
        data-reveal
    >
        <!-- Badge -->
        <div v-if="plan.badge" class="mb-4">
            <span class="px-3 py-1 bg-primary text-white text-xs font-semibold rounded-full">
                {{ plan.badge }}
            </span>
        </div>

        <!-- Name & Tagline -->
        <h3 class="text-2xl font-bold mb-2 text-text">{{ plan.name }}</h3>
        <p class="text-sm text-muted mb-6">{{ plan.tagline }}</p>

        <!-- Price -->
        <div class="mb-6">
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-bold text-text">${{ currentPrice.amount }}</span>
                <span class="text-muted">{{ currentPrice.suffix }}</span>
            </div>
        </div>

        <!-- Limits -->
        <PlanLimitsRow :limits="plan.limits" />

        <!-- Includes -->
        <div class="mb-6">
            <h4 class="text-sm font-semibold text-text mb-3">Includes:</h4>
            <ul class="space-y-2">
                <li
                    v-for="(item, idx) in plan.includes"
                    :key="idx"
                    class="flex items-start gap-2 text-sm text-muted"
                >
                    <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ item }}</span>
                </li>
            </ul>
        </div>

        <!-- CTAs -->
        <div class="space-y-3">
            <a
                :href="plan.ctas.primary.href"
                class="btn-primary w-full text-center block"
            >
                {{ plan.ctas.primary.label }}
            </a>
            <a
                :href="plan.ctas.secondary.href"
                class="btn-secondary w-full text-center block"
            >
                {{ plan.ctas.secondary.label }}
            </a>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import PlanLimitsRow from './PlanLimitsRow.vue';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    billingCycle: {
        type: String,
        default: 'monthly',
    },
});

const currentPrice = computed(() => {
    return props.plan.prices[props.billingCycle] || props.plan.prices.monthly;
});
</script>
