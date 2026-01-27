<template>
    <section class="feature-comparison py-20 bg-surface2">
        <div class="marketing-container max-w-7xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Compare Plans
            </h2>

            <!-- Desktop: Full Table -->
            <div class="hidden md:block overflow-x-auto" data-reveal>
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-border">
                            <th class="text-left py-4 px-4 text-sm font-semibold text-text">Feature</th>
                            <th
                                v-for="plan in plans"
                                :key="plan.id"
                                class="text-center py-4 px-4 text-sm font-semibold text-text"
                            >
                                {{ plan.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(group, groupIdx) in featureGroups" :key="groupIdx">
                            <tr v-if="groupIdx > 0" class="border-t-2 border-border">
                                <td colspan="4" class="py-2"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="py-3 px-4">
                                    <h3 class="text-lg font-bold text-text">{{ group.title }}</h3>
                                </td>
                            </tr>
                            <tr
                                v-for="(feature, featureIdx) in group.features"
                                :key="featureIdx"
                                class="border-b border-border"
                            >
                                <td class="py-3 px-4 text-sm text-muted">{{ feature.label }}</td>
                                <MatrixCell
                                    v-for="plan in plans"
                                    :key="plan.id"
                                    :value="getValue(plan.id, feature.key)"
                                />
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Mobile: Plan Selector + Grouped List -->
            <div class="md:hidden space-y-8" data-reveal>
                <div>
                    <label for="mobile-plan-select" class="block text-sm font-semibold text-text mb-2">
                        Select plan to view:
                    </label>
                    <select
                        id="mobile-plan-select"
                        v-model="selectedPlanId"
                        class="w-full px-4 py-3 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option
                            v-for="plan in plans"
                            :key="plan.id"
                            :value="plan.id"
                        >
                            {{ plan.name }}
                        </option>
                    </select>
                </div>

                <div
                    v-for="(group, groupIdx) in featureGroups"
                    :key="groupIdx"
                    class="marketing-card p-6"
                >
                    <h3 class="text-lg font-bold mb-4 text-text">{{ group.title }}</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(feature, featureIdx) in group.features"
                            :key="featureIdx"
                            class="flex items-center justify-between py-2 border-b border-border"
                        >
                            <span class="text-sm text-muted">{{ feature.label }}</span>
                            <div class="flex-shrink-0">
                                <MatrixCell :value="getValue(selectedPlanId, feature.key)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import MatrixCell from './MatrixCell.vue';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
    featureGroups: {
        type: Array,
        required: true,
    },
    matrix: {
        type: Object,
        required: true,
    },
});

const selectedPlanId = ref(props.plans[0]?.id || '');

const getValue = (planId, featureKey) => {
    return props.matrix[planId]?.[featureKey] ?? false;
};
</script>
