<template>
    <div class="plan-limits-row space-y-3 mb-6">
        <div
            v-for="(limit, idx) in limits"
            :key="idx"
            class="flex items-center justify-between text-sm"
        >
            <span class="text-muted">{{ limit.label }}</span>
            <span class="font-semibold text-text">{{ limit.value }}</span>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    limits: {
        type: Array,
        required: true,
    },
});
</script>
