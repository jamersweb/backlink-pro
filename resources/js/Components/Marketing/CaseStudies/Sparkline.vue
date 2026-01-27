<template>
    <div class="sparkline">
        <svg :width="width" :height="height" class="w-full">
            <polyline
                :points="points"
                fill="none"
                stroke="var(--color-primary)"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    width: {
        type: Number,
        default: 400,
    },
    height: {
        type: Number,
        default: 60,
    },
});

const points = computed(() => {
    if (!props.data || props.data.length === 0) return '';
    
    const max = Math.max(...props.data);
    const min = Math.min(...props.data);
    const range = max - min || 1;
    const padding = 10;
    const stepX = (props.width - padding * 2) / (props.data.length - 1);
    const stepY = props.height - padding * 2;

    return props.data.map((value, index) => {
        const x = padding + index * stepX;
        const y = props.height - padding - ((value - min) / range) * stepY;
        return `${x},${y}`;
    }).join(' ');
});
</script>
