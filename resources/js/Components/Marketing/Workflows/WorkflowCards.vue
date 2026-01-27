<template>
    <div class="workflow-cards grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <WorkflowCard
            v-for="item in items"
            :key="item.slug"
            :item="item"
        />
    </div>
</template>

<script setup>
import WorkflowCard from './WorkflowCard.vue';

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
});
</script>
