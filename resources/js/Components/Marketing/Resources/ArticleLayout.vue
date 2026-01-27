<template>
    <article class="article-layout py-20">
        <div class="marketing-container max-w-4xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="mb-8" aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-sm text-muted">
                    <li><Link href="/resources" class="hover:text-text">Resources</Link></li>
                    <li>/</li>
                    <li><Link :href="`/resources/${item.type}`" class="hover:text-text">{{ getTypeName(item.type) }}</Link></li>
                    <li>/</li>
                    <li class="text-text">{{ item.title }}</li>
                </ol>
            </nav>

            <!-- H1 -->
            <h1 class="text-4xl md:text-5xl font-bold mb-6 text-text" data-reveal>
                {{ item.title }}
            </h1>

            <!-- Meta Row -->
            <div class="flex items-center gap-4 text-sm text-muted mb-6" data-reveal>
                <span>{{ item.readingTime }}</span>
                <span>•</span>
                <span>{{ formatDate(item.date) }}</span>
                <span>•</span>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(topic, idx) in item.topics"
                        :key="idx"
                        class="px-2 py-1 bg-surface2 text-muted text-xs rounded"
                    >
                        {{ topic }}
                    </span>
                </div>
            </div>

            <!-- Disclosure -->
            <div class="p-4 bg-warning/10 border border-warning/20 rounded-lg mb-8" data-reveal>
                <p class="text-sm text-muted">
                    <strong class="text-text">Note:</strong> Outcomes vary. No guaranteed links. Actions are logged with evidence. Placements depend on moderation and relevance.
                </p>
            </div>

            <!-- Content -->
            <slot />
        </div>
    </article>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const getTypeName = (typeSlug) => {
    const types = {
        playbooks: 'Playbooks',
        templates: 'Templates',
        guides: 'Guides',
        tools: 'Tools',
    };
    return types[typeSlug] || typeSlug;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};
</script>
