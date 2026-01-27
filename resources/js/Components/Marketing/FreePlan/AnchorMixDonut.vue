<template>
    <div class="anchor-mix-donut">
        <h3 class="text-xl font-bold mb-6 text-text">Anchor Mix</h3>
        <!-- Simple Stacked Bar (no chart library) -->
        <div class="space-y-3">
            <div
                v-for="(value, key) in anchorMix"
                :key="key"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-text capitalize">{{ key }}</span>
                    <span class="text-sm text-muted">{{ value }}%</span>
                </div>
                <div class="w-full h-6 bg-surface2 rounded-full overflow-hidden flex">
                    <div
                        :class="[
                            'h-full transition-all duration-500',
                            getColorClass(key)
                        ]"
                        :style="{ width: `${value}%` }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    anchorMix: {
        type: Object,
        required: true,
    },
});

const getColorClass = (key) => {
    const colors = {
        brand: 'bg-primary',
        partial: 'bg-success',
        exact: 'bg-warning',
    };
    return colors[key] || 'bg-primary';
};
</script>
