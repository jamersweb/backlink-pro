<template>
    <Link
        :href="`/resources/${resource.type}/${resource.slug}`"
        class="resource-card marketing-card p-6 block transition-all hover:shadow-xl hover:-translate-y-1 hover:border-primary/50"
        data-reveal
    >
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <h3 class="text-xl font-bold text-text mb-2">{{ resource.title }}</h3>
                <p class="text-muted text-sm mb-4">{{ resource.excerpt }}</p>
            </div>
        </div>

        <div class="flex items-center gap-4 text-xs text-muted mb-4">
            <span>{{ resource.readingTime }}</span>
            <span>•</span>
            <span>{{ formatDate(resource.date) }}</span>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
            <span
                v-for="(topic, idx) in resource.topics"
                :key="idx"
                class="px-2 py-1 bg-surface2 text-muted text-xs rounded"
            >
                {{ topic }}
            </span>
        </div>

        <div class="text-primary hover:underline font-semibold text-sm">
            Read →
        </div>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    resource: {
        type: Object,
        required: true,
    },
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
