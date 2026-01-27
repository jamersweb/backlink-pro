<template>
    <div class="case-study-grid grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <CaseStudyCard
            v-for="item in filteredItems"
            :key="item.slug"
            :item="item"
        />
    </div>
</template>

<script setup>
import CaseStudyCard from './CaseStudyCard.vue';

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    filteredItems: {
        type: Array,
        required: true,
    },
});
</script>
