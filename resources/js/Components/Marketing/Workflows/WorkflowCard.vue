<template>
    <div class="workflow-card marketing-card p-6 hover:scale-105 transition-all cursor-pointer" data-reveal @click="navigate">
        <h3 class="text-xl font-bold mb-3 text-text">{{ item.title }}</h3>
        <p class="text-sm text-muted mb-4 line-clamp-2">{{ item.excerpt }}</p>
        <div class="flex flex-wrap gap-2 mb-4">
            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">
                Risk: {{ item.safetyProfile.risk }}
            </span>
            <span class="px-2 py-1 bg-success/20 text-success text-xs rounded">
                {{ item.safetyProfile.timeToFirstSignals }}
            </span>
        </div>
        <div class="mb-4">
            <h4 class="text-xs font-semibold text-muted mb-2">Best for:</h4>
            <ul class="space-y-1">
                <li
                    v-for="(use, idx) in item.bestFor.slice(0, 3)"
                    :key="idx"
                    class="text-xs text-muted flex items-start gap-1"
                >
                    <span class="text-primary">•</span>
                    <span>{{ use }}</span>
                </li>
            </ul>
        </div>
        <a
            :href="`/workflows/${item.slug}`"
            class="text-primary hover:underline text-sm font-semibold"
            @click.stop
        >
            View workflow →
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
    router.visit(`/workflows/${props.item.slug}`);
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
