<template>
    <div class="case-study-card marketing-card p-6 hover:scale-105 transition-all cursor-pointer" data-reveal @click="navigate">
        <div class="flex flex-wrap gap-2 mb-4">
            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">{{ item.segment }}</span>
            <span class="px-2 py-1 bg-success/20 text-success text-xs rounded">{{ item.riskMode }}</span>
            <span class="px-2 py-1 bg-surface2 text-muted text-xs rounded">{{ item.duration }}</span>
        </div>
        <h3 class="text-xl font-bold mb-3 text-text">{{ item.title }}</h3>
        <p class="text-sm text-muted mb-4 line-clamp-2">{{ item.excerpt }}</p>
        <div class="flex items-center justify-between text-xs text-muted mb-4">
            <span>Actions: {{ getActionsValue() }}</span>
            <span>{{ getTimeWindow() }}</span>
        </div>
        <a
            :href="`/case-studies/${item.slug}`"
            class="text-primary hover:underline text-sm font-semibold"
            @click.stop
        >
            Read case study →
        </a>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const navigate = () => {
    router.visit(`/case-studies/${props.item.slug}`);
};

const getActionsValue = () => {
    const metric = props.item.outcomes?.metrics?.find(m => m.label.includes('Actions executed'));
    return metric?.value || 'N/A';
};

const getTimeWindow = () => {
    const metric = props.item.outcomes?.metrics?.find(m => m.label.includes('Time to first'));
    return metric?.value || 'N/A';
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
