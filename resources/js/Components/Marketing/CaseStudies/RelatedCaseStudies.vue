<template>
    <section class="related-case-studies py-20 bg-surface2">
        <div class="marketing-container max-w-7xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Related Case Studies
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div
                    v-for="item in related"
                    :key="item.slug"
                    class="marketing-card p-6 hover:scale-105 transition-all cursor-pointer"
                    data-reveal
                    @click="navigate(item.slug)"
                >
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">{{ item.segment }}</span>
                        <span class="px-2 py-1 bg-success/20 text-success text-xs rounded">{{ item.riskMode }}</span>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-text">{{ item.title }}</h3>
                    <p class="text-sm text-muted mb-4 line-clamp-2">{{ item.excerpt }}</p>
                    <a
                        :href="`/case-studies/${item.slug}`"
                        class="text-primary hover:underline text-sm font-semibold"
                        @click.stop
                    >
                        Read case study →
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    related: {
        type: Array,
        required: true,
    },
});

const navigate = (slug) => {
    router.visit(`/case-studies/${slug}`);
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
