<template>
    <section class="resource-type-cards py-20">
        <div class="marketing-container">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Link
                    v-for="(type, idx) in types"
                    :key="idx"
                    :href="`/resources/${type.slug}`"
                    class="resource-type-card marketing-card p-6 block transition-all hover:shadow-xl hover:-translate-y-1 hover:border-primary/50"
                    data-reveal
                >
                    <div class="text-4xl mb-4">{{ getIcon(type.icon) }}</div>
                    <h3 class="text-xl font-bold text-text mb-2">{{ type.name }}</h3>
                    <p class="text-muted text-sm mb-4">{{ type.desc }}</p>
                    <div class="text-xs text-muted">
                        {{ getItemCount(type.slug) }} items
                    </div>
                </Link>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    types: {
        type: Array,
        required: true,
    },
    allItems: {
        type: Array,
        default: () => [],
    },
});

const getIcon = (icon) => {
    const icons = {
        book: '📚',
        file: '📄',
        map: '🗺️',
        wrench: '🔧',
    };
    return icons[icon] || '📄';
};

const getItemCount = (typeSlug) => {
    return props.allItems.filter(item => item.type === typeSlug).length;
};
</script>
