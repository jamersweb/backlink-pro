<template>
    <span
        :class="[
            'px-2 py-1 rounded text-xs font-semibold',
            getRiskClass(riskLevel)
        ]"
    >
        {{ riskLevel }}
    </span>
</template>

<script setup>
const props = defineProps({
    riskLevel: {
        type: String,
        required: true,
    },
});

const getRiskClass = (risk) => {
    if (risk.includes('Low')) {
        return 'bg-success/20 text-success';
    } else if (risk.includes('High')) {
        return 'bg-danger/20 text-danger';
    } else {
        return 'bg-warning/20 text-warning';
    }
};
</script>
