<template>
    <div class="feature-group mb-8">
        <h3 class="text-lg font-bold mb-4 text-text">{{ group.title }}</h3>
        <div class="space-y-2">
            <div
                v-for="(feature, idx) in group.features"
                :key="idx"
                class="flex items-center justify-between py-2 border-b border-border"
            >
                <span class="text-sm text-muted">{{ feature.label }}</span>
                <div class="flex gap-4">
                    <MatrixCell
                        v-for="planId in planIds"
                        :key="planId"
                        :value="getValue(planId, feature.key)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import MatrixCell from './MatrixCell.vue';

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
    matrix: {
        type: Object,
        required: true,
    },
    planIds: {
        type: Array,
        required: true,
    },
});

const getValue = (planId, featureKey) => {
    return props.matrix[planId]?.[featureKey] ?? false;
};
</script>
