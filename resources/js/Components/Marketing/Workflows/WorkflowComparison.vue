<template>
    <section class="workflow-comparison py-20 bg-surface2">
        <div class="marketing-container max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Compare Workflows
            </h2>

            <!-- Desktop: Table -->
            <div class="hidden md:block overflow-x-auto" data-reveal>
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-border">
                            <th
                                v-for="col in comparison.columns"
                                :key="col.key"
                                class="text-left py-4 px-4 text-sm font-semibold text-text"
                            >
                                {{ col.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <ComparisonRow
                            v-for="item in items"
                            :key="item.slug"
                            :item="item"
                        />
                    </tbody>
                </table>
            </div>

            <!-- Mobile: Accordion -->
            <div class="md:hidden space-y-4" data-reveal>
                <div
                    v-for="item in items"
                    :key="item.slug"
                    class="marketing-card"
                >
                    <button
                        @click="toggle(item.slug)"
                        @keydown.enter="toggle(item.slug)"
                        @keydown.space.prevent="toggle(item.slug)"
                        class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-primary rounded-lg p-2 -m-2"
                        :aria-expanded="openSlugs.includes(item.slug)"
                    >
                        <h3 class="font-semibold text-text">{{ item.title }}</h3>
                        <svg
                            :class="[
                                'w-6 h-6 text-muted flex-shrink-0 transition-transform',
                                openSlugs.includes(item.slug) && 'rotate-180'
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div
                        v-show="openSlugs.includes(item.slug)"
                        class="mt-4 pt-4 border-t border-border space-y-2 text-sm text-muted"
                    >
                        <div><strong>Risk:</strong> {{ item.safetyProfile.risk }}</div>
                        <div><strong>Time to signals:</strong> {{ item.safetyProfile.timeToFirstSignals }}</div>
                        <div><strong>Moderation variance:</strong> {{ item.safetyProfile.moderationVariance }}</div>
                        <div>
                            <strong>Best for:</strong>
                            <ul class="mt-1 space-y-1">
                                <li v-for="(use, idx) in item.bestFor" :key="idx">• {{ use }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import ComparisonRow from './ComparisonRow.vue';

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    comparison: {
        type: Object,
        required: true,
    },
});

const openSlugs = ref([]);

const toggle = (slug) => {
    const index = openSlugs.value.indexOf(slug);
    if (index > -1) {
        openSlugs.value.splice(index, 1);
    } else {
        openSlugs.value.push(slug);
    }
};
</script>
